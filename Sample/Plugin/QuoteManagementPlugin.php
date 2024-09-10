<?php
namespace Kitchen\Sample\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class QuoteManagementPlugin
{
    /**
     * @var QuoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * QuoteManagementPlugin constructor.
     *
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        QuoteCollectionFactory $quoteCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Before merging guest cart with customer cart, check if the product is ordered
     *
     * @param Quote $subject
     * @param Quote $quote
     * @return Quote[]
     * @throws LocalizedException
     */
    public function beforeMerge(Quote $subject, Quote $quote)
    {
        $customerId = $subject->getCustomerId();
        if ($customerId) {
            $productIdsToCheck = [];
            $itemsToRemove = [];

            foreach ($quote->getAllItems() as $item) {
                $productIdsToCheck[] = $item->getProductId();
            }

            if (!empty($productIdsToCheck)) {
                $orderCollection = $this->orderCollectionFactory->create()
                    ->addFieldToSelect('entity_id')
                    ->addFieldToFilter('customer_id', $customerId);

                $orderedProductIds = [];
                foreach ($orderCollection as $order) {
                    foreach ($order->getAllVisibleItems() as $orderedItem) {
                        if (in_array($orderedItem->getProductId(), $productIdsToCheck)) {
                            $orderedProductIds[] = $orderedItem->getProductId();
                        }
                    }
                }
                foreach ($quote->getAllItems() as $item) {
                    if (in_array($item->getProductId(), $orderedProductIds)) {
                        $itemsToRemove[] = $item;
                    }
                }
                foreach ($itemsToRemove as $item) {
                    $quote->removeItem($item->getId());
                }

                
                $quote->collectTotals();
                $quote->save();

              
                if (!empty($itemsToRemove)) {
                    $message = __('This sample products have already been ordered and have been removed from the cart.');
                    throw new LocalizedException($message);
                }
            }
        }

        return [$quote];
    }
}
