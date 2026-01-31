<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\DataProvider\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderApi\Api\Video\UploadInterface;

/**
 * Converts video array format to string path and moves from tmp directory.
 */
class PrepareVideoToString implements PrepareDataInterface
{
    /**
     * @param UploadInterface $videoUpload Video upload service
     * @param string $videoKey The key in data array containing the video data
     */
    public function __construct(
        private readonly UploadInterface $videoUpload,
        private readonly string $videoKey = 'video_path'
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        $videoData = $data[$this->videoKey] ?? [];

        if (is_array($videoData) && !empty($videoData) && isset($videoData[0]['name'])) {
            $video = $videoData[0];

            if (isset($video['tmp_name'])) {
                $data[$this->videoKey] = $this->videoUpload->moveFromTmp($video['name']);
            } else {
                $data[$this->videoKey] = $video['name'];
            }
        } elseif (empty($videoData) || !is_string($videoData)) {
            $data[$this->videoKey] = null;
        }
    }
}
