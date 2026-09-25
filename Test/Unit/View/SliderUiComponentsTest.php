<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\View;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The slider form declarations that the browser behaviour depends on.
 */
#[CoversNothing]
class SliderUiComponentsTest extends TestCase
{
    private const FORM = __DIR__ . '/../../../view/adminhtml/ui_component/hryvinskyi_banner_slider_slider_form.xml';

    /**
     * The pause/play button setting sits right after Autoplay in Slider Options, starts checked, and is shown only
     * while the Autoplay checkbox is checked
     *
     * @return void
     */
    public function testAutoPlayToggleFollowsAutoPlay(): void
    {
        $fields = [];
        foreach ($this->query('//fieldset[@name="slider_options"]/field/@name') as $name) {
            $fields[] = (string)$name->nodeValue;
        }
        $position = array_search('auto_play', $fields, true);
        self::assertIsInt($position);
        self::assertSame('show_autoplay_toggle', $fields[$position + 1] ?? null);

        $config = '//field[@name="show_autoplay_toggle"]/argument[@name="data"]/item[@name="config"]';
        self::assertSame('1', $this->text($config . '/item[@name="default"]'));
        self::assertSame(
            '${ $.parentName }.auto_play:checked',
            $this->text($config . '/item[@name="imports"]/item[@name="visible"]')
        );
        self::assertSame('Show Pause/Play Button', $this->text('//field[@name="show_autoplay_toggle"]//label'));
    }

    /**
     * Text of the single node an XPath query selects in the slider form
     *
     * @param string $xpath
     * @return string
     */
    private function text(string $xpath): string
    {
        $nodes = $this->query($xpath);
        self::assertSame(1, $nodes->length, $xpath);

        return trim((string)$nodes->item(0)?->nodeValue);
    }

    /**
     * The nodes an XPath query selects in the slider form
     *
     * @param string $xpath
     * @return \DOMNodeList<\DOMNameSpaceNode|\DOMNode>
     */
    private function query(string $xpath): \DOMNodeList
    {
        $document = new \DOMDocument();
        self::assertTrue($document->load(self::FORM));
        $nodes = (new \DOMXPath($document))->query($xpath);
        self::assertInstanceOf(\DOMNodeList::class, $nodes);

        return $nodes;
    }
}
