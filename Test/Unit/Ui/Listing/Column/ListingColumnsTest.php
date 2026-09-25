<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\Listing\Column;

use Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column\BannerActions;
use Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column\CustomerGroup;
use Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column\SliderActions;
use Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column\Thumbnail;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupSearchResultsInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomerGroup::class)]
#[CoversClass(Thumbnail::class)]
#[CoversClass(BannerActions::class)]
#[CoversClass(SliderActions::class)]
class ListingColumnsTest extends TestCase
{
    /**
     * Groups render as "All Customer Groups", their names, or "None"
     *
     * @return void
     */
    public function testRendersCustomerGroups(): void
    {
        $groups = $this->createMock(GroupSearchResultsInterface::class);
        $groups->method('getItems')->willReturn([
            $this->createConfiguredMock(GroupInterface::class, ['getId' => 1, 'getCode' => 'General']),
            $this->createConfiguredMock(GroupInterface::class, ['getId' => 2, 'getCode' => 'Wholesale']),
        ]);
        $repository = $this->createMock(GroupRepositoryInterface::class);
        $repository->expects(self::once())->method('getList')->willReturn($groups);
        $criteria = $this->createMock(SearchCriteriaBuilder::class);
        $criteria->method('create')->willReturn(new SearchCriteria());
        $column = new CustomerGroup(
            $this->createMock(ContextInterface::class),
            $this->createMock(UiComponentFactory::class),
            $repository,
            $criteria,
            [],
            ['name' => 'customer_group_ids']
        );

        $result = $column->prepareDataSource(['data' => ['items' => [
            ['all_customer_groups' => '1', 'customer_group_ids' => []],
            ['all_customer_groups' => '0', 'customer_group_ids' => [2, 1, 9]],
            ['all_customer_groups' => '0', 'customer_group_ids' => []],
        ]]]);

        self::assertSame(
            ['All Customer Groups', 'Wholesale, General', 'None'],
            array_column($this->items($result), 'customer_group_ids')
        );
    }

    /**
     * Video banners show the video placeholder; images their URL; missing or unsafe paths the image placeholder
     *
     * @return void
     */
    public function testRendersThumbnails(): void
    {
        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static function (string $path): string {
            if (str_contains($path, '..')) {
                throw new \InvalidArgumentException('Unsafe.');
            }

            return 'https://m.test/' . $path;
        });
        $assets = $this->createMock(AssetRepository::class);
        $assets->method('getUrl')->willReturnCallback(static fn (string $id): string => 'static:' . $id);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturn('https://admin.test/edit');
        $column = new Thumbnail(
            $this->createMock(ContextInterface::class),
            $this->createMock(UiComponentFactory::class),
            $resolver,
            $urlBuilder,
            $assets,
            [],
            ['name' => 'image']
        );

        $items = $this->items($column->prepareDataSource(['data' => ['items' => [
            ['banner_id' => 1, 'type' => '0', 'image' => 'banner_slider/image/a.jpg', 'title' => 'A'],
            ['banner_id' => 2, 'type' => '1', 'image' => 'banner_slider/image/b.jpg'],
            ['banner_id' => 3, 'type' => '0', 'image' => ''],
            ['banner_id' => 4, 'type' => '0', 'image' => '../etc/env.php'],
        ]]]));

        self::assertSame(
            [
                'https://m.test/banner_slider/image/a.jpg',
                'static:Hryvinskyi_BannerSliderAdminUi::images/placeholder/video.svg',
                'static:Hryvinskyi_BannerSliderAdminUi::images/placeholder/image.svg',
                'static:Hryvinskyi_BannerSliderAdminUi::images/placeholder/image.svg',
            ],
            array_column($items, 'image_src')
        );
        self::assertSame('A', $items[0]['image_alt'] ?? null);
        self::assertSame('https://admin.test/edit', $items[0]['image_link'] ?? null);
    }

    /**
     * Delete is offered only to admins allowed to delete; Manage Banners only to admins allowed to see banners
     *
     * @return void
     */
    public function testRowActionsFollowTheAcl(): void
    {
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(static fn (string $route): string => $route);
        $denied = $this->createMock(AuthorizationInterface::class);
        $denied->method('isAllowed')->willReturn(false);
        $allowed = $this->createMock(AuthorizationInterface::class);
        $allowed->method('isAllowed')->willReturn(true);
        $context = $this->createMock(ContextInterface::class);
        $factory = $this->createMock(UiComponentFactory::class);
        $data = ['name' => 'actions'];

        $bannerRows = ['data' => ['items' => [['banner_id' => 4]]]];
        $sliderRows = ['data' => ['items' => [['slider_id' => 2], 'not a row']]];
        $bannerDenied = (new BannerActions($context, $factory, $urlBuilder, $denied, [], $data))
            ->prepareDataSource($bannerRows);
        $bannerAllowed = (new BannerActions($context, $factory, $urlBuilder, $allowed, [], $data))
            ->prepareDataSource($bannerRows);
        $sliderDenied = (new SliderActions($context, $factory, $urlBuilder, $denied, [], $data))
            ->prepareDataSource($sliderRows);
        $sliderAllowed = (new SliderActions($context, $factory, $urlBuilder, $allowed, [], $data))
            ->prepareDataSource($sliderRows);

        self::assertSame(['edit'], $this->actionNames($bannerDenied));
        self::assertSame(['edit', 'delete'], $this->actionNames($bannerAllowed));
        self::assertSame(['edit'], $this->actionNames($sliderDenied));
        self::assertSame(['edit', 'manage_banners', 'delete'], $this->actionNames($sliderAllowed));
    }

    /**
     * The rows of a prepared data source
     *
     * @param array<mixed> $dataSource
     * @return list<array<mixed>>
     */
    private function items(array $dataSource): array
    {
        $data = $dataSource['data'] ?? [];
        $items = is_array($data) ? $data['items'] ?? [] : [];
        $rows = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item)) {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    /**
     * The action names of the first row
     *
     * @param array<mixed> $dataSource
     * @return list<int|string>
     */
    private function actionNames(array $dataSource): array
    {
        $actions = $this->items($dataSource)[0]['actions'] ?? [];

        return array_keys(is_array($actions) ? $actions : []);
    }
}
