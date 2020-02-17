<?php

namespace MageSuite\Swatchenator\Helper;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_ENABLED_CONFIG_PATH = 'swatchenator/general/is_enabled';

    public function isModuleEnabled()
    {
        return $this->scopeConfig->getValue(self::MODULE_ENABLED_CONFIG_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}