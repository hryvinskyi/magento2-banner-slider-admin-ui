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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BreakpointInputMapper;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BreakpointInputMapper::class)]
class BreakpointInputMapperTest extends TestCase
{
    /**
     * @var BreakpointRepositoryInterface&MockObject
     */
    private MockObject $repository;

    /**
     * @var BreakpointInputMapper
     */
    private BreakpointInputMapper $mapper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $stored = $this->createConfiguredMock(BreakpointInterface::class, [
            'getBreakpointId' => 7,
            'getName' => 'Desktop',
            'getIdentifier' => 'desktop',
            'getMediaQuery' => '(min-width: 1200px)',
            'getMinWidth' => 1200,
            'getTargetWidth' => 1920,
            'getTargetHeight' => null,
            'getSortOrder' => 10,
            'isEnabled' => true,
        ]);
        $this->repository = $this->createMock(BreakpointRepositoryInterface::class);
        $this->repository->method('getBySliderId')->willReturn([$stored]);
        $this->mapper = new BreakpointInputMapper($this->repository);
    }

    /**
     * With the marker, the posted rows are the desired set; deleted rows and helper keys are ignored
     *
     * @return void
     */
    public function testMapsPostedRows(): void
    {
        $errors = new FieldErrors();

        $inputs = $this->mapper->map(new PostData([
            'breakpoints_submitted' => '1',
            'breakpoints' => ['breakpoints_container' => [
                [
                    'breakpoint_id' => '7',
                    'name' => 'Desktop',
                    'identifier' => 'desktop',
                    'media_query' => '(min-width: 1200px)',
                    'min_width' => '1200',
                    'target_width' => '1920',
                    'target_height' => '',
                    'sort_order' => '10',
                    'enabled' => '1',
                    'record_id' => '0',
                ],
                [
                    'breakpoint_id' => '',
                    'name' => 'Phone',
                    'identifier' => 'phone',
                    'media_query' => '(max-width: 767px)',
                    'min_width' => '',
                    'target_width' => '767',
                    'target_height' => '500',
                    'enabled' => '0',
                ],
                ['name' => 'Gone', 'identifier' => 'gone', 'is_delete' => 'true'],
            ]],
        ]), 3, $errors);

        self::assertFalse($errors->hasErrors());
        self::assertCount(2, $inputs);
        self::assertSame(7, $inputs[0]->getBreakpointId());
        self::assertNull($inputs[0]->getTargetHeight());
        self::assertNull($inputs[1]->getBreakpointId());
        self::assertSame(0, $inputs[1]->getMinWidth());
        self::assertSame(500, $inputs[1]->getTargetHeight());
        self::assertFalse($inputs[1]->isEnabled());
    }

    /**
     * Marker posted and the rows key absent (an emptied list) means an empty set
     *
     * @return void
     */
    public function testMarkerWithoutRowsIsAnEmptySet(): void
    {
        $this->repository->expects(self::never())->method('getBySliderId');

        self::assertSame([], $this->mapper->map(new PostData(['breakpoints_submitted' => '1']), 3, new FieldErrors()));
    }

    /**
     * Without the marker the current set is kept; a new slider keeps nothing (and gets the defaults)
     *
     * @return void
     */
    public function testWithoutTheMarkerTheCurrentSetIsKept(): void
    {
        $inputs = $this->mapper->map(new PostData([]), 3, new FieldErrors());

        self::assertCount(1, $inputs);
        self::assertSame('desktop', $inputs[0]->getIdentifier());
        self::assertSame(7, $inputs[0]->getBreakpointId());
        self::assertSame([], $this->mapper->map(new PostData([]), null, new FieldErrors()));
    }

    /**
     * An invalid identifier, a non-numeric size and a missing media query are each reported for their row
     *
     * @return void
     */
    public function testReportsInvalidRows(): void
    {
        $errors = new FieldErrors();

        $inputs = $this->mapper->map(new PostData([
            'breakpoints_submitted' => '1',
            'breakpoints' => ['breakpoints_container' => [
                ['name' => 'Wide', 'identifier' => 'Wide Screen', 'media_query' => 'x', 'target_width' => '1'],
                ['name' => 'Tablet', 'identifier' => 'tablet', 'media_query' => 'x', 'target_width' => '10px'],
                ['identifier' => 'phone', 'media_query' => '', 'target_width' => '767'],
            ]],
        ]), 3, $errors);

        self::assertSame([], $inputs);
        self::assertSame(
            [
                'Breakpoint "Wide": the identifier may use only a-z, 0-9, "_" and "-", up to 50 characters.',
                'Breakpoint "Tablet": enter a whole number for "Target Width".',
                'Breakpoint "row 3": enter a name, a media query and sizes greater than 0.',
            ],
            array_map('strval', $errors->getMessages())
        );
    }
}
