<?php
/**
 * This file is part of phpMDocs.
 *
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

$is_https = (
    (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1)) ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);

return [
    'site_title'       => 'phpMDocs', // Displayed on homepage and meta tag
    'site_description' => '',
    'site_path'        => '/', // If a script is running in subdir, need to set the current directory name, e.g. /docs/ for site.com/docs
    'site_url'         => 'http'.($is_https ? 's' : '').'://'.$_SERVER['SERVER_NAME'], // If that doesn't work, it may be replaced with the actual URL
    'site_url_sitemap' => 'https://example.com', // For sitemap generator
    'keywords'         => ['docs', 'php', 'markdown'],
    'docs_path'        => __DIR__.'/docs', // Path to dir with documentation
    'ignore_files'     => [
        // List of ignored files and dirs in docs dir. If empty, these files will appear in the search results
        '.gitattributes', '.gitignore', 'LICENSE', 'README.md',
    ],
    'logo'             => 'LOGO', // or '<img src="'.(new RobiNN\Pmd\Documentation())->path('assets/img/logo.svg').'" alt="{site_title}">'
    'nav_links'        => [
        ['link' => '{site_url}', 'title' => 'Home'],
        //['link' => '/page', 'title' => 'Page Title'],
    ],
    'category_page'    => false, // Set true to show category with all pages in that category
    'reorder_items'    => [
        // e.g. ['page', 'category', 'page2'] - items will be displayed in this order,
        // pages that are not listed will not be displayed (homepage only), if is empty nothing will change.
        'home' => [],
    ],
    'twig_debug'       => false,
    'cache'            => [
        'enabled'    => true,
        'expiration' => 3600, // 1h default
        // Available config options - https://github.com/RobiNN1/Cache#usage
        'storage'    => 'file',
        'file'       => ['path' => __DIR__.'/cache/data', 'secret' => 'phpmdocs_cache'],
    ],
];
