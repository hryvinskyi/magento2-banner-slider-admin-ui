<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\DataProvider\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataInterface;
use Hryvinskyi\BannerSliderApi\Api\Video\VideoPathConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File\Mime;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Converts video path string to array format for UI components.
 */
class PrepareVideoToArray implements PrepareDataInterface
{
    private readonly WriteInterface $mediaDirectory;

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        private readonly File $filesystemIoFile,
        private readonly Mime $mime,
        private readonly VideoPathConfigInterface $videoPathConfig,
        private readonly string $videoKey = 'video_path'
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @inheritDoc
     * @throws LocalizedException When store URL cannot be retrieved
     */
    #[\Override]
    public function execute(array &$data): void
    {
        if (isset($data[$this->videoKey]) && is_string($data[$this->videoKey])) {
            $fileName = $data[$this->videoKey];
            $filePath = $this->videoPathConfig->getBasePath() . '/' . $fileName;
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $filePath;

            if ($this->mediaDirectory->isExist($filePath)) {
                $stat = $this->mediaDirectory->stat($filePath);
                $absolutePath = $this->mediaDirectory->getAbsolutePath($filePath);

                $mimeType = $this->mime->getMimeType($absolutePath);
                $fileInfo = $this->filesystemIoFile->getPathInfo($absolutePath);

                $data[$this->videoKey] = [
                    [
                        'name' => $fileInfo['basename'],
                        'url' => $url,
                        'size' => $stat['size'] ?? 0,
                        'type' => $mimeType,
                        'previewType' => 'video'
                    ]
                ];
            }
        }
    }
}
