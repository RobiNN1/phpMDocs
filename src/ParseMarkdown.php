<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

readonly class ParseMarkdown {
    private ParsedownExt $parsedown;

    private Documentation $docs;

    private ?string $text;

    public function __construct(?string $text = null) {
        $this->docs = new Documentation();
        $this->parsedown = new ParsedownExt($this->docs);
        $this->text = is_file($this->getFile($text)) ? file_get_contents($this->getFile($text)) : $text;
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
        $description = strip_tags((string) $this->docs->config('site_description'));

        $data = explode("\n", (string) $this->text);

        if (isset($data[2])) {
            $description = strip_tags($data[2]);
            $max_length = 158; // Recommended maximum for description size

            if (strlen($description) > $max_length) {
                $offset = ($max_length - 3) - strlen($description);
                $description = substr($description, 0, strrpos($description, ' ', $offset)).'...';
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
        $path = $this->docs->config('docs_path').'/'.trim($path, '/');

        return is_file($path.'.md') ? $path.'.md' : $path.'/README.md';
    }
}
