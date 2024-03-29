<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Controllers;

use RobiNN\Pmd\Documentation;
use RobiNN\Pmd\ParseMarkdown;

class DocController extends Documentation {
    public function show(string $path): void {
        if ($this->exists($path)) {
            $this->renderPage($path);
        } elseif ($this->exists($path.'/README')) {
            if ($this->config('category_page')) {
                $this->renderCategory($path);
            } else {
                $this->redirectToPage($path);
            }
        } else {
            $this->show404();
        }
    }

    private function renderPage(string $path): void {
        $md = new ParseMarkdown($path);

        // bugfix, content must first be parsed to use headings
        $content = $this->cacheData('html_'.$path, $md->parse());
        $title = $md->getTitle();
        $description = $md->getDescription();
        $toc = $this->cacheData('toc_'.$path, $md->getHeadings());
        $all_pages = $this->getPages($path); // pages in category - left sidebar

        echo $this->tpl('page', compact('title', 'description', 'content', 'toc', 'all_pages'));
    }

    private function renderCategory(string $path): void {
        $readme_path = $path.'/README';
        $pages = (array) $this->cacheData(str_replace('/', '_', $readme_path), $this->getPages($readme_path));
        $md = new ParseMarkdown($readme_path);

        echo $this->tpl('category', [
            'title'       => $md->getTitle(),
            'description' => $md->getDescription(),
            'content'     => $md->parse(),
            'columns'     => array_chunk($pages, max(1, (int) ceil(count($pages) / 3))),
        ]);
    }

    private function redirectToPage(string $path): void {
        $location = $this->getPages($path)[0]['url'];

        if (!headers_sent()) {
            header('Location: '.$location);
        } else {
            echo '<script>window.location.replace("'.$location.'");</script>';
        }
    }
}
