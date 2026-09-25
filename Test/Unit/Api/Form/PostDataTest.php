<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Api\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostData::class)]
class PostDataTest extends TestCase
{
    /**
     * Text is trimmed and an empty value reads as none; raw text keeps its spaces
     *
     * @return void
     */
    public function testReadsText(): void
    {
        $post = new PostData(['name' => '  Home  ', 'empty' => '   ', 'number' => 5, 'list' => ['a']]);

        self::assertSame('Home', $post->text('name'));
        self::assertNull($post->text('empty'));
        self::assertNull($post->text('missing'));
        self::assertSame('5', $post->text('number'));
        self::assertNull($post->text('list'));
        self::assertSame('  Home  ', $post->rawText('name'));
        self::assertTrue($post->has('empty'));
        self::assertFalse($post->has('missing'));
    }

    /**
     * Whole numbers are read from integers and digit text; empty is none
     *
     * @return void
     */
    public function testReadsIntegers(): void
    {
        $post = new PostData(['a' => '42', 'b' => ' -7 ', 'c' => '', 'd' => 9]);

        self::assertSame(42, $post->integer('a'));
        self::assertSame(-7, $post->integer('b'));
        self::assertNull($post->integer('c'));
        self::assertNull($post->integer('missing'));
        self::assertSame(9, $post->integer('d'));
    }

    /**
     * Anything else than a whole number is refused
     *
     * @param mixed $value
     * @return void
     */
    #[TestWith(['1.5'])]
    #[TestWith(['12px'])]
    #[TestWith([['1']])]
    public function testRefusesNonIntegers(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PostData(['a' => $value]))->integer('a');
    }

    /**
     * Boolean spellings are read; the default applies only to a missing key
     *
     * @return void
     */
    public function testReadsFlags(): void
    {
        $post = new PostData(['on' => '1', 'off' => '0', 'yes' => 'true', 'empty' => '', 'bool' => false]);

        self::assertTrue($post->flag('on', false));
        self::assertFalse($post->flag('off', true));
        self::assertTrue($post->flag('yes', false));
        self::assertFalse($post->flag('empty', true));
        self::assertFalse($post->flag('bool', true));
        self::assertTrue($post->flag('missing', true));
    }

    /**
     * A value that is not boolean-like is refused
     *
     * @return void
     */
    public function testRefusesNonFlags(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PostData(['a' => 'maybe']))->flag('a', false);
    }

    /**
     * Lists, sections and rows read nested arrays and skip what is not an array
     *
     * @return void
     */
    public function testReadsNestedValues(): void
    {
        $post = new PostData([
            'ids' => ['3' => '1', '9' => '2'],
            'section' => ['rows' => [['name' => 'a'], 'junk', ['name' => 'b']]],
        ]);

        self::assertSame(['1', '2'], $post->values('ids'));
        self::assertSame([], $post->values('missing'));
        $rows = $post->section('section')->rows('rows');
        self::assertCount(2, $rows);
        self::assertSame('b', $rows[1]->text('name'));
        self::assertSame([], $post->section('missing')->toArray());
    }
}
