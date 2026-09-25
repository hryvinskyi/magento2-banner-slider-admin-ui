<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\View;

use Hryvinskyi\BannerSliderAdminUi\Ui\Component\MassAction\ScopedAction;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * The banner form and grid declarations that the browser behaviour depends on.
 */
#[CoversNothing]
class BannerUiComponentsTest extends TestCase
{
    private const UI_COMPONENTS = __DIR__ . '/../../../view/adminhtml/ui_component/';

    /**
     * The form component binds its own save to the elements with ids `save` and `save_and_continue`, and a button
     * takes its name as id; the banner form's buttons use other names, so a click submits once
     *
     * @return void
     */
    public function testBannerFormButtonsAvoidTheFormSaveIds(): void
    {
        $names = [];
        foreach ($this->query('hryvinskyi_banner_slider_banner_form.xml', '//settings/buttons/button/@name') as $name) {
            $names[] = $name->nodeValue;
        }

        self::assertContains('save_banner', $names);
        self::assertContains('save_banner_and_continue', $names);
        self::assertNotContains('save', $names);
        self::assertNotContains('save_and_continue', $names);
    }

    /**
     * The image and video uploaders offer the Upload button only, never "Select from Gallery"
     *
     * @param string $field
     * @return void
     */
    #[TestWith(['image'])]
    #[TestWith(['video_path'])]
    public function testUploadersOfferNoMediaGallery(string $field): void
    {
        $templates = $this->query(
            'hryvinskyi_banner_slider_banner_form.xml',
            sprintf('//field[@name="%s"]/argument[@name="data"]/item[@name="config"]/item[@name="template"]', $field)
        );

        self::assertSame(1, $templates->length);
        $template = (string)$templates->item(0)?->nodeValue;
        self::assertSame('Hryvinskyi_BannerSliderAdminUi/form/element/uploader/upload-only', $template);
        $html = (string)file_get_contents(
            __DIR__ . '/../../../view/adminhtml/web/template/form/element/uploader/upload-only.html'
        );
        self::assertStringNotContainsString('openMediaBrowserDialog', $html);
        self::assertStringNotContainsString('addFileFromMediaGallery', $html);
        self::assertStringNotContainsString("translate=\"'Select from Gallery'\"", $html);
        self::assertStringContainsString("translate=\"'Upload'\"", $html);
    }

    /**
     * The video uploader accepts the file types the server stores
     *
     * @return void
     */
    public function testVideoUploaderAcceptsM4v(): void
    {
        $extensions = $this->query(
            'hryvinskyi_banner_slider_banner_form.xml',
            '//field[@name="video_path"]//allowedExtensions'
        );

        self::assertSame(['mp4', 'm4v', 'webm'], explode(' ', (string)$extensions->item(0)?->nodeValue));
    }

    /**
     * The grid's mass delete posts the slider the grid was opened for
     *
     * @return void
     */
    public function testMassDeletePostsTheGridSlider(): void
    {
        $action = '//massaction/action[@name="delete"]';
        $file = 'hryvinskyi_banner_slider_banner_listing.xml';

        self::assertSame(ScopedAction::class, $this->query($file, $action . '/@class')->item(0)?->nodeValue);
        self::assertSame(
            'slider_id',
            $this->query($file, $action . '/argument/item[@name="scopeParams"]/item')->item(0)?->nodeValue
        );
    }

    /**
     * The nodes an XPath query selects in a UI component file
     *
     * @param string $file
     * @param string $xpath
     * @return \DOMNodeList<\DOMNameSpaceNode|\DOMNode>
     */
    private function query(string $file, string $xpath): \DOMNodeList
    {
        $document = new \DOMDocument();
        self::assertTrue($document->load(self::UI_COMPONENTS . $file));
        $nodes = (new \DOMXPath($document))->query($xpath);
        self::assertInstanceOf(\DOMNodeList::class, $nodes);

        return $nodes;
    }
}
