<?php

namespace MageSuite\Swatchenator\Plugin\Swatches\Block\Product\Renderer\Configurable;

class AddOutOfStockOptionsToSwatches
{
    /**
     * @var \MageSuite\Swatchenator\Helper\Configuration
     */
    protected $configuration;
    /**
     * @var \MageSuite\Swatchenator\Service\JsonConfigModifier
     */
    protected $jsonConfigModifier;

    public function __construct(
        \MageSuite\Swatchenator\Helper\Configuration $configuration,
        \MageSuite\Swatchenator\Service\JsonConfigModifier $jsonConfigModifier
    ) {
        $this->configuration = $configuration;
        $this->jsonConfigModifier = $jsonConfigModifier;
    }

    public function afterGetJsonSwatchConfig(\Magento\Swatches\Block\Product\Renderer\Configurable $subject, $result)
    {
        if (!$this->configuration->isModuleEnabled()) {
            return $result;
        }

        return $this->jsonConfigModifier->addOutOfStockProductsToJsonSwatchesConfig($subject->getProduct(), $result);
    }

    public function afterGetJsonConfig(\Magento\Swatches\Block\Product\Renderer\Configurable $subject, $result)
    {
        if (!$this->configuration->isModuleEnabled()) {
            return $result;
        }

        return $this->jsonConfigModifier->addOutOfStockProductsToJsonConfig($subject->getProduct(), $result);
    }
}
