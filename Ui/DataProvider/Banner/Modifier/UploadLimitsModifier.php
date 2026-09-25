<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner\Modifier;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\ImageMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\VideoMapper;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Config\VideoConfigInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Gives the banner form's image and video uploaders the configured upload size limits, so the browser refuses a
 * file the server would refuse, with the limit in the field notice.
 */
class UploadLimitsModifier implements ModifierInterface
{
    private const BYTES_PER_MB = 1048576;

    /**
     * @param ImageConfigInterface $imageConfig
     * @param VideoConfigInterface $videoConfig
     */
    public function __construct(
        private readonly ImageConfigInterface $imageConfig,
        private readonly VideoConfigInterface $videoConfig
    ) {
    }

    /**
     * The data is left as it is
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * Set the uploaders' size limits and notices
     *
     * @param array<mixed> $meta
     * @return array<mixed>
     */
    public function modifyMeta(array $meta): array
    {
        $imageBytes = $this->imageConfig->getMaxUploadBytes();
        $videoBytes = $this->videoConfig->getMaxUploadBytes();

        return array_replace_recursive($meta, [
            'image_settings' => ['children' => [ImageMapper::FIELD => $this->fieldConfig(
                $imageBytes,
                (string)__(
                    'This image is the default source of every responsive crop. Maximum file size: %1 MB.',
                    $this->megabytes($imageBytes)
                )
            )]],
            'video_settings' => ['children' => [VideoMapper::PATH_FIELD => $this->fieldConfig(
                $videoBytes,
                (string)__('Upload an MP4, M4V or WebM video. Maximum file size: %1 MB.', $this->megabytes($videoBytes))
            )]],
        ]);
    }

    /**
     * Meta of one uploader field
     *
     * @param int $maxBytes
     * @param string $notice
     * @return array{arguments: array{data: array{config: array{maxFileSize: int, notice: string}}}}
     */
    private function fieldConfig(int $maxBytes, string $notice): array
    {
        return ['arguments' => ['data' => ['config' => ['maxFileSize' => $maxBytes, 'notice' => $notice]]]];
    }

    /**
     * A byte count in megabytes, with at most one decimal
     *
     * @param int $bytes
     * @return string
     */
    private function megabytes(int $bytes): string
    {
        $megabytes = round($bytes / self::BYTES_PER_MB, 1);

        return $megabytes === floor($megabytes) ? (string)(int)$megabytes : (string)$megabytes;
    }
}
