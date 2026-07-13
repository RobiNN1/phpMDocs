<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Tests;

use RobiNN\Pmd\Config;

final class ConfigTest extends PmdTestCase {
    public function testGet(): void {
        $this->assertSame('Test Docs', Config::get('site_title'));
        $this->assertSame(['docs'], Config::get('keywords'));
    }

    public function testGetDefault(): void {
        $this->assertNull(Config::get('nonexistent'));
        $this->assertSame('fallback', Config::get('nonexistent', 'fallback'));
        $this->assertSame([], Config::get('nonexistent', []));
    }
}
