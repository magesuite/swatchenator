<?php

namespace MageSuite\Swatchenator\Plugin\ConfigurableProduct\Model\Product\Type\Configurable;

class MarkConfigurableAsSaleable
{
    /**
     * @var \MageSuite\Swatchenator\Helper\Configuration
     */
    protected $configuration;

    public function __construct(\MageSuite\Swatchenator\Helper\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function aroundIsSalable(\Magento\ConfigurableProduct\Model\Product\Type\Configurable $subject, callable $proceed, $product)
    {
        if ($this->configuration->isModuleEnabled()) {
            return true;
        }

        return $proceed($product);
    }
}
