<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Presenter;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\StoredMedia;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * The JSON answer of an upload endpoint, in the shape the admin file uploader expects.
 *
 * `{file, name, url, size, type}` plus `width` and `height` for images. `file` is the stored media-relative path the
 * form posts back; `name` is the stored file name, never the name the browser sent.
 */
class UploadResultPresenter
{
    /**
     * @param MediaUrlResolverInterface $mediaUrlResolver
     * @param MediaFieldCodec $mediaField
     */
    public function __construct(
        private readonly MediaUrlResolverInterface $mediaUrlResolver,
        private readonly MediaFieldCodec $mediaField
    ) {
    }

    /**
     * The uploader answer for a stored file
     *
     * @param StoredMedia $media
     * @return array<string, int|string>
     * @throws NoSuchEntityException When the current store cannot be resolved for the file URL
     */
    public function present(StoredMedia $media): array
    {
        $path = $media->getRelativePath();
        $result = [
            'file' => $path,
            'name' => $this->mediaField->fileName($path),
            'url' => $this->mediaUrlResolver->getUrl($path),
            'size' => $media->getSize(),
            'type' => $media->getMimeType(),
        ];
        $dimensions = $media->getDimensions();
        if ($dimensions !== null) {
            $result['width'] = $dimensions->getWidth();
            $result['height'] = $dimensions->getHeight();
        }

        return $result;
    }
}
