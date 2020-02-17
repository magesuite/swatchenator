<?php

namespace MageSuite\Swatchenator\Service;

class JsonConfigModifier
{
    /**
     * @var \Magento\ConfigurableProduct\Helper\Data
     */
    protected $helper;

    public function __construct(\Magento\ConfigurableProduct\Helper\Data $helper)
    {
        $this->helper = $helper;
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
            foreach ($attributeData['options'] as $option) {
                if (isset($allAttributesOptions[$attributeId][$option['id']])) {
                    unset($allAttributesOptions[$attributeId][$option['id']]);
                }
            }

            $options = $this->updateJsonConfigOptions($productResource->getAttribute($attributeId), $attributeData['options'], $allAttributesOptions[$attributeId]);

            $jsonConfig['attributes'][$attributeId]['options'] = $options;
        }

        return json_encode($jsonConfig);
    }

    public function addOutOfStockProductsToJsonSwatchesConfig($product, $jsonSwatchesConfig)
    {
        $productResource = $product->getResource();

        $allAttributesOptions = $this->getAllAttributesOptions($product);

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
            $options = $this->updateJsonSwatchConfigOptions($productResource->getAttribute($attributeId), $jsonSwatchesConfig[$attributeId], $optionsToUpdate);

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
            ->setStoreId($product->getStoreId());

        $collection->addMediaGalleryData();
        $collection->addTierPriceData();

        $options = $this->helper->getOptions($product, $collection);

        return $options;
    }

    protected function updateJsonConfigOptions($attribute, $attributeOptions, $optionsToUpdate)
    {
        foreach ($optionsToUpdate as $id => $optionToUpdate) {
            $attributeOptions[] = [
                'id' => (string) $id,
                'label' => $attribute->getSource()->getOptionText($id),
                'products' => []
            ];
        }

        usort($attributeOptions, function ($a, $b) {
            return $a['label'] <=> $b['label'];
        });

        return $attributeOptions;
    }

    protected function updateJsonSwatchConfigOptions($attribute, $attributeOptions, $optionsToUpdate)
    {
        foreach ($optionsToUpdate as $id) {
            $attributeValue = $attribute->getSource()->getOptionText($id);
            $attributeOptions[$id] = [
                'type' => (string) 0,
                'value' => $attributeValue,
                'label' => $attributeValue
            ];
        }

        return $attributeOptions;
    }
}