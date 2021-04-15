<?php
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var  \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->create(\Magento\Eav\Api\AttributeRepositoryInterface::class);

/** @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository */
$productAttributeRepository = $objectManager->get(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class);

/** @var \Magento\Eav\Api\Data\AttributeInterface $attribute */
$attribute = $productAttributeRepository->get('test_configurable');
$options = $attribute->getOptions();
$options[1]->setSortOrder(100);
$options[2]->setSortOrder(10);
$attribute->setOptions($options);
$productAttributeRepository->save($attribute);