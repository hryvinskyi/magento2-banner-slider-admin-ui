<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\BreakpointInput;
use Magento\Framework\Phrase;

/**
 * The slider form's breakpoint rows as the desired breakpoint set for the slider editor.
 *
 * The rows are posted at `breakpoints[breakpoints_container]`. Dynamic rows leave deleted rows out of the post and
 * post no key at all for an emptied list, so the form carries a hidden `breakpoints_submitted` field:
 * - posted: the posted rows are the whole desired set (no rows = no breakpoints; a new slider then gets the default
 *   breakpoints);
 * - missing (a post from elsewhere): the slider's current breakpoints are kept unchanged.
 */
class BreakpointInputMapper
{
    public const SECTION = 'breakpoints';
    public const ROWS = 'breakpoints_container';
    public const MARKER = 'breakpoints_submitted';
    private const DELETE_FLAG = 'is_delete';

    /**
     * @param BreakpointRepositoryInterface $breakpointRepository
     */
    public function __construct(
        private readonly BreakpointRepositoryInterface $breakpointRepository
    ) {
    }

    /**
     * The desired breakpoint set of the slider
     *
     * @param PostData $post
     * @param int|null $sliderId The slider being saved, or null for a new one
     * @param FieldErrors $errors Receives a message for every invalid row
     * @return list<BreakpointInput>
     */
    public function map(PostData $post, ?int $sliderId, FieldErrors $errors): array
    {
        if (!$post->flag(self::MARKER, false)) {
            return $this->currentSet($sliderId);
        }

        $inputs = [];
        foreach ($post->section(self::SECTION)->rows(self::ROWS) as $index => $row) {
            if ($row->flag(self::DELETE_FLAG, false)) {
                continue;
            }
            $input = $this->mapRow($row, $index + 1, $errors);
            if ($input !== null) {
                $inputs[] = $input;
            }
        }

        return $inputs;
    }

    /**
     * One row as a breakpoint input, or null (with the errors recorded) when a value is invalid
     *
     * @param PostData $row
     * @param int $position 1-based row number, used to name a row without name
     * @param FieldErrors $errors
     * @return BreakpointInput|null
     */
    private function mapRow(PostData $row, int $position, FieldErrors $errors): ?BreakpointInput
    {
        $label = $row->text(BreakpointInterface::NAME) ?? (string)__('row %1', $position);
        $identifier = $row->text(BreakpointInterface::IDENTIFIER) ?? '';
        if (preg_match(BreakpointInput::IDENTIFIER_PATTERN, $identifier) !== 1) {
            $errors->add(
                __(
                    'Breakpoint "%1": the identifier may use only a-z, 0-9, "_" and "-", up to 50 characters.',
                    $label
                ),
                sprintf('Refused breakpoint identifier "%s".', $identifier)
            );

            return null;
        }

        $numbers = [];
        foreach ($this->numberFields() as $field => $fieldLabel) {
            try {
                $numbers[$field] = $row->integer($field);
            } catch (\InvalidArgumentException $exception) {
                $errors->add(
                    __('Breakpoint "%1": enter a whole number for "%2".', $label, $fieldLabel),
                    $exception->getMessage()
                );

                return null;
            }
        }

        $input = null;
        $errors->attempt(
            __('Breakpoint "%1": enter a name, a media query and sizes greater than 0.', $label),
            static function () use ($row, $identifier, $numbers, &$input): void {
                $breakpointId = $numbers[BreakpointInterface::BREAKPOINT_ID];
                $input = new BreakpointInput(
                    $breakpointId === 0 ? null : $breakpointId,
                    $row->text(BreakpointInterface::NAME) ?? '',
                    $identifier,
                    $row->text(BreakpointInterface::MEDIA_QUERY) ?? '',
                    $numbers[BreakpointInterface::MIN_WIDTH] ?? 0,
                    $numbers[BreakpointInterface::TARGET_WIDTH] ?? 0,
                    $numbers[BreakpointInterface::TARGET_HEIGHT],
                    $numbers[BreakpointInterface::SORT_ORDER] ?? 0,
                    $row->flag(BreakpointPresenter::ENABLED, true)
                );
            }
        );

        return $input;
    }

    /**
     * The whole-number fields of a row and their labels
     *
     * @return array<string, Phrase>
     */
    private function numberFields(): array
    {
        return [
            BreakpointInterface::BREAKPOINT_ID => __('ID'),
            BreakpointInterface::MIN_WIDTH => __('Min Width'),
            BreakpointInterface::TARGET_WIDTH => __('Target Width'),
            BreakpointInterface::TARGET_HEIGHT => __('Target Height'),
            BreakpointInterface::SORT_ORDER => __('Sort Order'),
        ];
    }

    /**
     * The slider's stored breakpoints as inputs, so saving leaves them as they are
     *
     * @param int|null $sliderId
     * @return list<BreakpointInput>
     */
    private function currentSet(?int $sliderId): array
    {
        if ($sliderId === null) {
            return [];
        }

        $inputs = [];
        foreach ($this->breakpointRepository->getBySliderId($sliderId) as $breakpoint) {
            $inputs[] = new BreakpointInput(
                $breakpoint->getBreakpointId(),
                $breakpoint->getName(),
                $breakpoint->getIdentifier(),
                $breakpoint->getMediaQuery(),
                $breakpoint->getMinWidth(),
                $breakpoint->getTargetWidth(),
                $breakpoint->getTargetHeight(),
                $breakpoint->getSortOrder(),
                $breakpoint->isEnabled()
            );
        }

        return $inputs;
    }
}
