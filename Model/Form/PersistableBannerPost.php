<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * A failed banner post, reduced to what may be kept in the admin session for the form to show again.
 *
 * The browser-encoded crop images (`encoded` in each `responsive_crops` entry) are base64 and can be megabytes, far
 * too much for the session, so they are dropped. The rest of each crop (area, formats, source) is kept, so the
 * admin does not have to crop again; the images are encoded again on the next save.
 *
 * The crop editor's state (`responsive_cropper`) is dropped too: the form rebuilds it for the slider of the post, so
 * a refused save that also changed the slider shows that slider's breakpoints.
 */
class PersistableBannerPost
{
    private const ENCODED = 'encoded';
    private const CROP_EDITOR_STATE = 'responsive_cropper';

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * The post without browser-encoded crop images and without the crop editor's state
     *
     * @param array<mixed> $post
     * @return array<mixed>
     */
    public function reduce(array $post): array
    {
        unset($post[self::CROP_EDITOR_STATE]);
        $entries = $this->cropEntries($post);
        if ($entries === null) {
            unset($post[CropInputMapper::FIELD]);

            return $post;
        }

        $kept = [];
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                unset($entry[self::ENCODED]);
            }
            $kept[] = $entry;
        }
        $post[CropInputMapper::FIELD] = $this->serializer->serialize($kept);

        return $post;
    }

    /**
     * Whether the post carried any browser-encoded crop image
     *
     * @param array<mixed> $post
     * @return bool
     */
    public function hasEncodedImages(array $post): bool
    {
        foreach ($this->cropEntries($post) ?? [] as $entry) {
            if (is_array($entry) && is_array($entry[self::ENCODED] ?? null) && $entry[self::ENCODED] !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * The posted crop entries, or null when there are none or they cannot be read
     *
     * @param array<mixed> $post
     * @return list<mixed>|null
     */
    private function cropEntries(array $post): ?array
    {
        $json = $post[CropInputMapper::FIELD] ?? null;
        if (!is_string($json) || $json === '') {
            return null;
        }

        try {
            $entries = $this->serializer->unserialize($json);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return is_array($entries) && array_is_list($entries) ? $entries : null;
    }
}
