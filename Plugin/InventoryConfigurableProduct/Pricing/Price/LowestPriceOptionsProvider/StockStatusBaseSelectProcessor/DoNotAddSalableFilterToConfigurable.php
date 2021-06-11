<?php

namespace MageSuite\Swatchenator\Plugin\InventoryConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider\StockStatusBaseSelectProcessor;

class DoNotAddSalableFilterToConfigurable
{
    /**
     * @var \MageSuite\Swatchenator\Helper\Configuration
     */
    protected $configuration;

    public function __construct(\MageSuite\Swatchenator\Helper\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function aroundProcess(\Magento\InventoryConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider\StockStatusBaseSelectProcessor $subject, callable $proceed, \Magento\Framework\DB\Select $select)
    {
        if ($this->configuration->isModuleEnabled()) {
            return $select;
        }

        return $proceed($select);
    }
}
