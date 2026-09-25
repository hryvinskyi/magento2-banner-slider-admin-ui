<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Api\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldErrors::class)]
class FieldErrorsTest extends TestCase
{
    /**
     * A rejected assignment records the admin message and keeps the developer detail apart
     *
     * @return void
     */
    public function testRecordsRejectedAssignments(): void
    {
        $errors = new FieldErrors();

        $passed = $errors->attempt(__('Fine'), static fn () => null);
        $failed = $errors->attempt(__('Enter a valid name.'), static function (): void {
            throw new \InvalidArgumentException('Name must not be empty.');
        });
        $errors->add(__('Second problem.'));

        self::assertTrue($passed);
        self::assertFalse($failed);
        self::assertTrue($errors->hasErrors());
        self::assertSame(
            ['Enter a valid name.', 'Second problem.'],
            array_map('strval', $errors->getMessages())
        );
        self::assertSame(['Name must not be empty.'], $errors->getDetails());
    }

    /**
     * Every message ends up in one validation exception
     *
     * @return void
     */
    public function testBuildsOneValidationException(): void
    {
        $errors = new FieldErrors();
        $errors->add(__('First.'));
        $errors->add(__('Second.'));

        $exception = $errors->toException();

        self::assertSame(
            ['First.', 'Second.'],
            array_map(static fn (\Exception $error): string => $error->getMessage(), $exception->getErrors())
        );
    }

    /**
     * Other exceptions are not swallowed
     *
     * @return void
     */
    public function testDoesNotSwallowOtherFailures(): void
    {
        $this->expectException(\RuntimeException::class);

        (new FieldErrors())->attempt(__('Never shown'), static function (): void {
            throw new \RuntimeException('Storage down');
        });
    }
}
