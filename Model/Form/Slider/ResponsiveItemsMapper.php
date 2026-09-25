<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\SliderFormMapperInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\ResponsiveItem;

/**
 * Slides-per-page rules of a slider, edited as dynamic rows (min width, slides per page, gap).
 *
 * Dynamic rows leave deleted rows out of the post, and an emptied list posts no key at all. So the form carries a
 * hidden `responsive_items_submitted` field: when it is posted, the posted rows are the whole desired list (no rows
 * = no rules); when it is missing (a post from elsewhere), the slider's rules are left unchanged.
 */
class ResponsiveItemsMapper implements SliderFormMapperInterface
{
    public const SECTION = 'responsive_items';
    public const ROWS = 'responsive_items_container';
    public const MARKER = 'responsive_items_submitted';
    public const MIN_WIDTH = 'min_width';
    public const PER_PAGE = 'per_page';
    public const GAP = 'gap';

    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, SliderInterface $slider, FieldErrors $errors): void
    {
        if (!$post->flag(self::MARKER, false)) {
            return;
        }

        $items = [];
        $valid = true;
        foreach ($post->section(self::SECTION)->rows(self::ROWS) as $index => $row) {
            $valid = $errors->attempt(
                __(
                    'Slides per page row %1 is invalid: check min width, slides (1-%2) and gap (e.g. 16px).',
                    $index + 1,
                    ResponsiveItem::MAX_PER_PAGE
                ),
                static function () use ($row, &$items): void {
                    $items[] = new ResponsiveItem(
                        $row->integer(self::MIN_WIDTH) ?? 0,
                        $row->integer(self::PER_PAGE) ?? 0,
                        $row->text(self::GAP)
                    );
                }
            ) && $valid;
        }

        if ($valid) {
            $slider->setResponsiveItems($items);
        }
    }

    /**
     * @inheritDoc
     */
    public function export(SliderInterface $slider): array
    {
        $rows = [];
        foreach ($slider->getResponsiveItems() as $item) {
            $rows[] = [
                self::MIN_WIDTH => (string)$item->getMinWidth(),
                self::PER_PAGE => (string)$item->getPerPage(),
                self::GAP => $item->getGap() ?? '',
            ];
        }

        return [
            self::SECTION => [self::ROWS => $rows],
            self::MARKER => '1',
        ];
    }
}
