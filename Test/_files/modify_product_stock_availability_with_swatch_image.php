<?php

declare(strict_types=1);

include BP.'/dev/tests/integration/testsuite/Magento/Catalog/_files/product_image.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$product = $productRepository->get('simple_40');

$product->setStockData(
    [
        'use_config_manage_stock' => 0,
        'manage_stock' => 0,
        'qty' => 0,
        'is_qty_decimal' => 0,
        'is_in_stock' => 0,
    ]
);

$productRepository->save($product);

$product->reindex();
$product->priceReindexCallback();
