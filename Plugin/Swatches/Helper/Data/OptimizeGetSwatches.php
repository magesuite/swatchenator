<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Plugin\Swatches\Helper\Data;

class OptimizeGetSwatches
{
    public function __construct(
        protected \MageSuite\Swatchenator\Helper\Configuration $configuration,
        protected \Magento\Swatches\Model\SwatchAttributesProvider $swatchAttributesProvider,
        protected \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetSwatchAttributesAsArray(
        \Magento\Swatches\Helper\Data $subject,
        callable $proceed,
        \Magento\Catalog\Api\Data\ProductInterface $product
    ): array {
        if (!$this->configuration->fetchOnlySpecificOptions()) {
            return $proceed($product);
        }

        $result = [];
        /** @var $product \Magento\Catalog\Model\Product */
        $swatchAttributes = $this->swatchAttributesProvider->provide($product);
        $childProducts = $this->getChildProducts($product);

        foreach ($swatchAttributes as $swatchAttribute) {
            $swatchAttribute->setStoreId($this->storeManager->getStore()->getId());
            $attributeData = $swatchAttribute->getData();

            $options = $this->getOptions($product, $childProducts, $swatchAttribute);

            foreach ($options as $option) {
                $attributeData['options'][$option['value']] = $option['label'];
            }

            $result[$attributeData['attribute_id']] = $attributeData;
        }

        return $result;
    }

    protected function getOptions($product, array $childProducts, $swatchAttribute): array //phpcs:ignore
    {
        try {
            $optionIds = $this->getOptionIds($product, $childProducts, $swatchAttribute->getAttributeCode());
            return $swatchAttribute->getSource()->getSpecificOptions($optionIds, false);
        } catch (\Exception $e) {
            return $swatchAttribute->getSource()->getAllOptions(false);
        }
    }

    protected function getOptionIds($product, array $childProducts, string $swatchAttributeCode): array
    {
        $optionIds = [];

        foreach ($childProducts as $childProduct) {
            $optionId = $childProduct->getData($swatchAttributeCode);

            if ($optionId && !in_array($optionId, $optionIds)) {
                $optionIds[] = $optionId;
            }
        }

        if ($product->getData($swatchAttributeCode)) {
            $optionIds[] = $product->getData($swatchAttributeCode);
        }

        return $optionIds;
    }

    protected function getChildProducts(\Magento\Catalog\Api\Data\ProductInterface $product): array
    {
        if ($product->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return [];
        }

        $childProducts = [];

        foreach ($product->getTypeInstance()->getUsedProducts($product, null) as $simpleProduct) {
            if ((int)$simpleProduct->getStatus() === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                $childProducts[] = $simpleProduct;
            }
        }

        return $childProducts;
    }
}
