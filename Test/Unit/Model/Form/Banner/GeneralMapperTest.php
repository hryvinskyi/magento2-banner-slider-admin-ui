<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\GeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderApi\Api\Value\BannerType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeneralMapper::class)]
class GeneralMapperTest extends TestCase
{
    /**
     * Posted values reach the banner and come back as form values
     *
     * @return void
     */
    public function testMapsBothWays(): void
    {
        $banner = new FakeBanner();
        $errors = new FieldErrors();
        $mapper = new GeneralMapper();

        $mapper->hydrate(new PostData([
            'name' => 'Spring',
            'slider_id' => '4',
            'status' => '0',
            'type' => '1',
            'position' => '3',
            'is_preload' => '1',
        ]), $banner, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(4, $banner->getSliderId());
        self::assertSame(BannerType::VIDEO, $banner->getType());
        self::assertSame(
            [
                'name' => 'Spring',
                'slider_id' => '4',
                'status' => '0',
                'type' => '1',
                'position' => '3',
                'is_preload' => '1',
            ],
            $mapper->export($banner)
        );
    }

    /**
     * Every rejected field is reported, the others are still applied; an empty slider stays unassigned
     *
     * @return void
     */
    public function testCollectsFieldErrors(): void
    {
        $banner = new FakeBanner();
        $errors = new FieldErrors();

        (new GeneralMapper())->hydrate(new PostData([
            'name' => '',
            'slider_id' => '',
            'type' => '7',
            'position' => '-1',
            'status' => '1',
        ]), $banner, $errors);

        self::assertSame(
            [
                'Enter a valid value for "Name".',
                'Select a valid banner type.',
                'Enter a whole number of 0 or more for "Position".',
            ],
            array_map('strval', $errors->getMessages())
        );
        self::assertNull($banner->getSliderId());
        self::assertTrue($banner->isEnabled());
    }
}
