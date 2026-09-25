<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Hryvinskyi\BannerSliderAdminUi\Api\Form\PostData;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * An uploader form field holding one media file, both ways, and the rule for which posted path may be saved.
 *
 * The uploader posts a list of file entries; the stored media-relative path is the `file` key of the first one. An
 * entry without that key is a file the field did not upload itself (for example one picked from the media gallery),
 * which has no path the entity could keep.
 * Because a path in a post is untrusted, a posted path is accepted only when it is the value already stored on the
 * entity (legacy values may sit anywhere in media) or a clean path inside the folder the upload service stores new
 * files in. Any other media file (a download, a customer upload) can never be attached by editing the post.
 */
class MediaFieldCodec
{
    private const FILE_KEY = 'file';

    /**
     * @param MediaUrlResolverInterface $mediaUrlResolver
     */
    public function __construct(
        private readonly MediaUrlResolverInterface $mediaUrlResolver
    ) {
    }

    /**
     * The posted media-relative path of the field, or null when the uploader is empty
     *
     * @param PostData $post
     * @param string $field
     * @return string|null
     * @throws \InvalidArgumentException When the uploader holds a file without a stored path
     */
    public function readPath(PostData $post, string $field): ?string
    {
        foreach ($post->rows($field) as $file) {
            return $file->text(self::FILE_KEY) ?? throw new \InvalidArgumentException(
                sprintf('Form field "%s" holds a file without a stored path.', $field)
            );
        }

        return null;
    }

    /**
     * Whether a posted path may be saved: the stored value unchanged, or a clean path inside the upload folder
     *
     * @param string $path Posted path
     * @param string|null $storedPath The entity's current value
     * @param string $uploadFolder Media-relative folder new uploads are stored in, without a trailing slash
     * @return bool
     */
    public function isAcceptable(string $path, ?string $storedPath, string $uploadFolder): bool
    {
        if ($storedPath !== null && $path === $storedPath) {
            return true;
        }
        $prefix = rtrim($uploadFolder, '/') . '/';

        return str_starts_with($path, $prefix)
            && strlen($path) > strlen($prefix)
            && $this->isClean($path);
    }

    /**
     * The uploader value of a stored path: one file entry, or none
     *
     * @param string|null $path
     * @param string $type MIME type (or a `type/*` family) the uploader previews the file as
     * @return list<array{file: string, name: string, url: string, type: string}>
     */
    public function export(?string $path, string $type): array
    {
        if ($path === null) {
            return [];
        }

        return [[
            'file' => $path,
            'name' => $this->fileName($path),
            'url' => $this->url($path),
            'type' => $type,
        ]];
    }

    /**
     * The last segment of a path
     *
     * @param string $path
     * @return string
     */
    public function fileName(string $path): string
    {
        $slash = strrpos($path, '/');

        return $slash === false ? $path : substr($path, $slash + 1);
    }

    /**
     * Relative, without parent segments, empty segments, NUL, backslash or scheme
     *
     * @param string $path
     * @return bool
     */
    private function isClean(string $path): bool
    {
        if (str_starts_with($path, '/') || strpbrk($path, "\\\0:") !== false) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * The public URL of a stored path, or an empty string when it cannot be resolved
     *
     * @param string $path
     * @return string
     */
    private function url(string $path): string
    {
        try {
            return $this->mediaUrlResolver->getUrl($path);
        } catch (\InvalidArgumentException | LocalizedException) {
            return '';
        }
    }
}
