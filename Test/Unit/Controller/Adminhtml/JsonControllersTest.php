<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Controller\Adminhtml;

use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Breakpoint\ImageUpload;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Image\Upload as ImageUploadController;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop\Breakpoints;
use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Video\Upload as VideoUploadController;
use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderAdminUi\Model\Upload\MediaUploadHandler;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\ImageUploadInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\VideoUploadInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Breakpoints::class)]
#[CoversClass(ImageUploadController::class)]
#[CoversClass(ImageUpload::class)]
#[CoversClass(VideoUploadController::class)]
class JsonControllersTest extends TestCase
{
    use BackendActionContext;

    /**
     * Data set on the JSON result
     *
     * @var mixed
     */
    private mixed $json = null;

    /**
     * The enabled breakpoints of the requested slider, through the shared presenter; nothing without a slider
     *
     * @return void
     */
    public function testAnswersBreakpoints(): void
    {
        $breakpoint = $this->createConfiguredMock(BreakpointInterface::class, [
            'getBreakpointId' => 1,
            'getName' => 'Mobile',
            'getIdentifier' => 'mobile',
            'getMediaQuery' => '(max-width: 767px)',
            'getMinWidth' => 0,
            'getTargetWidth' => 767,
            'getTargetHeight' => 500,
            'getSortOrder' => 40,
            'isEnabled' => true,
        ]);
        $repository = $this->createMock(BreakpointRepositoryInterface::class);
        $repository->expects(self::once())->method('getBySliderId')->with(3, true)->willReturn([$breakpoint]);

        (new Breakpoints(
            $this->actionContext(['slider_id' => '3']),
            $this->jsonFactory(),
            $repository,
            new BreakpointPresenter(),
            new EntityIdReader()
        ))->execute();
        self::assertSame(['breakpoints' => [(new BreakpointPresenter())->present($breakpoint)]], $this->json);

        (new Breakpoints(
            $this->actionContext(),
            $this->jsonFactory(),
            $repository,
            new BreakpointPresenter(),
            new EntityIdReader()
        ))->execute();
        self::assertSame(['breakpoints' => []], $this->json);
    }

    /**
     * Each upload endpoint hands its field and its upload service to the handler and answers what it returns
     *
     * @return void
     */
    public function testUploadEndpointsUseTheirFields(): void
    {
        $fields = [];
        $handler = $this->createMock(MediaUploadHandler::class);
        $handler->method('handle')->willReturnCallback(
            static function (mixed $request, string $field) use (&$fields): array {
                $fields[] = $field;

                return ['file' => 'banner_slider/x/' . $field];
            }
        );
        $image = $this->createMock(ImageUploadInterface::class);
        $video = $this->createMock(VideoUploadInterface::class);

        (new ImageUploadController($this->actionContext(), $this->jsonFactory(), $handler, $image))->execute();
        (new ImageUploadController(
            $this->actionContext(['param_name' => 'image_alt']),
            $this->jsonFactory(),
            $handler,
            $image
        ))->execute();
        (new ImageUpload($this->actionContext(), $this->jsonFactory(), $handler, $image))->execute();
        (new VideoUploadController($this->actionContext(), $this->jsonFactory(), $handler, $video))->execute();

        self::assertSame(['image', 'image_alt', 'breakpoint_image', 'video_path'], $fields);
        self::assertSame(['file' => 'banner_slider/x/video_path'], $this->json);
    }

    /**
     * A JSON result factory whose results record their data
     *
     * @return JsonFactory
     */
    private function jsonFactory(): JsonFactory
    {
        $json = $this->createMock(Json::class);
        $json->method('setData')->willReturnCallback(function (mixed $data) use ($json): Json {
            $this->json = $data;

            return $json;
        });
        $factory = $this->createMock(JsonFactory::class);
        $factory->method('create')->willReturn($json);

        return $factory;
    }
}
