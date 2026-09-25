<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Api\Form;

use Magento\Framework\Phrase;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResult;

/**
 * Collects the field errors of one form submission, so the admin sees all of them at once.
 *
 * Each error pairs a translated message for the admin with a developer detail (usually the message of the
 * `\InvalidArgumentException` a setter or value object threw) that belongs in the log, not on the screen.
 *
 * @api
 */
class FieldErrors
{
    /**
     * @var list<Phrase>
     */
    private array $messages = [];

    /**
     * @var list<string>
     */
    private array $details = [];

    /**
     * Run one field assignment; when it rejects its value, record the message instead of failing the whole form
     *
     * @param Phrase $message Shown to the admin when the assignment throws `\InvalidArgumentException`
     * @param \Closure():mixed $assignment
     * @return bool Whether the assignment succeeded
     */
    public function attempt(Phrase $message, \Closure $assignment): bool
    {
        try {
            $assignment();
        } catch (\InvalidArgumentException $exception) {
            $this->add($message, $exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Record an error
     *
     * @param Phrase $message Shown to the admin
     * @param string $detail Developer detail for the log
     * @return void
     */
    public function add(Phrase $message, string $detail = ''): void
    {
        $this->messages[] = $message;
        if ($detail !== '') {
            $this->details[] = $detail;
        }
    }

    /**
     * Whether any error was recorded
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->messages !== [];
    }

    /**
     * The messages for the admin, in the order they were recorded
     *
     * @return list<Phrase>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * The developer details, in the order they were recorded
     *
     * @return list<string>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * One validation exception carrying every recorded message
     *
     * @return ValidationException
     */
    public function toException(): ValidationException
    {
        return new ValidationException(
            __('The form has invalid values.'),
            null,
            0,
            new ValidationResult($this->messages)
        );
    }
}
