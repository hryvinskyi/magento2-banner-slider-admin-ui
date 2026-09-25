<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\SliderFormMapperInterface;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\ActiveWindowFormCodec;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;

/**
 * The period in which a slider is shown (`from_date`, `to_date`).
 */
class ScheduleMapper implements SliderFormMapperInterface
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
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        $window = $this->windowCodec->hydrate($post, $slider->getActiveWindow(), $errors);
        if ($window !== null) {
            $slider->setActiveWindow($window);
        }
    }

    /**
     * @inheritDoc
     */
    public function export(SliderInterface $slider): array
    {
        return $this->windowCodec->export($slider->getActiveWindow());
    }
}
