<?php

namespace MageSuite\Swatchenator\Plugin\Catalog\Model\Product;

class AddHasAllChildrenSalableFlag
{
    /**
     * @var \MageSuite\Swatchenator\Service\JsonConfigModifier
     */
    protected \MageSuite\Swatchenator\Service\JsonConfigModifier $jsonConfigModifier;

    public function __construct(\MageSuite\Swatchenator\Service\JsonConfigModifier $jsonConfigModifier) {
        $this->jsonConfigModifier = $jsonConfigModifier;
    }

    public function aroundGetData(\Magento\Catalog\Model\Product $subject, callable $proceed, $key = '', $index = null)
    {
        if ($key !== 'has_all_children_not_salable') {
            return $proceed($key, $index);
        }

        if ($subject->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return false;
        }

        return $this->hasAllChildrenNotSalable($subject);
    }

    protected function hasAllChildrenNotSalable($product)
    {
        $childProductsCollection = $this->jsonConfigModifier->getAllAttributesProducts($product);

        /** @var \Magento\Catalog\Model\Product $childProduct */
        foreach ($childProductsCollection as $childProduct) {
            if ($childProduct->isSalable()) {
                return false;
            }
        }

        return true;
    }
}
