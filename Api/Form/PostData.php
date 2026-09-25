<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Api\Form;

/**
 * Read-only, typed view of a posted admin form (or one nested part of it).
 *
 * Every value a browser posts is untrusted text or a nested array. The readers here turn it into the type a field
 * needs and never guess: an empty value reads as "no value" (null), and text that is not the requested type throws
 * `\InvalidArgumentException`, which form mappers turn into a field error.
 *
 * @api
 */
class PostData
{
    private const INTEGER_PATTERN = '/^-?\d{1,18}$/';

    /**
     * @param array<mixed> $values
     */
    public function __construct(
        private readonly array $values
    ) {
    }

    /**
     * Whether the form posted the key at all (with any value, empty included)
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Trimmed text of a key; null when absent, empty or not text
     *
     * @param string $key
     * @return string|null
     */
    public function text(string $key): ?string
    {
        $value = $this->scalarText($key);
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Text of a key exactly as posted; null when absent, empty or not text
     *
     * @param string $key
     * @return string|null
     */
    public function rawText(string $key): ?string
    {
        $value = $this->scalarText($key);

        return $value === null || $value === '' ? null : $value;
    }

    /**
     * Whole number of a key; null when absent or empty
     *
     * @param string $key
     * @return int|null
     * @throws \InvalidArgumentException When the value is not a whole number
     */
    public function integer(string $key): ?int
    {
        $value = $this->values[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }
        $text = $this->text($key);
        if ($text === null) {
            if ($value !== null && !is_scalar($value)) {
                throw new \InvalidArgumentException(sprintf('Form value "%s" is not a whole number.', $key));
            }

            return null;
        }
        if (preg_match(self::INTEGER_PATTERN, $text) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Form value "%s" is not a whole number, got "%s".', $key, $text)
            );
        }

        return (int)$text;
    }

    /**
     * Boolean of a key: `1`, `true`, `on`, `yes` read as true, `0`, `false`, `off`, `no` and empty as false
     *
     * @param string $key
     * @param bool $default Returned when the key is absent
     * @return bool
     * @throws \InvalidArgumentException When the value is not boolean-like
     */
    public function flag(string $key, bool $default): bool
    {
        if (!$this->has($key)) {
            return $default;
        }
        $value = $this->values[$key];
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return false;
        }
        $flag = is_int($value) || is_string($value)
            ? filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        if ($flag === null) {
            throw new \InvalidArgumentException(sprintf('Form value "%s" is not a yes/no value.', $key));
        }

        return $flag;
    }

    /**
     * The values of a list key (a multiselect, rows) in posted order; empty when absent or not a list
     *
     * @param string $key
     * @return list<mixed>
     */
    public function values(string $key): array
    {
        $value = $this->values[$key] ?? null;

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * A nested part of the form; empty when absent or not an array
     *
     * @param string $key
     * @return PostData
     */
    public function section(string $key): PostData
    {
        $value = $this->values[$key] ?? null;

        return new PostData(is_array($value) ? $value : []);
    }

    /**
     * The nested rows of a key (dynamic rows, uploader files) in posted order; entries that are not arrays are left out
     *
     * @param string $key
     * @return list<PostData>
     */
    public function rows(string $key): array
    {
        $rows = [];
        foreach ($this->values($key) as $row) {
            if (is_array($row)) {
                $rows[] = new PostData($row);
            }
        }

        return $rows;
    }

    /**
     * The posted values as given
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * A scalar value as text; null when absent or not scalar
     *
     * @param string $key
     * @return string|null
     */
    private function scalarText(string $key): ?string
    {
        $value = $this->values[$key] ?? null;
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        return null;
    }
}
