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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\ImageMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\ImageFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageMapper::class)]
class ImageMapperTest extends TestCase
{
    /**
     * @var ImageMapper
     */
    private ImageMapper $mapper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static fn (string $path): string => 'https://m.test/' . $path);
        $registry = $this->createMock(ImageFormatRegistryInterface::class);
        $registry->method('getByExtension')->willReturnCallback(
            static fn (string $extension): ?ImageFormat => $extension === 'jpg'
                ? new ImageFormat('jpeg', 'image/jpeg', 'jpg')
                : null
        );
        $this->mapper = new ImageMapper(new MediaFieldCodec($resolver), $registry);
    }

    /**
     * A freshly uploaded image and the title are applied and exported as an uploader entry
     *
     * @return void
     */
    public function testAppliesAnUploadedImage(): void
    {
        $banner = new FakeBanner();
        $errors = new FieldErrors();

        $this->mapper->hydrate(new PostData([
            'image' => [['file' => 'banner_slider/image/ab12.jpg', 'name' => 'ab12.jpg']],
            'title' => 'Spring sale',
        ]), $banner, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(
            [
                'image' => [[
                    'file' => 'banner_slider/image/ab12.jpg',
                    'name' => 'ab12.jpg',
                    'url' => 'https://m.test/banner_slider/image/ab12.jpg',
                    'type' => 'image/jpeg',
                ]],
                'title' => 'Spring sale',
            ],
            $this->mapper->export($banner)
        );
        self::assertNull($banner->getImageDimensions());
    }

    /**
     * A legacy path outside the upload folder is kept when it is the stored value
     *
     * @return void
     */
    public function testKeepsTheStoredLegacyPath(): void
    {
        $banner = (new FakeBanner())->setImage('legacy_slider/image/old.png');
        $errors = new FieldErrors();

        $this->mapper->hydrate(
            new PostData(['image' => [['file' => 'legacy_slider/image/old.png']]]),
            $banner,
            $errors
        );

        self::assertFalse($errors->hasErrors());
        self::assertSame('legacy_slider/image/old.png', $banner->getImage());
        self::assertSame(
            [[
                'file' => 'legacy_slider/image/old.png',
                'name' => 'old.png',
                'url' => 'https://m.test/legacy_slider/image/old.png',
                'type' => 'image/*',
            ]],
            $this->mapper->export($banner)['image']
        );
    }

    /**
     * Any other media path is refused and the stored image is left as it is
     *
     * @return void
     */
    public function testRefusesAForeignPath(): void
    {
        $banner = (new FakeBanner())->setImage('banner_slider/image/current.jpg');
        $errors = new FieldErrors();

        $this->mapper->hydrate(
            new PostData(['image' => [['file' => 'downloadable/files/links/secret.jpg']]]),
            $banner,
            $errors
        );

        self::assertSame(
            ['Upload the banner image through the image field.'],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame('banner_slider/image/current.jpg', $banner->getImage());
    }

    /**
     * A file picked outside the uploader (no stored path) is a field error; the image and the title stay
     *
     * @return void
     */
    public function testRefusesAFileWithoutStoredPath(): void
    {
        $banner = (new FakeBanner())->setImage('banner_slider/image/current.jpg');
        $errors = new FieldErrors();

        $this->mapper->hydrate(
            new PostData([
                'image' => [['name' => 'gallery.jpg', 'url' => 'https://m.test/wysiwyg/gallery.jpg']],
                'title' => 'Kept',
            ]),
            $banner,
            $errors
        );

        self::assertSame(
            ['Choose the file for "Image" with the Upload button.'],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame('banner_slider/image/current.jpg', $banner->getImage());
        self::assertSame('Kept', $banner->getTitle());
    }

    /**
     * An empty uploader posts nothing, which removes the image
     *
     * @return void
     */
    public function testRemovesTheImageWhenTheUploaderIsEmpty(): void
    {
        $banner = (new FakeBanner())->setImage('banner_slider/image/current.jpg');

        $this->mapper->hydrate(new PostData(['title' => '']), $banner, new FieldErrors());

        self::assertNull($banner->getImage());
        self::assertNull($banner->getTitle());
        self::assertSame(['image' => [], 'title' => ''], $this->mapper->export($banner));
    }
}
