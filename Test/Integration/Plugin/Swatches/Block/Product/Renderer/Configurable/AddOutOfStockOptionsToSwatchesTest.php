<?php

namespace MageSuite\Swatchenator\Test\Integration\Plugin\Swatches\Block\Product\Renderer\Configurable;

class AddOutOfStockOptionsToSwatchesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Swatches\Block\Product\Renderer\Configurable
     */
    protected $swatchRenderer;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

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
     * @magentoDataFixture modifySimpleProductStockAvailability
     */
    public function testItAddOutOfStockOptionsToSwatchesConfig()
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
     * @magentoDataFixture modifyAttributeOptionSortOrder
     * @magentoDataFixture modifySimpleProductStockAvailability
     */
    public function testSwatchesOrderIsCorrect()
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
     * @magentoDataFixture disableOneSimpleProduct
     */
    public function testItNotIncludeDisabledProduct()
    {
        $product = $this->productRepository->get('configurable');
        $swatchRenderer = $this->swatchRenderer->setProduct($product);

        $jsonConfig = $swatchRenderer->getJsonConfig();
        $jsonConfig = json_decode($jsonConfig, true);

        $attributeData = array_shift($jsonConfig['attributes']);

        $this->assertNotEmpty($attributeData['options'][0]['products']);
        $this->assertEmpty($attributeData['options'][1]['products']);
    }

    public static function modifySimpleProductStockAvailability()
    {
        require __DIR__ . '/../../../../../../../_files/modify_product_stock_availability.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }

    public static function disableOneSimpleProduct()
    {
        require __DIR__ . '/../../../../../../../_files/disable_one_simple_product.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }

    public static function modifyAttributeOptionSortOrder()
    {
        require __DIR__ . '/../../../../../../../_files/modify_attribute_option_sort_order.php';
    }

    public static function modifySimpleProductStockAvailabilityWithSwatchImage()
    {
        require __DIR__ . '/../../../../../../../_files/modify_product_stock_availability_with_swatch_image.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }

    public static function addConfigurableProductWithSwatchOptions()
    {
        require __DIR__ . '/../../../../../../../_files/configurable_products.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }

    public static function addVisualSwatchAttributeWithDifferentOptionsType()
    {
        require __DIR__ . '/../../../../../../../_files/visual_swatch_attribute_with_different_options_type.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }
}
