<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\DataProvider\Banner\Modifier;

use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\NestedValue;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner\Modifier\ResponsiveCropperModifier;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\CropVariantInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\CropRect;
use Hryvinskyi\BannerSliderApi\Api\Value\ImageFormat;
use Magento\Framework\Convert\DataSize;
use Magento\Framework\File\Size;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponsiveCropperModifier::class)]
class ResponsiveCropperModifierTest extends TestCase
{
    use NestedValue;

    /**
     * @var ResponsiveCropperModifier
     */
    private ResponsiveCropperModifier $modifier;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $desktop = $this->breakpoint(1, 'desktop', 1200);
        $mobile = $this->breakpoint(2, 'mobile', 0);
        $breakpoints = $this->createMock(BreakpointRepositoryInterface::class);
        $breakpoints->method('getBySliderId')->with(3, true)->willReturn([$desktop, $mobile]);

        $variant = $this->createConfiguredMock(CropVariantInterface::class, [
            'getFormat' => 'webp',
            'getQuality' => 85,
            'getPath' => 'banner_slider/responsive/5/desktop_ab.webp',
        ]);
        $crop = $this->createConfiguredMock(ResponsiveCropInterface::class, [
            'getBreakpointId' => 1,
            'getCropRect' => new CropRect(10, 20, 1920, 600),
            'getSourceImage' => null,
            'isEnabled' => false,
            'getVariants' => [$variant],
            'getCroppedImage' => 'banner_slider/responsive/5/desktop_ab.jpg',
        ]);
        $crops = $this->createMock(ResponsiveCropRepositoryInterface::class);
        $crops->method('getByBannerId')->with(5)->willReturn([$crop]);

        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static fn (string $path): string => 'https://m.test/' . $path);
        $webp = new ImageFormat('webp', 'image/webp', 'webp');
        $avif = new ImageFormat('avif', 'image/avif', 'avif');
        $registry = $this->createMock(ImageFormatRegistryInterface::class);
        $registry->method('getVariantFormats')->willReturn([$avif, $webp]);
        $registry->method('isEncodable')->willReturnCallback(
            static fn (ImageFormat $format): bool => $format->getCode() === 'webp'
        );
        $config = $this->createMock(ImageConfigInterface::class);
        $config->method('getDefaultVariantFormats')->willReturn(['webp']);
        $config->method('getDefaultQuality')->willReturnCallback(static function (string $code): int {
            if ($code === 'avif') {
                throw new \InvalidArgumentException('No quality configured.');
            }

            return 85;
        });
        $config->method('getMaxUploadBytes')->willReturn(10485760);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route): string => 'https://admin.test/' . $route
        );
        $size = $this->createMock(Size::class);
        $size->method('getPostMaxSize')->willReturn('8M');

        $this->modifier = new ResponsiveCropperModifier(
            $breakpoints,
            $crops,
            new BreakpointPresenter(),
            $resolver,
            $registry,
            $config,
            $urlBuilder,
            $size,
            new DataSize(),
            new EntityIdReader()
        );
    }

    /**
     * Every enabled breakpoint of the record's slider comes with the banner's crop for it, or empty crop values, and
     * the state names the slider it was built for
     *
     * @return void
     */
    public function testAddsTheCropEditorState(): void
    {
        $data = $this->modifier->modifyData([5 => ['banner_id' => '5', 'slider_id' => '3'], '' => []]);

        $entries = $this->nested($data, 5, 'responsive_cropper', 'breakpoints');
        self::assertIsArray($entries);
        self::assertSame(
            [
                'breakpoint_id' => 1,
                'name' => 'Desktop',
                'identifier' => 'desktop',
                'media_query' => '(min-width: 1200px)',
                'min_width' => 1200,
                'target_width' => 1920,
                'target_height' => 600,
                'sort_order' => 0,
                'enabled' => false,
                'crop' => ['x' => 10, 'y' => 20, 'width' => 1920, 'height' => 600],
                'source_image' => null,
                'source_url' => null,
                'variants' => [[
                    'format' => 'webp',
                    'quality' => 85,
                    'url' => 'https://m.test/banner_slider/responsive/5/desktop_ab.webp',
                ]],
                'cropped_url' => 'https://m.test/banner_slider/responsive/5/desktop_ab.jpg',
            ],
            $entries[0]
        );
        self::assertIsArray($entries[1]);
        self::assertNull($entries[1]['crop']);
        self::assertTrue($entries[1]['enabled']);
        self::assertSame([], $entries[1]['variants']);
        self::assertSame(3, $this->nested($data, 5, 'responsive_cropper', 'slider_id'));
        self::assertSame(['responsive_cropper' => ['slider_id' => null, 'breakpoints' => []]], $data['']);
    }

    /**
     * The component gets the endpoints, formats, defaults and both size limits
     *
     * @return void
     */
    public function testAddsTheComponentConfig(): void
    {
        $meta = $this->modifier->modifyMeta([
            'image_settings' => ['children' => ['responsive_cropper_container' => ['arguments' => ['data' => [
                'config' => ['label' => 'Responsive Images'],
            ]]]]],
        ]);

        self::assertSame(
            [
                'label' => 'Responsive Images',
                'breakpointsUrl' => 'https://admin.test/banner_slider/responsivecrop/breakpoints',
                'imageUploadUrl' => 'https://admin.test/banner_slider/breakpoint/imageupload',
                'imageUploadField' => 'breakpoint_image',
                'formats' => [
                    ['code' => 'avif', 'mime' => 'image/avif', 'label' => 'AVIF', 'encodable' => false],
                    ['code' => 'webp', 'mime' => 'image/webp', 'label' => 'WEBP', 'encodable' => true],
                ],
                'defaultFormats' => ['webp'],
                'defaultQuality' => ['webp' => 85],
                'maxUploadBytes' => 10485760,
                'maxPostBytes' => 8388608,
            ],
            $this->nested(
                $meta,
                'image_settings',
                'children',
                'responsive_cropper_container',
                'arguments',
                'data',
                'config'
            )
        );
    }

    /**
     * A breakpoint double
     *
     * @param int $id
     * @param string $identifier
     * @param int $minWidth
     * @return BreakpointInterface
     */
    private function breakpoint(int $id, string $identifier, int $minWidth): BreakpointInterface
    {
        return $this->createConfiguredMock(BreakpointInterface::class, [
            'getBreakpointId' => $id,
            'getName' => ucfirst($identifier),
            'getIdentifier' => $identifier,
            'getMediaQuery' => $minWidth > 0 ? sprintf('(min-width: %dpx)', $minWidth) : '(max-width: 767px)',
            'getMinWidth' => $minWidth,
            'getTargetWidth' => $minWidth > 0 ? 1920 : 767,
            'getTargetHeight' => $minWidth > 0 ? 600 : null,
            'getSortOrder' => 0,
            'isEnabled' => true,
        ]);
    }
}
