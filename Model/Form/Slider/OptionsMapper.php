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
use Hryvinskyi\BannerSliderApi\Api\Value\SlideEffect;
use Magento\Framework\Phrase;

/**
 * Playback options of a slider: effect, the on/off options, autoplay interval and preload count.
 */
class OptionsMapper implements SliderFormMapperInterface
{
    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        if ($post->has(SliderInterface::EFFECT)) {
            $errors->attempt(__('Select a valid animation effect.'), static function () use ($post, $slider): void {
                $effect = SlideEffect::tryFrom($post->text(SliderInterface::EFFECT) ?? '');
                if ($effect === null) {
                    throw new \InvalidArgumentException('Unknown slide effect.');
                }
                $slider->setEffect($effect);
            });
        }

        foreach ($this->toggles($slider) as $field => [$label, $apply]) {
            if ($post->has($field)) {
                $errors->attempt(
                    __('Enter a valid value for "%1".', $label),
                    static fn () => $apply($post->flag($field, false))
                );
            }
        }

        if ($post->has(SliderInterface::AUTO_PLAY_TIMEOUT)) {
            $errors->attempt(
                __(
                    'The autoplay timeout must be a whole number of at least %1 ms.',
                    SliderInterface::MIN_AUTO_PLAY_INTERVAL
                ),
                static fn () => $slider->setAutoPlayInterval(
                    $post->integer(SliderInterface::AUTO_PLAY_TIMEOUT) ?? SliderInterface::DEFAULT_AUTO_PLAY_INTERVAL
                )
            );
        }
        if ($post->has(SliderInterface::PRELOAD_BANNERS_COUNT)) {
            $errors->attempt(
                __('Enter a whole number of 0 or more for "%1".', __('Preload First N Banners')),
                static fn () => $slider->setPreloadBannersCount(
                    $post->integer(SliderInterface::PRELOAD_BANNERS_COUNT) ?? 0
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function export(SliderInterface $slider): array
    {
        return [
            SliderInterface::EFFECT => $slider->getEffect()->value,
            SliderInterface::AUTO_WIDTH => $slider->isAutoWidthEnabled() ? '1' : '0',
            SliderInterface::AUTO_HEIGHT => $slider->isAutoHeightEnabled() ? '1' : '0',
            SliderInterface::LOOP => $slider->isLoopEnabled() ? '1' : '0',
            SliderInterface::LAZY_LOAD => $slider->isLazyLoadEnabled() ? '1' : '0',
            SliderInterface::AUTO_PLAY => $slider->isAutoPlayEnabled() ? '1' : '0',
            SliderInterface::SHOW_AUTOPLAY_TOGGLE => $slider->isAutoPlayToggleEnabled() ? '1' : '0',
            SliderInterface::NAV => $slider->isNavigationEnabled() ? '1' : '0',
            SliderInterface::DOTS => $slider->isPaginationEnabled() ? '1' : '0',
            SliderInterface::AUTO_PLAY_TIMEOUT => (string)$slider->getAutoPlayInterval(),
            SliderInterface::PRELOAD_BANNERS_COUNT => (string)$slider->getPreloadBannersCount(),
        ];
    }

    /**
     * The on/off fields: field => [label, setter]
     *
     * @param SliderInterface $slider
     * @return array<string, array{0: Phrase, 1: \Closure(bool): mixed}>
     */
    private function toggles(SliderInterface $slider): array
    {
        return [
            SliderInterface::AUTO_WIDTH => [__('Auto Width'), $slider->setAutoWidthEnabled(...)],
            SliderInterface::AUTO_HEIGHT => [__('Auto Height'), $slider->setAutoHeightEnabled(...)],
            SliderInterface::LOOP => [__('Loop'), $slider->setLoopEnabled(...)],
            SliderInterface::LAZY_LOAD => [__('Lazy Load Images'), $slider->setLazyLoadEnabled(...)],
            SliderInterface::AUTO_PLAY => [__('Autoplay'), $slider->setAutoPlayEnabled(...)],
            SliderInterface::SHOW_AUTOPLAY_TOGGLE => [
                __('Show Pause/Play Button'),
                $slider->setAutoPlayToggleEnabled(...),
            ],
            SliderInterface::NAV => [__('Show Navigation Arrows'), $slider->setNavigationEnabled(...)],
            SliderInterface::DOTS => [__('Show Pagination Dots'), $slider->setPaginationEnabled(...)],
        ];
    }
}
