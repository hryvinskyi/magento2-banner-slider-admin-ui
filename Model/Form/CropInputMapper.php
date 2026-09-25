<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\FieldErrors;
use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\CropInput;
use Hryvinskyi\BannerSliderApi\Api\Value\CropRect;
use Hryvinskyi\BannerSliderApi\Api\Value\EncodedImage;
use Hryvinskyi\BannerSliderApi\Api\Value\FormatRequest;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * The crop editor's `responsive_crops` post (a JSON list) as crop inputs for the banner editor.
 *
 * Each entry: `{breakpoint_id, remove, enabled, source_image|null, crop: {x, y, width, height}|null,
 * formats: [{format, quality}], encoded: [{format, data}]}`.
 * - The crop area is required unless the crop is removed, and its values must be whole pixels.
 * - A requested format must be a registered image format.
 * - `encoded` holds images the browser already encoded, as plain base64. They are optional and untrusted: an entry
 *   of an unknown format, larger than the image upload limit or not strict base64 is dropped (and logged), and the
 *   server generates that format instead. No file name or extension is ever taken from this data.
 * - Crops the post does not mention are left untouched; a post without `responsive_crops` changes no crop.
 */
class CropInputMapper
{
    public const FIELD = 'responsive_crops';

    /**
     * @param SerializerInterface $serializer
     * @param StrictBase64Decoder $base64Decoder
     * @param ImageFormatRegistryInterface $formatRegistry
     * @param ImageConfigInterface $imageConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly StrictBase64Decoder $base64Decoder,
        private readonly ImageFormatRegistryInterface $formatRegistry,
        private readonly ImageConfigInterface $imageConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The posted crop changes
     *
     * @param PostData $post
     * @param FieldErrors $errors Receives a message for every invalid entry
     * @return list<CropInput>
     */
    public function map(PostData $post, FieldErrors $errors): array
    {
        $json = $post->rawText(self::FIELD);
        if ($json === null) {
            return [];
        }

        try {
            $entries = $this->serializer->unserialize($json);
        } catch (\InvalidArgumentException $exception) {
            $this->addUnreadable($errors, $exception->getMessage());

            return [];
        }
        if (!is_array($entries) || !array_is_list($entries)) {
            $this->addUnreadable($errors, 'The responsive crops value is not a JSON list.');

            return [];
        }

        $inputs = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                $this->addUnreadable($errors, 'A responsive crop entry is not a JSON object.');
                continue;
            }
            $input = $this->mapEntry(new PostData($entry), $errors);
            if ($input !== null) {
                $inputs[] = $input;
            }
        }

        return $inputs;
    }

    /**
     * One entry as a crop input, or null (with the error recorded) when it is invalid
     *
     * @param PostData $entry
     * @param FieldErrors $errors
     * @return CropInput|null
     */
    private function mapEntry(PostData $entry, FieldErrors $errors): ?CropInput
    {
        try {
            $breakpointId = $entry->integer('breakpoint_id');
            $remove = $entry->flag('remove', false);
            $enabled = $entry->flag('enabled', true);
        } catch (\InvalidArgumentException $exception) {
            $this->addUnreadable($errors, $exception->getMessage());

            return null;
        }
        if ($breakpointId === null || $breakpointId < 1) {
            $this->addUnreadable($errors, 'A responsive crop entry has no valid breakpoint id.');

            return null;
        }

        $rect = $this->rect($entry, $breakpointId, $remove, $errors);
        $formats = $this->formats($entry, $breakpointId, $errors);
        if ($rect === false || $formats === null) {
            return null;
        }

        $input = null;
        $errors->attempt(
            __('The crop for breakpoint %1 could not be read. Crop the image again and save.', $breakpointId),
            function () use ($entry, $breakpointId, $rect, $formats, $enabled, $remove, &$input): void {
                $input = new CropInput(
                    $breakpointId,
                    $entry->text('source_image'),
                    $rect,
                    $formats,
                    $this->encodedImages($entry, $breakpointId),
                    $enabled,
                    $remove
                );
            }
        );

        return $input;
    }

    /**
     * The crop area of an entry: a rect, null when there is none and the crop is removed, false when invalid
     *
     * @param PostData $entry
     * @param int $breakpointId
     * @param bool $remove
     * @param FieldErrors $errors
     * @return CropRect|false|null
     */
    private function rect(PostData $entry, int $breakpointId, bool $remove, FieldErrors $errors): CropRect|false|null
    {
        $crop = $entry->toArray()['crop'] ?? null;
        if ($crop === null) {
            if ($remove) {
                return null;
            }
            $errors->add(__('Select a crop area for breakpoint %1.', $breakpointId));

            return false;
        }

        $values = is_array($crop) ? $crop : [];
        $pixels = [];
        foreach (['x', 'y', 'width', 'height'] as $key) {
            $value = $values[$key] ?? null;
            if (!is_int($value)) {
                $errors->add(
                    __('The crop area for breakpoint %1 must be given in whole pixels.', $breakpointId),
                    sprintf('Crop value "%s" of breakpoint %d is not an integer.', $key, $breakpointId)
                );

                return false;
            }
            $pixels[$key] = $value;
        }

        $rect = false;
        $errors->attempt(
            __('The crop area for breakpoint %1 is outside the image.', $breakpointId),
            static function () use ($pixels, &$rect): void {
                $rect = new CropRect($pixels['x'], $pixels['y'], $pixels['width'], $pixels['height']);
            }
        );

        return $rect;
    }

    /**
     * The requested extra formats of an entry, or null (with the error recorded) when one is invalid
     *
     * @param PostData $entry
     * @param int $breakpointId
     * @param FieldErrors $errors
     * @return list<FormatRequest>|null
     */
    private function formats(PostData $entry, int $breakpointId, FieldErrors $errors): ?array
    {
        $requests = [];
        foreach ($entry->rows('formats') as $format) {
            $code = $format->text('format') ?? '';
            if (!$this->formatRegistry->has($code)) {
                $errors->add(__('Breakpoint %1 asks for the unknown image format "%2".', $breakpointId, $code));

                return null;
            }
            $valid = $errors->attempt(
                __(
                    'The %1 quality of breakpoint %2 must be a whole number from 1 to 100.',
                    strtoupper($code),
                    $breakpointId
                ),
                static function () use ($format, $code, &$requests): void {
                    $requests[] = new FormatRequest($code, $format->integer('quality') ?? 0);
                }
            );
            if (!$valid) {
                return null;
            }
        }

        return $requests;
    }

    /**
     * The usable browser-encoded images of an entry; unusable ones are logged and left for the server to generate
     *
     * @param PostData $entry
     * @param int $breakpointId
     * @return list<EncodedImage>
     */
    private function encodedImages(PostData $entry, int $breakpointId): array
    {
        $images = [];
        foreach ($entry->rows('encoded') as $encoded) {
            $code = $encoded->text('format') ?? '';
            try {
                $images[] = new EncodedImage($code, $this->decode($code, $encoded->rawText('data') ?? ''));
            } catch (\InvalidArgumentException $exception) {
                $this->logger->info(sprintf(
                    'Banner slider: the browser-encoded "%s" crop image of breakpoint %d is ignored and encoded on'
                    . ' the server instead: %s',
                    $code,
                    $breakpointId,
                    $exception->getMessage()
                ));
            }
        }

        return $images;
    }

    /**
     * The bytes of one browser-encoded image
     *
     * @param string $formatCode
     * @param string $data Plain base64
     * @return string
     * @throws \InvalidArgumentException When the format is unknown, the data is not strict base64 or it is larger
     *     than the image upload limit
     */
    private function decode(string $formatCode, string $data): string
    {
        $limit = $this->imageConfig->getMaxUploadBytes();
        if (!$this->formatRegistry->has($formatCode)) {
            throw new \InvalidArgumentException('Its format is unknown.');
        }
        if ($this->base64Decoder->decodedSizeLimit($data) > $limit + 2) {
            throw new \InvalidArgumentException('It is larger than the image upload limit.');
        }
        $bytes = $this->base64Decoder->decode($data);
        if ($bytes === null) {
            throw new \InvalidArgumentException('It is not strict base64.');
        }
        if (strlen($bytes) > $limit) {
            throw new \InvalidArgumentException('It is larger than the image upload limit.');
        }

        return $bytes;
    }

    /**
     * Record that the crop data could not be read
     *
     * @param FieldErrors $errors
     * @param string $detail
     * @return void
     */
    private function addUnreadable(FieldErrors $errors, string $detail): void
    {
        $errors->add(__('The responsive image data could not be read. Crop the images again and save.'), $detail);
    }
}
