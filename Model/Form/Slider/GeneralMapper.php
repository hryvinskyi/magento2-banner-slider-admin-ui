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
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;

/**
 * Name, status, location code and priority of a slider.
 */
class GeneralMapper implements SliderFormMapperInterface
{
    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        if ($post->has(SliderInterface::NAME)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Name')),
                static fn () => $slider->setName($post->text(SliderInterface::NAME) ?? '')
            );
        }
        if ($post->has(SliderInterface::STATUS)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Enabled')),
                static fn () => $slider->setIsEnabled($post->flag(SliderInterface::STATUS, true))
            );
        }
        if ($post->has(SliderInterface::LOCATION)) {
            $errors->attempt(
                __('The location may contain only letters, digits, "_" and "-", up to 255 characters.'),
                static fn () => $slider->setLocation($post->text(SliderInterface::LOCATION))
            );
        }
        if ($post->has(SliderInterface::PRIORITY)) {
            $errors->attempt(
                __('Enter a whole number of 0 or more for "%1".', __('Priority')),
                static fn () => $slider->setPriority($post->integer(SliderInterface::PRIORITY) ?? 0)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function export(SliderInterface $slider): array
    {
        return [
            SliderInterface::NAME => $slider->getName(),
            SliderInterface::STATUS => $slider->isEnabled() ? '1' : '0',
            SliderInterface::LOCATION => $slider->getLocation() ?? '',
            SliderInterface::PRIORITY => (string)$slider->getPriority(),
        ];
    }
}
