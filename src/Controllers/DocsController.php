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

use RobiNN\Docs\Documentation;
use RobiNN\Docs\ParseMarkdown;

class DocsController extends Documentation {
    /**
     * @param string $path
     *
     * @return void
     */
    public function show(string $path): void {
        if ($this->exists($path)) {
            $this->renderPage($path);
        } else if ($this->exists($path.'/README')) {
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
        $content = $this->cacheData('html-'.$path, $md->parse());
        $title = $md->getTitle();
        $description = $md->getDescription();
        $toc = $this->cacheData('toc-'.$path, $md->getHeadings());
        $all_pages = $this->getPages($path); // pages in category - left sidebar

        echo $this->tpl('page',
            compact('title', 'description', 'content', 'toc', 'all_pages')
        );
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

        echo $this->tpl('category', [
            'title'       => $md->getTitle(),
            'description' => $md->getDescription(),
            'content'     => $md->parse(),
            'columns'     => array_chunk($pages, (int)ceil(count($pages) / 3)),
        ]);
    }
}
