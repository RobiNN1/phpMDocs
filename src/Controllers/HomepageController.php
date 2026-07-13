<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Controllers;

use RobiNN\Pmd\Config;
use RobiNN\Pmd\Documentation;
use RobiNN\Pmd\Helpers;
use RobiNN\Pmd\Template;

class HomepageController extends Documentation {
    public function show(): void {
        $categories = $this->getPages();
        $reorder_items = (array) Config::get('reorder_items')['home'];

        if ($reorder_items !== []) {
            $by_path = array_column($categories, null, 'path');
            $categories = Helpers::orderByArray(array_intersect_key($by_path, array_flip($reorder_items)), 'home');
        }

        echo Template::render('home', ['categories' => $categories]);
    }
}
