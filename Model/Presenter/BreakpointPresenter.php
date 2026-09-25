<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Presenter;

use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;

/**
 * The one admin view of a breakpoint, shared by the breakpoints JSON endpoint, the crop editor data and the
 * breakpoint rows of the slider form, so the three can never drift apart.
 */
class BreakpointPresenter
{
    public const ENABLED = 'enabled';

    /**
     * Typed view for JSON consumers
     *
     * @param BreakpointInterface $breakpoint
     * @return array{breakpoint_id: int|null, name: string, identifier: string, media_query: string,
     *     min_width: int, target_width: int, target_height: int|null, sort_order: int, enabled: bool}
     */
    public function present(BreakpointInterface $breakpoint): array
    {
        return [
            BreakpointInterface::BREAKPOINT_ID => $breakpoint->getBreakpointId(),
            BreakpointInterface::NAME => $breakpoint->getName(),
            BreakpointInterface::IDENTIFIER => $breakpoint->getIdentifier(),
            BreakpointInterface::MEDIA_QUERY => $breakpoint->getMediaQuery(),
            BreakpointInterface::MIN_WIDTH => $breakpoint->getMinWidth(),
            BreakpointInterface::TARGET_WIDTH => $breakpoint->getTargetWidth(),
            BreakpointInterface::TARGET_HEIGHT => $breakpoint->getTargetHeight(),
            BreakpointInterface::SORT_ORDER => $breakpoint->getSortOrder(),
            self::ENABLED => $breakpoint->isEnabled(),
        ];
    }

    /**
     * The same view as form field values: text, an empty string for "none", `1`/`0` for the enabled toggle
     *
     * @param BreakpointInterface $breakpoint
     * @return array<string, string>
     */
    public function presentFormRow(BreakpointInterface $breakpoint): array
    {
        $row = [];
        foreach ($this->present($breakpoint) as $field => $value) {
            $row[$field] = match (true) {
                is_bool($value) => $value ? '1' : '0',
                $value === null => '',
                default => (string)$value,
            };
        }

        return $row;
    }
}
