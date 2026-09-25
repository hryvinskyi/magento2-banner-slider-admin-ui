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
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;

/**
 * Custom content of a banner: trusted admin HTML, kept exactly as posted.
 */
class ContentMapper implements BannerFormMapperInterface
{
    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        if ($post->has(BannerInterface::CONTENT)) {
            $content = $post->rawText(BannerInterface::CONTENT);
            $banner->setContent($content === null || trim($content) === '' ? null : $content);
        }
    }

    /**
     * @inheritDoc
     */
    public function export(BannerInterface $banner): array
    {
        return [BannerInterface::CONTENT => $banner->getContent() ?? ''];
    }
}
