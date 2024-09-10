<?php

namespace Kitchen\Sample\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class CabinetLine extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return [
            ['value' => 'faucet_and_sink', 'label' => __('Faucets And Sink')],
            ['value' => 'hoods', 'label' => __('Hoods')],
            ['value' => 'zline', 'label' => __('Zline')],
        ];
    }
}