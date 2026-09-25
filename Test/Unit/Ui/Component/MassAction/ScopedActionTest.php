<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\Component\MassAction;

use Hryvinskyi\BannerSliderAdminUi\Ui\Component\MassAction\ScopedAction;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopedAction::class)]
class ScopedActionTest extends TestCase
{
    /**
     * The action URL carries the listing's scope as requested, empty when the listing was opened without it
     *
     * @param mixed $requested
     * @param string $baseUrl
     * @param string $expectedUrl
     * @return void
     */
    #[TestWith(['3', 'https://a.test/massDelete/key/abc/', 'https://a.test/massDelete/key/abc/?slider_id=3'])]
    #[TestWith([null, 'https://a.test/massDelete/', 'https://a.test/massDelete/?slider_id='])]
    #[TestWith(['a b', 'https://a.test/massDelete/?x=1', 'https://a.test/massDelete/?x=1&slider_id=a+b'])]
    #[TestWith([['3'], 'https://a.test/massDelete/', 'https://a.test/massDelete/'])]
    public function testAddsTheListingScopeToTheUrl(mixed $requested, string $baseUrl, string $expectedUrl): void
    {
        $action = $this->action($requested, ['url' => $baseUrl, 'type' => 'delete'], ['slider_id']);

        $action->prepare();

        self::assertSame($expectedUrl, $action->getConfiguration()['url'] ?? null);
        self::assertSame('delete', $action->getConfiguration()['type'] ?? null);
    }

    /**
     * Without scope parameters, or without a URL, the configuration is left as it is
     *
     * @return void
     */
    public function testLeavesAnUnscopedActionAlone(): void
    {
        $withoutScope = $this->action('3', ['url' => 'https://admin.test/massDelete/'], []);
        $withoutUrl = $this->action('3', ['type' => 'delete'], ['slider_id']);

        $withoutScope->prepare();
        $withoutUrl->prepare();

        self::assertSame('https://admin.test/massDelete/', $withoutScope->getConfiguration()['url'] ?? null);
        self::assertArrayNotHasKey('url', $withoutUrl->getConfiguration());
    }

    /**
     * An action on a request that answers `slider_id` with the given value
     *
     * @param mixed $requested
     * @param array<string, mixed> $config
     * @param list<string> $scopeParams
     * @return ScopedAction
     */
    private function action(mixed $requested, array $config, array $scopeParams): ScopedAction
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createMock(Processor::class));
        $context->method('getRequestParam')->willReturnCallback(
            static fn (string $name): mixed => $name === 'slider_id' ? $requested : null
        );
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);

        return new ScopedAction(
            $context,
            $authorization,
            [],
            [
                'aclResource' => 'Hryvinskyi_BannerSlider::banner_delete',
                'scopeParams' => $scopeParams,
                'config' => $config,
            ]
        );
    }
}
