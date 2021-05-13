<?php

namespace MageSuite\Swatchenator\Service;

class JsonConfigModifier
{
    /**
     * @var \Magento\ConfigurableProduct\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchHelper;

    public function __construct(
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Magento\Swatches\Helper\Data $swatchHelper
    )
    {
        $this->helper = $helper;
        $this->swatchHelper = $swatchHelper;
    }

    public function addOutOfStockProductsToJsonConfig($product, $jsonConfig)
    {
        $productResource = $product->getResource();
        $allAttributesOptions = $this->getAllAttributesOptions($product);

        $jsonConfig = json_decode($jsonConfig, true);

        if (!isset($jsonConfig['attributes']) || empty($jsonConfig['attributes'])) {
            return $jsonConfig;
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

        $allAttributesOptions = $this->getAllAttributesOptions($product);
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
            $options = $this->updateJsonSwatchConfigOptions($productResource->getAttribute($attributeId), $jsonSwatchesConfig[$attributeId], $optionsToUpdate, $swatchesData);

            $jsonSwatchesConfig[$attributeId] = $options;
        }

        return json_encode($jsonSwatchesConfig);
    }

    protected function getAllAttributesOptions($product)
    {
        $collection = $product->getTypeInstance()->getUsedProductCollection($product);

        $collection->setFlag('has_stock_status_filter', true);

        $collection
            ->addFilterByRequiredOptions()
            ->addAttributeToFilter(\Magento\Catalog\Api\Data\ProductInterface::STATUS, ['eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED])
            ->setStoreId($product->getStoreId());

        $collection->addMediaGalleryData();
        $collection->addTierPriceData();

        $options = $this->helper->getOptions($product, $collection);

        return $options;
    }

    protected function updateJsonConfigOptions($attribute, $attributeOptions, $optionsToUpdate, $sortOrder = [])
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
        uasort($attributeOptions, function ($a, $b) use ($sortOrder) {
            return array_search(intval($a['id']), $sortOrder) <=> array_search(intval($b['id']), $sortOrder);
        });
        return array_values($attributeOptions);
    }

    protected function updateJsonSwatchConfigOptions($attribute, $attributeOptions, $optionsToUpdate, $swatchesData)
    {
        foreach ($optionsToUpdate as $id) {
            $attributeValue = $attribute->getSource()->getOptionText($id);
            $attributeOptions[$id] = [
                'type' => $swatchesData[$id]['type'],
                'value' => $swatchesData[$id]['value'],
                'label' => $attributeValue
            ];
        }

        return $attributeOptions;
    }

    protected function prepareSortOrder(\Magento\Catalog\Model\ResourceModel\Product $productResource, $attributeId, $allAttributesOptions)
    {
        $resourceAttributeOptions = $productResource->getAttribute($attributeId)->getOptions();
        $attributeOptions = $allAttributesOptions[$attributeId] ?? [];
        $filteredOptions = array_map(function ($option) use ($attributeOptions) {
            return isset($attributeOptions[$option->getValue()]) ? intval($option->getValue()) : false;
        }, $resourceAttributeOptions);
        return array_filter($filteredOptions);
    }
}
