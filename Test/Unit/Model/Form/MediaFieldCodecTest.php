<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaFieldCodec::class)]
class MediaFieldCodecTest extends TestCase
{
    /**
     * @var MediaFieldCodec
     */
    private MediaFieldCodec $codec;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static function (string $path): string {
            if (str_starts_with($path, 'bad/')) {
                throw new \InvalidArgumentException('Unsafe path.');
            }

            return 'https://shop.test/media/' . $path;
        });
        $this->codec = new MediaFieldCodec($resolver);
    }

    /**
     * The path is the `file` key of the first uploader entry
     *
     * @return void
     */
    public function testReadsThePostedPath(): void
    {
        $post = new PostData(['image' => [['file' => 'banner_slider/image/a.jpg', 'name' => 'a.jpg']]]);

        self::assertSame('banner_slider/image/a.jpg', $this->codec->readPath($post, 'image'));
        self::assertNull($this->codec->readPath(new PostData([]), 'image'));
    }

    /**
     * An entry without a stored path (a file the uploader did not upload itself) is refused, never read as "empty"
     *
     * @param array<string, mixed> $entry
     * @return void
     */
    #[TestWith([['name' => 'x.jpg', 'url' => 'https://m.test/wysiwyg/x.jpg', 'type' => 'image/jpeg']])]
    #[TestWith([['file' => '', 'name' => 'x.jpg']])]
    #[TestWith([['file' => ['nested'], 'name' => 'x.jpg']])]
    public function testRefusesAnEntryWithoutStoredPath(array $entry): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->codec->readPath(new PostData(['image' => [$entry]]), 'image');
    }

    /**
     * Only the stored value (wherever it lives) or a clean path in the upload folder is accepted
     *
     * @param string $path
     * @param string|null $stored
     * @param bool $accepted
     * @return void
     */
    #[TestWith(['banner_slider/image/new.jpg', null, true])]
    #[TestWith(['legacy_slider/image/legacy.jpg', 'legacy_slider/image/legacy.jpg', true])]
    #[TestWith(['legacy_slider/image/other.jpg', 'legacy_slider/image/legacy.jpg', false])]
    #[TestWith(['downloadable/files/secret.pdf', null, false])]
    #[TestWith(['banner_slider/image/../../customer/x.jpg', null, false])]
    #[TestWith(['banner_slider/image/', null, false])]
    #[TestWith(['/banner_slider/image/a.jpg', null, false])]
    #[TestWith(['banner_slider/image/a\\b.jpg', null, false])]
    #[TestWith(['banner_slider/imagex/a.jpg', null, false])]
    public function testAcceptsOnlyStoredOrUploadedPaths(string $path, ?string $stored, bool $accepted): void
    {
        self::assertSame($accepted, $this->codec->isAcceptable($path, $stored, 'banner_slider/image'));
    }

    /**
     * A stored path exports as one uploader entry; an unresolvable one keeps an empty URL
     *
     * @return void
     */
    public function testExportsUploaderValue(): void
    {
        self::assertSame(
            [[
                'file' => 'banner_slider/image/a.jpg',
                'name' => 'a.jpg',
                'url' => 'https://shop.test/media/banner_slider/image/a.jpg',
                'type' => 'image/jpeg',
            ]],
            $this->codec->export('banner_slider/image/a.jpg', 'image/jpeg')
        );
        self::assertSame('', $this->codec->export('bad/x.jpg', 'image/jpeg')[0]['url']);
        self::assertSame([], $this->codec->export(null, 'image/jpeg'));
        self::assertSame('plain.jpg', $this->codec->fileName('plain.jpg'));
    }
}
