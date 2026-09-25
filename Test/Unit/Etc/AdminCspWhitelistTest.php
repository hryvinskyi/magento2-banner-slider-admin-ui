<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit\Etc;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The admin Content Security Policy sources the crop editor needs.
 */
#[CoversNothing]
class AdminCspWhitelistTest extends TestCase
{
    private const WHITELIST = __DIR__ . '/../../../etc/adminhtml/csp_whitelist.xml';
    private const MODULE = __DIR__ . '/../../../etc/module.xml';

    /**
     * The crop editor loads blob: images, so the admin whitelist allows them for img-src only
     *
     * @return void
     */
    public function testBlobImagesAreAllowedInTheAdmin(): void
    {
        $xpath = $this->xpath(self::WHITELIST);

        $values = $xpath->query('//policy[@id="img-src"]/values/value[@type="host"]');
        self::assertNotFalse($values);
        self::assertSame(1, $values->length);
        self::assertSame('blob:', trim((string)$values->item(0)?->nodeValue));

        $policies = $xpath->query('//policy');
        self::assertNotFalse($policies);
        self::assertSame(1, $policies->length, 'Only img-src is widened.');
    }

    /**
     * The module is sequenced after the module whose whitelist it extends
     *
     * @return void
     */
    public function testCspModuleIsDeclared(): void
    {
        $modules = $this->xpath(self::MODULE)->query('//sequence/module[@name="Magento_Csp"]');
        self::assertNotFalse($modules);
        self::assertSame(1, $modules->length);
    }

    /**
     * XPath over an XML file of the module
     *
     * @param string $file
     * @return \DOMXPath
     */
    private function xpath(string $file): \DOMXPath
    {
        $document = new \DOMDocument();
        self::assertTrue($document->load($file));

        return new \DOMXPath($document);
    }
}
