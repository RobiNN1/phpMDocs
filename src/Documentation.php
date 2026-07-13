<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

use Closure;
use FilesystemIterator;
use RobiNN\Cache\Cache;
use RobiNN\Cache\CacheException;
use SplFileInfo;

class Documentation {
    private ?Cache $cache = null;

    public function __construct() {
        if (Config::get('cache')['enabled']) {
            try {
                $this->cache = new Cache(Config::get('cache'));
            } catch (CacheException $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * Get recursively all files and dirs.
     *
     * @return array<int, string>
     */
    public function scanDir(string $dir): array {
        $items = [];

        foreach (scandir($dir) ?: [] as $filename) {
            if ($this->isIgnored($filename)) {
                continue;
            }

            $file_path = $dir.'/'.$filename;

            if (is_dir($file_path)) {
                if (!$this->hasMarkdown($file_path)) {
                    continue;
                }

                foreach ($this->scanDir($file_path) as $child_filename) {
                    $items[] = $filename.'/'.$child_filename;
                }

                $items[] = $filename;
            } elseif (str_ends_with($filename, '.md')) {
                $items[] = basename($filename, '.md');
            }
        }

        natsort($items);

        return array_values($items);
    }

    /**
     * Get an array of pages in a category.
     *
     * @return array<int, array<string, string>>
     */
    public function getPages(string $path = ''): array {
        $category = $this->getCategory($path);

        return $this->cacheData('get_pages:'.$category, function () use ($category): array {
            $dir = Config::get('docs_path').'/'.$category;

            if (!is_dir($dir)) {
                return [];
            }

            $pages = [];

            foreach (new FilesystemIterator($dir) as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }
                if (!$this->isListable($file)) {
                    continue;
                }
                $file_path = $category.'/'.$file->getBasename('.md');
                $md = new ParseMarkdown($file_path);

                $pages[] = [
                    'title'       => $md->getTitle(),
                    'description' => $md->getDescription(),
                    'url'         => Config::get('site_url').ltrim($file_path, '/'),
                    'is_dir'      => $file->isDir(),
                    'id'          => $file->getFilename(),
                    'path'        => trim($file_path, '/'),
                ];
            }

            usort($pages, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

            return $pages;
        });
    }

    public function exists(string $path): bool {
        return is_file(Config::get('docs_path').'/'.$path.'.md');
    }

    public function getCategory(string $path): string {
        if ($this->exists($path)) {
            $paths = explode('/', $path);
            array_pop($paths); // Remove the page name so that we can retrieve its folder
            $path = implode('/', $paths);
        }

        return $path;
    }

    public function show404(): void {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        echo Template::render('404');
    }

    /**
     * Get the data from cache or store the value / result of a closure.
     *
     * Closures are executed only when the data is not cached yet.
     */
    public function cacheData(string $key, mixed $value): mixed {
        if ($this->cache instanceof Cache && $this->cache->isConnected()) {
            $key = trim(strtr($key, ['/' => ':']), ':');

            return $this->cache->remember($key, $value, (int) Config::get('cache')['expiration']);
        }

        return $value instanceof Closure ? $value() : $value;
    }

    private function isIgnored(string $filename): bool {
        return str_starts_with($filename, '.') || in_array($filename, Config::get('ignore_files'), true);
    }

    /**
     * Only Markdown files and categories that contain at least one Markdown file (skips asset dirs).
     */
    private function isListable(SplFileInfo $file): bool {
        if ($this->isIgnored($file->getFilename())) {
            return false;
        }

        return $file->isDir() ? $this->hasMarkdown($file->getPathname()) : $file->getExtension() === 'md';
    }

    private function hasMarkdown(string $dir): bool {
        return (glob($dir.'/*.md') ?: []) !== [];
    }
}
