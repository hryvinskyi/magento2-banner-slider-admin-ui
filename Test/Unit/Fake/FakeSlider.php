<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Fake;

use Hryvinskyi\BannerSliderApi\Api\Data\SliderExtensionInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\ActiveWindow;
use Hryvinskyi\BannerSliderApi\Api\Value\LocationCode;
use Hryvinskyi\BannerSliderApi\Api\Value\ResponsiveItem;
use Hryvinskyi\BannerSliderApi\Api\Value\SlideEffect;
use Hryvinskyi\BannerSliderApi\Api\Value\Visibility;

/**
 * In-memory slider for tests. Its setters refuse what the published contract says a slider refuses (empty name,
 * malformed location, negative priority or preload count, autoplay under 1000 ms, CSS containing `<`).
 */
class FakeSlider implements SliderInterface
{
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
     * @var string|null
     */
    private ?string $location = null;

    /**
     * @var int
     */
    private int $priority = 0;

    /**
     * @var SlideEffect
     */
    private SlideEffect $effect = SlideEffect::SLIDE;

    /**
     * @var array<string, bool>
     */
    private array $toggles = [
        'auto_width' => false,
        'auto_height' => false,
        'loop' => true,
        'lazy_load' => true,
        'auto_play' => true,
        'nav' => true,
        'dots' => true,
    ];

    /**
     * @var int
     */
    private int $autoPlayInterval = SliderInterface::DEFAULT_AUTO_PLAY_INTERVAL;

    /**
     * @var list<ResponsiveItem>
     */
    private array $responsiveItems = [];

    /**
     * @var int
     */
    private int $preloadBannersCount = 0;

    /**
     * @var ActiveWindow
     */
    private ActiveWindow $activeWindow;

    /**
     * @var Visibility
     */
    private Visibility $visibility;

    /**
     * @var string|null
     */
    private ?string $customCss = null;

    /**
     * @var SliderExtensionInterface|null
     */
    private ?SliderExtensionInterface $extensionAttributes = null;

    /**
     * Start with the contract's defaults: always active, visible nowhere
     */
    public function __construct()
    {
        $this->activeWindow = new ActiveWindow(null, null);
        $this->visibility = new Visibility([], [], false);
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
    public function setSliderId(int $sliderId): SliderInterface
    {
        if ($sliderId < 1) {
            throw new \InvalidArgumentException('Slider id must be greater than 0.');
        }
        $this->sliderId = $sliderId;

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
    public function setName(string $name): SliderInterface
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Slider name must not be empty.');
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
    public function setIsEnabled(bool $enabled): SliderInterface
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @inheritDoc
     */
    public function setLocation(?string $location): SliderInterface
    {
        $this->location = $location === null ? null : (new LocationCode($location))->getCode();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function setPriority(int $priority): SliderInterface
    {
        if ($priority < 0) {
            throw new \InvalidArgumentException('Priority must not be negative.');
        }
        $this->priority = $priority;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEffect(): SlideEffect
    {
        return $this->effect;
    }

    /**
     * @inheritDoc
     */
    public function setEffect(SlideEffect $effect): SliderInterface
    {
        $this->effect = $effect;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isAutoWidthEnabled(): bool
    {
        return $this->toggles['auto_width'];
    }

    /**
     * @inheritDoc
     */
    public function setAutoWidthEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('auto_width', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function isAutoHeightEnabled(): bool
    {
        return $this->toggles['auto_height'];
    }

    /**
     * @inheritDoc
     */
    public function setAutoHeightEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('auto_height', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function isLoopEnabled(): bool
    {
        return $this->toggles['loop'];
    }

    /**
     * @inheritDoc
     */
    public function setLoopEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('loop', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function isLazyLoadEnabled(): bool
    {
        return $this->toggles['lazy_load'];
    }

    /**
     * @inheritDoc
     */
    public function setLazyLoadEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('lazy_load', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function isAutoPlayEnabled(): bool
    {
        return $this->toggles['auto_play'];
    }

    /**
     * @inheritDoc
     */
    public function setAutoPlayEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('auto_play', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function getAutoPlayInterval(): int
    {
        return $this->autoPlayInterval;
    }

    /**
     * @inheritDoc
     */
    public function setAutoPlayInterval(int $milliseconds): SliderInterface
    {
        if ($milliseconds < SliderInterface::MIN_AUTO_PLAY_INTERVAL) {
            throw new \InvalidArgumentException('Autoplay interval is too short.');
        }
        $this->autoPlayInterval = $milliseconds;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isNavigationEnabled(): bool
    {
        return $this->toggles['nav'];
    }

    /**
     * @inheritDoc
     */
    public function setNavigationEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('nav', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function isPaginationEnabled(): bool
    {
        return $this->toggles['dots'];
    }

    /**
     * @inheritDoc
     */
    public function setPaginationEnabled(bool $enabled): SliderInterface
    {
        return $this->toggle('dots', $enabled);
    }

    /**
     * @inheritDoc
     */
    public function getResponsiveItems(): array
    {
        return $this->responsiveItems;
    }

    /**
     * @inheritDoc
     */
    public function setResponsiveItems(array $items): SliderInterface
    {
        $this->responsiveItems = $items;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPreloadBannersCount(): int
    {
        return $this->preloadBannersCount;
    }

    /**
     * @inheritDoc
     */
    public function setPreloadBannersCount(int $count): SliderInterface
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Preload count must not be negative.');
        }
        $this->preloadBannersCount = $count;

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
    public function setActiveWindow(ActiveWindow $window): SliderInterface
    {
        $this->activeWindow = $window;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(Visibility $visibility): SliderInterface
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCustomCss(): ?string
    {
        return $this->customCss;
    }

    /**
     * @inheritDoc
     */
    public function setCustomCss(?string $customCss): SliderInterface
    {
        if ($customCss !== null && str_contains($customCss, '<')) {
            throw new \InvalidArgumentException('Custom CSS must not contain "<".');
        }
        $this->customCss = $customCss;

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
    public function getExtensionAttributes(): ?SliderExtensionInterface
    {
        return $this->extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(SliderExtensionInterface $extensionAttributes): SliderInterface
    {
        $this->extensionAttributes = $extensionAttributes;

        return $this;
    }

    /**
     * Set one on/off option
     *
     * @param string $option
     * @param bool $enabled
     * @return $this
     */
    private function toggle(string $option, bool $enabled): static
    {
        $this->toggles[$option] = $enabled;

        return $this;
    }
}
