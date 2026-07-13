<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Controllers;

use RobiNN\Pmd\Config;
use RobiNN\Pmd\Documentation;
use RobiNN\Pmd\ParseMarkdown;
use RobiNN\Pmd\Template;

class DocController extends Documentation {
    public function show(string $path): void {
        if ($this->exists($path)) {
            $this->renderPage($path);
        } elseif ($this->exists($path.'/README')) {
            if (Config::get('category_page')) {
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

        // One cache entry per page, content must be parsed first so that the title and headings are available.
        $page = (array) $this->cacheData('page:'.$path, static fn (): array => [
            'content'     => $md->parse(),
            'title'       => $md->getTitle(),
            'description' => $md->getDescription(),
            'toc'         => $md->getHeadings(),
        ]);

        $page['all_pages'] = $this->getPages($path); // pages in category - left sidebar

        echo Template::render('page', $page);
    }

    private function renderCategory(string $path): void {
        $readme_path = $path.'/README';
        $pages = $this->getPages($readme_path);
        $md = new ParseMarkdown($readme_path);

        $category = (array) $this->cacheData('category:'.$path, static fn (): array => [
            'content'     => $md->parse(),
            'title'       => $md->getTitle(),
            'description' => $md->getDescription(),
        ]);

        $category['columns'] = array_chunk($pages, max(1, (int) ceil(count($pages) / 3)));

        echo Template::render('category', $category);
    }

    private function redirectToPage(string $path): void {
        $pages = $this->getPages($path);

        if ($pages === []) {
            $this->show404();

            return;
        }

        $location = $pages[0]['url'];

        if (!headers_sent()) {
            header('Location: '.$location);
        } else {
            echo '<script>window.location.replace("'.$location.'");</script>';
        }
    }
}
