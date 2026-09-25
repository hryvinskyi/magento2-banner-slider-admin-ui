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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\ActiveWindowFormCodec;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\ScheduleMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScheduleMapper::class)]
class ScheduleMapperTest extends TestCase
{
    /**
     * The posted window is stored in UTC and exported back unchanged; an invalid window leaves the banner alone
     *
     * @return void
     */
    public function testMapsTheWindowBothWays(): void
    {
        $banner = new FakeBanner();
        $mapper = new ScheduleMapper(new ActiveWindowFormCodec());

        $mapper->hydrate(
            new PostData(['from_date' => '2026-04-01T22:00:00.000Z', 'to_date' => '2026-04-30T21:59:00.000Z']),
            $banner,
            new FieldErrors()
        );
        $errors = new FieldErrors();
        $mapper->hydrate(new PostData(['from_date' => 'soon']), $banner, $errors);

        self::assertTrue($errors->hasErrors());
        self::assertSame(
            ['from_date' => '2026-04-01 22:00:00', 'to_date' => '2026-04-30 21:59:00'],
            $mapper->export($banner)
        );
    }
}
