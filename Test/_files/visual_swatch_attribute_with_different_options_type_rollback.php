<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$filesystem = $objectManager->create(\Magento\Framework\Filesystem::class);
$media = $objectManager->create(\Magento\Swatches\Helper\Media::class);
$mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

$imageName = '/visual_swatch_attribute_option_type_image.jpg';

$mediaDirectory->delete($media->getAttributeSwatchPath($imageName));
