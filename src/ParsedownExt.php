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

namespace RobiNN\Pmd;

use Parsedown;

class ParsedownExt extends Parsedown {
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $headings = [];

    public string $title = '';

    public function __construct(private readonly Documentation $docs) {
    }

    /**
     * Get headings.
     *
     * @param mixed $Line
     *
     * @return ?array<string, mixed>
     */
    protected function blockHeader(mixed $Line): ?array {
        $block = parent::blockHeader($Line);

        // Set headings
        if (isset($block['element']['text'])) {
            $page_title = $block['element']['text'];
            $level = $block['element']['name'];
            $id = $this->createIdFromTitle($page_title);

            if ($level === 'h1') {
                $this->title = $page_title;
            }

            if (in_array($level, ['h2', 'h3', 'h4', 'h5', 'h6'])) {
                $this->headings[] = [
                    'title' => $page_title,
                    'id'    => $id,
                ];

                $block['element']['text'] = '<'.$level.' id="'.$id.'"><a href="#'.$id.'">'.$page_title.'</a></'.$level.'>';
            }
        }

        return $block;
    }

    /**
     * Create id from title.
     *
     * @param string $title
     *
     * @return string
     */
    public function createIdFromTitle(string $title): string {
        $title = preg_replace('~[^\pL\d]+~u', '-', $title);
        $title = preg_replace('~[^-\w]+~', '', $title);
        $title = trim($title, '-');
        $title = preg_replace('~-+~', '-', $title);

        return strtolower($title);
    }

    /**
     * Fix image paths and add css class.
     *
     * @param mixed $Excerpt
     *
     * @return ?array<string, mixed>
     */
    protected function inlineImage(mixed $Excerpt): ?array {
        $inline = parent::inlineImage($Excerpt);

        if (isset($inline)) {
            if (!str_starts_with($inline['element']['attributes']['src'], 'http')) {
                $path = $this->docs->config('docs_path').'/'.str_replace('../', '', $inline['element']['attributes']['src']);
                $path = realpath($path);

                if (is_file($path)) {
                    $image_type = pathinfo($path, PATHINFO_EXTENSION);
                    $img_data = base64_encode(file_get_contents($path));
                } else {
                    $image_type = 'n\a';
                    $img_data = 'n\a';
                }

                $inline['element']['attributes']['src'] = 'data:image/'.$image_type.';base64,'.$img_data;
            }

            $inline['element']['attributes']['class'] = 'max-w-full h-auto';
        }

        return $inline;
    }

    /**
     * Add class to the table.
     *
     * @param mixed $Line
     * @param mixed $Block
     *
     * @return ?array<string, mixed>
     */
    protected function blockTable(mixed $Line, mixed $Block = null): ?array {
        $block = parent::blockTable($Line, $Block);

        if (isset($block)) {
            $block['element']['attributes']['class'] = 'table-auto w-full text-left';
        }

        return $block;
    }

    /**
     * Remove .md from relative paths.
     *
     * @param mixed $Excerpt
     *
     * @return ?array<string, mixed>
     */
    protected function inlineLink(mixed $Excerpt): ?array {
        $block = parent::inlineLink($Excerpt);

        if (isset($block)) {
            $href = $block['element']['attributes']['href'];

            if (!str_starts_with($href, 'http')) {
                $block['element']['attributes']['href'] = str_ends_with($href, '.md') ? str_replace('.md', '', $href) : $href;
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
     * Only 1 class can be added.
     *
     * @param mixed $Line
     *
     * @return ?array<string, mixed>
     */
    protected function blockFencedCode(mixed $Line): ?array {
        $block = parent::blockFencedCode($Line);

        if (isset($block) && str_contains($Line['text'], '{') && str_ends_with($Line['text'], '}')) {
            $parts = explode('{', $Line['text'], 2);
            $Line['text'] = trim($parts[0]);

            $block['element']['attributes']['class'] = trim($parts[1], '.}');
        }

        return $block;
    }
}
