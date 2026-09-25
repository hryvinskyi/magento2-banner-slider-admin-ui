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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\GeneralMapper as BannerGeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\ImageMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\LinkMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\CustomCssMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\GeneralMapper as SliderGeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\ResponsiveItemsMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\ResponsiveItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BannerFormHydrator::class)]
#[CoversClass(SliderFormHydrator::class)]
class FormHydratorsTest extends TestCase
{
    /**
     * Every mapper of the pool runs, every field error of every mapper is collected, exports are merged
     *
     * @return void
     */
    public function testBannerHydratorRunsTheWholePool(): void
    {
        $hydrator = new BannerFormHydrator(['general' => new BannerGeneralMapper(), 'link' => new LinkMapper()]);
        $banner = new FakeBanner();
        $errors = new FieldErrors();

        $hydrator->hydrate(
            new PostData(['name' => '', 'position' => '2', 'link_url' => 'vbscript:x', 'open_in_new_tab' => '1']),
            $banner,
            $errors
        );

        self::assertSame(
            [
                'Enter a valid value for "Name".',
                'Enter a link URL that starts with http:, https:, mailto: or tel:, or a relative URL.',
            ],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame(2, $banner->getPosition());
        self::assertTrue($banner->isOpenInNewTab());
        $export = $hydrator->export($banner);
        self::assertArrayHasKey('position', $export);
        self::assertArrayHasKey('link_url', $export);
    }

    /**
     * The slider hydrator collects errors across mappers the same way
     *
     * @return void
     */
    public function testSliderHydratorRunsTheWholePool(): void
    {
        $hydrator = new SliderFormHydrator(['general' => new SliderGeneralMapper(), 'css' => new CustomCssMapper()]);
        $errors = new FieldErrors();
        $slider = new FakeSlider();

        $hydrator->hydrate(
            new PostData(['name' => 'Home', 'location' => 'bad location', 'custom_css' => '<b>']),
            $slider,
            $errors
        );

        self::assertCount(2, $errors->getMessages());
        self::assertSame('Home', $slider->getName());
        self::assertSame('Home', $hydrator->export($slider)['name']);
    }

    /**
     * A refused banner post is shown through the mappers: a changed slider and a removed image come back as posted,
     * and a refused value is shown as it was entered
     *
     * @return void
     */
    public function testRestoresARefusedBannerPost(): void
    {
        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static fn (string $path): string => 'https://m.test/' . $path);
        $hydrator = new BannerFormHydrator([
            'general' => new BannerGeneralMapper(),
            'image' => new ImageMapper(
                new MediaFieldCodec($resolver),
                $this->createMock(ImageFormatRegistryInterface::class)
            ),
        ]);
        $banner = (new FakeBanner())->setName('Stored')->setSliderId(2)->setImage('banner_slider/image/a.jpg');

        $values = $hydrator->restore(new PostData(['name' => '', 'slider_id' => '5', 'title' => 'Alt']), $banner);

        self::assertSame('5', $values['slider_id'] ?? null);
        self::assertSame([], $values['image'] ?? null, 'An image removed in the post stays removed.');
        self::assertSame('', $values['name'] ?? null, 'A refused value is shown as it was entered.');
        self::assertSame('Alt', $values['title'] ?? null);
    }

    /**
     * A list emptied under its marker stays empty; a mapper that cannot read the post leaves its fields as stored
     *
     * @return void
     */
    public function testRestoresARefusedSliderPost(): void
    {
        $hydrator = new SliderFormHydrator([
            'general' => new SliderGeneralMapper(),
            'responsive_items' => new ResponsiveItemsMapper(),
        ]);
        $stored = static fn (): SliderInterface => (new FakeSlider())->setName('Stored')
            ->setResponsiveItems([new ResponsiveItem(0, 2, null)]);

        $emptied = $hydrator->restore(
            new PostData(['name' => 'Typed', 'responsive_items_submitted' => '1']),
            $stored()
        );
        $unreadable = $hydrator->restore(new PostData(['responsive_items_submitted' => 'maybe']), $stored());

        self::assertSame('Typed', $emptied['name'] ?? null);
        self::assertSame(['responsive_items_container' => []], $emptied['responsive_items'] ?? null);
        self::assertSame(
            ['responsive_items_container' => [['min_width' => '0', 'per_page' => '2', 'gap' => '']]],
            $unreadable['responsive_items'] ?? null
        );
        self::assertSame('Stored', $unreadable['name'] ?? null);
    }

    /**
     * A pool entry that is not a mapper fails loudly at construction
     *
     * @return void
     */
    public function testRefusesAForeignPoolEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BannerFormHydrator(['general' => new \stdClass()]);
    }

    /**
     * The slider pool is checked the same way
     *
     * @return void
     */
    public function testRefusesAForeignSliderPoolEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SliderFormHydrator(['general' => new LinkMapper()]);
    }
}
