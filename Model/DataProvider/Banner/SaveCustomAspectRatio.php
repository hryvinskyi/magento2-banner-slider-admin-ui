<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\DataProvider\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderAdminUi\Model\Source\AspectRatio;

/**
 * Processes custom aspect ratio before saving
 */
class SaveCustomAspectRatio implements PrepareDataInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array &$data): void
    {
        if (!isset($data['video_aspect_ratio'])) {
            return;
        }

        if ($data['video_aspect_ratio'] === AspectRatio::CUSTOM
            && isset($data['video_custom_aspect_ratio'])
            && !empty($data['video_custom_aspect_ratio'])
        ) {
            $data['video_aspect_ratio'] = $data['video_custom_aspect_ratio'];
        }

        unset($data['video_custom_aspect_ratio']);
    }
}
