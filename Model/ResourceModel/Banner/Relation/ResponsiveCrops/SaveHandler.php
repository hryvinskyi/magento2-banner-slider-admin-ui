<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\ResourceModel\Banner\Relation\ResponsiveCrops;

use Hryvinskyi\BannerSliderAdminUi\Api\ResponsiveCropsSaverInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Handles saving responsive crops data when banner is saved via EntityManager
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @param ResponsiveCropsSaverInterface $responsiveCropsSaver
     */
    public function __construct(
        private readonly ResponsiveCropsSaverInterface $responsiveCropsSaver
    ) {
    }

    /**
     * Save responsive crops from extension attributes
     *
     * @param BannerInterface $entity
     * @param array<string, mixed> $arguments
     * @return BannerInterface
     */
    public function execute($entity, $arguments = []): BannerInterface
    {
        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes === null) {
            return $entity;
        }

        $cropsData = $extensionAttributes->getResponsiveCropsData();

        if (empty($cropsData) || !is_array($cropsData)) {
            return $entity;
        }

        $bannerImage = $entity->getImage();

        if ($bannerImage) {
            $cropsData = $this->normalizeSourceImages($cropsData, $bannerImage);
        }

        $this->responsiveCropsSaver->save((int)$entity->getBannerId(), $cropsData);

        return $entity;
    }

    /**
     * Normalize source_image paths in crops data
     *
     * @param array<int, array<string, mixed>> $cropsData
     * @param string $bannerImage
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSourceImages(array $cropsData, string $bannerImage): array
    {
        foreach ($cropsData as &$cropItem) {
            $sourceImage = $cropItem['source_image'] ?? null;

            if (!$sourceImage) {
                continue;
            }

            if (!str_contains($sourceImage, '/')) {
                $cropItem['source_image'] = $bannerImage;
            }
        }

        return $cropsData;
    }
}
