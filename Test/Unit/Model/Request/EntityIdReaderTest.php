<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Request;

use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityIdReader::class)]
class EntityIdReaderTest extends TestCase
{
    /**
     * Only positive whole numbers are ids
     *
     * @param mixed $value
     * @param int|null $expected
     * @return void
     */
    #[TestWith(['12', 12])]
    #[TestWith([' 7 ', 7])]
    #[TestWith([3, 3])]
    #[TestWith(['0', null])]
    #[TestWith([0, null])]
    #[TestWith(['-4', null])]
    #[TestWith(['4abc', null])]
    #[TestWith([null, null])]
    #[TestWith([['1'], null])]
    public function testParsesIds(mixed $value, ?int $expected): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturn($value);

        self::assertSame($expected, (new EntityIdReader())->read($request, 'id'));
    }
}
