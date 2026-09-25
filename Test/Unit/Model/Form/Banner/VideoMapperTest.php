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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\AspectRatioPresets;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\VideoMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\AspectRatioParser;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(VideoMapper::class)]
class VideoMapperTest extends TestCase
{
    /**
     * @var VideoMapper
     */
    private VideoMapper $mapper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static fn (string $path): string => 'https://m.test/' . $path);
        $presets = $this->createMock(OptionSourceInterface::class);
        $presets->method('toOptionArray')->willReturn([
            ['value' => '16:9', 'label' => '16:9'],
            ['value' => '4:3', 'label' => '4:3'],
        ]);
        $this->mapper = new VideoMapper(
            new MediaFieldCodec($resolver),
            new AspectRatioPresets($presets),
            new AspectRatioParser()
        );
    }

    /**
     * URL, uploaded file, custom ratio and background mode reach the banner and export back
     *
     * @return void
     */
    public function testMapsBothWays(): void
    {
        $banner = new FakeBanner();
        $errors = new FieldErrors();

        $this->mapper->hydrate(new PostData([
            'video_url' => 'https://youtu.be/abc',
            'video_path' => [['file' => 'banner_slider/video/clip.mp4']],
            'video_aspect_ratio' => 'custom',
            'video_custom_aspect_ratio' => '3:2',
            'video_as_background' => '1',
        ]), $banner, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(
            [
                'video_url' => 'https://youtu.be/abc',
                'video_path' => [[
                    'file' => 'banner_slider/video/clip.mp4',
                    'name' => 'clip.mp4',
                    'url' => 'https://m.test/banner_slider/video/clip.mp4',
                    'type' => 'video/mp4',
                ]],
                'video_aspect_ratio' => 'custom',
                'video_custom_aspect_ratio' => '3:2',
                'video_as_background' => '1',
            ],
            $this->mapper->export($banner)
        );
    }

    /**
     * A preset ratio exports as the preset, with an empty custom field
     *
     * @return void
     */
    public function testExportsAPreset(): void
    {
        $banner = new FakeBanner();

        $this->mapper->hydrate(new PostData(['video_aspect_ratio' => '4:3']), $banner, new FieldErrors());

        $values = $this->mapper->export($banner);
        self::assertSame('4:3', $values['video_aspect_ratio']);
        self::assertSame('', $values['video_custom_aspect_ratio']);
    }

    /**
     * A custom ratio the parser refuses is a field error, not a crash
     *
     * @param string $ratio
     * @return void
     */
    #[TestWith(['2.35:1'])]
    #[TestWith(['0:9'])]
    #[TestWith(['wide'])]
    #[TestWith([''])]
    public function testReportsAnInvalidAspectRatio(string $ratio): void
    {
        $errors = new FieldErrors();

        $this->mapper->hydrate(
            new PostData(['video_aspect_ratio' => 'custom', 'video_custom_aspect_ratio' => $ratio]),
            new FakeBanner(),
            $errors
        );

        self::assertSame(
            ['Enter the aspect ratio as width:height in whole numbers from 1 to 100, for example 3:2.'],
            array_map('strval', $errors->getMessages())
        );
    }

    /**
     * A video path outside the video upload folder is refused unless it is the stored value, and a file without a
     * stored path never removes the stored one
     *
     * @return void
     */
    public function testGuardsTheVideoPath(): void
    {
        $banner = (new FakeBanner())->setVideoPath('banner_slider/video/old.webm');
        $errors = new FieldErrors();

        $this->mapper->hydrate(
            new PostData(['video_path' => [['file' => 'banner_slider/image/../../env.php']]]),
            $banner,
            $errors
        );
        $this->mapper->hydrate(
            new PostData(['video_path' => [['file' => 'banner_slider/video/old.webm']], 'video_url' => 'ftp://x']),
            $banner,
            $errors
        );
        $this->mapper->hydrate(
            new PostData(['video_path' => [['name' => 'clip.mp4', 'url' => 'https://m.test/wysiwyg/clip.mp4']]]),
            $banner,
            $errors
        );

        self::assertSame(
            [
                'Upload the video file through the video field.',
                'Enter a video URL that starts with http:// or https://.',
                'Choose the file for "Local Video File" with the Upload button.',
            ],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame('banner_slider/video/old.webm', $banner->getVideoPath());
    }
}
