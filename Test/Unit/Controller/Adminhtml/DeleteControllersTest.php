<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml;

use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner\Delete as BannerDelete;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner\MassDelete as BannerMassDelete;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider\Delete as SliderDelete;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider\MassDelete as SliderMassDelete;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(BannerDelete::class)]
#[CoversClass(SliderDelete::class)]
#[CoversClass(BannerMassDelete::class)]
#[CoversClass(SliderMassDelete::class)]
class DeleteControllersTest extends TestCase
{
    use BackendActionContext;

    /**
     * One banner is deleted by id; a missing id deletes nothing
     *
     * @return void
     */
    public function testDeletesOneBanner(): void
    {
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->expects(self::once())->method('deleteById')->with(6);

        (new BannerDelete($this->actionContext(['banner_id' => '6']), $repository, new EntityIdReader()))->execute();
        (new BannerDelete($this->actionContext(), $repository, new EntityIdReader()))->execute();

        self::assertSame(
            ['success: The banner has been deleted.', 'error: Choose a banner to delete.'],
            $this->messages
        );
        self::assertSame(['*/*/', []], $this->redirectedTo);
    }

    /**
     * A slider that cannot be deleted reports the repository's message
     *
     * @return void
     */
    public function testReportsASliderThatCannotBeDeleted(): void
    {
        $repository = $this->createMock(SliderRepositoryInterface::class);
        $repository->method('deleteById')->willThrowException(new CouldNotDeleteException(__('Locked.')));

        (new SliderDelete($this->actionContext(['slider_id' => '2']), $repository, new EntityIdReader()))->execute();

        self::assertSame(['error: Locked.'], $this->messages);
    }

    /**
     * The selection is resolved on the injected grid collection in one query, then each id is deleted; ids that
     * are already gone are skipped and failures are reported without stopping the rest
     *
     * @return void
     */
    public function testMassDeletesBanners(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects(self::once())->method('getAllIds')->willReturn(['1', '2', '3', 'x']);
        $filter = $this->createMock(Filter::class);
        $filter->method('getCollection')->with($collection)->willReturn($collection);
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $deleted = [];
        $repository->method('deleteById')->willReturnCallback(static function (int $id) use (&$deleted): void {
            if ($id === 2) {
                throw new NoSuchEntityException(__('Gone.'));
            }
            if ($id === 3) {
                throw new CouldNotDeleteException(__('Locked.'));
            }
            $deleted[] = $id;
        });

        (new BannerMassDelete(
            $this->actionContext(['slider_id' => '']),
            $filter,
            $collection,
            $repository,
            new EntityIdReader()
        ))->execute();

        self::assertSame([1], $deleted);
        self::assertSame(
            ['error: Banner 3 could not be deleted: Locked.', 'success: A total of 1 banner(s) have been deleted.'],
            $this->messages
        );
    }

    /**
     * The grid of one slider deletes only that slider's banners: the collection is limited to the posted slider
     * before the selection is applied, so an id of another slider in the selection is never deleted
     *
     * @return void
     */
    public function testMassDeleteKeepsToThePostedSlider(): void
    {
        $sliderOfBanner = [1 => 3, 2 => 3, 7 => 4];
        /** @var array<string, mixed> $filters */
        $filters = [];
        $collection = $this->createMock(AbstractDb::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            static function (string $field, mixed $condition) use (&$filters, $collection): AbstractDb {
                $filters[$field] = $condition;

                return $collection;
            }
        );
        $collection->method('getAllIds')->willReturnCallback(
            static function () use (&$filters, $sliderOfBanner): array {
                $ids = [];
                foreach ($sliderOfBanner as $bannerId => $sliderId) {
                    $inSlider = ($filters['slider_id'] ?? null) === ['eq' => $sliderId];
                    $selected = ($filters['banner_id'] ?? null) === ['in' => [1, 2, 7]];
                    if ($inSlider && $selected) {
                        $ids[] = (string)$bannerId;
                    }
                }

                return $ids;
            }
        );
        $filter = $this->createMock(Filter::class);
        $filter->method('getCollection')->willReturnCallback(
            static function (AbstractDb $grid) use (&$filters): AbstractDb {
                self::assertArrayHasKey('slider_id', $filters, 'The slider is applied before the selection.');
                $grid->addFieldToFilter('banner_id', ['in' => [1, 2, 7]]);

                return $grid;
            }
        );
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $deleted = [];
        $repository->method('deleteById')->willReturnCallback(static function (int $id) use (&$deleted): void {
            $deleted[] = $id;
        });

        (new BannerMassDelete(
            $this->actionContext(['slider_id' => '3']),
            $filter,
            $collection,
            $repository,
            new EntityIdReader()
        ))->execute();

        self::assertSame([1, 2], $deleted);
        self::assertSame(['success: A total of 2 banner(s) have been deleted.'], $this->messages);
        self::assertSame(['*/*/', ['slider_id' => 3]], $this->redirectedTo);
    }

    /**
     * A request that does not state the grid's slider, or states something that is not a slider id, deletes nothing
     *
     * @param array<string, string> $params
     * @return void
     */
    #[TestWith([[]])]
    #[TestWith([['slider_id' => 'abc']])]
    #[TestWith([['slider_id' => '0']])]
    public function testMassDeleteRefusesAnUnstatedSlider(array $params): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->expects(self::never())->method('addFieldToFilter');
        $filter = $this->createMock(Filter::class);
        $filter->expects(self::never())->method('getCollection');
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->expects(self::never())->method('deleteById');

        (new BannerMassDelete($this->actionContext($params), $filter, $collection, $repository, new EntityIdReader()))
            ->execute();

        self::assertSame(
            ['error: Nothing was deleted: reload the banner grid and select the banners again.'],
            $this->messages
        );
        self::assertSame(['*/*/', []], $this->redirectedTo);
    }

    /**
     * An empty selection is reported with the filter's message
     *
     * @return void
     */
    public function testMassDeleteWithoutSelection(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $filter = $this->createMock(Filter::class);
        $filter->method('getCollection')->willThrowException(
            new LocalizedException(__('An item needs to be selected.'))
        );
        $repository = $this->createMock(SliderRepositoryInterface::class);
        $repository->expects(self::never())->method('deleteById');

        (new SliderMassDelete($this->actionContext(), $filter, $collection, $repository, new EntityIdReader()))
            ->execute();

        self::assertSame(['error: An item needs to be selected.'], $this->messages);
        self::assertSame(['*/*/', []], $this->redirectedTo);
    }

    /**
     * Sliders are mass deleted the same way
     *
     * @return void
     */
    public function testMassDeletesSliders(): void
    {
        $collection = $this->createMock(AbstractDb::class);
        $collection->method('getAllIds')->willReturn([4, 5]);
        $filter = $this->createMock(Filter::class);
        $filter->method('getCollection')->willReturn($collection);
        $repository = $this->createMock(SliderRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('deleteById');

        (new SliderMassDelete($this->actionContext(), $filter, $collection, $repository, new EntityIdReader()))
            ->execute();

        self::assertSame(['success: A total of 2 slider(s) have been deleted.'], $this->messages);
    }
}
