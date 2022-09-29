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

namespace RobiNN\Docs;

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
     * Render template.
     *
     * @param string               $tpl
     * @param array<string, mixed> $data
     *
     * @return string
     */
    public function tpl(string $tpl, array $data = []): string {
        try {
            $loader = new FilesystemLoader(__DIR__.'/../templates');
            $twig = new Environment($loader, [
                'cache' => __DIR__.'/../cache/twig',
                'debug' => $this->config('twig_debug'),
            ]);

            if ($this->config('twig_debug')) {
                $twig->addExtension(new DebugExtension());
            }

            $twig->addFunction(new TwigFunction('config', [$this, 'config']));
            $twig->addFunction(new TwigFunction('path', [$this, 'path']));
            $twig->addFunction(new TwigFunction('is_active', [$this, 'isActive']));

            return $twig->render($tpl.'.twig', $data);
        } catch (Exception $e) {
            return $e->getMessage().' in '.$e->getFile().' at line: '.$e->getLine();
        }
    }

    /**
     * Check if a link is active.
     *
     * @param string $page
     * @param bool   $start_with
     *
     * @return bool
     */
    public function isActive(string $page, bool $start_with = false): bool {
        $uri = str_replace($this->config('site_path'), '/', $_SERVER['REQUEST_URI']);
        $page = preg_replace('/(\/+)/', '/', $page); // Remove trailing slashes

        return ($uri === $page || $uri === $page.'/') || ($start_with ? str_starts_with($uri, $page) : null);
    }

    /**
     * Get a relative path to docs from url.
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path = ''): string {
        $count = substr_count($this->currentPath(), '/');
        $docs_path = str_repeat('../', $count);

        return $path !== '' ? $docs_path.$path : $docs_path;
    }

    /**
     * Get a current path.
     *
     * @return string
     */
    public function currentPath(): string {
        $current_path = html_entity_decode($_SERVER['REQUEST_URI']);

        // Remove extra slashes and domain
        if (strcmp($this->config('site_path'), '/') !== 0) {
            $current_path = str_replace($this->config('site_path'), '', $current_path);
        } else {
            $current_path = ltrim($current_path, '/');
        }

        return $current_path;
    }

    /**
     * Get recursively all files and dirs.
     *
     * @param string $dir
     * @param bool   $remove_md_ext
     *
     * @return array<int, string>
     */
    public function scanDir(string $dir, bool $remove_md_ext = true): array {
        $dirs = [];

        foreach (scandir($dir) as $filename) {
            if ($filename[0] === '.') {
                continue;
            }

            if (!in_array($filename, $this->config('ignore_files'), true)) {
                $file_path = $dir.'/'.$filename;

                if (is_dir($file_path)) {
                    foreach ($this->scanDir($file_path) as $child_filename) {
                        $dirs[] = $filename.'/'.$child_filename;
                    }
                }
                $dirs[] = $filename;
            }
        }

        natsort($dirs);
        $dirs = array_values($dirs);

        return $remove_md_ext ? array_map(static fn ($name) => strtr($name, ['.md' => '']), $dirs) : $dirs;
    }

    /**
     * Get an array of pages in category.
     *
     * @param string $path
     * @param bool   $description
     *
     * @return array<int, array<string, string>>
     */
    public function getPages(string $path = '', bool $description = false): array {
        static $pages = [];

        $path = $this->getCategory($path);

        if (is_dir($this->config('docs_path').$path)) {
            $dir = new DirectoryIterator($this->config('docs_path').$path);

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

        usort($pages, static fn ($a, $b) => strcmp($a['id'], $b['id']));

        return $this->cacheData('get_pages'.$path, $pages);
    }

    /**
     * Check if page exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists(string $path): bool {
        return is_file($this->config('docs_path').$path.'.md');
    }

    /**
     * Show error 404 page.
     *
     * @return void
     */
    public function show404(): void {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        echo $this->tpl('404');
    }

    /**
     * Cache data.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function cacheData(string $key, mixed $value): mixed {
        $key = strtr($key, ['/' => '-']);

        if ($this->config('cache')['enabled'] && $this->cache->isConnected()) {
            if ($this->cache->has($key)) {
                $value = $this->cache->get($key);
            } else {
                $this->cache->set($key, $value, $this->config('cache')['expiration']);
            }
        }

        return $value;
    }

    /**
     * Get category path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getCategory(string $path): string {
        if ($this->exists($path)) {
            $paths = explode('/', $path);
            array_pop($paths); // Remove the page name so that we can retrieve its folder
            $path = implode('/', $paths);
        }

        return $path;
    }

    /**
     * Get Docs config.
     *
     * @param ?string $key
     *
     * @return mixed
     */
    public function config(?string $key = null): mixed {
        static $config = [];

        $config = (array) require __DIR__.'/../config.php';

        $config['site_url'] .= $config['site_path'];

        return $config[$key] ?? null;
    }
}
