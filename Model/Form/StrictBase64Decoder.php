<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

/**
 * Decodes plain base64 text strictly: any character outside the base64 alphabet (a `data:` URL prefix included)
 * makes the whole value invalid, instead of being skipped silently. Whitespace is ignored.
 */
class StrictBase64Decoder
{
    /**
     * Decoded bytes, or null when the text is not strict base64 or decodes to nothing
     *
     * @param string $encoded
     * @return string|null
     */
    public function decode(string $encoded): ?string
    {
        $bytes = base64_decode($encoded, true);

        return $bytes === false || $bytes === '' ? null : $bytes;
    }

    /**
     * Upper bound of the decoded size of base64 text, without decoding it
     *
     * @param string $encoded
     * @return int
     */
    public function decodedSizeLimit(string $encoded): int
    {
        return intdiv(strlen($encoded) * 3, 4);
    }
}
