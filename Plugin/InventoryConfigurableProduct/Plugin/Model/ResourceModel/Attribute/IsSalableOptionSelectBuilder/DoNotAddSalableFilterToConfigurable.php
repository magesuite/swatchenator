<?php

namespace MageSuite\Swatchenator\Plugin\InventoryConfigurableProduct\Plugin\Model\ResourceModel\Attribute\IsSalableOptionSelectBuilder;

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

    public function aroundAfterGetSelect(
        \Magento\InventoryConfigurableProduct\Plugin\Model\ResourceModel\Attribute\IsSalableOptionSelectBuilder $plugin,
        callable $proceed,
        \Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface $subject,
        \Magento\Framework\DB\Select $select
    ) {
        if ($this->configuration->isModuleEnabled()) {
            return $select;
        }

        return $proceed($subject, $select);
    }
}
