<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml;

use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner\Edit as BannerEdit;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner\Index as BannerIndex;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider\Edit as SliderEdit;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider\Index as SliderIndex;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\PageFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BannerEdit::class)]
#[CoversClass(SliderEdit::class)]
#[CoversClass(BannerIndex::class)]
#[CoversClass(SliderIndex::class)]
class EditControllersTest extends TestCase
{
    use BackendActionContext;

    /**
     * Titles given to the page
     *
     * @var list<string>
     */
    private array $titles = [];

    /**
     * A stored banner opens its form titled with its name
     *
     * @return void
     */
    public function testOpensAStoredBanner(): void
    {
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->method('getById')->with(5)->willReturn((new FakeBanner())->setName('Spring'));
        $controller = new BannerEdit(
            $this->actionContext(['banner_id' => '5']),
            $this->pageFactory(),
            $repository,
            new EntityIdReader()
        );

        self::assertInstanceOf(Page::class, $controller->execute());
        self::assertSame(['Edit Banner: Spring'], $this->titles);
    }

    /**
     * A banner that no longer exists redirects to the grid with a message, instead of failing on the result type
     *
     * @return void
     */
    public function testRedirectsForAMissingBanner(): void
    {
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('No such banner.')));
        $controller = new BannerEdit(
            $this->actionContext(['banner_id' => '404']),
            $this->pageFactory(),
            $repository,
            new EntityIdReader()
        );

        self::assertInstanceOf(Redirect::class, $controller->execute());
        self::assertSame(['error: This banner no longer exists.'], $this->messages);
        self::assertSame(['*/*/', []], $this->redirectedTo);
    }

    /**
     * A missing slider redirects the same way; a new slider opens the empty form
     *
     * @return void
     */
    public function testHandlesSliders(): void
    {
        $repository = $this->createMock(SliderRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('No such slider.')));

        $missing = new SliderEdit(
            $this->actionContext(['slider_id' => '8']),
            $this->pageFactory(),
            $repository,
            new EntityIdReader()
        );
        $new = new SliderEdit($this->actionContext(), $this->pageFactory(), $repository, new EntityIdReader());

        self::assertInstanceOf(Redirect::class, $missing->execute());
        self::assertInstanceOf(Page::class, $new->execute());
        self::assertSame(['New Slider'], $this->titles);
    }

    /**
     * Both grid pages load their default layout handles, set the menu and the title
     *
     * @return void
     */
    public function testGridPagesLoadTheirLayoutHandles(): void
    {
        self::assertInstanceOf(Page::class, (new SliderIndex($this->actionContext(), $this->pageFactory()))->execute());
        self::assertInstanceOf(Page::class, (new BannerIndex($this->actionContext(), $this->pageFactory()))->execute());
        self::assertSame(['Sliders', 'Banners'], $this->titles);
    }

    /**
     * A page factory whose page records its title
     *
     * @return PageFactory
     */
    private function pageFactory(): PageFactory
    {
        $title = $this->createMock(Title::class);
        $title->method('prepend')->willReturnCallback(function (string $prefix): void {
            $this->titles[] = $prefix;
        });
        $config = $this->createMock(Config::class);
        $config->method('getTitle')->willReturn($title);
        $page = $this->createMock(Page::class);
        $handleAdded = false;
        $page->method('addDefaultHandle')->willReturnCallback(function () use (&$handleAdded, $page): Page {
            $handleAdded = true;

            return $page;
        });
        $page->method('getConfig')->willReturnCallback(function () use (&$handleAdded, $config): Config {
            self::assertTrue($handleAdded, 'The page gets its default layout handles before it is used.');

            return $config;
        });
        $page->method('setActiveMenu')->willReturnCallback(function () use (&$handleAdded, $page): Page {
            self::assertTrue($handleAdded, 'The page gets its default layout handles before the menu is set.');

            return $page;
        });
        $factory = $this->createMock(PageFactory::class);
        $factory->method('create')->willReturn($page);

        return $factory;
    }
}
