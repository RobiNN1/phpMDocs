<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

$router = new RobiNN\Pmd\Router();

$docs = new RobiNN\Pmd\Documentation();
$router->set404($docs->show404(...));
$router->setBasePath(RobiNN\Pmd\Config::get('site_path'));

/**
 * @uses RobiNN\Pmd\Controllers\HomepageController::show()
 * @uses RobiNN\Pmd\Controllers\SearchController::show()
 * @uses RobiNN\Pmd\Controllers\DocController::show()
 */
$router->get('/', RobiNN\Pmd\Controllers\HomepageController::class);
$router->get('search', RobiNN\Pmd\Controllers\SearchController::class);
$router->get('(.*)', RobiNN\Pmd\Controllers\DocController::class); // It must be at the end

$router->run();
