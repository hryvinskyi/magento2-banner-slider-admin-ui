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
 * Prepares custom aspect ratio field for form display
 */
class PrepareCustomAspectRatio implements PrepareDataInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array &$data): void
    {
        if (!isset($data['video_aspect_ratio'])) {
            return;
        }

        $aspectRatio = $data['video_aspect_ratio'];

        if (!in_array($aspectRatio, AspectRatio::PREDEFINED_RATIOS, true)) {
            $data['video_custom_aspect_ratio'] = $aspectRatio;
            $data['video_aspect_ratio'] = AspectRatio::CUSTOM;
        }
    }
}
