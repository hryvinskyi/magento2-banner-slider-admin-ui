<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\ResourceModel\Banner\Relation\ResponsiveCrops;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerExtensionFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Handles loading responsive crops data when banner is loaded via EntityManager
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @param ResponsiveCropRepositoryInterface $responsiveCropRepository
     * @param BannerExtensionFactory $extensionFactory
     */
    public function __construct(
        private readonly ResponsiveCropRepositoryInterface $responsiveCropRepository,
        private readonly BannerExtensionFactory $extensionFactory
    ) {
    }

    /**
     * Load responsive crops into extension attributes
     *
     * @param BannerInterface $entity
     * @param array<string, mixed> $arguments
     * @return BannerInterface
     */
    public function execute($entity, $arguments = []): BannerInterface
    {
        $bannerId = (int)$entity->getBannerId();

        if (!$bannerId) {
            return $entity;
        }

        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $crops = $this->responsiveCropRepository->getByBannerId($bannerId);
        $cropsData = $this->convertCropsToArray($crops);

        $extensionAttributes->setResponsiveCropsData($cropsData);
        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    /**
     * Convert crop entities to array format
     *
     * @param array<int, \Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface> $crops
     * @return array<int, array<string, mixed>>
     */
    private function convertCropsToArray(array $crops): array
    {
        $result = [];

        foreach ($crops as $crop) {
            $result[] = $crop->getData();
        }

        return $result;
    }
}
