<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Presenter;

use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BreakpointPresenter::class)]
class BreakpointPresenterTest extends TestCase
{
    /**
     * The JSON view is typed; the form row view is text with an empty "auto" height and a 1/0 toggle
     *
     * @return void
     */
    public function testPresentsBothViews(): void
    {
        $breakpoint = $this->createConfiguredMock(BreakpointInterface::class, [
            'getBreakpointId' => 5,
            'getName' => 'Tablet',
            'getIdentifier' => 'tablet',
            'getMediaQuery' => '(min-width: 768px)',
            'getMinWidth' => 768,
            'getTargetWidth' => 992,
            'getTargetHeight' => null,
            'getSortOrder' => 30,
            'isEnabled' => false,
        ]);
        $presenter = new BreakpointPresenter();

        self::assertSame(
            [
                'breakpoint_id' => 5,
                'name' => 'Tablet',
                'identifier' => 'tablet',
                'media_query' => '(min-width: 768px)',
                'min_width' => 768,
                'target_width' => 992,
                'target_height' => null,
                'sort_order' => 30,
                'enabled' => false,
            ],
            $presenter->present($breakpoint)
        );
        self::assertSame(
            [
                'breakpoint_id' => '5',
                'name' => 'Tablet',
                'identifier' => 'tablet',
                'media_query' => '(min-width: 768px)',
                'min_width' => '768',
                'target_width' => '992',
                'target_height' => '',
                'sort_order' => '30',
                'enabled' => '0',
            ],
            $presenter->presentFormRow($breakpoint)
        );
    }
}
