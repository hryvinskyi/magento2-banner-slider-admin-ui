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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\ContentMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\LinkMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkMapper::class)]
#[CoversClass(ContentMapper::class)]
class LinkAndContentMapperTest extends TestCase
{
    /**
     * Link and content reach the banner and export back
     *
     * @return void
     */
    public function testMapsBothWays(): void
    {
        $banner = new FakeBanner();
        $errors = new FieldErrors();
        $link = new LinkMapper();
        $content = new ContentMapper();
        $post = new PostData([
            'link_url' => '/sale.html',
            'open_in_new_tab' => '1',
            'content' => '<p>{{widget type="x"}}</p>',
        ]);

        $link->hydrate($post, $banner, $errors);
        $content->hydrate($post, $banner, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(['link_url' => '/sale.html', 'open_in_new_tab' => '1'], $link->export($banner));
        self::assertSame(['content' => '<p>{{widget type="x"}}</p>'], $content->export($banner));
    }

    /**
     * A refused link scheme is a field error; blank content is no content
     *
     * @return void
     */
    public function testRefusesUnsafeLinksAndClearsBlankContent(): void
    {
        $banner = (new FakeBanner())->setContent('<p>old</p>');
        $errors = new FieldErrors();

        (new LinkMapper())->hydrate(new PostData(['link_url' => 'javascript:alert(1)']), $banner, $errors);
        (new ContentMapper())->hydrate(new PostData(['content' => "  \n"]), $banner, $errors);

        self::assertSame(
            ['Enter a link URL that starts with http:, https:, mailto: or tel:, or a relative URL.'],
            array_map('strval', $errors->getMessages())
        );
        self::assertNull($banner->getLinkUrl());
        self::assertNull($banner->getContent());
    }
}
