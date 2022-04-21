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

class DocsController extends Functions {
    /**
     * @param string $path
     *
     * @return void
     */
    public function show(string $path): void {
        if (is_file(self::config('docs_path').$path.'.md')) {
            $this->renderPage($path);
        } else if (is_file(self::config('docs_path').$path.'/README.md')) {
            $this->renderCategory($path);
        } else {
            $this->show404();
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function renderPage(string $path): void {
        $md = new ParseMarkdown($path);

        // bugfix, content must first be parsed in order to use headings
        $html = $md->parse();
        $contents = $md->getHeadings();

        $all_pages = $this->getPages($path);

        echo $this->renderTpl('page.twig', [
            'title'       => $md->getTitle(),
            'description' => $md->getDescription(),
            'content'     => $html,
            'links'       => $contents,
            'all_pages'   => $all_pages, // pages in category
        ]);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function renderCategory(string $path): void {
        $readme_path = $path.'/README';
        $pages = $this->cacheData(str_replace('/', '-', $readme_path), $this->getPages($readme_path));
        $md = new ParseMarkdown($readme_path);

        echo $this->renderTpl('category.twig', [
            'title'       => $md->getTitle(),
            'description' => $md->getDescription(),
            'content'     => $md->parse(),
            'columns'     => array_chunk($pages, (int)ceil(count($pages) / 3)),
        ]);
    }
}
