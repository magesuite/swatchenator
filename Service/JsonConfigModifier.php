<?php

namespace MageSuite\Swatchenator\Service;

class JsonConfigModifier
{
    protected $configurableProduct = null;

    protected $simpleProductsCollection = null;

    /**
     * @var \Magento\ConfigurableProduct\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchHelper;
    /**
     * @var \Magento\Catalog\Model\Product\Image\UrlBuilder
     */
    protected $imageUrlBuilder;
    /**
     * @var \Magento\Swatches\Helper\Media
     */
    protected $swatchMediaHelper;
    /**
     * @var \Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory
     */
    protected $stockStatusFactory;

    public function __construct(
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\Catalog\Model\Product\Image\UrlBuilder $imageUrlBuilder,
        \Magento\Swatches\Helper\Media $swatchMediaHelper,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory $stockStatusFactory
    ) {
        $this->helper = $helper;
        $this->swatchHelper = $swatchHelper;
        $this->imageUrlBuilder = $imageUrlBuilder;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->stockStatusFactory = $stockStatusFactory;
    }

    public function addOutOfStockProductsToJsonConfig($product, $jsonConfig)
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
            $sortOrder = $this->prepareSortOrder($productResource, $attributeId, $allAttributesOptions);

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

    public function addOutOfStockProductsToJsonSwatchesConfig($product, $jsonSwatchesConfig)
    {
        $productResource = $product->getResource();

        $simpleProductsCollection = $this->getAllAttributesProducts($product);
        $allAttributesOptions = $this->getAllAttributesOptions($product, $simpleProductsCollection);
        $swatchesData = $this->swatchHelper->getSwatchesByOptionsId($allAttributesOptions);

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

    public function getAllAttributesProducts($product)
    {
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

    public function getAllAttributesOptions($product, $simpleProductsCollection)
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

    public function prepareSortOrder(\Magento\Catalog\Model\ResourceModel\Product $productResource, $attributeId, $allAttributesOptions)
    {
        $resourceAttributeOptions = $productResource->getAttribute($attributeId)->getOptions();
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

    public function getVariationMedia($product, $attributeCode, $optionId)
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

    public function getVariationProduct($product, $attributeCode, $optionId)
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

    public function extractNecessarySwatchData(array $swatchDataArray)
    {
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

    public function getSwatchProductImage(\Magento\Catalog\Model\Product $childProduct, $imageType)
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

    public function productHasImage(\Magento\Catalog\Model\Product $product, $imageType)
    {
        return $product->getData($imageType) !== null && $product->getData($imageType) != \Magento\Swatches\Helper\Data::EMPTY_IMAGE_VALUE;
    }

    protected function shouldSimpleProductCollectionBeReloaded(\Magento\Catalog\Model\Product $product)
    {
        if (!$this->simpleProductsCollection || !$this->configurableProduct) {
            return true;
        }

        if ($this->configurableProduct->getId() != $product->getId()) {
            return true;
        }

        return false;
    }

    /**
     * @param $sortOrder
     * @return \Closure
     */
    protected function uaSort($sortOrder): \Closure
    {
        return function ($leftItem, $rightItem) use ($sortOrder) { // @codingStandardsIgnoreLine
            return array_search((int)$leftItem['id'], $sortOrder) <=> array_search((int)$rightItem['id'], $sortOrder);
        };
    }

    /**
     * @param $attributeOptions
     * @return \Closure
     */
    protected function getOptionValue($attributeOptions): \Closure
    {
        return function ($option) use ($attributeOptions) { // @codingStandardsIgnoreLine
            return isset($attributeOptions[$option->getValue()]) ? (int)$option->getValue() : false;
        };
    }
}
