<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Upload;

use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\UploadResultPresenter;
use Hryvinskyi\BannerSliderApi\Api\Value\StoredMedia;
use Hryvinskyi\BannerSliderApi\Api\Value\UploadedFile;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;
use Psr\Log\LoggerInterface;

/**
 * Runs one upload request for the admin uploader endpoints and builds their JSON answer.
 *
 * Success: the stored file (see `UploadResultPresenter`). Failure: `{error, errorcode}`. A refused or unstorable
 * file carries the upload service's own message; any other failure is logged and answered with a generic message, so
 * no internal detail reaches the browser.
 */
class MediaUploadHandler
{
    /**
     * @param UploadedFileReader $fileReader
     * @param UploadResultPresenter $presenter
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly UploadedFileReader $fileReader,
        private readonly UploadResultPresenter $presenter,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Upload the file posted under `$fieldName` through `$upload`
     *
     * @param RequestInterface $request
     * @param string $fieldName
     * @param \Closure(UploadedFile):StoredMedia $upload
     * @return array<string, int|string>
     */
    public function handle(RequestInterface $request, string $fieldName, \Closure $upload): array
    {
        try {
            return $this->presenter->present($upload($this->fileReader->read($request, $fieldName)));
        } catch (ValidationException $exception) {
            return $this->error($this->validationMessage($exception), (int)$exception->getCode());
        } catch (LocalizedException $exception) {
            return $this->error($exception->getMessage(), (int)$exception->getCode());
        } catch (\Exception $exception) {
            $this->logger->error('Banner slider: upload failed.', ['exception' => $exception]);

            return $this->error((string)__('The file could not be uploaded. Please try again.'), 0);
        }
    }

    /**
     * All messages of a validation failure, joined
     *
     * @param ValidationException $exception
     * @return string
     */
    private function validationMessage(ValidationException $exception): string
    {
        $messages = [];
        foreach ($exception->getErrors() as $error) {
            $messages[] = $error->getMessage();
        }

        return $messages === [] ? $exception->getMessage() : implode(' ', $messages);
    }

    /**
     * The uploader's error answer
     *
     * @param string $message
     * @param int $code
     * @return array{error: string, errorcode: int}
     */
    private function error(string $message, int $code): array
    {
        return ['error' => $message, 'errorcode' => $code];
    }
}
