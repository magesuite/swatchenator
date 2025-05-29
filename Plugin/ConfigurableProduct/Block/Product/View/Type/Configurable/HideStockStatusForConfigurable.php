<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable;

class HideStockStatusForConfigurable
{
    public function __construct(
        protected \MageSuite\Swatchenator\Helper\Configuration $configuration,
    ) {}

    public function afterDisplayProductStockStatus(\Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject, $result): bool
    {
        return $this->configuration->isModuleEnabled() && $this->configuration->isHideConfigurableStockStatus() ? false : $result;
    }
}
