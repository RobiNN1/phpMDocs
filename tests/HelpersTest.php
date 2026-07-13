<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Tests;

use RobiNN\Pmd\Config;
use RobiNN\Pmd\Helpers;

final class HelpersTest extends PmdTestCase {
    public function testCurrentPath(): void {
        $_SERVER['REQUEST_URI'] = '/category_a/page-one';
        $this->assertSame('category_a/page-one', Helpers::currentPath());

        $_SERVER['REQUEST_URI'] = '/category_a/page-one?foo=bar';
        $this->assertSame('category_a/page-one', Helpers::currentPath());

        $_SERVER['REQUEST_URI'] = '/';
        $this->assertSame('', Helpers::currentPath());
    }

    public function testPath(): void {
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertSame('style.css', Helpers::path('style.css'));

        $_SERVER['REQUEST_URI'] = '/category_a/page-one';
        $this->assertSame('../style.css', Helpers::path('style.css'));

        $_SERVER['REQUEST_URI'] = '/category_a/nested/deep-page';
        $this->assertSame('../../style.css', Helpers::path('style.css'));
    }

    public function testAsset(): void {
        $_SERVER['REQUEST_URI'] = '/category_a/page-one';

        $this->assertMatchesRegularExpression('~^\.\./assets/js/scripts\.js\?v=\d+$~', Helpers::asset('assets/js/scripts.js'));
        $this->assertSame('../nonexistent.js?v=1', Helpers::asset('nonexistent.js'));
    }

    public function testIsActive(): void {
        $_SERVER['REQUEST_URI'] = '/category_a/page-one';

        $this->assertTrue(Helpers::isActive('/category_a/page-one'));
        $this->assertTrue(Helpers::isActive('//category_a//page-one')); // Extra slashes are removed
        $this->assertTrue(Helpers::isActive('/category_a', true));
        $this->assertFalse(Helpers::isActive('/category_a'));
        $this->assertFalse(Helpers::isActive('/other'));
    }

    public function testOrderByArray(): void {
        $items = [
            'a' => ['title' => 'A'],
            'b' => ['title' => 'B'],
            'c' => ['title' => 'C'],
        ];

        $ordered = Helpers::orderByArray($items, 'home'); // 'home' => [] keeps the original order
        $this->assertSame(['a', 'b', 'c'], array_keys($ordered));

        $_SERVER['REQUEST_URI'] = '/';
        Config::set(['reorder_items' => ['home' => ['c', 'a', 'b']]]);

        $ordered = Helpers::orderByArray($items, 'home');
        $this->assertSame(['c', 'a', 'b'], array_keys($ordered));
    }

    public function testSvgFromFile(): void {
        $svg = Helpers::svg(__DIR__.'/fixtures/icon.svg', 20, 'test-class');

        $this->assertStringContainsString('width="20" height="20"', $svg);
        $this->assertStringContainsString('class="test-class"', $svg);
        $this->assertStringContainsString('viewBox="0 0 16 16"', $svg);
        $this->assertStringNotContainsString("\n", $svg);
    }

    public function testSvgFromCode(): void {
        $svg = Helpers::svg('<svg viewBox="0 0 8 8"><path d="M0 0h8v8H0z"/></svg>', 12);

        $this->assertStringContainsString('viewBox="0 0 8 8" width="12" height="12"', $svg);
    }
}
