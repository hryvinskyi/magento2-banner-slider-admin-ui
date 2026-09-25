<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\ResponsiveItemsMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Hryvinskyi\BannerSliderApi\Api\Value\ResponsiveItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponsiveItemsMapper::class)]
class ResponsiveItemsMapperTest extends TestCase
{
    /**
     * With the marker, the posted rows are the whole list and export back with the marker
     *
     * @return void
     */
    public function testMapsRowsWithTheMarker(): void
    {
        $slider = new FakeSlider();
        $mapper = new ResponsiveItemsMapper();
        $errors = new FieldErrors();

        $mapper->hydrate(new PostData([
            'responsive_items_submitted' => '1',
            'responsive_items' => ['responsive_items_container' => [
                ['min_width' => '0', 'per_page' => '1', 'gap' => ''],
                ['min_width' => '768', 'per_page' => '3', 'gap' => '16px', 'record_id' => '1'],
            ]],
        ]), $slider, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertSame(
            [
                'responsive_items' => ['responsive_items_container' => [
                    ['min_width' => '0', 'per_page' => '1', 'gap' => ''],
                    ['min_width' => '768', 'per_page' => '3', 'gap' => '16px'],
                ]],
                'responsive_items_submitted' => '1',
            ],
            $mapper->export($slider)
        );
    }

    /**
     * With the marker and no rows key (an emptied list), the slider ends up without rules
     *
     * @return void
     */
    public function testEmptiesTheListWhenOnlyTheMarkerIsPosted(): void
    {
        $slider = (new FakeSlider())->setResponsiveItems([new ResponsiveItem(0, 2, null)]);

        (new ResponsiveItemsMapper())->hydrate(
            new PostData(['responsive_items_submitted' => '1']),
            $slider,
            new FieldErrors()
        );

        self::assertSame([], $slider->getResponsiveItems());
    }

    /**
     * Without the marker (a post from elsewhere), the rules are left unchanged
     *
     * @return void
     */
    public function testKeepsTheListWithoutTheMarker(): void
    {
        $items = [new ResponsiveItem(0, 2, null)];
        $slider = (new FakeSlider())->setResponsiveItems($items);

        (new ResponsiveItemsMapper())->hydrate(new PostData([]), $slider, new FieldErrors());

        self::assertSame($items, $slider->getResponsiveItems());
    }

    /**
     * An invalid row is reported by its number and nothing is changed
     *
     * @return void
     */
    public function testReportsAnInvalidRow(): void
    {
        $items = [new ResponsiveItem(0, 2, null)];
        $slider = (new FakeSlider())->setResponsiveItems($items);
        $errors = new FieldErrors();

        (new ResponsiveItemsMapper())->hydrate(new PostData([
            'responsive_items_submitted' => '1',
            'responsive_items' => ['responsive_items_container' => [
                ['min_width' => '0', 'per_page' => '1'],
                ['min_width' => '768', 'per_page' => '12', 'gap' => 'wide'],
            ]],
        ]), $slider, $errors);

        self::assertSame(
            ['Slides per page row 2 is invalid: check min width, slides (1-10) and gap (e.g. 16px).'],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame($items, $slider->getResponsiveItems());
    }
}
