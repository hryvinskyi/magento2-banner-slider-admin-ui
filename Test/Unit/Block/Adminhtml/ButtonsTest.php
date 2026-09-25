<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Block\Adminhtml;

use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Edit\BackButton as BannerBackButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Edit\DeleteButton as BannerDeleteButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Edit\SaveAndContinueButton as BannerSaveAndContinue;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Edit\SaveButton as BannerSaveButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Listing\AddNewButton as BannerAddNewButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Listing\BackToSliderButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\GenericButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit\BackButton as SliderBackButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit\DeleteButton as SliderDeleteButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit\ManageBannersButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit\SaveAndContinueButton as SliderSaveAndContinue;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit\SaveButton as SliderSaveButton;
use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Listing\AddNewButton as SliderAddNewButton;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericButton::class)]
#[CoversClass(BannerBackButton::class)]
#[CoversClass(BannerDeleteButton::class)]
#[CoversClass(BannerSaveButton::class)]
#[CoversClass(BannerSaveAndContinue::class)]
#[CoversClass(BannerAddNewButton::class)]
#[CoversClass(BackToSliderButton::class)]
#[CoversClass(SliderBackButton::class)]
#[CoversClass(SliderDeleteButton::class)]
#[CoversClass(ManageBannersButton::class)]
#[CoversClass(SliderSaveButton::class)]
#[CoversClass(SliderSaveAndContinue::class)]
#[CoversClass(SliderAddNewButton::class)]
class ButtonsTest extends TestCase
{
    /**
     * Without the ACL resources every action button is hidden; only the navigation back stays
     *
     * @return void
     */
    public function testHidesButtonsWithoutPermission(): void
    {
        $visible = [];
        foreach ($this->buttons(false) as $name => $button) {
            if ($button->getButtonData() !== []) {
                $visible[] = $name;
            }
        }

        self::assertSame(['banner_back', 'slider_back'], $visible);
    }

    /**
     * With the ACL resources every button of a stored entity is shown
     *
     * @return void
     */
    public function testShowsButtonsWithPermission(): void
    {
        foreach ($this->buttons(true) as $name => $button) {
            self::assertNotSame([], $button->getButtonData(), $name);
        }
    }

    /**
     * Delete confirms and posts through data attributes, never through script built from text
     *
     * @return void
     */
    public function testDeleteUsesDataAttributes(): void
    {
        $data = $this->buttons(true)['banner_delete']->getButtonData();

        self::assertSame('', $data['on_click'] ?? null);
        self::assertEquals(
            [
                'post' => [
                    'action' => '*/*/delete?banner_id=5',
                    'data' => [
                        'confirmation' => true,
                        'confirmationMessage' => 'Are you sure you want to delete this banner?',
                    ],
                ],
                'mage-init' => ['mage/dataPost' => new \stdClass()],
            ],
            $data['data_attribute'] ?? null
        );
    }

    /**
     * The banner form's save buttons never take the ids the form component binds its own save to (`save`,
     * `save_and_continue`); with those ids one click would submit the form twice
     *
     * @return void
     */
    public function testBannerSaveButtonsAvoidTheFormSaveIds(): void
    {
        $buttons = $this->buttons(true);

        self::assertSame('save_banner', $buttons['banner_save']->getButtonData()['id'] ?? null);
        self::assertSame('save_banner_and_continue', $buttons['banner_save_continue']->getButtonData()['id'] ?? null);
    }

    /**
     * Every button provider of the module, keyed by a short name
     *
     * @param bool $allowed
     * @return array<string, GenericButton>
     */
    private function buttons(bool $allowed): array
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn (string $name): ?string => ['banner_id' => '5', 'slider_id' => '3'][$name] ?? null
        );
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route, array $params = []): string => $route
                . ($params === [] ? '' : '?' . http_build_query($params))
        );
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn($allowed);
        $reader = new EntityIdReader();

        return [
            'banner_back' => new BannerBackButton($context, $authorization, $reader),
            'banner_delete' => new BannerDeleteButton($context, $authorization, $reader),
            'banner_save' => new BannerSaveButton($context, $authorization, $reader),
            'banner_save_continue' => new BannerSaveAndContinue($context, $authorization, $reader),
            'banner_add' => new BannerAddNewButton($context, $authorization, $reader),
            'banner_back_to_slider' => new BackToSliderButton($context, $authorization, $reader),
            'slider_back' => new SliderBackButton($context, $authorization, $reader),
            'slider_delete' => new SliderDeleteButton($context, $authorization, $reader),
            'slider_manage_banners' => new ManageBannersButton($context, $authorization, $reader),
            'slider_save' => new SliderSaveButton($context, $authorization, $reader),
            'slider_save_continue' => new SliderSaveAndContinue($context, $authorization, $reader),
            'slider_add' => new SliderAddNewButton($context, $authorization, $reader),
        ];
    }
}
