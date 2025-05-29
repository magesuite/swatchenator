<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Helper;

class Configuration
{
    public function __construct(
        protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {}

    public const MODULE_ENABLED_CONFIG_PATH = 'swatchenator/general/is_enabled';
    public const XML_PATH_HIDE_CONFIGURABLE_STOCK_STATUS = 'swatchenator/general/hide_configurable_stock_status';

    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::MODULE_ENABLED_CONFIG_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isHideConfigurableStockStatus(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_HIDE_CONFIGURABLE_STOCK_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }
}
