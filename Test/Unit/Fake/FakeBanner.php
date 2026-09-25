<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerExtensionInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\ActiveWindow;
use Hryvinskyi\BannerSliderApi\Api\Value\AspectRatio;
use Hryvinskyi\BannerSliderApi\Api\Value\BannerType;
use Hryvinskyi\BannerSliderApi\Api\Value\Dimensions;

/**
 * In-memory banner for tests. Its setters refuse what the published contract says a banner refuses (empty name,
 * id below 1, negative position, empty or absolute paths, disallowed URL schemes).
 */
class FakeBanner implements BannerInterface
{
    /**
     * @var int|null
     */
    private ?int $bannerId = null;

    /**
     * @var int|null
     */
    private ?int $sliderId = null;

    /**
     * @var string
     */
    private string $name = '';

    /**
     * @var bool
     */
    private bool $enabled = true;

    /**
     * @var BannerType
     */
    private BannerType $type = BannerType::IMAGE;

    /**
     * @var string|null
     */
    private ?string $content = null;

    /**
     * @var string|null
     */
    private ?string $image = null;

    /**
     * @var Dimensions|null
     */
    private ?Dimensions $imageDimensions = null;

    /**
     * @var string|null
     */
    private ?string $videoUrl = null;

    /**
     * @var string|null
     */
    private ?string $videoPath = null;

    /**
     * @var AspectRatio
     */
    private AspectRatio $videoAspectRatio;

    /**
     * @var bool
     */
    private bool $videoAsBackground = false;

    /**
     * @var string|null
     */
    private ?string $linkUrl = null;

    /**
     * @var string|null
     */
    private ?string $title = null;

    /**
     * @var bool
     */
    private bool $openInNewTab = false;

    /**
     * @var ActiveWindow
     */
    private ActiveWindow $activeWindow;

    /**
     * @var int
     */
    private int $position = 0;

    /**
     * @var bool
     */
    private bool $preloadEnabled = false;

    /**
     * @var BannerExtensionInterface|null
     */
    private ?BannerExtensionInterface $extensionAttributes = null;

    /**
     * Start with the contract's defaults: 16:9 video, always active
     */
    public function __construct()
    {
        $this->videoAspectRatio = new AspectRatio(AspectRatio::DEFAULT_WIDTH, AspectRatio::DEFAULT_HEIGHT);
        $this->activeWindow = new ActiveWindow(null, null);
    }

    /**
     * @inheritDoc
     */
    public function getBannerId(): ?int
    {
        return $this->bannerId;
    }

    /**
     * @inheritDoc
     */
    public function setBannerId(int $bannerId): BannerInterface
    {
        $this->bannerId = $this->positive($bannerId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSliderId(): ?int
    {
        return $this->sliderId;
    }

    /**
     * @inheritDoc
     */
    public function setSliderId(int $sliderId): BannerInterface
    {
        $this->sliderId = $this->positive($sliderId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): BannerInterface
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Banner name must not be empty.');
        }
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function setIsEnabled(bool $enabled): BannerInterface
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getType(): BannerType
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function setType(BannerType $type): BannerInterface
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @inheritDoc
     */
    public function setContent(?string $content): BannerInterface
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @inheritDoc
     */
    public function setImage(?string $image): BannerInterface
    {
        $this->image = $this->mediaPath($image);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getImageDimensions(): ?Dimensions
    {
        return $this->imageDimensions;
    }

    /**
     * @inheritDoc
     */
    public function setImageDimensions(?Dimensions $dimensions): BannerInterface
    {
        $this->imageDimensions = $dimensions;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    /**
     * @inheritDoc
     */
    public function setVideoUrl(?string $videoUrl): BannerInterface
    {
        if ($videoUrl !== null && preg_match('#^https?://#i', $videoUrl) !== 1) {
            throw new \InvalidArgumentException('Video URL must be http or https.');
        }
        $this->videoUrl = $videoUrl;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVideoPath(): ?string
    {
        return $this->videoPath;
    }

    /**
     * @inheritDoc
     */
    public function setVideoPath(?string $videoPath): BannerInterface
    {
        $this->videoPath = $this->mediaPath($videoPath);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVideoAspectRatio(): AspectRatio
    {
        return $this->videoAspectRatio;
    }

    /**
     * @inheritDoc
     */
    public function setVideoAspectRatio(AspectRatio $aspectRatio): BannerInterface
    {
        $this->videoAspectRatio = $aspectRatio;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isVideoAsBackground(): bool
    {
        return $this->videoAsBackground;
    }

    /**
     * @inheritDoc
     */
    public function setVideoAsBackground(bool $asBackground): BannerInterface
    {
        $this->videoAsBackground = $asBackground;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    /**
     * @inheritDoc
     */
    public function setLinkUrl(?string $linkUrl): BannerInterface
    {
        if ($linkUrl !== null && preg_match('~^(https?:|mailto:|tel:|[/#?]|[^:]*$)~i', $linkUrl) !== 1) {
            throw new \InvalidArgumentException('Link URL scheme is not allowed.');
        }
        $this->linkUrl = $linkUrl;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function setTitle(?string $title): BannerInterface
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    /**
     * @inheritDoc
     */
    public function setOpenInNewTab(bool $openInNewTab): BannerInterface
    {
        $this->openInNewTab = $openInNewTab;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getActiveWindow(): ActiveWindow
    {
        return $this->activeWindow;
    }

    /**
     * @inheritDoc
     */
    public function setActiveWindow(ActiveWindow $window): BannerInterface
    {
        $this->activeWindow = $window;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function setPosition(int $position): BannerInterface
    {
        if ($position < 0) {
            throw new \InvalidArgumentException('Banner position must not be negative.');
        }
        $this->position = $position;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPreloadEnabled(): bool
    {
        return $this->preloadEnabled;
    }

    /**
     * @inheritDoc
     */
    public function setPreloadEnabled(bool $enabled): BannerInterface
    {
        $this->preloadEnabled = $enabled;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?BannerExtensionInterface
    {
        return $this->extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(BannerExtensionInterface $extensionAttributes): BannerInterface
    {
        $this->extensionAttributes = $extensionAttributes;

        return $this;
    }

    /**
     * An id greater than 0
     *
     * @param int $id
     * @return int
     * @throws \InvalidArgumentException
     */
    private function positive(int $id): int
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('Id must be greater than 0.');
        }

        return $id;
    }

    /**
     * A media-relative path or null
     *
     * @param string|null $path
     * @return string|null
     * @throws \InvalidArgumentException
     */
    private function mediaPath(?string $path): ?string
    {
        if ($path !== null && ($path === '' || str_starts_with($path, '/'))) {
            throw new \InvalidArgumentException('Media path must be relative and not empty.');
        }

        return $path;
    }
}
