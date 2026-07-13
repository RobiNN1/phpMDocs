<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Controllers;

use JsonException;
use RobiNN\Pmd\Config;
use RobiNN\Pmd\Documentation;
use RobiNN\Pmd\ParseMarkdown;

class SearchController extends Documentation {
    public function show(): void {
        $results = [];
        $search_page = filter_input(INPUT_GET, 'page');

        if ($search_page !== null && $search_page !== '') {
            foreach ($this->allPages() as $page) {
                foreach (explode(' ', (string) $search_page) as $word) {
                    if (stripos($page['title'], $word) !== false) {
                        $results[$page['link']] = $page; // Keyed by link to avoid duplicates.
                        break;
                    }
                }
            }
        }

        $results = array_values($results);

        if ($results === []) {
            $results['status'] = "We didn't find any results!";
        }

        header('Content-Type: application/json');
        try {
            echo json_encode($results, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Get all pages.
     *
     * @return array<int, array<string, string>>
     */
    private function allPages(): array {
        return $this->cacheData('search:all_pages', function (): array {
            $pages = [];

            if (!is_dir(Config::get('docs_path'))) {
                return $pages;
            }

            foreach ($this->scanDir(Config::get('docs_path')) as $file) {
                $md = new ParseMarkdown($file);
                $md->parse();
                $page_title = $md->getTitle();

                $pages[] = [
                    'page'  => $page_title,
                    'title' => $page_title,
                    'link'  => Config::get('site_url').$file,
                ];

                foreach ($md->getHeadings() as $heading) {
                    $pages[] = [
                        'page'  => $page_title,
                        'title' => $heading['title'],
                        'link'  => Config::get('site_url').$file.'#'.$heading['id'],
                    ];
                }
            }

            return $pages;
        });
    }
}
