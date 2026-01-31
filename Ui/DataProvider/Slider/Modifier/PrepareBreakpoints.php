<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider\Modifier;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;

/**
 * Prepares breakpoints data for slider form
 */
class PrepareBreakpoints implements PrepareDataInterface
{
    /**
     * @param BreakpointRepositoryInterface $breakpointRepository
     */
    public function __construct(
        private readonly BreakpointRepositoryInterface $breakpointRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function execute(array &$data): void
    {
        if (!isset($data[SliderInterface::SLIDER_ID])) {
            return;
        }

        $sliderId = (int)$data[SliderInterface::SLIDER_ID];
        $data['breakpoints']['breakpoints_container'] = $this->getBreakpointsData($sliderId);
    }

    /**
     * Get breakpoints data for slider
     *
     * @param int $sliderId
     * @return array<int, array<string, mixed>>
     */
    private function getBreakpointsData(int $sliderId): array
    {
        $breakpoints = $this->breakpointRepository->getBySliderId($sliderId);
        $result = [];

        foreach ($breakpoints as $breakpoint) {
            $result[] = $breakpoint->getData();
        }

        return $result;
    }
}
