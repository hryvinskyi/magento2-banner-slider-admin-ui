<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Ui\Component\Form;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\AspectRatioPresets;
use Hryvinskyi\BannerSliderAdminUi\Ui\Component\Form\StoreViewOptions;
use Hryvinskyi\BannerSliderAdminUi\Ui\Component\Form\VideoAspectRatioOptions;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StoreViewOptions::class)]
#[CoversClass(VideoAspectRatioOptions::class)]
#[CoversClass(AspectRatioPresets::class)]
class FormOptionsTest extends TestCase
{
    /**
     * "All Store Views" (store 0) comes first, followed by the wrapped store view tree
     *
     * @return void
     */
    public function testStoreViewsStartWithAllStoreViews(): void
    {
        $tree = [['label' => 'Main Website', 'value' => [['label' => 'Default Store View', 'value' => '1']]]];
        $source = $this->createMock(OptionSourceInterface::class);
        $source->expects(self::once())->method('toOptionArray')->willReturn($tree);
        $options = new StoreViewOptions($source);

        $result = $options->toOptionArray();

        self::assertEquals(['label' => __('All Store Views'), 'value' => '0'], $result[0]);
        self::assertSame($tree[0], $result[1]);
        self::assertSame($result, $options->toOptionArray());
    }

    /**
     * The aspect ratio select lists the presets, then "Custom"; malformed preset entries are skipped
     *
     * @return void
     */
    public function testAspectRatiosEndWithCustom(): void
    {
        $source = $this->createMock(OptionSourceInterface::class);
        $source->method('toOptionArray')->willReturn([
            ['value' => '16:9', 'label' => '16:9'],
            ['label' => 'no value'],
            ['value' => '4:3'],
        ]);
        $presets = new AspectRatioPresets($source);

        $options = (new VideoAspectRatioOptions($presets))->toOptionArray();

        self::assertSame(['16:9', '4:3', 'custom'], array_column($options, 'value'));
        self::assertSame('Custom', (string)$options[2]['label']);
        self::assertTrue($presets->isPreset('4:3'));
        self::assertFalse($presets->isPreset('3:2'));
    }
}
