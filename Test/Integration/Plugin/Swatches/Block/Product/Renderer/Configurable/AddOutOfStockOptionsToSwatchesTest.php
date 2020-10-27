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
     * @magentoDataFixture modifySimpleProducts
     */
    public function testItAddOutOfStockOptionsToSwatchesConfig()
    {
        $product = $this->productRepository->get('configurable');
        $swatchRenderer = $this->swatchRenderer->setProduct($product);

        $jsonConfig = $swatchRenderer->getJsonConfig();
        $jsonConfig = json_decode($jsonConfig, true);

        $attributeData = array_shift($jsonConfig['attributes']);

        $firstOption = array_shift($attributeData['options']);

        $this->assertEmpty($firstOption['products']);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture modifyAttributes
     * @magentoDataFixture modifySimpleProducts
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

    public static function modifySimpleProducts()
    {
        require __DIR__ . '/../../../../../../../_files/products.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }

    public static function modifyAttributes()
    {
        require __DIR__ . '/../../../../../../../_files/attributes.php';
    }
}
