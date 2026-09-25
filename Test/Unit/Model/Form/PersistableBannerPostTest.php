<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\PersistableBannerPost;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersistableBannerPost::class)]
class PersistableBannerPostTest extends TestCase
{
    /**
     * The encoded images leave the post; crop areas, formats and every other field stay
     *
     * @return void
     */
    public function testDropsOnlyTheEncodedImages(): void
    {
        $post = [
            'name' => 'Spring',
            'responsive_crops' => (string)json_encode([[
                'breakpoint_id' => 4,
                'crop' => ['x' => 1, 'y' => 2, 'width' => 3, 'height' => 4],
                'formats' => [['format' => 'webp', 'quality' => 85]],
                'encoded' => [['format' => 'webp', 'data' => str_repeat('QUFB', 1000)]],
            ]]),
        ];
        $persistable = new PersistableBannerPost(new Json());

        $reduced = $persistable->reduce($post);

        self::assertTrue($persistable->hasEncodedImages($post));
        self::assertFalse($persistable->hasEncodedImages($reduced));
        self::assertSame('Spring', $reduced['name']);
        self::assertSame(
            [[
                'breakpoint_id' => 4,
                'crop' => ['x' => 1, 'y' => 2, 'width' => 3, 'height' => 4],
                'formats' => [['format' => 'webp', 'quality' => 85]],
            ]],
            json_decode(is_string($reduced['responsive_crops']) ? $reduced['responsive_crops'] : '', true)
        );
        self::assertStringNotContainsString('QUFB', (string)json_encode($reduced));
    }

    /**
     * The crop editor state is dropped, so the form rebuilds it for the slider of the post
     *
     * @return void
     */
    public function testDropsTheCropEditorState(): void
    {
        $reduced = (new PersistableBannerPost(new Json()))->reduce([
            'slider_id' => '8',
            'responsive_cropper' => ['slider_id' => 2, 'breakpoints' => [['breakpoint_id' => 1]]],
        ]);

        self::assertSame(['slider_id' => '8'], $reduced);
    }

    /**
     * Unreadable crop data is dropped entirely; a post without crops is unchanged
     *
     * @return void
     */
    public function testHandlesMissingOrBrokenCrops(): void
    {
        $persistable = new PersistableBannerPost(new Json());

        self::assertSame(['name' => 'A'], $persistable->reduce(['name' => 'A', 'responsive_crops' => '{broken']));
        self::assertSame(['name' => 'A'], $persistable->reduce(['name' => 'A']));
        self::assertFalse($persistable->hasEncodedImages(['name' => 'A']));
    }
}
