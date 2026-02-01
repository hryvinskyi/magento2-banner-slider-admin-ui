<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model;

use Hryvinskyi\BannerSlider\Model\ResponsiveCropFactory;
use Hryvinskyi\BannerSliderAdminUi\Api\ResponsiveCropsSaverInterface;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImagePathConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Psr\Log\LoggerInterface;

/**
 * Saves responsive crops data and browser-generated images for a banner
 */
class ResponsiveCropsSaver implements ResponsiveCropsSaverInterface
{
    private const DEFAULT_WEBP_QUALITY = 85;
    private const DEFAULT_AVIF_QUALITY = 80;

    private ?WriteInterface $mediaDirectory = null;

    /**
     * @param ResponsiveCropRepositoryInterface $responsiveCropRepository
     * @param ResponsiveCropFactory $responsiveCropFactory
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param Filesystem $filesystem
     * @param ImagePathConfigInterface $imagePathConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ResponsiveCropRepositoryInterface $responsiveCropRepository,
        private readonly ResponsiveCropFactory $responsiveCropFactory,
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly Filesystem $filesystem,
        private readonly ImagePathConfigInterface $imagePathConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(int $bannerId, array $cropsData): void
    {
        foreach ($cropsData as $cropItem) {
            $this->saveSingleCrop($bannerId, $cropItem);
        }
    }

    /**
     * Save a single crop item with images
     *
     * @param int $bannerId
     * @param array<string, mixed> $cropItem
     * @return void
     */
    private function saveSingleCrop(int $bannerId, array $cropItem): void
    {
        $breakpointId = (int)($cropItem['breakpoint_id'] ?? 0);

        if (!$breakpointId) {
            return;
        }

        try {
            $existingCrop = $this->responsiveCropRepository->getByBannerAndBreakpoint(
                $bannerId,
                $breakpointId
            );

            $crop = $existingCrop ?? $this->responsiveCropFactory->create();

            $this->populateCrop($crop, $bannerId, $cropItem);
            $this->saveImages($crop, $bannerId, $breakpointId, $cropItem);
            $this->responsiveCropRepository->save($crop);
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to save responsive crop data',
                [
                    'banner_id' => $bannerId,
                    'breakpoint_id' => $breakpointId,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Save base64 images to files
     *
     * @param ResponsiveCropInterface $crop
     * @param int $bannerId
     * @param int $breakpointId
     * @param array<string, mixed> $data
     * @return void
     */
    private function saveImages(
        ResponsiveCropInterface $crop,
        int $bannerId,
        int $breakpointId,
        array $data
    ): void {
        $breakpointIdentifier = $this->getBreakpointIdentifier($breakpointId);
        $hash = $this->generateImageHash($data);
        $basePath = $this->imagePathConfig->getResponsivePath();
        $baseDir = sprintf('%s/%d', $basePath, $bannerId);

        $this->deleteExistingImages($crop);

        if (!empty($data['cropped_image_base64'])) {
            $format = $data['cropped_image_format'] ?? 'jpg';
            $fileName = sprintf('%s_%s.%s', $breakpointIdentifier, $hash, $format);
            $filePath = $this->saveBase64Image($data['cropped_image_base64'], $baseDir, $fileName);

            if ($filePath) {
                $crop->setCroppedImage($filePath);
            }
        }

        if (!empty($data['webp_image_base64'])) {
            $fileName = sprintf('%s_%s.webp', $breakpointIdentifier, $hash);
            $filePath = $this->saveBase64Image($data['webp_image_base64'], $baseDir, $fileName);

            if ($filePath) {
                $crop->setWebpImage($filePath);
            }
        }

        if (!empty($data['avif_image_base64'])) {
            $fileName = sprintf('%s_%s.avif', $breakpointIdentifier, $hash);
            $filePath = $this->saveBase64Image($data['avif_image_base64'], $baseDir, $fileName);

            if ($filePath) {
                $crop->setAvifImage($filePath);
            }
        }
    }

    /**
     * Save base64 encoded image to file
     *
     * @param string $base64Data
     * @param string $directory
     * @param string $fileName
     * @return string|null
     */
    private function saveBase64Image(string $base64Data, string $directory, string $fileName): ?string
    {
        try {
            $imageData = base64_decode($base64Data, true);

            if ($imageData === false) {
                return null;
            }

            $mediaDirectory = $this->getMediaDirectory();
            $mediaDirectory->create($directory);

            $filePath = $directory . '/' . $fileName;
            $mediaDirectory->writeFile($filePath, $imageData);

            return $filePath;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save image from base64', [
                'directory' => $directory,
                'fileName' => $fileName,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Delete existing generated images
     *
     * @param ResponsiveCropInterface $crop
     * @return void
     */
    private function deleteExistingImages(ResponsiveCropInterface $crop): void
    {
        $mediaDirectory = $this->getMediaDirectory();
        $imagePaths = [
            $crop->getCroppedImage(),
            $crop->getWebpImage(),
            $crop->getAvifImage()
        ];

        foreach ($imagePaths as $imagePath) {
            if ($imagePath && $mediaDirectory->isExist($imagePath)) {
                try {
                    $mediaDirectory->delete($imagePath);
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to delete old image', [
                        'path' => $imagePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Get breakpoint identifier
     *
     * @param int $breakpointId
     * @return string
     */
    private function getBreakpointIdentifier(int $breakpointId): string
    {
        try {
            $breakpoint = $this->breakpointRepository->getById($breakpointId);

            return $breakpoint->getIdentifier();
        } catch (\Exception $e) {
            return 'breakpoint_' . $breakpointId;
        }
    }

    /**
     * Generate hash for image based on crop parameters
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function generateImageHash(array $data): string
    {
        $hashData = [
            $data['source_image'] ?? '',
            $data['crop_x'] ?? 0,
            $data['crop_y'] ?? 0,
            $data['crop_width'] ?? 0,
            $data['crop_height'] ?? 0,
            $data['webp_quality'] ?? self::DEFAULT_WEBP_QUALITY,
            $data['avif_quality'] ?? self::DEFAULT_AVIF_QUALITY
        ];

        return substr(md5(implode('_', array_map('strval', $hashData))), 0, 8);
    }

    /**
     * Populate crop entity with data
     *
     * @param ResponsiveCropInterface $crop
     * @param int $bannerId
     * @param array<string, mixed> $data
     * @return void
     */
    private function populateCrop(ResponsiveCropInterface $crop, int $bannerId, array $data): void
    {
        $crop->setBannerId($bannerId);
        $crop->setBreakpointId((int)($data['breakpoint_id'] ?? 0));
        $crop->setSourceImage($data['source_image'] ?? null);
        $crop->setCropX((int)($data['crop_x'] ?? 0));
        $crop->setCropY((int)($data['crop_y'] ?? 0));
        $crop->setCropWidth((int)($data['crop_width'] ?? 0));
        $crop->setCropHeight((int)($data['crop_height'] ?? 0));
        $crop->setGenerateWebpEnabled((bool)($data['generate_webp'] ?? true));
        $crop->setGenerateAvifEnabled((bool)($data['generate_avif'] ?? false));
        $crop->setWebpQuality((int)($data['webp_quality'] ?? self::DEFAULT_WEBP_QUALITY));
        $crop->setAvifQuality((int)($data['avif_quality'] ?? self::DEFAULT_AVIF_QUALITY));
        $crop->setSortOrder((int)($data['sort_order'] ?? 0));
        $crop->setStatus(1);
    }

    /**
     * Get media directory instance
     *
     * @return WriteInterface
     */
    private function getMediaDirectory(): WriteInterface
    {
        if ($this->mediaDirectory === null) {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        }

        return $this->mediaDirectory;
    }
}
