<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\DataProvider;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\GeneralMapper as BannerGeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\GeneralMapper as SliderGeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\NestedValue;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\AbstractEntityFormDataProvider;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner\FormDataProvider as BannerFormDataProvider;
use Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Slider\FormDataProvider as SliderFormDataProvider;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractEntityFormDataProvider::class)]
#[CoversClass(BannerFormDataProvider::class)]
#[CoversClass(SliderFormDataProvider::class)]
class FormDataProvidersTest extends TestCase
{
    use NestedValue;

    /**
     * The records the modifier pool was given, in call order
     *
     * @var list<array<mixed>>
     */
    private array $modifierSaw = [];

    /**
     * Whether the kept post was cleared
     *
     * @var bool
     */
    private bool $cleared = false;

    /**
     * The id filter selects the banner, which is exported by the hydrator, keyed by its id, then modified
     *
     * @return void
     */
    public function testLoadsAStoredBannerThroughTheRepository(): void
    {
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->expects(self::once())->method('getById')->with(5)->willReturn(
            (new FakeBanner())->setName('Spring')->setSliderId(2)
        );
        $provider = $this->bannerProvider($repository, $this->refusedPosts(null));

        $provider->addFilter($this->filter('entity_id', 'ignored'));
        $provider->addFilter($this->filter('banner_id', '5'));
        $data = $provider->getData();

        self::assertSame(['5'], array_map('strval', array_keys($data)));
        self::assertSame('5', $data[5]['banner_id'] ?? null);
        self::assertSame('Spring', $data[5]['name'] ?? null);
        self::assertSame('2', $data[5]['slider_id'] ?? null);
        self::assertTrue($data[5]['modified'] ?? null);
        self::assertSame($data, $provider->getData());
    }

    /**
     * A new banner starts in the slider given by the request, under the empty key
     *
     * @return void
     */
    public function testStartsANewBannerInTheRequestedSlider(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('slider_id')->willReturn('4');
        $provider = $this->bannerProvider(
            $this->createMock(BannerRepositoryInterface::class),
            $this->refusedPosts(null),
            $request
        );

        $provider->addFilter($this->filter('banner_id', ''));

        self::assertSame(['' => ['slider_id' => '4', 'modified' => true]], $provider->getData());
    }

    /**
     * A banner that no longer exists yields no record
     *
     * @return void
     */
    public function testYieldsNothingForAMissingBanner(): void
    {
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('Gone')));
        $provider = $this->bannerProvider($repository, $this->refusedPosts(null));

        $provider->addFilter($this->filter('banner_id', '9'));

        self::assertSame([], $provider->getData());
    }

    /**
     * A refused post of the same banner is restored before the modifiers run, so they build on the submitted
     * slider, and the kept post is cleared
     *
     * @return void
     */
    public function testRestoresTheRefusedPostBeforeTheModifiers(): void
    {
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->method('getById')->willReturn((new FakeBanner())->setName('Stored')->setSliderId(2));
        $provider = $this->bannerProvider(
            $repository,
            $this->refusedPosts(['entity_id' => 5, 'post' => ['banner_id' => '5', 'slider_id' => '8', 'name' => '']])
        );

        $provider->addFilter($this->filter('banner_id', '5'));
        $record = $provider->getData()[5] ?? [];

        self::assertSame('8', $this->nested($this->modifierSaw, 0, 5, 'slider_id'));
        self::assertSame('', $record['name'] ?? null);
        self::assertSame('5', $record['banner_id'] ?? null);
        self::assertTrue($this->cleared);
    }

    /**
     * A refused post of a new banner fills the new banner's form
     *
     * @return void
     */
    public function testRestoresTheRefusedPostOfANewBanner(): void
    {
        $factory = $this->createMock(BannerInterfaceFactory::class);
        $factory->expects(self::once())->method('create')->willReturn(new FakeBanner());
        $provider = $this->bannerProvider(
            $this->createMock(BannerRepositoryInterface::class),
            $this->refusedPosts(['entity_id' => null, 'post' => ['slider_id' => '3', 'name' => 'Draft']]),
            null,
            $factory
        );

        $provider->addFilter($this->filter('banner_id', ''));
        $record = $provider->getData()[''] ?? [];

        self::assertSame('3', $record['slider_id'] ?? null);
        self::assertSame('Draft', $record['name'] ?? null);
    }

    /**
     * A post kept for another slider is not shown on this one, and is cleared
     *
     * @return void
     */
    public function testIgnoresAPostKeptForAnotherSlider(): void
    {
        $repository = $this->createMock(SliderRepositoryInterface::class);
        $repository->method('getById')->willReturn((new FakeSlider())->setName('Stored')->setPriority(3));
        $provider = $this->sliderProvider(
            $repository,
            $this->refusedPosts(['entity_id' => 6, 'post' => ['slider_id' => '6', 'name' => 'Other']])
        );

        $provider->addFilter($this->filter('slider_id', '7'));
        $record = $provider->getData()[7] ?? [];

        self::assertSame('Stored', $record['name'] ?? null);
        self::assertSame('3', $record['priority'] ?? null);
        self::assertTrue($this->cleared);
        self::assertSame(['modified_meta' => true], $provider->getMeta());
    }

    /**
     * A refused post of the same slider is shown as entered; the modifiers see it
     *
     * @return void
     */
    public function testRestoresTheRefusedPostOfTheSlider(): void
    {
        $repository = $this->createMock(SliderRepositoryInterface::class);
        $repository->method('getById')->willReturn((new FakeSlider())->setName('Stored')->setPriority(3));
        $provider = $this->sliderProvider(
            $repository,
            $this->refusedPosts(['entity_id' => 7, 'post' => ['slider_id' => '7', 'name' => 'Typed']])
        );

        $provider->addFilter($this->filter('slider_id', '7'));
        $record = $provider->getData()[7] ?? [];

        self::assertSame('Typed', $record['name'] ?? null);
        self::assertSame('Typed', $this->nested($this->modifierSaw, 0, 7, 'name'));
        self::assertSame('3', $record['priority'] ?? null);
    }

    /**
     * The banner provider with a real hydrator
     *
     * @param BannerRepositoryInterface $repository
     * @param RefusedPostStore $refusedPosts
     * @param RequestInterface|null $request
     * @param BannerInterfaceFactory|null $factory
     * @return BannerFormDataProvider
     */
    private function bannerProvider(
        BannerRepositoryInterface $repository,
        RefusedPostStore $refusedPosts,
        ?RequestInterface $request = null,
        ?BannerInterfaceFactory $factory = null
    ): BannerFormDataProvider {
        return new BannerFormDataProvider(
            'banner_form',
            'banner_id',
            'banner_id',
            $refusedPosts,
            $this->pool(),
            new EntityIdReader(),
            $repository,
            $factory ?? $this->createMock(BannerInterfaceFactory::class),
            new BannerFormHydrator(['general' => new BannerGeneralMapper()]),
            $request ?? $this->createMock(RequestInterface::class)
        );
    }

    /**
     * The slider provider with a real hydrator
     *
     * @param SliderRepositoryInterface $repository
     * @param RefusedPostStore $refusedPosts
     * @return SliderFormDataProvider
     */
    private function sliderProvider(
        SliderRepositoryInterface $repository,
        RefusedPostStore $refusedPosts
    ): SliderFormDataProvider {
        return new SliderFormDataProvider(
            'slider_form',
            'slider_id',
            'slider_id',
            $refusedPosts,
            $this->pool(),
            new EntityIdReader(),
            $repository,
            $this->createMock(SliderInterfaceFactory::class),
            new SliderFormHydrator(['general' => new SliderGeneralMapper()])
        );
    }

    /**
     * A modifier pool whose modifier records what it was given and marks every record and the meta
     *
     * @return PoolInterface
     */
    private function pool(): PoolInterface
    {
        $modifier = $this->createMock(ModifierInterface::class);
        $modifier->method('modifyData')->willReturnCallback(function (array $data): array {
            $this->modifierSaw[] = $data;
            foreach ($data as $key => $record) {
                $data[$key] = (is_array($record) ? $record : []) + ['modified' => true];
            }

            return $data;
        });
        $modifier->method('modifyMeta')->willReturnCallback(
            static fn (array $meta): array => $meta + ['modified_meta' => true]
        );
        $pool = $this->createMock(PoolInterface::class);
        $pool->method('getModifiersInstances')->willReturn([$modifier]);

        return $pool;
    }

    /**
     * A refused post store whose session holds the given value
     *
     * @param array<mixed>|null $kept
     * @return RefusedPostStore
     */
    private function refusedPosts(?array $kept): RefusedPostStore
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->method('get')->willReturn($kept);
        $persistor->method('clear')->willReturnCallback(function (): void {
            $this->cleared = true;
        });

        return new RefusedPostStore($persistor);
    }

    /**
     * A filter on one field
     *
     * @param string $field
     * @param string $value
     * @return Filter
     */
    private function filter(string $field, string $value): Filter
    {
        $filter = new Filter();
        $filter->setField($field);
        $filter->setValue($value);

        return $filter;
    }
}
