<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use DateTimeImmutable;
use DateTimeZone;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderApi\Api\Value\ActiveWindow;

/**
 * The `from_date` / `to_date` form fields of an active window, both ways.
 *
 * The forms use the framework's date-and-time element, which shows a value in the admin's configured time zone and
 * converts it back itself: it receives the stored UTC time as `Y-m-d H:i:s` and posts UTC as ISO 8601 (for example
 * `2026-03-01T08:00:00.000Z`). So this codec works in UTC only: it exports UTC and reads UTC, honouring an explicit
 * offset when the posted value carries one, and treating a value without an offset as UTC.
 */
class ActiveWindowFormCodec
{
    public const FROM_FIELD = 'from_date';
    public const TO_FIELD = 'to_date';

    private const EXPORT_FORMAT = 'Y-m-d H:i:s';
    private const PATTERN = '/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:?\d{2})?$/';

    /**
     * The posted window, or null when the form posted neither field or a posted value is invalid
     *
     * A field the form did not post keeps its current end; an empty field means an open end.
     *
     * @param PostData $post
     * @param ActiveWindow $current
     * @param FieldErrors $errors Receives a message for every invalid value
     * @return ActiveWindow|null
     */
    public function hydrate(PostData $post, ActiveWindow $current, FieldErrors $errors): ?ActiveWindow
    {
        if (!$post->has(self::FROM_FIELD) && !$post->has(self::TO_FIELD)) {
            return null;
        }

        $valid = true;
        $from = $current->getFrom();
        $to = $current->getTo();
        if ($post->has(self::FROM_FIELD)) {
            $valid = $errors->attempt(
                __('Enter a valid date and time for "%1".', __('From')),
                function () use ($post, &$from): void {
                    $from = $this->parse($post->text(self::FROM_FIELD));
                }
            );
        }
        if ($post->has(self::TO_FIELD)) {
            $valid = $errors->attempt(
                __('Enter a valid date and time for "%1".', __('To')),
                function () use ($post, &$to): void {
                    $to = $this->parse($post->text(self::TO_FIELD));
                }
            ) && $valid;
        }
        if (!$valid) {
            return null;
        }

        $window = null;
        $errors->attempt(
            __('The "To" date must not be before the "From" date.'),
            function () use ($from, $to, &$window): void {
                $window = new ActiveWindow($from, $to);
            }
        );

        return $window;
    }

    /**
     * The form values of a window: UTC `Y-m-d H:i:s`, or an empty string for an open end
     *
     * @param ActiveWindow $window
     * @return array{from_date: string, to_date: string}
     */
    public function export(ActiveWindow $window): array
    {
        return [
            self::FROM_FIELD => $window->getFrom()?->format(self::EXPORT_FORMAT) ?? '',
            self::TO_FIELD => $window->getTo()?->format(self::EXPORT_FORMAT) ?? '',
        ];
    }

    /**
     * Parse one posted date and time
     *
     * @param string|null $value
     * @return DateTimeImmutable|null Null for an empty value
     * @throws \InvalidArgumentException When the value is not a valid ISO 8601 date and time
     */
    private function parse(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        if (preg_match(self::PATTERN, $value, $matches) !== 1) {
            throw new \InvalidArgumentException(sprintf('"%s" is not an ISO 8601 date and time.', $value));
        }

        try {
            $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid date and time.', $value),
                0,
                $exception
            );
        }
        if ($date->format('Y-m-d') !== $matches[1]) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a calendar date.', $value));
        }

        return $date->setTimezone(new DateTimeZone('UTC'));
    }
}
