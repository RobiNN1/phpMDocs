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

namespace RobiNN\Docs\Controllers;

use RobiNN\Docs\Functions;
use RobiNN\Docs\ParseMarkdown;

class SearchController extends Functions {
    /**
     * @return void
     */
    public function show(): void {
        $results = [];
        $search_page = filter_input(INPUT_GET, 'page');

        if (!empty($search_page)) {
            $pages = $this->allPages();

            foreach ($pages as $doc) {
                foreach ($doc['pages'] as $page) {
                    foreach (explode(' ', $search_page) as $word) {
                        if (stripos($page['title'], $word) !== false) {
                            $results[] = [
                                'page'  => $doc['page'],
                                'title' => $page['title'],
                                'link'  => $page['link'],
                            ];
                        }
                    }
                }
            }
        }

        usort($results, fn($a, $b) => strcmp($a['title'], $b['title']));

        if (empty($results)) {
            $results['status'] = 'We didn\'t find any results!';
        }

        header('Content-Type: application/json');
        echo json_encode($results);
    }

    /**
     * @return array
     */
    private function allPages(): array {
        $results = [];

        if (is_dir($this->config('docs_path'))) {
            $dirs = $this->scanDir($this->config('docs_path'));

            foreach ($dirs as $file) {
                $md = new ParseMarkdown($file);
                $md->parse();
                $page_title = $md->getTitle();
                $headings = $md->getHeadings();

                $pages = [];
                $pages[] = [
                    'title' => $page_title,
                    'link'  => $this->config('site_url').$file,
                ];

                if (!empty($headings)) {
                    foreach ($headings as $heading) {
                        $pages[] = [
                            'title' => $heading['title'],
                            'link'  => $this->config('site_url').$file.'#'.$heading['id'],
                        ];
                    }
                }

                $results[] = [
                    'page'  => $page_title,
                    'pages' => $pages,
                ];
            }
        }

        return $this->cacheData('search_all_pages', $results);
    }
}
