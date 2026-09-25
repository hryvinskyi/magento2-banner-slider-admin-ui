<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\StrictBase64Decoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(StrictBase64Decoder::class)]
class StrictBase64DecoderTest extends TestCase
{
    /**
     * Plain base64 decodes
     *
     * @return void
     */
    public function testDecodesPlainBase64(): void
    {
        self::assertSame('hello', (new StrictBase64Decoder())->decode('aGVsbG8='));
    }

    /**
     * Anything outside the alphabet, or nothing at all, is not accepted
     *
     * @param string $encoded
     * @return void
     */
    #[TestWith(['data:image/png;base64,aGVsbG8='])]
    #[TestWith(['aGVs*bG8='])]
    #[TestWith([''])]
    public function testRefusesNonStrictInput(string $encoded): void
    {
        self::assertNull((new StrictBase64Decoder())->decode($encoded));
    }

    /**
     * The size bound never underestimates the decoded size
     *
     * @return void
     */
    public function testBoundsTheDecodedSize(): void
    {
        $encoded = base64_encode(str_repeat('z', 100));

        self::assertGreaterThanOrEqual(100, (new StrictBase64Decoder())->decodedSizeLimit($encoded));
    }
}
