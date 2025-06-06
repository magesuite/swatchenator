<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Plugin\Swatches\Block\Product\Renderer\Configurable;

class AddOutOfStockOptionsToSwatches
{
    public function __construct(
        protected \MageSuite\Swatchenator\Helper\Configuration $configuration,
        protected \MageSuite\Swatchenator\Service\JsonConfigModifier $jsonConfigModifier
    ) {
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

        if ($this->configuration->fetchOnlySpecificOptions()) {
            $this->jsonConfigModifier->setFetchOnlySpecificOptionsFlag(true);
        }

        return $this->jsonConfigModifier->addOutOfStockProductsToJsonConfig($subject->getProduct(), $result);
    }
}
