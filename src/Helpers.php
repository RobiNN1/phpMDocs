<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

class Helpers {
    public static function isActive(string $page, bool $start_with = false): bool {
        $uri = str_replace(Config::get('site_path'), '/', (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $page = (string) preg_replace('/(\/+)/', '/', $page); // Remove extra slashes

        return $uri === $page || $uri === $page.'/' || ($start_with && str_starts_with($uri, $page));
    }

    /**
     * Get a relative path to an asset with a cache busting version, so that changes always show up.
     */
    public static function asset(string $file): string {
        $full_path = __DIR__.'/../'.$file;
        $version = is_file($full_path) ? (string) filemtime($full_path) : '1';

        return self::path($file).'?v='.$version;
    }

    /**
     * Get a relative path to docs from url.
     */
    public static function path(string $path = ''): string {
        $count = substr_count(self::currentPath(), '/');
        $docs_path = str_repeat('../', $count);

        return $path !== '' ? $docs_path.$path : $docs_path;
    }

    public static function currentPath(): string {
        $current_path = html_entity_decode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        // Remove extra slashes and domain
        if (strcmp((string) Config::get('site_path'), '/') !== 0) {
            return str_replace(Config::get('site_path'), '', $current_path);
        }

        return ltrim($current_path, '/');
    }

    /**
     * Order an array by another array.
     *
     * It uses an array from the config
     *
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function orderByArray(array $array, string $key): array {
        $order = (array) Config::get('reorder_items')[$key];

        return array_replace(array_intersect_key(array_flip($order), $array), $array);
    }

    /**
     * @param string $icon Icon name from `assets/icons/`, custom path or svg code.
     */
    public static function svg(string $icon, ?int $size = 16, ?string $class = null): string {
        static $cache = [];

        $cache_key = $icon.'|'.$size.'|'.$class;

        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $file = is_file($icon) ? $icon : __DIR__.'/../assets/icons/'.$icon.'.svg';
        $content = is_file($file) ? trim((string) file_get_contents($file)) : $icon;

        preg_match('~<svg([^<>]*)>~', $content, $attributes);

        $size_attr = $size !== null ? ' width="'.$size.'" height="'.$size.'"' : '';
        $class_attr = $class !== null ? ' class="'.$class.'"' : '';
        $svg = (string) preg_replace('~<svg([^<>]*)>~', '<svg'.($attributes[1] ?? '').$size_attr.$class_attr.'>', $content);
        $svg = (string) preg_replace('/\s+/', ' ', $svg);

        return $cache[$cache_key] = str_replace("\n", '', $svg);
    }
}
