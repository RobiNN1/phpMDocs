<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

readonly class ParseMarkdown {
    private ParsedownExt $parsedown;

    private ?string $text;

    /**
     * @param ?string $text Path to a markdown file (relative to docs_path) or raw markdown text.
     */
    public function __construct(?string $text = null) {
        $this->parsedown = new ParsedownExt();

        $file = $text !== null ? $this->getFile($text) : null;

        if ($file !== null && is_file($file)) {
            $this->parsedown->page_dir = dirname($file);
            $this->text = (string) file_get_contents($file);
        } else {
            $this->text = $text;
        }
    }

    public function parse(): string {
        return $this->parsedown->text($this->text);
    }

    public function getTitle(): string {
        if ($this->parsedown->title === '') {
            $data = explode("\n", (string) $this->text);

            return array_reverse(explode('# ', $data[0], 2))[0];
        }

        return $this->parsedown->title;
    }

    public function getDescription(): string {
        $description = strip_tags((string) Config::get('site_description'));

        $data = explode("\n", (string) $this->text);

        if (isset($data[2])) {
            $description = strip_tags($data[2]);
            $max_length = 158; // Recommended maximum for description size

            if (mb_strlen($description) > $max_length) {
                $cut = mb_substr($description, 0, $max_length - 3);
                $pos = mb_strrpos($cut, ' ');
                $description = ($pos !== false ? mb_substr($cut, 0, $pos) : $cut).'...';
            }
        }

        return $description;
    }

    /**
     * Get headings.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHeadings(): array {
        return $this->parsedown->headings;
    }

    private function getFile(string $path): string {
        $path = Config::get('docs_path').'/'.trim($path, '/');

        return is_file($path.'.md') ? $path.'.md' : $path.'/README.md';
    }
}
