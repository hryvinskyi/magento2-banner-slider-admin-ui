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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\GeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeneralMapper::class)]
class GeneralMapperTest extends TestCase
{
    /**
     * Name, status, location and priority reach the slider and export back; an empty location is none
     *
     * @return void
     */
    public function testMapsBothWays(): void
    {
        $slider = new FakeSlider();
        $mapper = new GeneralMapper();
        $errors = new FieldErrors();

        $mapper->hydrate(
            new PostData(['name' => 'Home', 'status' => '1', 'location' => 'home-top', 'priority' => '2']),
            $slider,
            $errors
        );
        self::assertSame(
            ['name' => 'Home', 'status' => '1', 'location' => 'home-top', 'priority' => '2'],
            $mapper->export($slider)
        );

        $mapper->hydrate(new PostData(['location' => '']), $slider, $errors);
        self::assertNull($slider->getLocation());
        self::assertFalse($errors->hasErrors());
    }

    /**
     * An invalid location or priority is a translated field error
     *
     * @return void
     */
    public function testReportsInvalidValues(): void
    {
        $errors = new FieldErrors();

        (new GeneralMapper())->hydrate(
            new PostData(['name' => ' ', 'location' => 'home top!', 'priority' => 'first']),
            new FakeSlider(),
            $errors
        );

        self::assertSame(
            [
                'Enter a valid value for "Name".',
                'The location may contain only letters, digits, "_" and "-", up to 255 characters.',
                'Enter a whole number of 0 or more for "Priority".',
            ],
            array_map('strval', $errors->getMessages())
        );
    }
}
