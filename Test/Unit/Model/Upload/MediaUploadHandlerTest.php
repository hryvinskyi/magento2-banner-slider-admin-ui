<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Model\Upload;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\UploadResultPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Upload\MediaUploadHandler;
use Hryvinskyi\BannerSliderAdminUi\Model\Upload\UploadedFileReader;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\Dimensions;
use Hryvinskyi\BannerSliderApi\Api\Value\StoredMedia;
use Hryvinskyi\BannerSliderApi\Api\Value\UploadedFile;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Validation\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(MediaUploadHandler::class)]
#[CoversClass(UploadedFileReader::class)]
#[CoversClass(UploadResultPresenter::class)]
class MediaUploadHandlerTest extends TestCase
{
    /**
     * Messages logged as errors
     *
     * @var list<string>
     */
    private array $logged = [];

    /**
     * @var MediaUploadHandler
     */
    private MediaUploadHandler $handler;

    /**
     * @var HttpRequest
     */
    private HttpRequest $request;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $resolver = $this->createMock(MediaUrlResolverInterface::class);
        $resolver->method('getUrl')->willReturnCallback(static fn (string $path): string => 'https://m.test/' . $path);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function (string|\Stringable $message): void {
            $this->logged[] = (string)$message;
        });
        $this->handler = new MediaUploadHandler(
            new UploadedFileReader(),
            new UploadResultPresenter($resolver, new MediaFieldCodec($resolver)),
            $logger
        );
        $request = $this->createMock(HttpRequest::class);
        $request->method('getFiles')->willReturnCallback(static fn (string $name): mixed => match ($name) {
            'image' => [
                'name' => 'holiday photo.JPG',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => UPLOAD_ERR_OK,
                'size' => 2048,
            ],
            'broken' => 'not an array',
            default => null,
        });
        $this->request = $request;
    }

    /**
     * A stored upload answers the uploader JSON with the stored name, never the client's
     *
     * @return void
     */
    public function testAnswersTheStoredFile(): void
    {
        $received = null;

        $upload = static function (UploadedFile $file) use (&$received): StoredMedia {
            $received = $file;

            return new StoredMedia('banner_slider/image/9f86d0.jpg', new Dimensions(1200, 600), 'image/jpeg', 2048);
        };

        $result = $this->handler->handle($this->request, 'image', $upload);

        self::assertSame(
            [
                'file' => 'banner_slider/image/9f86d0.jpg',
                'name' => '9f86d0.jpg',
                'url' => 'https://m.test/banner_slider/image/9f86d0.jpg',
                'size' => 2048,
                'type' => 'image/jpeg',
                'width' => 1200,
                'height' => 600,
            ],
            $result
        );
        self::assertInstanceOf(UploadedFile::class, $received);
        self::assertSame('/tmp/php123', $received->getTemporaryPath());
        self::assertTrue($received->isHttpUpload());
    }

    /**
     * A missing or malformed file reaches the service as a failed upload
     *
     * @return void
     */
    public function testPassesAMissingFileAsFailedUpload(): void
    {
        $codes = [];
        $upload = static function (UploadedFile $file) use (&$codes): StoredMedia {
            $codes[] = $file->getErrorCode();

            throw new ValidationException(__('No file was uploaded.'));
        };

        $this->handler->handle($this->request, 'missing', $upload);
        $this->handler->handle($this->request, 'broken', $upload);

        self::assertSame([UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_FILE], $codes);
    }

    /**
     * Refusals and storage failures carry the service's message; anything else is logged and answered generically
     *
     * @return void
     */
    public function testAnswersErrors(): void
    {
        $refused = $this->handler->handle($this->request, 'image', static function (): StoredMedia {
            throw new ValidationException(
                __('Invalid upload.'),
                null,
                0,
                new ValidationResult([__('The file is not a supported image.')])
            );
        });
        $notStored = $this->handler->handle($this->request, 'image', static function (): StoredMedia {
            throw new CouldNotSaveException(__('The image could not be stored.'));
        });
        $crashed = $this->handler->handle($this->request, 'image', static function (): StoredMedia {
            throw new \RuntimeException('/var/www/html/pub/media is read-only');
        });

        self::assertSame(['error' => 'The file is not a supported image.', 'errorcode' => 0], $refused);
        self::assertSame(['error' => 'The image could not be stored.', 'errorcode' => 0], $notStored);
        self::assertSame(['error' => 'The file could not be uploaded. Please try again.', 'errorcode' => 0], $crashed);
        self::assertSame(['Banner slider: upload failed.'], $this->logged);
    }
}
