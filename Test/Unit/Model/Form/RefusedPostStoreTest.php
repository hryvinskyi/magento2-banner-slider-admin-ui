<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Magento\Framework\App\Request\DataPersistorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefusedPostStore::class)]
class RefusedPostStoreTest extends TestCase
{
    /**
     * Session values by key
     *
     * @var array<string, mixed>
     */
    private array $session = [];

    /**
     * @var RefusedPostStore
     */
    private RefusedPostStore $store;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->method('set')->willReturnCallback(function (string $key, mixed $data): void {
            $this->session[$key] = $data;
        });
        $persistor->method('get')->willReturnCallback(fn (string $key): mixed => $this->session[$key] ?? null);
        $persistor->method('clear')->willReturnCallback(function (string $key): void {
            unset($this->session[$key]);
        });
        $this->store = new RefusedPostStore($persistor);
    }

    /**
     * The form of the entity the post was meant for gets it back once
     *
     * @param int|null $entityId
     * @return void
     */
    #[TestWith([7])]
    #[TestWith([null])]
    public function testHandsThePostBackToTheSameEntityOnce(?int $entityId): void
    {
        $this->store->keep('form', $entityId, ['name' => 'Typed']);

        self::assertSame(['name' => 'Typed'], $this->store->take('form', $entityId));
        self::assertNull($this->store->take('form', $entityId));
    }

    /**
     * The form of another entity (or of a new one) does not get the post, and the post is cleared
     *
     * @param int|null $keptFor
     * @param int|null $shown
     * @return void
     */
    #[TestWith([7, 8])]
    #[TestWith([7, null])]
    #[TestWith([null, 7])]
    public function testClearsAPostMeantForAnotherEntity(?int $keptFor, ?int $shown): void
    {
        $this->store->keep('form', $keptFor, ['name' => 'Typed']);

        self::assertNull($this->store->take('form', $shown));
        self::assertSame([], $this->session);
        self::assertNull($this->store->take('form', $keptFor));
    }

    /**
     * A value that is not a kept post (a post kept before posts were tied to an entity) is cleared, not applied
     *
     * @return void
     */
    public function testIgnoresAnUnknownValue(): void
    {
        $this->session['form'] = ['name' => 'Old shape'];

        self::assertNull($this->store->take('form', null));
        self::assertSame([], $this->session);
    }

    /**
     * Forgetting drops the post of that form only
     *
     * @return void
     */
    public function testForgetsOneForm(): void
    {
        $this->store->keep('banner', 1, ['a' => 1]);
        $this->store->keep('slider', 2, ['b' => 2]);

        $this->store->forget('banner');

        self::assertNull($this->store->take('banner', 1));
        self::assertSame(['b' => 2], $this->store->take('slider', 2));
    }
}
