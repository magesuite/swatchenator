<?php

declare(strict_types=1);

namespace MageSuite\Swatchenator\Test\Integration\Plugin\Catalog\Model\Product;

class AddHasAllChildrenSalableFlagTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\TestFramework\ObjectManager $objectManager;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

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
     * @magentoDataFixture MageSuite_Swatchenator::Test/_files/set_out_of_stock_configurable_children.php
     */
    public function testItReturnCorrectFlagForConfigurableProductAfterUpdateSimple()
    {
        $product = $this->productRepository->get('configurable');

        $hasAllChildrenNotSalable = $product->getHasAllChildrenNotSalable();

        $this->assertTrue($hasAllChildrenNotSalable);
    }
}
