<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Controllers;

use RobiNN\Pmd\Documentation;

class HomepageController extends Documentation {
    public function show(): void {
        $reorder_items = (array) $this->config('reorder_items')['home'];

        if ($reorder_items !== []) {
            static $categories = [];

            foreach ($this->getPages('', true) as $category) {
                if (in_array($category['path'], $reorder_items, true)) {
                    $categories[$category['path']] = $category;
                }
            }

            $categories = $this->orderByArray($categories, 'home');
        } else {
            $categories = $this->getPages('', true);
        }

        echo $this->tpl('home', [
            'categories' => $this->cacheData('homepage_categories', $categories),
        ]);
    }
}
