<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\OptionsMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Hryvinskyi\BannerSliderApi\Api\Value\SlideEffect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OptionsMapper::class)]
class OptionsMapperTest extends TestCase
{
    /**
     * Every playback option reaches the slider and exports back
     *
     * @return void
     */
    public function testMapsBothWays(): void
    {
        $slider = new FakeSlider();
        $mapper = new OptionsMapper();
        $errors = new FieldErrors();
        $posted = [
            'effect' => 'fade',
            'auto_width' => '1',
            'auto_height' => '1',
            'loop' => '0',
            'lazy_load' => '0',
            'auto_play' => '0',
            'nav' => '0',
            'dots' => '0',
            'auto_play_timeout' => '7000',
            'preload_banners_count' => '2',
        ];

        $mapper->hydrate(new PostData($posted), $slider, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(SlideEffect::FADE, $slider->getEffect());
        self::assertSame($posted, $mapper->export($slider));
    }

    /**
     * Unknown effect, a too short autoplay interval and a negative preload count are field errors
     *
     * @return void
     */
    public function testReportsInvalidValues(): void
    {
        $slider = new FakeSlider();
        $errors = new FieldErrors();

        (new OptionsMapper())->hydrate(
            new PostData(['effect' => 'cube', 'auto_play_timeout' => '200', 'preload_banners_count' => '-1']),
            $slider,
            $errors
        );

        self::assertSame(
            [
                'Select a valid animation effect.',
                'The autoplay timeout must be a whole number of at least 1000 ms.',
                'Enter a whole number of 0 or more for "Preload First N Banners".',
            ],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame(SlideEffect::SLIDE, $slider->getEffect());
    }
}
