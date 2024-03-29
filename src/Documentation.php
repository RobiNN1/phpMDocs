<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

use DirectoryIterator;
use Exception;
use RobiNN\Cache\Cache;
use RobiNN\Cache\CacheException;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Documentation {
    private Cache $cache;

    public function __construct() {
        if ($this->config('cache')['enabled']) {
            try {
                $this->cache = new Cache($this->config('cache'));
            } catch (CacheException $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * @template Default
     *
     * @param Default $default
     *
     * @return mixed|Default
     */
    public function config(string $key, $default = null) {
        if (is_file(__DIR__.'/../config.php')) {
            $config = (array) require __DIR__.'/../config.php';
        } else {
            exit('The configuration file is missing.');
        }

        $config['site_url'] .= $config['site_path'];

        return $config[$key] ?? $default;
    }

    /**
     * Render template.
     *
     * @param array<string, mixed> $data
     */
    public function tpl(string $tpl, array $data = []): string {
        $loader = new FilesystemLoader(__DIR__.'/../templates');

        $twig = new Environment($loader, [
            'cache' => __DIR__.'/../cache/twig',
            'debug' => $this->config('twig_debug'),
        ]);

        if ($this->config('twig_debug')) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->addFunction(new TwigFunction('config', $this->config(...)));
        $twig->addFunction(new TwigFunction('path', $this->path(...)));
        $twig->addFunction(new TwigFunction('is_active', $this->isActive(...)));

        try {
            return $twig->render($tpl.'.twig', $data);
        } catch (Exception $e) {
            return $e->getMessage().' in '.$e->getFile().' at line: '.$e->getLine();
        }
    }

    public function isActive(string $page, bool $start_with = false): bool {
        $uri = str_replace($this->config('site_path'), '/', $_SERVER['REQUEST_URI']);
        $page = preg_replace('/(\/+)/', '/', $page); // Remove trailing slashes

        return ($uri === $page || $uri === $page.'/') || ($start_with ? str_starts_with($uri, $page) : null);
    }

    /**
     * Get a relative path to docs from url.
     */
    public function path(string $path = ''): string {
        $count = substr_count($this->currentPath(), '/');
        $docs_path = str_repeat('../', $count);

        return $path !== '' ? $docs_path.$path : $docs_path;
    }

    public function currentPath(): string {
        $current_path = html_entity_decode($_SERVER['REQUEST_URI']);

        // Remove extra slashes and domain
        if (strcmp($this->config('site_path'), '/') !== 0) {
            return str_replace($this->config('site_path'), '', $current_path);
        }

        return ltrim($current_path, '/');
    }

    /**
     * Get recursively all files and dirs.
     *
     * @return array<int, string>
     */
    public function scanDir(string $dir): array {
        $dirs = [];

        foreach (scandir($dir) as $filename) {
            if ($filename[0] === '.') {
                continue;
            }

            if (in_array($filename, $this->config('ignore_files'), true)) {
                continue;
            }

            $file_path = $dir.'/'.$filename;

            if (is_dir($file_path)) {
                foreach ($this->scanDir($file_path) as $child_filename) {
                    $dirs[] = $filename.'/'.$child_filename;
                }
            }

            $dirs[] = $filename;
        }

        natsort($dirs);

        return array_map(static fn ($name): string => strtr($name, ['.md' => '']), $dirs);
    }

    /**
     * Get an array of pages in category.
     *
     * @return array<int, array<string, string>>
     */
    public function getPages(string $path = '', bool $description = false): array {
        static $pages = [];

        $path = $this->getCategory($path);

        if (is_dir($this->config('docs_path').'/'.$path)) {
            $dir = new DirectoryIterator($this->config('docs_path').'/'.$path);

            foreach ($dir as $file) {
                if (!$file->isDot() && !in_array($file->getFilename(), $this->config('ignore_files'), true)) {
                    $file_path = $path.'/'.str_replace('.md', '', $file->getFilename());

                    $md = new ParseMarkdown($file_path);

                    $pages[] = [
                        'title'       => $md->getTitle(),
                        'description' => $description ? $md->getDescription() : '',
                        'url'         => $this->config('site_url').ltrim($file_path, '/'),
                        'is_dir'      => $file->isDir(),
                        'id'          => $file->getFilename(),
                        'path'        => trim($file_path, '/'),
                    ];
                }
            }
        }

        usort($pages, static fn ($a, $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $this->cacheData('get_pages'.$path, $pages);
    }

    public function exists(string $path): bool {
        return is_file($this->config('docs_path').'/'.$path.'.md');
    }

    public function show404(): void {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        echo $this->tpl('404');
    }

    /**
     * @template Return
     *
     * @param Return $value
     */
    public function cacheData(string $key, mixed $value): mixed {
        if ($this->config('cache')['enabled'] && $this->cache->isConnected()) {
            return $this->cache->remember(strtr($key, ['/' => '_']), $value, $this->config('cache')['expiration']);
        }

        return $value;
    }

    public function getCategory(string $path): string {
        if ($this->exists($path)) {
            $paths = explode('/', $path);
            array_pop($paths); // Remove the page name so that we can retrieve its folder
            $path = implode('/', $paths);
        }

        return $path;
    }

    /**
     * Order an array by another array.
     *
     * It uses an array from the config
     *
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    public function orderByArray(array $array, string $key): array {
        $order = (array) $this->config('reorder_items')[$key];

        return array_replace(array_flip($order), $array);
    }
}
