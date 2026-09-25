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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\ActiveWindowFormCodec;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\CustomCssMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\ScheduleMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScheduleMapper::class)]
#[CoversClass(CustomCssMapper::class)]
class ScheduleAndCustomCssMapperTest extends TestCase
{
    /**
     * The window and the CSS reach the slider and export back
     *
     * @return void
     */
    public function testMapsBothWays(): void
    {
        $slider = new FakeSlider();
        $errors = new FieldErrors();
        $schedule = new ScheduleMapper(new ActiveWindowFormCodec());
        $css = new CustomCssMapper();
        $post = new PostData([
            'from_date' => '2026-12-01T00:00:00+01:00',
            'to_date' => '',
            'custom_css' => '.banner-slider-3 { margin: 0; }',
        ]);

        $schedule->hydrate($post, $slider, $errors);
        $css->hydrate($post, $slider, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(['from_date' => '2026-11-30 23:00:00', 'to_date' => ''], $schedule->export($slider));
        self::assertSame(['custom_css' => '.banner-slider-3 { margin: 0; }'], $css->export($slider));
    }

    /**
     * CSS with `<` is refused with its own message; blank CSS is none
     *
     * @return void
     */
    public function testGuardsTheCss(): void
    {
        $slider = (new FakeSlider())->setCustomCss('a { color: red; }');
        $errors = new FieldErrors();
        $mapper = new CustomCssMapper();

        $mapper->hydrate(new PostData(['custom_css' => '</style><script>']), $slider, $errors);
        self::assertSame(['Custom CSS must not contain "<".'], array_map('strval', $errors->getMessages()));
        self::assertSame('a { color: red; }', $slider->getCustomCss());

        $mapper->hydrate(new PostData(['custom_css' => '   ']), $slider, $errors);
        self::assertNull($slider->getCustomCss());
    }
}
