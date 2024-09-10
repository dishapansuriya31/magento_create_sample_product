<?php
namespace Kitchen\Sample\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;

class SetSampleProductQty implements ObserverInterface
{
    
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;


    public function __construct(
        ProductRepository $productRepository,
        QuoteRepository    $quoteRepository ,
        OrderCollectionFactory $orderCollectionFactory,
        CustomerSession $customerSession,
    ) {
        $this->productRepository = $productRepository;
        $this->quoteRepository = $quoteRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerSession = $customerSession;
      }
    /**
     * Execute observer
     *
     * @param Observer $observer
     * @throws LocalizedException
     * @return void
     */
    public function execute(Observer $observer)  
    {  
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/test.log');  
        $logger = new \Zend_Log();  
        $logger->addWriter($writer);  
        
        $quoteItem = $observer->getEvent()->getItem();  
        $product = $quoteItem->getProduct();  
        // $logger->debug(var_export(get_class_methods($quoteItem), true));
        //  $logger->debug(var_export($observer->getEvent()->getItem()), true);
        if (!$quoteItem->getId()) {  
            $product = $quoteItem->getProduct();  
            $this->validateSampleProduct($quoteItem, $product);
           
        } else {  
            $sku = $quoteItem->getProduct()->getSku();  
            $product = $this->getProductBySku($sku, $quoteItem->getStore()->getId());  
                   $this->validateSampleProduct($quoteItem, $product);
        } 
        $sku = $quoteItem->getProduct()->getSku();  
        $product = $this->getProductBySku($sku, $quoteItem->getStore()->getId());  
        $customerId = $this->customerSession->getCustomerId();  
        if ($product->getIsSample() && $product->getCabinateMultiselect()) {  
       
        $orderCollection = $this->orderCollectionFactory->create()  
            ->addFieldToSelect('entity_id')  
            ->addFieldToFilter('customer_id', $customerId);  

        foreach ($orderCollection as $order) {  
            foreach ($order->getAllVisibleItems() as $item) {  
                if ($item->getProductId() == $product->getId()) {  
                    throw new LocalizedException(__('This sample product has already been ordered and cannot be purchased again.'));  
                    }  
                }  
            }      
        }
    }
    public function getProductBySku($sku='', $storeId='')
    {
        try {
            if($sku){
                return $this->productRepository->get($sku, false, $storeId);
            }
                return false;
            } catch (NoSuchEntityException $e) {
                return false;
            }
    }
     /**
     * Validate sample product
     *
     * @param QuoteItem $quoteItem
     * @param Product $product
     * @throws LocalizedException
     * @return void
     */
    protected function validateSampleProduct(QuoteItem $quoteItem, $product)
    {
        if ($product->getIsSample() && $product->getCabinateMultiselect()) {
            if ($quoteItem->getQty() > 1) {
                $quoteItem->setQty(1);
                throw new LocalizedException(__('1 quantity purchase is allowed.'));
            }
        }
    }
}



