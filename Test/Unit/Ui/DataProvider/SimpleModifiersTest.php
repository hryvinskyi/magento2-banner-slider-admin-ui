<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\DataProvider;

use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\NestedValue;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner\Modifier\UploadLimitsModifier;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider\Modifier\BreakpointRowsModifier;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Config\VideoConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UploadLimitsModifier::class)]
#[CoversClass(BreakpointRowsModifier::class)]
class SimpleModifiersTest extends TestCase
{
    use NestedValue;

    /**
     * The uploaders get the configured limits and a notice naming them
     *
     * @return void
     */
    public function testSetsUploadLimits(): void
    {
        $image = $this->createMock(ImageConfigInterface::class);
        $image->method('getMaxUploadBytes')->willReturn(10485760);
        $video = $this->createMock(VideoConfigInterface::class);
        $video->method('getMaxUploadBytes')->willReturn(157286400);
        $modifier = new UploadLimitsModifier($image, $video);

        $meta = $modifier->modifyMeta(['image_settings' => ['children' => ['image' => ['keep' => 1]]]]);

        self::assertSame(['a' => 1], $modifier->modifyData(['a' => 1]));
        self::assertSame(
            [
                'keep' => 1,
                'arguments' => ['data' => ['config' => [
                    'maxFileSize' => 10485760,
                    'notice' => 'This image is the default source of every responsive crop. Maximum file size: 10 MB.',
                ]]],
            ],
            $this->nested($meta, 'image_settings', 'children', 'image')
        );
        self::assertSame(
            'Upload an MP4, M4V or WebM video. Maximum file size: 150 MB.',
            $this->nested($meta, 'video_settings', 'children', 'video_path', 'arguments', 'data', 'config', 'notice')
        );
    }

    /**
     * A stored slider gets its breakpoint rows and the marker; a new slider only the marker
     *
     * @return void
     */
    public function testAddsBreakpointRowsAndTheMarker(): void
    {
        $breakpoint = $this->createConfiguredMock(BreakpointInterface::class, [
            'getBreakpointId' => 1,
            'getName' => 'Desktop',
            'getIdentifier' => 'desktop',
            'getMediaQuery' => '(min-width: 1200px)',
            'getMinWidth' => 1200,
            'getTargetWidth' => 1920,
            'getTargetHeight' => 600,
            'getSortOrder' => 10,
            'isEnabled' => true,
        ]);
        $repository = $this->createMock(BreakpointRepositoryInterface::class);
        $repository->expects(self::once())->method('getBySliderId')->with(3)->willReturn([$breakpoint]);
        $modifier = new BreakpointRowsModifier($repository, new BreakpointPresenter(), new EntityIdReader());

        $data = $modifier->modifyData([3 => ['slider_id' => '3'], '' => []]);

        self::assertSame(
            [
                3 => [
                    'slider_id' => '3',
                    'breakpoints_submitted' => '1',
                    'breakpoints' => ['breakpoints_container' => [
                        (new BreakpointPresenter())->presentFormRow($breakpoint),
                    ]],
                ],
                '' => ['breakpoints_submitted' => '1'],
            ],
            $data
        );
        self::assertSame(['m' => 1], $modifier->modifyMeta(['m' => 1]));
    }

    /**
     * A record that shows a refused post keeps the submitted rows; an emptied list stays empty
     *
     * @return void
     */
    public function testKeepsTheSubmittedRowsOfARefusedPost(): void
    {
        $repository = $this->createMock(BreakpointRepositoryInterface::class);
        $repository->expects(self::never())->method('getBySliderId');
        $modifier = new BreakpointRowsModifier($repository, new BreakpointPresenter(), new EntityIdReader());
        $row = ['identifier' => 'typed', 'name' => 'Typed'];

        $data = $modifier->modifyData([
            3 => [
                'slider_id' => '3',
                'breakpoints_submitted' => '1',
                'breakpoints' => ['breakpoints_container' => [2 => $row]],
            ],
            4 => ['slider_id' => '4', 'breakpoints_submitted' => '1'],
        ]);

        self::assertSame(['breakpoints_container' => [$row]], $this->nested($data, 3, 'breakpoints'));
        self::assertSame(['breakpoints_container' => []], $this->nested($data, 4, 'breakpoints'));
    }
}
