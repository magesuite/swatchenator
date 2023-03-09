<?php

namespace MageSuite\Swatchenator\Plugin\Catalog\Model\Product;

class AddHasAllChildrenSalableFlag
{
    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory
     */
    protected \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory $productCollectionFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Collection\SalableProcessor
     */
    protected $salableProcessor;

    public function __construct(
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory $productCollectionFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Collection\SalableProcessor $salableProcessor
    ) {
        $this->salableProcessor = $salableProcessor;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function aroundGetData(\Magento\Catalog\Model\Product $subject, callable $proceed, $key = '', $index = null)
    {
        if ($key !== 'has_all_children_not_salable') {
            return $proceed($key, $index);
        }

        if ($subject->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return false;
        }

        return $this->hasAllChildrenNotSalable($subject);
    }

    protected function hasAllChildrenNotSalable($product)
    {
        $storeId = $this->getStoreFilter($product);
        $collection = $this->getLinkedProductCollection($product);
        $collection->addStoreFilter($storeId);
        $collection = $this->salableProcessor->process($collection);

        return $collection->getSize() === 0;
    }

    protected function getLinkedProductCollection($product)
    {
        $collection = $this->productCollectionFactory->create()
            ->setFlag('product_children', true)
            ->setProductFilter($product);

        if ($this->getStoreFilter($product) !== null) {
            $collection->addStoreFilter($this->getStoreFilter($product));
        }

        return $collection;
    }

    public function getStoreFilter($product)
    {
        $cacheKey = '_cache_instance_store_filter';
        return $product->getData($cacheKey);
    }
}
