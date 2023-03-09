<?php

namespace MageSuite\Swatchenator\Test\Integration\Plugin\Catalog\Model\Product;

class AddHasAllChildrenSalableFlagTest extends \PHPUnit\Framework\TestCase
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
        $this->productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testItReturnCorrectFlagForSimpleProduct()
    {
        $product = $this->productRepository->get('simple_10');

        $hasAllChildrenNotSalable = $product->getHasAllChildrenNotSalable();

        $this->assertFalse($hasAllChildrenNotSalable);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testItReturnCorrectFlagForConfigurableProductBeforeUpdateSimple()
    {
        $product = $this->productRepository->get('configurable');

        $hasAllChildrenNotSalable = $product->getHasAllChildrenNotSalable();

        $this->assertFalse($hasAllChildrenNotSalable);
    }
    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store swatchenator/general/is_enabled 1
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture setOutOfStockConfigurableChildren
     */
    public function testItReturnCorrectFlagForConfigurableProductAfterUpdateSimple()
    {
        $product = $this->productRepository->get('configurable');

        $hasAllChildrenNotSalable = $product->getHasAllChildrenNotSalable();

        $this->assertTrue($hasAllChildrenNotSalable);
    }

    public static function setOutOfStockConfigurableChildren()
    {
        require __DIR__ . '/../../../../../_files/set_out_of_stock_configurable_children.php';

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexAll();
    }
}
