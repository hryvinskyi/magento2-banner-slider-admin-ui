<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml\Banner;

use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner\Save;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\BannerFormSubmission;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\PersistableBannerPost;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\RefusedPostStore;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml\BackendActionContext;
use Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake\FakeBanner;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Save::class)]
#[CoversClass(RefusedPostStore::class)]
class SaveTest extends TestCase
{
    use BackendActionContext;

    /**
     * What the persistor was given
     *
     * @var array<mixed>|null
     */
    private ?array $persisted = null;

    /**
     * A saved banner returns to its form on "Save and Continue", and the kept post is cleared
     *
     * @return void
     */
    public function testSavesAndContinues(): void
    {
        $submission = $this->createMock(BannerFormSubmission::class);
        $submission->method('submit')->willReturn((new FakeBanner())->setBannerId(9));
        $persistor = $this->persistor();
        $persistor->expects(self::once())->method('clear')->with('hryvinskyi_banner_slider_banner');

        $this->controller($submission, $persistor, ['back' => 'edit'], ['name' => 'Spring'])->execute();

        self::assertSame(['success: The banner has been saved.'], $this->messages);
        self::assertSame(['*/*/edit', ['banner_id' => 9]], $this->redirectedTo);
    }

    /**
     * Every validation error is shown; the post is kept for a new banner without the browser-encoded images and the
     * crop editor state, and a new banner returns to the "new" form of its slider
     *
     * @return void
     */
    public function testKeepsThePostWithoutEncodedImagesOnValidationErrors(): void
    {
        $submission = $this->createMock(BannerFormSubmission::class);
        $submission->method('submit')->willThrowException(new ValidationException(
            __('Invalid'),
            null,
            0,
            new ValidationResult([__('Enter a valid value for "Name".'), __('Select a crop area for breakpoint 3.')])
        ));
        $post = [
            'banner_id' => '',
            'slider_id' => '4',
            'name' => '',
            'responsive_cropper' => ['breakpoints' => [['breakpoint_id' => 3]]],
            'responsive_crops' => (string)json_encode([[
                'breakpoint_id' => 3,
                'crop' => ['x' => 0, 'y' => 0, 'width' => 5, 'height' => 5],
                'encoded' => [['format' => 'webp', 'data' => 'UklGRiQAAABXRUJQVlA4']],
            ]]),
        ];

        $this->controller($submission, $this->persistor(), [], $post)->execute();

        self::assertSame(
            [
                'error: Enter a valid value for "Name".',
                'error: Select a crop area for breakpoint 3.',
                'notice: The crop images prepared in the browser were not kept. They are prepared again when you save.',
            ],
            $this->messages
        );
        self::assertSame(['*/*/new', ['slider_id' => 4]], $this->redirectedTo);
        self::assertNotNull($this->persisted);
        self::assertArrayHasKey('entity_id', $this->persisted);
        self::assertNull($this->persisted['entity_id']);
        $kept = $this->persisted['post'] ?? null;
        self::assertIsArray($kept);
        self::assertSame('4', $kept['slider_id'] ?? null);
        self::assertArrayNotHasKey('responsive_cropper', $kept);
        self::assertStringNotContainsString('UklGR', (string)json_encode($kept));
        $crops = $kept['responsive_crops'] ?? null;
        self::assertIsString($crops);
        self::assertStringContainsString('"width":5', $crops);
    }

    /**
     * A storage failure shows its own message; anything unexpected shows a generic one; both return to the form
     *
     * @return void
     */
    public function testReportsOtherFailures(): void
    {
        $submission = $this->createMock(BannerFormSubmission::class);
        $submission->method('submit')->willReturnOnConsecutiveCalls(
            self::throwException(new CouldNotSaveException(__('The crop file could not be stored.'))),
            self::throwException(new \RuntimeException('SQLSTATE[HY000]: secret detail'))
        );
        $controller = $this->controller($submission, $this->persistor(), [], ['banner_id' => '9', 'name' => 'A']);

        $controller->execute();
        $controller->execute();

        self::assertSame(
            ['error: The crop file could not be stored.', 'exception: Something went wrong while saving the banner.'],
            $this->messages
        );
        self::assertSame(['*/*/edit', ['banner_id' => 9]], $this->redirectedTo);
        self::assertSame(9, $this->persisted['entity_id'] ?? null, 'The post is kept for the banner it edits.');
    }

    /**
     * An empty post goes back to the grid without saving
     *
     * @return void
     */
    public function testIgnoresAnEmptyPost(): void
    {
        $submission = $this->createMock(BannerFormSubmission::class);
        $submission->expects(self::never())->method('submit');

        $this->controller($submission, $this->persistor())->execute();

        self::assertSame(['*/*/', []], $this->redirectedTo);
    }

    /**
     * The controller on a context answering the given parameters and post
     *
     * @param BannerFormSubmission $submission
     * @param DataPersistorInterface $persistor
     * @param array<string, mixed> $params
     * @param array<mixed> $post
     * @return Save
     */
    private function controller(
        BannerFormSubmission $submission,
        DataPersistorInterface $persistor,
        array $params = [],
        array $post = []
    ): Save {
        return new Save(
            $this->actionContext($params, $post),
            $submission,
            new RefusedPostStore($persistor),
            new PersistableBannerPost(new Json()),
            new EntityIdReader()
        );
    }

    /**
     * A persistor double that records what is kept
     *
     * @return DataPersistorInterface&MockObject
     */
    private function persistor(): MockObject
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->method('set')->willReturnCallback(function (string $key, mixed $data): void {
            $this->persisted = is_array($data) ? $data : null;
        });

        return $persistor;
    }
}
