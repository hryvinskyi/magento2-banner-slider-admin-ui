<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\BannerFormMapperInterface;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\ActiveWindowFormCodec;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;

/**
 * The period in which a banner is shown (`from_date`, `to_date`).
 */
class ScheduleMapper implements BannerFormMapperInterface
{
    /**
     * @param ActiveWindowFormCodec $windowCodec
     */
    public function __construct(
        private readonly ActiveWindowFormCodec $windowCodec
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        $window = $this->windowCodec->hydrate($post, $banner->getActiveWindow(), $errors);
        if ($window !== null) {
            $banner->setActiveWindow($window);
        }
    }

    /**
     * @inheritDoc
     */
    public function export(BannerInterface $banner): array
    {
        return $this->windowCodec->export($banner->getActiveWindow());
    }
}
