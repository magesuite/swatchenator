<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Test\Integration\Plugin\Swatches\Block\Product\Renderer\Configurable;

class AddOutOfStockOptionsToSwatchesTest extends \PHPUnit\Framework\TestCase
{
    protected \Magento\Framework\App\ObjectManager $objectManager;

    protected \Magento\Swatches\Block\Product\Renderer\Configurable $swatchRenderer;

    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();

        $this->swatchRenderer = $this->objectManager->create(\Magento\Swatches\Block\Product\Renderer\Configurable::class);
        $this->productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/modify_product_stock_availability.php
     */
    public function testItAddOutOfStockOptionsToSwatchesConfig(): void
    {
        $product = $this->productRepository->get('configurable');
        $swatchRenderer = $this->swatchRenderer->setProduct($product);

        $jsonConfig = $swatchRenderer->getJsonConfig();
        $jsonConfig = json_decode($jsonConfig, true);

        $attributeData = array_shift($jsonConfig['attributes']);

        $this->assertEmpty($attributeData['options'][0]['products']);
        $this->assertNotEmpty($attributeData['options'][1]['products']);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/modify_attribute_option_sort_order.php
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/modify_product_stock_availability.php
     */
    public function testSwatchesOrderIsCorrect(): void
    {
        $product = $this->productRepository->get('configurable');
        $swatchRenderer = $this->swatchRenderer->setProduct($product);

        $jsonConfig = $swatchRenderer->getJsonConfig();
        $jsonConfig = json_decode($jsonConfig, true);

        $attributeData = array_shift($jsonConfig['attributes']);

        $this->assertEquals('Option 2', $attributeData['options'][0]['label']);
        $this->assertEquals('Option 1', $attributeData['options'][1]['label']);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/disable_one_simple_product.php
     */
    public function testItNotIncludeDisabledProduct(): void
    {
        $product = $this->productRepository->get('configurable');
        $swatchRenderer = $this->swatchRenderer->setProduct($product);

        $jsonConfig = $swatchRenderer->getJsonConfig();
        $jsonConfig = json_decode($jsonConfig, true);

        $attributeData = array_shift($jsonConfig['attributes']);

        $this->assertNotEmpty($attributeData['options'][0]['products']);
        $this->assertEmpty($attributeData['options'][1]['products']);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/visual_swatch_attribute_with_different_options_type.php
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/configurable_products.php
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/modify_product_stock_availability_with_swatch_image.php
     */
    public function testItReturnCorrectConfigurationForImageSwatch(): void
    {
        $product = $this->productRepository->get('configurable_12345');
        $swatchRenderer = $this->swatchRenderer->setProduct($product);

        $jsonSwatchConfig = $swatchRenderer->getJsonSwatchConfig();
        $jsonSwatchConfig = json_decode($jsonSwatchConfig, true);

        $optionsData = array_shift($jsonSwatchConfig);

        $outOfStockOption = $this->getOption($optionsData, 'option 2');

        $this->assertEquals(\Magento\Swatches\Model\Swatch::SWATCH_TYPE_VISUAL_IMAGE, $outOfStockOption['type']);
        $this->assertEquals('http://localhost/media/attribute/swatch/swatch_image/30x20/visual_swatch_attribute_option_type_image.jpg', str_replace('pub/', '', $outOfStockOption['value']));
        $this->assertEquals('http://localhost/media/attribute/swatch/swatch_thumb/110x90/visual_swatch_attribute_option_type_image.jpg', str_replace('pub/', '', $outOfStockOption['thumb']));
        $this->assertEquals('option 2', $outOfStockOption['label']);
    }

    /**
     * @param array $optionsData
     * @return array
     */
    protected function getOption(array $optionsData, string $label): array
    {
        foreach ($optionsData as $option) {
            if (isset($option['label']) && $option['label'] == $label) {
                return $option;
            }
        }

        return [
            'type' => null,
            'value' => null,
            'thumb' => null,
            'label' => null,
        ];
    }
}
