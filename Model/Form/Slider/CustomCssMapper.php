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
 * Custom CSS of a slider: plain CSS, kept as posted; CSS containing `<` is refused.
 */
class CustomCssMapper implements SliderFormMapperInterface
{
    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        if (!$post->has(SliderInterface::CUSTOM_CSS)) {
            return;
        }
        $css = $post->rawText(SliderInterface::CUSTOM_CSS);

        $errors->attempt(
            __('Custom CSS must not contain "<".'),
            static fn () => $slider->setCustomCss($css === null || trim($css) === '' ? null : $css)
        );
    }

    /**
     * @inheritDoc
     */
    public function export(SliderInterface $slider): array
    {
        return [SliderInterface::CUSTOM_CSS => $slider->getCustomCss() ?? ''];
    }
}
