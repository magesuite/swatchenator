<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Service;

class JsonConfigModifier
{
    protected $configurableProduct = null; // @codingStandardsIgnoreLine

    protected $simpleProductsCollection = null; // @codingStandardsIgnoreLine

    protected bool $fetchOnlySpecificOptions = false;

    public function __construct(
        protected \Magento\ConfigurableProduct\Helper\Data $helper,
        protected \Magento\Swatches\Helper\Data $swatchHelper,
        protected \Magento\Catalog\Model\Product\Image\UrlBuilder $imageUrlBuilder,
        protected \Magento\Swatches\Helper\Media $swatchMediaHelper,
        protected \Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory $stockStatusFactory,
        protected \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
    ) {
    }

    public function addOutOfStockProductsToJsonConfig($product, $jsonConfig) // @codingStandardsIgnoreLine
    {
        $productResource = $product->getResource();

        $simpleProductsCollection = $this->getAllAttributesProducts($product);
        $allAttributesOptions = $this->getAllAttributesOptions($product, $simpleProductsCollection);

        if (empty($allAttributesOptions)) {
            return $jsonConfig;
        }

        $jsonConfig = json_decode($jsonConfig, true);

        if (empty($jsonConfig['attributes'])) {
            return json_encode($jsonConfig);
        }

        foreach ($jsonConfig['attributes'] as $attributeId => $attributeData) {
            $sortOrder = $this->prepareSortOrder($productResource, (int)$attributeId, $allAttributesOptions);

            foreach ($attributeData['options'] as $option) {
                if (isset($allAttributesOptions[$attributeId][$option['id']])) {
                    unset($allAttributesOptions[$attributeId][$option['id']]);
                }
            }

            $options = $this->updateJsonConfigOptions($productResource->getAttribute($attributeId), $attributeData['options'], $allAttributesOptions[$attributeId], $sortOrder);

            $jsonConfig['attributes'][$attributeId]['options'] = $options;
        }

        return json_encode($jsonConfig);
    }

    public function addOutOfStockProductsToJsonSwatchesConfig($product, $jsonSwatchesConfig) // @codingStandardsIgnoreLine
    {
        $productResource = $product->getResource();

        $simpleProductsCollection = $this->getAllAttributesProducts($product);
        $allAttributesOptions = $this->getAllAttributesOptions($product, $simpleProductsCollection);
        $optionIds = [];

        foreach ($allAttributesOptions as $attributeId => $options) {
            $optionIds = array_merge($optionIds, array_keys($options));
        }

        $swatchesData = $this->swatchHelper->getSwatchesByOptionsId($optionIds);
        $jsonSwatchesConfig = json_decode($jsonSwatchesConfig, true);

        $optionsToUpdate = [];
        foreach ($allAttributesOptions as $attributeId => $options) {
            if (!isset($jsonSwatchesConfig[$attributeId])) {
                continue;
            }

            foreach ($options as $optionId => $option) {
                if (!isset($jsonSwatchesConfig[$attributeId][$optionId])) {
                    $optionsToUpdate[] = $optionId;
                }

            }
            $options = $this->updateJsonSwatchConfigOptions($product, $productResource->getAttribute($attributeId), $jsonSwatchesConfig[$attributeId], $optionsToUpdate, $swatchesData);

            $jsonSwatchesConfig[$attributeId] = $options;
        }

        return json_encode($jsonSwatchesConfig);
    }

    public function getAllAttributesProducts($product) // @codingStandardsIgnoreLine
    {
        if ($this->stockConfiguration->isShowOutOfStock()) {
            return array_filter(
                $product->getTypeInstance()->getUsedProducts($product),
                fn($variant) => (int) $variant->getStatus() === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            );
        }

        if ($this->shouldSimpleProductCollectionBeReloaded($product)) {
            $collection = $product->getTypeInstance()->getUsedProductCollection($product);

            $collection->setFlag('has_stock_status_filter', true);

            $collection
                ->addFilterByRequiredOptions()
                ->addAttributeToFilter(\Magento\Catalog\Api\Data\ProductInterface::STATUS, ['eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED])
                ->setStoreId($product->getStoreId());

            $stockStatusResource = $this->stockStatusFactory->create();
            $stockStatusResource->addStockDataToCollection($collection, false);

            $collection->addMediaGalleryData();
            $collection->addTierPriceData();

            $this->configurableProduct = $product;
            $this->simpleProductsCollection = $collection;
        }

        return $this->simpleProductsCollection;
    }

    public function getAllAttributesOptions($product, $simpleProductsCollection) // @codingStandardsIgnoreLine
    {
        $options = [];
        $allowAttributes = $this->helper->getAllowAttributes($product);

        foreach ($simpleProductsCollection as $simpleProduct) {
            $productId = $simpleProduct->getId();
            foreach ($allowAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $simpleProduct->getData($productAttribute->getAttributeCode());
                $options[$productAttributeId][$attributeValue][] = $productId;
                $options['index'][$productId][$productAttributeId] = $attributeValue;
            }
        }

        return $options;
    }

    public function updateJsonConfigOptions($attribute, $attributeOptions, $optionsToUpdate, $sortOrder = []) // @codingStandardsIgnoreLine
    {
        if (empty($optionsToUpdate)) {
            return $attributeOptions;
        }
        foreach ($optionsToUpdate as $id => $optionToUpdate) {
            $attributeOptions[] = [
                'id' => (string) $id,
                'label' => $attribute->getSource()->getOptionText($id),
                'products' => []
            ];
        }
        uasort($attributeOptions, $this->uaSort($sortOrder));
        return array_values($attributeOptions);
    }

    public function updateJsonSwatchConfigOptions($product, $attribute, $attributeOptions, $optionsToUpdate, $swatchesData) // @codingStandardsIgnoreLine
    {
        foreach ($optionsToUpdate as $id) {
            $attributeValue = $attribute->getSource()->getOptionText($id);
            if (!isset($swatchesData[$id])) {
                continue;
            }
            $attributeOptions[$id] = [
                'type' => $swatchesData[$id]['type'],
                'value' => $swatchesData[$id]['value'],
                'label' => $attributeValue
            ];

            if ($swatchesData[$id]['type'] == \Magento\Swatches\Model\Swatch::SWATCH_TYPE_VISUAL_IMAGE ||
                $attribute->getUseProductImageForSwatch()
            ) {
                $attributeOptions[$id] = $this->extractNecessarySwatchData($attributeOptions[$id]);
                $attributeOptions[$id] = $this->addAdditionalMediaData($product, $attributeOptions[$id], $id, $attribute->getData());
                $attributeOptions[$id]['label'] = $attributeValue;
            }
        }

        return $attributeOptions;
    }

    public function prepareSortOrder(\Magento\Catalog\Model\ResourceModel\Product $productResource, int $attributeId, array $allAttributesOptions): array
    {
        $resourceAttributeOptions = $this->getAttributeOptions($productResource, $attributeId, $allAttributesOptions);
        $attributeOptions = $allAttributesOptions[$attributeId] ?? [];
        $filteredOptions = array_map($this->getOptionValue($attributeOptions), $resourceAttributeOptions);

        return array_filter($filteredOptions);
    }

    public function addAdditionalMediaData($product, array $swatch, $optionId, array $attributeDataArray)  // @codingStandardsIgnoreLine
    {
        if (isset($attributeDataArray['use_product_image_for_swatch'])
            && $attributeDataArray['use_product_image_for_swatch']
        ) {
            $variationMedia = $this->getVariationMedia($product, $attributeDataArray['attribute_code'], $optionId);
            if (! empty($variationMedia)) {
                $swatch['type'] = \Magento\Swatches\Model\Swatch::SWATCH_TYPE_VISUAL_IMAGE;
                $swatch = array_merge($swatch, $variationMedia);
            }
        }
        return $swatch;
    }

    public function getVariationMedia($product, $attributeCode, $optionId) // @codingStandardsIgnoreLine
    {
        $variationProduct = $this->getVariationProduct($product, $attributeCode, $optionId);

        $variationMediaArray = [];
        if ($variationProduct) {
            $variationMediaArray = [
                'value' => $this->getSwatchProductImage($variationProduct, \Magento\Swatches\Model\Swatch::SWATCH_IMAGE_NAME),
                'thumb' => $this->getSwatchProductImage($variationProduct, \Magento\Swatches\Model\Swatch::SWATCH_THUMBNAIL_NAME),
            ];
        }

        return $variationMediaArray;
    }

    public function getVariationProduct($product, $attributeCode, $optionId) // @codingStandardsIgnoreLine
    {
        $simpleProductCollection = $this->getAllAttributesProducts($product);

        $variationProduct = [];

        foreach ($simpleProductCollection as $simpleProduct) {
            if ($simpleProduct->getData($attributeCode) == $optionId) {
                $variationProduct = $simpleProduct;
            }
        }

        return $variationProduct;
    }

    public function extractNecessarySwatchData(array $swatchDataArray): array
    {
        $result = [];

        $result['type'] = $swatchDataArray['type'];

        if ($result['type'] == \Magento\Swatches\Model\Swatch::SWATCH_TYPE_VISUAL_IMAGE && !empty($swatchDataArray['value'])) {
            $result['value'] = $this->swatchMediaHelper->getSwatchAttributeImage(
                \Magento\Swatches\Model\Swatch::SWATCH_IMAGE_NAME,
                $swatchDataArray['value']
            );
            $result['thumb'] = $this->swatchMediaHelper->getSwatchAttributeImage(
                \Magento\Swatches\Model\Swatch::SWATCH_THUMBNAIL_NAME,
                $swatchDataArray['value']
            );
        } else {
            $result['value'] = $swatchDataArray['value'];
        }

        return $result;
    }

    public function getSwatchProductImage(\Magento\Catalog\Model\Product $childProduct, $imageType) // @codingStandardsIgnoreLine
    {
        if ($this->productHasImage($childProduct, \Magento\Swatches\Model\Swatch::SWATCH_IMAGE_NAME)) {
            $swatchImageId = $imageType;
            $imageAttributes = ['type' => \Magento\Swatches\Model\Swatch::SWATCH_IMAGE_NAME];
        } elseif ($this->productHasImage($childProduct, 'image')) {
            $swatchImageId = $imageType == \Magento\Swatches\Model\Swatch::SWATCH_IMAGE_NAME ? 'swatch_image_base' : 'swatch_thumb_base';
            $imageAttributes = ['type' => 'image'];
        }

        if (!empty($swatchImageId) && !empty($imageAttributes['type'])) {
            return $this->imageUrlBuilder->getUrl($childProduct->getData($imageAttributes['type']), $swatchImageId);
        }
    }

    public function productHasImage(\Magento\Catalog\Model\Product $product, $imageType): bool // @codingStandardsIgnoreLine
    {
        return $product->getData($imageType) !== null && $product->getData($imageType) != \Magento\Swatches\Helper\Data::EMPTY_IMAGE_VALUE;
    }

    protected function shouldSimpleProductCollectionBeReloaded(\Magento\Catalog\Model\Product $product): bool
    {
        if (!$this->simpleProductsCollection || !$this->configurableProduct) {
            return true;
        }

        if ($this->configurableProduct->getId() != $product->getId()) {
            return true;
        }

        return false;
    }

    protected function uaSort(array $sortOrder): \Closure
    {
        return function ($leftItem, $rightItem) use ($sortOrder) { // @codingStandardsIgnoreLine
            return array_search((int)$leftItem['id'], $sortOrder) <=> array_search((int)$rightItem['id'], $sortOrder);
        };
    }

    protected function getOptionValue(array $attributeOptions): \Closure
    {
        return function ($option) use ($attributeOptions) { // @codingStandardsIgnoreLine
            $value = is_array($option) ? $option['value'] : $option->getValue();
            return isset($attributeOptions[$value]) ? (int)$value : false;
        };
    }

    protected function getAttributeOptions(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        int $attributeId,
        array $allAttributesOptions
    ): array {
        if (!$this->getFetchOnlySpecificOptionsFlag()) {
            //fetch all options
            return $productResource->getAttribute($attributeId)->getOptions();
        }

        try {
            //fetch only specific options if available
            $attribute = $productResource->getAttribute($attributeId);
            $resourceAttributeOptions = $attribute->getSource()->getSpecificOptions(
                array_keys($allAttributesOptions[$attributeId]),
                false
            );
        } catch (\Exception $e) {
            $resourceAttributeOptions = $productResource->getAttribute($attributeId)->getOptions();
        }

        return $resourceAttributeOptions;
    }

    public function setFetchOnlySpecificOptionsFlag(bool $value): void
    {
        $this->fetchOnlySpecificOptions = $value;
    }

    public function getFetchOnlySpecificOptionsFlag(): bool
    {
        return $this->fetchOnlySpecificOptions;
    }
}
