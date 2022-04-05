<?php
/**
 * This file is part of Docs.
 *
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        if (is_file($this->config('docs_path').$path.'.md')) {
            $md = new ParseMarkdown($path);

            // bugfix, content must first be parsed in order to use headings
            $html = $md->parse();
            $contents = $md->getHeadings();

            $all_pages = $this->getPages($path);

            echo $this->renderTpl('page.twig', [
                'title'       => $md->getTitle(),
                'description' => !empty($md->getDescription()) ? $md->getDescription() : $this->config('site_description'),
                'content'     => $html,
                'links'       => $contents,
                'all_pages'   => $all_pages, // pages in category
            ]);
        } else if (is_file($this->config('docs_path').$path.'/README.md')) {
            $readme_path = $path.'/README';
            $pages = $this->cacheData(str_replace('/', '-', $readme_path), $this->getPages($readme_path));
            $md = new ParseMarkdown($readme_path);

            echo $this->renderTpl('category.twig', [
                'title'       => $md->getTitle(),
                'description' => $md->getDescription(),
                'content'     => $md->parse(),
                'columns'     => array_chunk($pages, (int)ceil(count($pages) / 3)),
            ]);
        } else {
            $this->show404();
        }
    }
}
