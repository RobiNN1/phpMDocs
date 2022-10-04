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

namespace RobiNN\Pmd\Controllers;

use RobiNN\Pmd\Documentation;

class HomepageController extends Documentation {
    public function show(): void {
        $reorder_items = $this->config('reorder_items')['home'];

        if (count($reorder_items) > 0) {
            static $categories = [];

            foreach ($this->getPages('', true) as $category) {
                if (in_array($category['path'], $reorder_items, true)) {
                    $categories[$category['path']] = $category;
                }
            }

            $this->orderByArray($categories, 'home');
        } else {
            $categories = $this->getPages('', true);
        }

        echo $this->tpl('home', [
            'categories' => $this->cacheData('homepage_categories', $categories),
        ]);
    }
}
