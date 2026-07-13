<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Tests;

use RobiNN\Pmd\Config;
use RobiNN\Pmd\Documentation;

final class DocumentationTest extends PmdTestCase {
    private Documentation $docs;

    protected function setUp(): void {
        parent::setUp();
        $this->docs = new Documentation();
    }

    public function testExists(): void {
        $this->assertTrue($this->docs->exists('category_a/page-one'));
        $this->assertTrue($this->docs->exists('category_a/README'));
        $this->assertFalse($this->docs->exists('category_a'));
        $this->assertFalse($this->docs->exists('nonexistent'));
    }

    public function testGetCategory(): void {
        $this->assertSame('category_a', $this->docs->getCategory('category_a/page-one'));
        $this->assertSame('category_a', $this->docs->getCategory('category_a')); // Not a file, unchanged
        $this->assertSame('', $this->docs->getCategory('root-page'));
    }

    public function testScanDir(): void {
        $items = $this->docs->scanDir(Config::get('docs_path'));

        $this->assertContains('category_a', $items);
        $this->assertContains('category_a/page-one', $items);
        $this->assertContains('root-page', $items);

        $this->assertNotContains('assets_dir', $items); // Dir without Markdown files
        $this->assertNotContains('category_a/notes', $items); // Not a Markdown file
        $this->assertNotContains('category_a/notes.txt', $items);
        $this->assertNotContains('ignored-page', $items); // In ignore_files
    }

    public function testGetPages(): void {
        $pages = $this->docs->getPages('category_a/page-one'); // Resolves to the category

        $this->assertSame(['category_a/page-one', 'category_a/page-two'], array_column($pages, 'path'));

        $page = $pages[0];

        $this->assertSame('Page One', $page['title']);
        $this->assertSame('First page description.', $page['description']);
        $this->assertSame('http://test.host/category_a/page-one', $page['url']);
        $this->assertSame('category_a/page-one', $page['path']);
        $this->assertFalse($page['is_dir']);
    }

    public function testGetPagesInRoot(): void {
        $paths = array_column($this->docs->getPages(), 'path');

        $this->assertContains('category_a', $paths);
        $this->assertContains('root-page', $paths);
        $this->assertNotContains('assets_dir', $paths);
        $this->assertNotContains('ignored-page', $paths);
    }

    public function testGetPagesNonexistentDir(): void {
        $this->assertSame([], $this->docs->getPages('nonexistent'));
    }

    public function testCacheDataWithDisabledCacheExecutesClosure(): void {
        $calls = 0;
        $callback = static function () use (&$calls): string {
            $calls++;

            return 'value';
        };

        $this->assertSame('value', $this->docs->cacheData('test:key', $callback));
        $this->assertSame('value', $this->docs->cacheData('test:key', $callback));
        $this->assertSame(2, $calls); // No caching, the closure runs every time.

        $this->assertSame('plain', $this->docs->cacheData('test:key2', 'plain'));
    }
}
