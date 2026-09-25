<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider\Modifier;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\BreakpointInputMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Adds the slider's breakpoints to the form as dynamic rows (`breakpoints[breakpoints_container]`), and the hidden
 * `breakpoints_submitted` marker that tells the save that the rows are the complete set.
 *
 * A record that already carries the marker shows a refused post again: its rows are the ones the admin submitted,
 * and no rows under the marker is an emptied list, so the stored breakpoints are not put back.
 */
class BreakpointRowsModifier implements ModifierInterface
{
    /**
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param BreakpointPresenter $presenter
     * @param EntityIdReader $idReader
     */
    public function __construct(
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly BreakpointPresenter $presenter,
        private readonly EntityIdReader $idReader
    ) {
    }

    /**
     * Add the breakpoint rows and the marker to every record
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyData(array $data): array
    {
        foreach ($data as $key => $record) {
            if (!is_array($record)) {
                continue;
            }
            $data[$key] = array_key_exists(BreakpointInputMapper::MARKER, $record)
                ? $this->withSubmittedRows($record)
                : $this->withStoredRows($record);
        }

        return $data;
    }

    /**
     * A record with the rows of its slider's stored breakpoints and the marker
     *
     * @param array<mixed> $record
     * @return array<mixed>
     */
    private function withStoredRows(array $record): array
    {
        $record[BreakpointInputMapper::MARKER] = '1';
        $sliderId = $this->idReader->parse($record[SliderInterface::SLIDER_ID] ?? null);
        if ($sliderId !== null) {
            $rows = [];
            foreach ($this->breakpointRepository->getBySliderId($sliderId) as $breakpoint) {
                $rows[] = $this->presenter->presentFormRow($breakpoint);
            }
            $record[BreakpointInputMapper::SECTION] = [BreakpointInputMapper::ROWS => $rows];
        }

        return $record;
    }

    /**
     * A record that shows a refused post: the submitted rows in posted order, none when the admin emptied the list
     *
     * @param array<mixed> $record
     * @return array<mixed>
     */
    private function withSubmittedRows(array $record): array
    {
        $section = $record[BreakpointInputMapper::SECTION] ?? null;
        $rows = is_array($section) ? $section[BreakpointInputMapper::ROWS] ?? null : null;
        $record[BreakpointInputMapper::MARKER] = '1';
        $record[BreakpointInputMapper::SECTION] = [
            BreakpointInputMapper::ROWS => is_array($rows) ? array_values($rows) : [],
        ];

        return $record;
    }

    /**
     * The meta is left as it is
     *
     * @param array<mixed> $meta
     * @return array<mixed>
     */
    public function modifyMeta(array $meta): array
    {
        return $meta;
    }
}
