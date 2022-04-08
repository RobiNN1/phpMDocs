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

require_once __DIR__.'/vendor/autoload.php';

use RobiNN\Docs\Controllers\DocsController;
use RobiNN\Docs\Controllers\HomepageController;
use RobiNN\Docs\Controllers\SearchController;
use RobiNN\Docs\Functions;
use RobiNN\Docs\Router;

$router = new Router();
$router->setBasePath(Functions::config('site_path'));

$router->get('/', [(new HomepageController), 'show']);
$router->get('search', [(new SearchController), 'show']);
$router->get('(.*)', [(new DocsController), 'show']);

$router->set404([(new Functions), 'show404']);

$router->run();
