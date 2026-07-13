<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Tests;

use PHPUnit\Framework\TestCase;
use RobiNN\Pmd\Config;

abstract class PmdTestCase extends TestCase {
    protected function setUp(): void {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

        foreach (self::fixtures() as $file => $content) {
            if (!is_file($file)) {
                @mkdir(dirname($file), 0777, true);
                file_put_contents($file, $content);
            }
        }

        Config::set([
            'site_title'       => 'Test Docs',
            'site_description' => 'Default site description.',
            'site_path'        => '/',
            'site_url'         => 'http://test.host/',
            'keywords'         => ['docs'],
            'docs_path'        => __DIR__.'/fixtures/docs',
            'ignore_files'     => ['README.md', 'ignored-page.md'],
            'logo'             => 'LOGO',
            'nav_links'        => [],
            'category_page'    => false,
            'reorder_items'    => ['home' => []],
            'twig_debug'       => false,
            'cache'            => ['enabled' => false, 'expiration' => 3600],
        ]);
    }

    protected function tearDown(): void {
        Config::set(null);

        foreach (array_keys(self::fixtures()) as $file) {
            @unlink($file);
        }

        @rmdir(__DIR__.'/fixtures/docs/assets_dir');
        @rmdir(__DIR__.'/fixtures/docs/category_a/img');
    }

    /**
     * Asset fixtures are generated at runtime so that binary and non-markdown files do not need to be committed.
     *
     * @return array<string, string>
     */
    private static function fixtures(): array {
        $docs = __DIR__.'/fixtures/docs';

        return [
            __DIR__.'/fixtures/icon.svg'    => "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 16 16\">\n    <path d=\"M2 2h12v12H2z\"/>\n</svg>",
            $docs.'/assets_dir/style.css'   => 'body { color: red; }',
            $docs.'/category_a/notes.txt'   => 'Not a markdown file, must not be listed.',
            $docs.'/category_a/img/dot.png' => (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAC0lEQVQImWNgQAYAAA4AAbGa6gYAAAAASUVORK5CYII='),
        ];
    }
}
