<?php
namespace Kitchen\Sample\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

class CartPlugin
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var QuoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * CartPlugin constructor.
     *
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param QuoteCollectionFactory $quoteCollectionFactory
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        QuoteCollectionFactory $quoteCollectionFactory
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
    }

    /**
     * Validate if product can be added to the cart.
     *
     * @param Cart $subject
     * @param mixed $productInfo
     * @param mixed|null $requestInfo
     * @return array
     * @throws LocalizedException
     */
    public function beforeAddProduct(Cart $subject, $productInfo, $requestInfo = null)
    {   
        $productId = $productInfo->getId();
        $customerId = $this->customerSession->getCustomerId();
        $isSample = $productInfo->getData('is_sample');
        $cabinetLine = $productInfo->getData('cabinate_multiselect');
       
        if ($isSample && $cabinetLine) {
            
            $quote = $this->checkoutSession->getQuote();
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    throw new LocalizedException(__('This sample product is already in the cart.'));
                }
            }
            if ($customerId) {
            $quoteCollection = $this->quoteCollectionFactory->create()
                ->addFieldToSelect('entity_id')
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('is_active', 1);

            foreach ($quoteCollection as $quote) {
                foreach ($quote->getAllVisibleItems() as $item) {
                    if ($item->getProductId() == $productId) {
                        throw new LocalizedException(__('This sample product is already in an active quote.'));
                        }
                    }
                }
            }
        }
        return [$productInfo, $requestInfo];
    }
}
