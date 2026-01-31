<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Source;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Banner type source model
 */
class BannerType implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => BannerInterface::TYPE_IMAGE, 'label' => __('Image')],
            ['value' => BannerInterface::TYPE_VIDEO, 'label' => __('Video')],
            ['value' => BannerInterface::TYPE_CUSTOM, 'label' => __('Custom HTML')],
        ];
    }
}
