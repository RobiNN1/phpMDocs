<?php
/**
 * This file is part of Docs.
 *
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RobiNN\Docs;

use DirectoryIterator;
use Exception;
use RobiNN\Cache\Cache;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Functions {
    /**
     * @var ?Cache
     */
    private ?Cache $cache = null;

    public function __construct() {
        if ($this->config('cache')['enable']) {
            $this->cache = new Cache($this->config('cache'));
        }
    }

    /**
     * Render template
     *
     * @param string $tpl
     * @param array  $data
     *
     * @return string
     */
    public function renderTpl(string $tpl, array $data = []): string {
        try {
            $loader = new FilesystemLoader(__DIR__.'/../twig');
            $twig = new Environment($loader, [
                'cache' => __DIR__.'/../cache/twig',
                'debug' => $this->config('twig_debug'),
            ]);

            if ($this->config('twig_debug')) {
                $twig->addExtension(new DebugExtension());
            }

            $twig->addFunction(new TwigFunction('config', [$this, 'config']));
            $twig->addFunction(new TwigFunction('path', [$this, 'path']));
            $twig->addFunction(new TwigFunction('svg', [$this, 'svg'], ['is_safe' => ['html']]));
            $twig->addFunction(new TwigFunction('is_active', [$this, 'isActive']));

            return $twig->render($tpl, $data);
        } catch (Exception $e) {
            return $e->getMessage().' File: '.$e->getFile().' Line: '.$e->getLine();
        }
    }

    /**
     * Check if link is active
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
     * Get relative path to docs from url
     *
     * @param string $path
     *
     * @return string
     */
    public static function path(string $path = ''): string {
        $current_path = html_entity_decode($_SERVER['REQUEST_URI']);

        if (strcmp(self::config('site_path'), '/') != 0) {
            $current_path = str_replace(self::config('site_path'), '', $current_path);
        } else {
            $current_path = ltrim($current_path, '/');
        }

        $count = substr_count($current_path, '/');
        $docs_path = str_repeat('../', $count);

        return !empty($path) ? $docs_path.$path : $docs_path;
    }

    /**
     * Get recursively all files and dirs
     *
     * @param string $dir
     * @param bool   $remove_md_ext
     *
     * @return array
     */
    public static function scanDir(string $dir, bool $remove_md_ext = true): array {
        $dirs = [];

        foreach (scandir($dir) as $filename) {
            if ($filename[0] === '.') {
                continue;
            }

            if (!in_array($filename, self::config('ignore_files'))) {
                $file_path = $dir.'/'.$filename;

                if (is_dir($file_path)) {
                    foreach (self::scanDir($file_path) as $child_filename) {
                        $dirs[] = $filename.'/'.$child_filename;
                    }
                }
                $dirs[] = $filename;
            }
        }

        natsort($dirs);
        $dirs = array_values($dirs);

        return $remove_md_ext ? array_map(fn($name) => strtr($name, ['.md' => '']), $dirs) : $dirs;
    }

    /**
     * Get file
     *
     * @param string $path
     *
     * @return string
     */
    public static function getFile(string $path): string {
        $path = self::config('docs_path').trim($path, '/');
        return is_file($path.'.md') ? $path.'.md' : $path.'/README.md';
    }

    /**
     * Get array of pages in category
     *
     * @param string $path
     * @param bool   $description
     *
     * @return array
     */
    public function getPages(string $path = '', bool $description = false): array {
        static $pages = [];

        $path = $this->getCategory($path);

        if (is_dir($this->config('docs_path').$path)) {
            $dir = new DirectoryIterator($this->config('docs_path').$path);

            foreach ($dir as $file) {
                if (!$file->isDot() && !in_array($file->getFilename(), $this->config('ignore_files'))) {
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

        usort($pages, fn($a, $b) => strcmp($a['id'], $b['id']));

        return $this->cacheData('get_pages'.$path, $pages);
    }

    /**
     * Get svg icon from file
     *
     * @param string $icon
     * @param int    $size
     * @param string $class
     *
     * @return ?string
     */
    public static function svg(string $icon, int $size = 16, string $class = ''): ?string {
        $file = __DIR__.'/../assets/icons/'.$icon.'.svg';

        if (is_file($file)) {
            $content = trim(file_get_contents($file));
            $attributes = 'width="'.$size.'" height="'.$size.'" fill="currentColor" class="bi'.(!empty($class) ? ' '.$class : '').'" viewBox="0 0 16 16"';
            return preg_replace('~<svg([^<>]*)>~', '<svg xmlns="http://www.w3.org/2000/svg" '.$attributes.'>', $content);
        }

        return null;
    }

    /**
     * Show error 404 page
     *
     * @return void
     */
    public function show404(): void {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        echo $this->renderTpl('404.twig');
    }

    /**
     * Cache data
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function cacheData(string $key, mixed $value): mixed {
        if ($this->config('cache')['enable'] && $this->cache->isConnected()) {
            if ($this->cache->has($key)) {
                $value = $this->cache->get($key);
            } else {
                $this->cache->set($key, $value, $this->config('cache')['expiration']);
            }
        }

        return $value;
    }

    /**
     * Get category path
     *
     * @param string $path
     *
     * @return string
     */
    public function getCategory(string $path): string {
        if (is_file($this->config('docs_path').$path.'.md')) {
            $path = explode('/', $path);
            array_pop($path);
            $path = implode('/', $path);
        }

        return $path;
    }

    /**
     * Get Docs config
     *
     * @param ?string $key
     *
     * @return mixed
     */
    public static function config(?string $key = null): mixed {
        static $config = [];

        $config = (array)require __DIR__.'/../config.php';

        $config['site_url'] = $config['site_url'].$config['site_path'];

        return $config[$key] ?? null;
    }
}
