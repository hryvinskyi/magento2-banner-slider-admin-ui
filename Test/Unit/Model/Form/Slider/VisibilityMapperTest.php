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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\VisibilityMapper;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Hryvinskyi\BannerSliderApi\Api\Value\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VisibilityMapper::class)]
class VisibilityMapperTest extends TestCase
{
    /**
     * "ALL GROUPS" means every group, whatever else is selected, and exports as that option alone
     *
     * @return void
     */
    public function testMapsAllGroups(): void
    {
        $slider = new FakeSlider();
        $mapper = new VisibilityMapper();

        $mapper->hydrate(
            new PostData(['store_ids' => ['0'], 'customer_group_ids' => ['1', '32000', '2']]),
            $slider,
            new FieldErrors()
        );

        self::assertTrue($slider->getVisibility()->isForAllCustomerGroups());
        self::assertSame([], $slider->getVisibility()->getCustomerGroupIds());
        self::assertSame(['store_ids' => ['0'], 'customer_group_ids' => ['32000']], $mapper->export($slider));
    }

    /**
     * Listed groups restrict the slider and export as themselves
     *
     * @return void
     */
    public function testMapsRestrictedGroups(): void
    {
        $slider = new FakeSlider();
        $mapper = new VisibilityMapper();

        $mapper->hydrate(
            new PostData(['store_ids' => ['2', '1'], 'customer_group_ids' => ['3', '1']]),
            $slider,
            new FieldErrors()
        );

        self::assertFalse($slider->getVisibility()->isForAllCustomerGroups());
        self::assertSame(['store_ids' => ['1', '2'], 'customer_group_ids' => ['1', '3']], $mapper->export($slider));
    }

    /**
     * A slider visible nowhere and to nobody exports nothing selected, so the required fields make the admin choose
     *
     * @return void
     */
    public function testExportsNothingForASliderVisibleNowhere(): void
    {
        $slider = (new FakeSlider())->setVisibility(new Visibility([], [], false));

        self::assertSame(
            ['store_ids' => [], 'customer_group_ids' => []],
            (new VisibilityMapper())->export($slider)
        );
    }

    /**
     * A missing field keeps its current value; a malformed id is a field error that changes nothing
     *
     * @return void
     */
    public function testKeepsAndGuardsValues(): void
    {
        $slider = (new FakeSlider())->setVisibility(new Visibility([1], [], true));
        $mapper = new VisibilityMapper();
        $errors = new FieldErrors();

        $mapper->hydrate(new PostData(['store_ids' => ['0', '3']]), $slider, $errors);
        self::assertSame([0, 3], $slider->getVisibility()->getStoreIds());
        self::assertTrue($slider->getVisibility()->isForAllCustomerGroups());

        $mapper->hydrate(new PostData(['customer_group_ids' => ['-1']]), $slider, $errors);
        self::assertSame(['Select valid customer groups.'], array_map('strval', $errors->getMessages()));
        self::assertTrue($slider->getVisibility()->isForAllCustomerGroups());
    }
}
