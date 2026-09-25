<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\Component\MassAction;

use Hryvinskyi\BannerSliderAdminUi\Ui\Component\MassAction\AuthorizedAction;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthorizedAction::class)]
class AuthorizedActionTest extends TestCase
{
    /**
     * The action is disabled exactly when its resource is not allowed
     *
     * @param bool $allowed
     * @return void
     */
    #[TestWith([true])]
    #[TestWith([false])]
    public function testDisablesTheActionWithoutPermission(bool $allowed): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createMock(Processor::class));
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::once())->method('isAllowed')
            ->with('Hryvinskyi_BannerSlider::banner_delete')
            ->willReturn($allowed);
        $action = new AuthorizedAction(
            $context,
            $authorization,
            [],
            ['aclResource' => 'Hryvinskyi_BannerSlider::banner_delete', 'config' => ['type' => 'delete']]
        );

        $action->prepare();

        self::assertSame(!$allowed, $action->getConfiguration()['actionDisable'] ?? false);
        self::assertSame('delete', $action->getConfiguration()['type'] ?? null);
    }
}
