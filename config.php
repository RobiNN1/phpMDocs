<?php
/**
 * This file is part of Docs.
 *
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

return [
    'site_title'       => 'Documentation', // Displayed on homepage and meta tag
    'site_description' => '',
    'site_path'        => '/simple_docs/', // If script is running in subdir, need to set the current directory name, e.g. /docs/ for site.com/docs
    'site_url'         => sprintf('http%s://%s', ((
        isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
    ) ? 's' : ''), $_SERVER['SERVER_NAME']), // If that doesn't work, it may be replaced with the actual URL
    'site_url_sitemap' => 'http://docs.host', // For sitemap generator
    'keywords'         => 'docs,php',
    'docs_path'        => __DIR__.'/docs/', // Path to dir with documentation
    'ignore_files'     => [
        // List of ignored files and dirs in docs dir. If empty, these files will appear in the search results
        '.gitattributes', '.gitignore', 'LICENSE', 'README.md',
    ],
    'logo'             => 'LOGO', // '<img src="'.RobiNN\Docs\Functions::path('assets/img/logo.svg').'" alt="{site_title}">' <img> tag or text
    'nav_links'        => [
        ['link' => '{site_url}', 'title' => 'Home'],
        //['link' => '/page', 'title' => 'Page Title'],
    ],
    'twig_debug'       => true,
    'cache'            => [
        'enable'     => false, // Enable / Disable cache
        'expiration' => 3600, // 1h default
        // Available config options -  https://github.com/RobiNN1/Cache#usage
        'storage'    => 'file',
        'path'       => __DIR__.'/cache/data',
        'secret_key' => 'docs_cache', // Any random string to secure FileCache
    ],
];
