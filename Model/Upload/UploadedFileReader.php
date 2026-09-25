<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Upload;

use Hryvinskyi\BannerSliderApi\Api\Value\UploadedFile;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;

/**
 * Detaches one uploaded file of an HTTP request into the upload services' `UploadedFile`.
 *
 * A missing or malformed file entry becomes a failed upload (`UPLOAD_ERR_NO_FILE`), which the upload service
 * reports with its own message.
 */
class UploadedFileReader
{
    /**
     * The file posted under a form field name
     *
     * @param RequestInterface $request
     * @param string $fieldName
     * @return UploadedFile
     */
    public function read(RequestInterface $request, string $fieldName): UploadedFile
    {
        $file = $request instanceof HttpRequest ? $request->getFiles($fieldName) : null;
        if (!is_array($file)) {
            return $this->missing();
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        $clientName = $file['name'] ?? '';
        $size = $file['size'] ?? 0;
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if (!is_string($temporaryPath) || !is_string($clientName) || !is_numeric($size) || !is_numeric($error)) {
            return $this->missing();
        }

        $errorCode = max(0, (int)$error);
        if ($errorCode === UPLOAD_ERR_OK && $temporaryPath === '') {
            return $this->missing();
        }

        return new UploadedFile($temporaryPath, $clientName, max(0, (int)$size), $errorCode, true);
    }

    /**
     * A failed upload that received no file
     *
     * @return UploadedFile
     */
    private function missing(): UploadedFile
    {
        return new UploadedFile('', '', 0, UPLOAD_ERR_NO_FILE, true);
    }
}
