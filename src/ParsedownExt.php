<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

use Override;
use Parsedown;

class ParsedownExt extends Parsedown {
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $headings = [];

    public string $title = '';

    /**
     * @var ?string Absolute path to the directory of the currently parsed page, for relative images.
     */
    public ?string $page_dir = null;

    /**
     * Create id from title.
     */
    public function createIdFromTitle(string $title): string {
        $title = preg_replace('~[^\pL\d]+~u', '-', $title);
        $title = preg_replace('~[^\-\w]+~', '', (string) $title);
        $title = trim((string) $title, '-');
        $title = preg_replace('~-+~', '-', $title);

        return strtolower((string) $title);
    }

    /**
     * Get headings.
     *
     * @return ?array<string, mixed>
     */
    #[Override]
    protected function blockHeader(mixed $Line): ?array {
        $block = parent::blockHeader($Line);

        // Set headings
        if (isset($block['element']['handler']['argument'])) {
            $page_title = (string) $block['element']['handler']['argument'];
            $level = $block['element']['name'];
            $id = $this->createIdFromTitle($page_title);

            if ($level === 'h1') {
                $this->title = $page_title;
            }

            if (in_array($level, ['h2', 'h3', 'h4', 'h5', 'h6'], true)) {
                $this->headings[] = [
                    'title' => $page_title,
                    'id'    => $id,
                ];

                $block['element']['attributes']['id'] = $id;
                $block['element']['handler']['argument'] = '['.$page_title.'](#'.$id.')';
            }
        }

        return $block;
    }

    /**
     * Fix image paths and add a CSS class.
     *
     * @return ?array<string, mixed>
     */
    #[Override]
    protected function inlineImage(mixed $Excerpt): ?array {
        $inline = parent::inlineImage($Excerpt);

        if (isset($inline)) {
            $src = (string) $inline['element']['attributes']['src'];

            if (!str_starts_with($src, 'http') && !str_starts_with($src, 'data:')) {
                $path = $this->resolveImage($src);

                if ($path !== null) {
                    $image_type = pathinfo($path, PATHINFO_EXTENSION);
                    $img_data = base64_encode((string) file_get_contents($path));

                    $inline['element']['attributes']['src'] = 'data:image/'.$image_type.';base64,'.$img_data;
                }
            }

            $inline['element']['attributes']['class'] = 'img-fluid';
        }

        return $inline;
    }

    /**
     * Resolve an image relative to the current page (or the docs root), only files inside the docs directory are allowed.
     */
    private function resolveImage(string $src): ?string {
        $docs_root = realpath(Config::get('docs_path'));

        if ($docs_root === false) {
            return null;
        }

        foreach ([$this->page_dir, $docs_root] as $base) {
            if ($base === null) {
                continue;
            }

            $path = realpath($base.'/'.$src);

            if ($path !== false && is_file($path) && str_starts_with($path, $docs_root.DIRECTORY_SEPARATOR)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Add a class to the table.
     *
     * @return ?array<string, mixed>
     */
    #[Override]
    protected function blockTable(mixed $Line, mixed $Block = null): ?array {
        $block = parent::blockTable($Line, $Block);

        if (isset($block)) {
            $block['element']['attributes']['class'] = 'table-responsive';
        }

        return $block;
    }

    /**
     * Remove .md from relative paths.
     *
     * @return ?array<string, mixed>
     */
    #[Override]
    protected function inlineLink(mixed $Excerpt): ?array {
        $block = parent::inlineLink($Excerpt);

        if (isset($block)) {
            $href = $block['element']['attributes']['href'];

            if (!str_starts_with((string) $href, 'http')) {
                $block['element']['attributes']['href'] = str_ends_with((string) $href, '.md') ? str_replace('.md', '', (string) $href) : $href;
            } else {
                $block['element']['attributes']['target'] = '_blank';
            }
        }

        return $block;
    }

    /**
     * Add custom class to the code blocks.
     *
     * ```php {.custom-class}
     * // code
     * ```
     * It will add .custom-class to the <pre> tag.
     * Only one class can be added.
     *
     * @return ?array<string, mixed>
     */
    #[Override]
    protected function blockFencedCode(mixed $Line): ?array {
        $block = parent::blockFencedCode($Line);

        if (isset($block) && str_contains((string) $Line['text'], '{') && str_ends_with((string) $Line['text'], '}')) {
            $parts = explode('{', (string) $Line['text'], 2);

            $block['element']['attributes']['class'] = trim($parts[1], '.}');
        }

        return $block;
    }
}
