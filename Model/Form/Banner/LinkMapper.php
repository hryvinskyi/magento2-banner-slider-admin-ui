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
 * Link target of a banner and whether it opens in a new tab.
 */
class LinkMapper implements BannerFormMapperInterface
{
    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        if ($post->has(BannerInterface::LINK_URL)) {
            $errors->attempt(
                __('Enter a link URL that starts with http:, https:, mailto: or tel:, or a relative URL.'),
                static fn () => $banner->setLinkUrl($post->text(BannerInterface::LINK_URL))
            );
        }
        if ($post->has(BannerInterface::OPEN_IN_NEW_TAB)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Open in New Tab')),
                static fn () => $banner->setOpenInNewTab($post->flag(BannerInterface::OPEN_IN_NEW_TAB, false))
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function export(BannerInterface $banner): array
    {
        return [
            BannerInterface::LINK_URL => $banner->getLinkUrl() ?? '',
            BannerInterface::OPEN_IN_NEW_TAB => $banner->isOpenInNewTab() ? '1' : '0',
        ];
    }
}
