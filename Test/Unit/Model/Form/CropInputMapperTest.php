<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\CropInputMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\StrictBase64Decoder;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\EncodedImage;
use Hryvinskyi\BannerSliderApi\Api\Value\FormatRequest;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CropInputMapper::class)]
class CropInputMapperTest extends TestCase
{
    private const MAX_BYTES = 64;

    /**
     * Log lines written while mapping
     *
     * @var list<string>
     */
    private array $log = [];

    /**
     * @var CropInputMapper
     */
    private CropInputMapper $mapper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $registry = $this->createMock(ImageFormatRegistryInterface::class);
        $registry->method('has')->willReturnCallback(
            static fn (string $code): bool => in_array($code, ['jpeg', 'png', 'webp', 'avif'], true)
        );
        $config = $this->createMock(ImageConfigInterface::class);
        $config->method('getMaxUploadBytes')->willReturn(self::MAX_BYTES);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function (string|\Stringable $message): void {
            $this->log[] = (string)$message;
        });
        $this->mapper = new CropInputMapper(new Json(), new StrictBase64Decoder(), $registry, $config, $logger);
    }

    /**
     * A full entry becomes a crop input with its rect, formats and decoded images
     *
     * @return void
     */
    public function testMapsAnEntry(): void
    {
        $errors = new FieldErrors();

        $inputs = $this->mapper->map($this->post([[
            'breakpoint_id' => 4,
            'remove' => false,
            'enabled' => true,
            'source_image' => 'banner_slider/image/tablet.jpg',
            'crop' => ['x' => 10, 'y' => 0, 'width' => 800, 'height' => 400],
            'formats' => [['format' => 'webp', 'quality' => 85], ['format' => 'avif', 'quality' => '80']],
            'encoded' => [['format' => 'webp', 'data' => base64_encode('RIFF-webp-bytes')]],
        ]]), $errors);

        self::assertFalse($errors->hasErrors());
        self::assertCount(1, $inputs);
        $input = $inputs[0];
        self::assertSame(4, $input->getBreakpointId());
        self::assertSame('banner_slider/image/tablet.jpg', $input->getSourceImage());
        self::assertSame([10, 0, 800, 400], [
            $input->getRect()?->getX(),
            $input->getRect()?->getY(),
            $input->getRect()?->getWidth(),
            $input->getRect()?->getHeight(),
        ]);
        self::assertSame(
            ['webp:85', 'avif:80'],
            array_map(
                static fn (FormatRequest $request): string => $request->getFormatCode() . ':' . $request->getQuality(),
                $input->getFormats()
            )
        );
        self::assertSame(
            ['webp:RIFF-webp-bytes'],
            array_map(
                static fn (EncodedImage $image): string => $image->getFormatCode() . ':' . $image->getBytes(),
                $input->getEncodedImages()
            )
        );
    }

    /**
     * A removed crop needs no rect; a missing key changes no crop
     *
     * @return void
     */
    public function testMapsARemovalAndNothing(): void
    {
        $inputs = $this->mapper->map($this->post([['breakpoint_id' => 2, 'remove' => true]]), new FieldErrors());

        self::assertCount(1, $inputs);
        self::assertTrue($inputs[0]->shouldRemove());
        self::assertNull($inputs[0]->getRect());
        self::assertSame([], $this->mapper->map(new PostData([]), new FieldErrors()));
    }

    /**
     * Browser-encoded images that are not strict base64, too large or of an unknown format are dropped and logged,
     * so the server encodes that format instead; the crop itself is kept
     *
     * @param string $format
     * @param string $data
     * @return void
     */
    #[TestWith(['webp', 'data:image/webp;base64,UklGRg=='])]
    #[TestWith(['webp', 'not base64 at all!'])]
    #[TestWith(['bmp', 'QUFBQQ=='])]
    public function testDropsUnusableEncodedImages(string $format, string $data): void
    {
        $errors = new FieldErrors();

        $inputs = $this->mapper->map($this->post([[
            'breakpoint_id' => 4,
            'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10],
            'encoded' => [['format' => $format, 'data' => $data]],
        ]]), $errors);

        self::assertFalse($errors->hasErrors());
        self::assertCount(1, $inputs);
        self::assertSame([], $inputs[0]->getEncodedImages());
        self::assertCount(1, $this->log);
    }

    /**
     * An image above the upload limit is dropped before it is decoded
     *
     * @return void
     */
    public function testDropsAnImageAboveTheLimit(): void
    {
        $inputs = $this->mapper->map($this->post([[
            'breakpoint_id' => 4,
            'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10],
            'encoded' => [['format' => 'png', 'data' => base64_encode(str_repeat('x', self::MAX_BYTES + 1))]],
        ]]), new FieldErrors());

        self::assertSame([], $inputs[0]->getEncodedImages());
        self::assertStringContainsString('larger than the image upload limit', $this->log[0]);
    }

    /**
     * Missing or non-integer rects, unknown formats, bad qualities and unreadable data are field errors
     *
     * @param string $json
     * @param string $message
     * @return void
     */
    #[TestWith(['[{"breakpoint_id":4}]', 'Select a crop area for breakpoint 4.'])]
    #[TestWith([
        '[{"breakpoint_id":4,"crop":{"x":0.5,"y":0,"width":10,"height":10}}]',
        'The crop area for breakpoint 4 must be given in whole pixels.',
    ])]
    #[TestWith([
        '[{"breakpoint_id":4,"crop":{"x":-1,"y":0,"width":10,"height":10}}]',
        'The crop area for breakpoint 4 is outside the image.',
    ])]
    #[TestWith([
        '[{"breakpoint_id":4,"crop":{"x":0,"y":0,"width":1,"height":1},"formats":[{"format":"exe","quality":80}]}]',
        'Breakpoint 4 asks for the unknown image format "exe".',
    ])]
    #[TestWith([
        '[{"breakpoint_id":4,"crop":{"x":0,"y":0,"width":1,"height":1},"formats":[{"format":"webp","quality":101}]}]',
        'The WEBP quality of breakpoint 4 must be a whole number from 1 to 100.',
    ])]
    #[TestWith(['{not json', 'The responsive image data could not be read. Crop the images again and save.'])]
    #[TestWith(['{"a":1}', 'The responsive image data could not be read. Crop the images again and save.'])]
    #[TestWith([
        '[{"breakpoint_id":0}]',
        'The responsive image data could not be read. Crop the images again and save.',
    ])]
    public function testReportsInvalidEntries(string $json, string $message): void
    {
        $errors = new FieldErrors();

        $inputs = $this->mapper->map(new PostData(['responsive_crops' => $json]), $errors);

        self::assertSame([], $inputs);
        self::assertSame([$message], array_map('strval', $errors->getMessages()));
    }

    /**
     * The form post carrying the given entries
     *
     * @param list<array<string, mixed>> $entries
     * @return PostData
     */
    private function post(array $entries): PostData
    {
        return new PostData(['responsive_crops' => (string)json_encode($entries)]);
    }
}
