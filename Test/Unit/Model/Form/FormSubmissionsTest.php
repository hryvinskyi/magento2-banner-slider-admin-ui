<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner\GeneralMapper as BannerGeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormSubmission;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BreakpointInputMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\CropInputMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\Slider\GeneralMapper as SliderGeneralMapper;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormHydrator;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormSubmission;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\StrictBase64Decoder;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Hryvinskyi\BannerSliderApi\Api\Banner\BannerEditorInterface;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterfaceFactory;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Slider\SliderEditorInterface;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\BreakpointInput;
use Hryvinskyi\BannerSliderApi\Api\Value\CropInput;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Validation\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(BannerFormSubmission::class)]
#[CoversClass(SliderFormSubmission::class)]
class FormSubmissionsTest extends TestCase
{
    /**
     * A stored banner is loaded, updated from the post and saved with its crops through the editor
     *
     * @return void
     */
    public function testSavesAStoredBannerWithItsCrops(): void
    {
        $stored = (new FakeBanner())->setBannerId(12)->setName('Old');
        $repository = $this->createMock(BannerRepositoryInterface::class);
        $repository->method('getById')->with(12)->willReturn($stored);
        $saved = [];
        $editor = $this->createMock(BannerEditorInterface::class);
        $editor->expects(self::once())->method('save')->willReturnCallback(
            static function (BannerInterface $banner, array $crops) use (&$saved): BannerInterface {
                $saved = [$banner, $crops];

                return $banner;
            }
        );

        $result = $this->bannerSubmission($repository, $editor)->submit([
            'banner_id' => '12',
            'name' => 'New',
            'responsive_crops' => '[{"breakpoint_id":3,"remove":true}]',
        ]);

        self::assertSame($stored, $result);
        self::assertSame('New', $stored->getName());
        self::assertSame($stored, $saved[0] ?? null);
        $crops = $saved[1] ?? [];
        self::assertIsArray($crops);
        self::assertCount(1, $crops);
        self::assertInstanceOf(CropInput::class, $crops[0]);
        self::assertTrue($crops[0]->shouldRemove());
    }

    /**
     * Field errors of the banner and of its crops arrive together, and nothing is saved
     *
     * @return void
     */
    public function testReportsEveryBannerErrorBeforeSaving(): void
    {
        $editor = $this->createMock(BannerEditorInterface::class);
        $editor->expects(self::never())->method('save');

        try {
            $this->bannerSubmission($this->createMock(BannerRepositoryInterface::class), $editor)->submit([
                'name' => '',
                'responsive_crops' => '[{"breakpoint_id":3}]',
            ]);
            self::fail('A validation exception was expected.');
        } catch (ValidationException $exception) {
            self::assertSame(
                ['Enter a valid value for "Name".', 'Select a crop area for breakpoint 3.'],
                array_map(static fn (\Exception $error): string => $error->getMessage(), $exception->getErrors())
            );
        }
    }

    /**
     * A banner id that is not a number is refused
     *
     * @return void
     */
    public function testRefusesAMalformedBannerId(): void
    {
        $this->expectException(LocalizedException::class);

        $this->bannerSubmission(
            $this->createMock(BannerRepositoryInterface::class),
            $this->createMock(BannerEditorInterface::class)
        )->submit(['banner_id' => 'abc']);
    }

    /**
     * A new slider is created, updated from the post and saved with the posted breakpoint set
     *
     * @return void
     */
    public function testSavesANewSliderWithItsBreakpoints(): void
    {
        $slider = new FakeSlider();
        $factory = $this->createMock(SliderInterfaceFactory::class);
        $factory->method('create')->willReturn($slider);
        $breakpoints = [];
        $editor = $this->createMock(SliderEditorInterface::class);
        $editor->expects(self::once())->method('save')->willReturnCallback(
            static function (SliderInterface $saved, array $inputs) use (&$breakpoints): SliderInterface {
                $breakpoints = $inputs;

                return $saved;
            }
        );

        $result = $this->sliderSubmission($factory, $editor)->submit([
            'slider_id' => '',
            'name' => 'Home',
            'breakpoints_submitted' => '1',
            'breakpoints' => ['breakpoints_container' => [[
                'name' => 'Desktop',
                'identifier' => 'desktop',
                'media_query' => '(min-width: 1200px)',
                'min_width' => '1200',
                'target_width' => '1920',
            ]]],
        ]);

        self::assertSame($slider, $result);
        self::assertSame('Home', $slider->getName());
        self::assertCount(1, $breakpoints);
        self::assertInstanceOf(BreakpointInput::class, $breakpoints[0]);
    }

    /**
     * Slider and breakpoint errors arrive together, and nothing is saved
     *
     * @return void
     */
    public function testReportsEverySliderErrorBeforeSaving(): void
    {
        $factory = $this->createMock(SliderInterfaceFactory::class);
        $factory->method('create')->willReturn(new FakeSlider());
        $editor = $this->createMock(SliderEditorInterface::class);
        $editor->expects(self::never())->method('save');

        try {
            $this->sliderSubmission($factory, $editor)->submit([
                'name' => '',
                'breakpoints_submitted' => '1',
                'breakpoints' => ['breakpoints_container' => [['name' => 'X', 'identifier' => 'Bad Id']]],
            ]);
            self::fail('A validation exception was expected.');
        } catch (ValidationException $exception) {
            self::assertCount(2, $exception->getErrors());
        }
    }

    /**
     * The banner submission with real mappers and doubles for the services
     *
     * @param BannerRepositoryInterface $repository
     * @param BannerEditorInterface $editor
     * @return BannerFormSubmission
     */
    private function bannerSubmission(
        BannerRepositoryInterface $repository,
        BannerEditorInterface $editor
    ): BannerFormSubmission {
        $factory = $this->createMock(BannerInterfaceFactory::class);
        $factory->method('create')->willReturn(new FakeBanner());
        $registry = $this->createMock(ImageFormatRegistryInterface::class);
        $registry->method('has')->willReturn(true);

        return new BannerFormSubmission(
            $repository,
            $factory,
            new BannerFormHydrator(['general' => new BannerGeneralMapper()]),
            new CropInputMapper(
                new Json(),
                new StrictBase64Decoder(),
                $registry,
                $this->createMock(ImageConfigInterface::class),
                $this->createMock(LoggerInterface::class)
            ),
            $editor,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * The slider submission with real mappers and doubles for the services
     *
     * @param SliderInterfaceFactory $factory
     * @param SliderEditorInterface $editor
     * @return SliderFormSubmission
     */
    private function sliderSubmission(
        SliderInterfaceFactory $factory,
        SliderEditorInterface $editor
    ): SliderFormSubmission {
        return new SliderFormSubmission(
            $this->createMock(SliderRepositoryInterface::class),
            $factory,
            new SliderFormHydrator(['general' => new SliderGeneralMapper()]),
            new BreakpointInputMapper($this->createMock(BreakpointRepositoryInterface::class)),
            $editor,
            $this->createMock(LoggerInterface::class)
        );
    }
}
