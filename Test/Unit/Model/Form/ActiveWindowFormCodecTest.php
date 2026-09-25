<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use DateTimeImmutable;
use DateTimeZone;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\ActiveWindowFormCodec;
use Hryvinskyi\BannerSliderApi\Api\Value\ActiveWindow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActiveWindowFormCodec::class)]
class ActiveWindowFormCodecTest extends TestCase
{
    /**
     * @var ActiveWindowFormCodec
     */
    private ActiveWindowFormCodec $codec;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->codec = new ActiveWindowFormCodec();
    }

    /**
     * The date element posts UTC as ISO 8601 (a local time with its offset is converted too), and the export
     * gives the element the stored UTC time back
     *
     * @param string $posted
     * @param string $utc
     * @return void
     */
    #[TestWith(['2026-03-01T08:00:00.000Z', '2026-03-01 08:00:00'])]
    #[TestWith(['2026-03-01T10:00:00+02:00', '2026-03-01 08:00:00'])]
    #[TestWith(['2026-03-01 08:00:00', '2026-03-01 08:00:00'])]
    #[TestWith(['2026-03-01T08:00', '2026-03-01 08:00:00'])]
    public function testRoundTripsThroughUtc(string $posted, string $utc): void
    {
        $errors = new FieldErrors();

        $window = $this->codec->hydrate(
            new PostData(['from_date' => $posted, 'to_date' => '']),
            new ActiveWindow(null, null),
            $errors
        );

        self::assertFalse($errors->hasErrors());
        self::assertNotNull($window);
        self::assertSame('UTC', $window->getFrom()?->getTimezone()->getName());
        self::assertNull($window->getTo());
        self::assertSame(['from_date' => $utc, 'to_date' => ''], $this->codec->export($window));
    }

    /**
     * A field the form did not post keeps its current end; no field posted means no change
     *
     * @return void
     */
    public function testKeepsWhatWasNotPosted(): void
    {
        $utc = new DateTimeZone('UTC');
        $current = new ActiveWindow(
            new DateTimeImmutable('2026-01-01 00:00:00', $utc),
            new DateTimeImmutable('2026-12-31 00:00:00', $utc)
        );

        $window = $this->codec->hydrate(
            new PostData(['to_date' => '2026-06-30T00:00:00Z']),
            $current,
            new FieldErrors()
        );

        self::assertNull($this->codec->hydrate(new PostData([]), $current, new FieldErrors()));
        self::assertSame(
            ['from_date' => '2026-01-01 00:00:00', 'to_date' => '2026-06-30 00:00:00'],
            $window === null ? [] : $this->codec->export($window)
        );
    }

    /**
     * Malformed dates and a reversed window become field errors, not exceptions
     *
     * @param string $from
     * @param string $to
     * @param string $message
     * @return void
     */
    #[TestWith(['next week', '', 'Enter a valid date and time for "From".'])]
    #[TestWith(['2026-02-31T10:00:00Z', '', 'Enter a valid date and time for "From".'])]
    #[TestWith(['2026-05-01T00:00:00Z', '2026-04-01T00:00:00Z', 'The "To" date must not be before the "From" date.'])]
    public function testReportsInvalidValues(string $from, string $to, string $message): void
    {
        $errors = new FieldErrors();

        $window = $this->codec->hydrate(
            new PostData(['from_date' => $from, 'to_date' => $to]),
            new ActiveWindow(null, null),
            $errors
        );

        self::assertNull($window);
        self::assertSame([$message], array_map('strval', $errors->getMessages()));
    }
}
