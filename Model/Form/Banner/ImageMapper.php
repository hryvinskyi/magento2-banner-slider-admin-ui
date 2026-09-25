<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form\Banner;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\BannerFormMapperInterface;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;

/**
 * The banner image uploader and its title (alternative text).
 *
 * The image is also the default source every responsive crop is cut from, so only the stored path or a file the
 * image upload returned may be saved (see `MediaFieldCodec`). An empty uploader removes the image: the uploader
 * posts nothing at all when it is empty, so a missing `image` key means "no image". The pixel size is never taken
 * from the form; the banner editor reads it from the file whenever the path changes. A posted file without a stored
 * path (one the uploader did not upload itself) is a field error, never "no image", so it cannot remove the image
 * and the crops cut from it.
 */
class ImageMapper implements BannerFormMapperInterface
{
    public const FIELD = 'image';

    /**
     * @param MediaFieldCodec $mediaField
     * @param ImageFormatRegistryInterface $formatRegistry
     * @param string $uploadFolder Folder the image upload service stores new images in
     */
    public function __construct(
        private readonly MediaFieldCodec $mediaField,
        private readonly ImageFormatRegistryInterface $formatRegistry,
        private readonly string $uploadFolder = 'banner_slider/image'
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        $this->hydrateImage($post, $banner, $errors);

        if ($post->has(BannerInterface::TITLE)) {
            $banner->setTitle($post->text(BannerInterface::TITLE));
        }
    }

    /**
     * Apply the posted image path when it is one the banner may keep; the uploader's own entries only
     *
     * @param PostData $post
     * @param BannerInterface $banner
     * @param FieldErrors $errors
     * @return void
     */
    private function hydrateImage(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        try {
            $path = $this->mediaField->readPath($post, self::FIELD);
        } catch (\InvalidArgumentException $exception) {
            $errors->add(
                __('Choose the file for "%1" with the Upload button.', __('Image')),
                $exception->getMessage()
            );

            return;
        }

        if ($path !== null && !$this->mediaField->isAcceptable($path, $banner->getImage(), $this->uploadFolder)) {
            $errors->add(
                __('Upload the banner image through the image field.'),
                sprintf('Refused banner image path "%s".', $path)
            );

            return;
        }

        $errors->attempt(
            __('Upload the banner image through the image field.'),
            static fn () => $banner->setImage($path)
        );
    }

    /**
     * @inheritDoc
     */
    public function export(BannerInterface $banner): array
    {
        $path = $banner->getImage();

        return [
            self::FIELD => $this->mediaField->export($path, $this->mimeType($path)),
            BannerInterface::TITLE => $banner->getTitle() ?? '',
        ];
    }

    /**
     * MIME type of a stored image from its extension, `image/*` when unknown
     *
     * @param string|null $path
     * @return string
     */
    private function mimeType(?string $path): string
    {
        $dot = $path === null ? false : strrpos($path, '.');
        $format = $path === null || $dot === false
            ? null
            : $this->formatRegistry->getByExtension(substr($path, $dot + 1));

        return $format?->getMimeType() ?? 'image/*';
    }
}
