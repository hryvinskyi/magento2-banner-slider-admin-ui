<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml\Slider;

use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider\Save;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\SliderFormSubmission;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml\BackendActionContext;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeSlider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Save::class)]
class SaveTest extends TestCase
{
    use BackendActionContext;

    /**
     * A saved slider returns to the grid
     *
     * @return void
     */
    public function testSavesAndReturnsToTheGrid(): void
    {
        $submission = $this->createMock(SliderFormSubmission::class);
        $submission->method('submit')->willReturn((new FakeSlider())->setSliderId(3));

        $this->controller($submission, $this->createMock(DataPersistorInterface::class), [], ['name' => 'Home'])
            ->execute();

        self::assertSame(['success: The slider has been saved.'], $this->messages);
        self::assertSame(['*/*/', []], $this->redirectedTo);
    }

    /**
     * Validation errors are all shown, the post is kept and the admin returns to the slider's form
     *
     * @return void
     */
    public function testKeepsThePostOnValidationErrors(): void
    {
        $submission = $this->createMock(SliderFormSubmission::class);
        $submission->method('submit')->willThrowException(new ValidationException(
            __('Invalid'),
            null,
            0,
            new ValidationResult([__('Breakpoint "desktop" is used twice.')])
        ));
        $persistor = $this->createMock(DataPersistorInterface::class);
        $post = ['slider_id' => '3', 'name' => 'Home'];
        $persistor->expects(self::once())->method('set')
            ->with('hryvinskyi_banner_slider_slider', ['entity_id' => 3, 'post' => $post]);

        $this->controller($submission, $persistor, [], $post)->execute();

        self::assertSame(['error: Breakpoint "desktop" is used twice.'], $this->messages);
        self::assertSame(['*/*/edit', ['slider_id' => 3]], $this->redirectedTo);
    }

    /**
     * A new slider that fails returns to the "new" form, not to an edit page without id
     *
     * @return void
     */
    public function testNewSliderReturnsToTheNewForm(): void
    {
        $submission = $this->createMock(SliderFormSubmission::class);
        $submission->method('submit')->willThrowException(new \LogicException('boom'));

        $this->controller($submission, $this->createMock(DataPersistorInterface::class), [], ['name' => 'X'])
            ->execute();

        self::assertSame(['exception: Something went wrong while saving the slider.'], $this->messages);
        self::assertSame(['*/*/new', []], $this->redirectedTo);
    }

    /**
     * The controller on a context answering the given parameters and post
     *
     * @param SliderFormSubmission $submission
     * @param DataPersistorInterface $persistor
     * @param array<string, mixed> $params
     * @param array<mixed> $post
     * @return Save
     */
    private function controller(
        SliderFormSubmission $submission,
        DataPersistorInterface $persistor,
        array $params,
        array $post
    ): Save {
        return new Save(
            $this->actionContext($params, $post),
            $submission,
            new RefusedPostStore($persistor),
            new EntityIdReader()
        );
    }
}
