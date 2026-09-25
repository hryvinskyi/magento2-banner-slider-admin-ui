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
use Hryvinskyi\BannerSliderApi\Api\Value\BannerType;

/**
 * Name, slider, status, type, position and preload flag of a banner.
 *
 * An empty slider is left unassigned, so the banner validator reports it with its own message.
 */
class GeneralMapper implements BannerFormMapperInterface
{
    /**
     * @inheritDoc
     */
    public function hydrate(PostData $post, BannerInterface $banner, FieldErrors $errors): void
    {
        if ($post->has(BannerInterface::NAME)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Name')),
                static fn () => $banner->setName($post->text(BannerInterface::NAME) ?? '')
            );
        }
        if ($post->has(BannerInterface::SLIDER_ID)) {
            $errors->attempt(__('Select a valid slider.'), static function () use ($post, $banner): void {
                $sliderId = $post->integer(BannerInterface::SLIDER_ID);
                if ($sliderId !== null) {
                    $banner->setSliderId($sliderId);
                }
            });
        }
        if ($post->has(BannerInterface::STATUS)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Enabled')),
                static fn () => $banner->setIsEnabled($post->flag(BannerInterface::STATUS, true))
            );
        }
        if ($post->has(BannerInterface::TYPE)) {
            $errors->attempt(__('Select a valid banner type.'), static function () use ($post, $banner): void {
                $type = BannerType::tryFrom($post->integer(BannerInterface::TYPE) ?? BannerType::IMAGE->value);
                if ($type === null) {
                    throw new \InvalidArgumentException('Unknown banner type.');
                }
                $banner->setType($type);
            });
        }
        if ($post->has(BannerInterface::POSITION)) {
            $errors->attempt(
                __('Enter a whole number of 0 or more for "%1".', __('Position')),
                static fn () => $banner->setPosition($post->integer(BannerInterface::POSITION) ?? 0)
            );
        }
        if ($post->has(BannerInterface::IS_PRELOAD)) {
            $errors->attempt(
                __('Enter a valid value for "%1".', __('Preload Image')),
                static fn () => $banner->setPreloadEnabled($post->flag(BannerInterface::IS_PRELOAD, false))
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function export(BannerInterface $banner): array
    {
        $sliderId = $banner->getSliderId();

        return [
            BannerInterface::NAME => $banner->getName(),
            BannerInterface::SLIDER_ID => $sliderId === null ? '' : (string)$sliderId,
            BannerInterface::STATUS => $banner->isEnabled() ? '1' : '0',
            BannerInterface::TYPE => (string)$banner->getType()->value,
            BannerInterface::POSITION => (string)$banner->getPosition(),
            BannerInterface::IS_PRELOAD => $banner->isPreloadEnabled() ? '1' : '0',
        ];
    }
}
