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
use Hryvinskyi\BannerSliderAdminUi\Model\Form\AspectRatioPresets;
use Hryvinskyi\BannerSliderAdminUi\Model\Form\MediaFieldCodec;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\AspectRatioParser;

/**
 * Video URL, uploaded video file, aspect ratio and background mode of a banner.
 *
 * - The uploaded file follows the same rule as the image: only the stored path or a clean path inside the video
 *   upload folder is saved, and an empty uploader removes the file. A posted file without a stored path is a field
 *   error, never "no file".
 * - The aspect ratio is a preset (`video_aspect_ratio` = `W:H`) or, with the select on "custom", the text of
 *   `video_custom_aspect_ratio`; both are parsed by the API's aspect ratio parser, so the rule lives in one place.
 */
class VideoMapper implements BannerFormMapperInterface
{
    public const PATH_FIELD = 'video_path';
    public const CUSTOM_RATIO_FIELD = 'video_custom_aspect_ratio';

    /**
     * @param MediaFieldCodec $mediaField
     * @param AspectRatioPresets $presets
     * @param AspectRatioParser $aspectRatioParser
     * @param string $uploadFolder Folder the video upload service stores new videos in
     */
    public function __construct(
        private readonly MediaFieldCodec $mediaField,
        private readonly AspectRatioPresets $presets,
        private readonly AspectRatioParser $aspectRatioParser,
        private readonly string $uploadFolder = 'banner_slider/video'
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        if ($post->has(BannerInterface::VIDEO_URL)) {
            $errors->attempt(
                __('Enter a video URL that starts with http:// or https://.'),
                static fn () => $banner->setVideoUrl($post->text(BannerInterface::VIDEO_URL))
            );
        }

        $this->hydrateVideoPath($post, $banner, $errors);
        $this->hydrateAspectRatio($post, $banner, $errors);

        if ($post->has(BannerInterface::VIDEO_AS_BACKGROUND)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Video as Background')),
                static fn () => $banner->setVideoAsBackground($post->flag(BannerInterface::VIDEO_AS_BACKGROUND, false))
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function export(BannerInterface $banner): array
    {
        $path = $banner->getVideoPath();
        $ratio = $banner->getVideoAspectRatio()->toString();
        $isPreset = $this->presets->isPreset($ratio);

        return [
            BannerInterface::VIDEO_URL => $banner->getVideoUrl() ?? '',
            self::PATH_FIELD => $this->mediaField->export($path, $this->mimeType($path)),
            BannerInterface::VIDEO_ASPECT_RATIO => $isPreset ? $ratio : AspectRatioPresets::CUSTOM_CHOICE,
            self::CUSTOM_RATIO_FIELD => $isPreset ? '' : $ratio,
            BannerInterface::VIDEO_AS_BACKGROUND => $banner->isVideoAsBackground() ? '1' : '0',
        ];
    }

    /**
     * Apply the posted video file path when it is one the banner may keep; the uploader's own entries only
     *
     * @param PostData $post
     * @param BannerInterface $banner
     * @param FieldErrors $errors
     * @return void
     */
    private function hydrateVideoPath(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        try {
            $path = $this->mediaField->readPath($post, self::PATH_FIELD);
        } catch (\InvalidArgumentException $exception) {
            $errors->add(
                __('Choose the file for "%1" with the Upload button.', __('Local Video File')),
                $exception->getMessage()
            );

            return;
        }

        if ($path !== null && !$this->mediaField->isAcceptable($path, $banner->getVideoPath(), $this->uploadFolder)) {
            $errors->add(
                __('Upload the video file through the video field.'),
                sprintf('Refused banner video path "%s".', $path)
            );

            return;
        }

        $errors->attempt(
            __('Upload the video file through the video field.'),
            static fn () => $banner->setVideoPath($path)
        );
    }

    /**
     * Apply the preset or custom aspect ratio, when the form posted a choice
     *
     * @param PostData $post
     * @param BannerInterface $banner
     * @param FieldErrors $errors
     * @return void
     */
    private function hydrateAspectRatio(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        $choice = $post->text(BannerInterface::VIDEO_ASPECT_RATIO);
        if ($choice === null) {
            return;
        }
        $ratio = $choice === AspectRatioPresets::CUSTOM_CHOICE ? $post->text(self::CUSTOM_RATIO_FIELD) ?? '' : $choice;

        $errors->attempt(
            __('Enter the aspect ratio as width:height in whole numbers from 1 to 100, for example 3:2.'),
            fn () => $banner->setVideoAspectRatio($this->aspectRatioParser->parse($ratio))
        );
    }

    /**
     * MIME type the uploader previews a stored video as, from its extension
     *
     * @param string|null $path
     * @return string
     */
    private function mimeType(?string $path): string
    {
        $dot = $path === null ? false : strrpos($path, '.');

        return $path === null || $dot === false ? 'video/*' : 'video/' . strtolower(substr($path, $dot + 1));
    }
}
