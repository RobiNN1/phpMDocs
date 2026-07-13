<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd\Tests;

use RobiNN\Pmd\Router;

final class RouterTest extends PmdTestCase {
    /**
     * @param array<string, mixed> $routes
     */
    private function dispatch(string $uri, array $routes, ?callable $not_found = null): bool {
        $_SERVER['REQUEST_URI'] = $uri;

        $router = new Router();

        foreach ($routes as $pattern => $callback) {
            $router->get($pattern, $callback);
        }

        $router->set404($not_found ?? static function (): void {
        });

        ob_start();
        $handled = $router->run();
        ob_end_clean();

        return $handled;
    }

    public function testHomepageRoute(): void {
        $called = false;

        $handled = $this->dispatch('/', ['/' => static function () use (&$called): void {
            $called = true;
        }]);

        $this->assertTrue($handled);
        $this->assertTrue($called);
    }

    public function testWildcardRoutePassesPath(): void {
        $captured = null;

        $this->dispatch('/category_a/page-one', ['(.*)' => static function (?string $path = null) use (&$captured): void {
            $captured = $path;
        }]);

        $this->assertSame('category_a/page-one', $captured);
    }

    public function testQueryStringIsIgnored(): void {
        $captured = null;

        $this->dispatch('/search?page=test', ['search' => static function () use (&$captured): void {
            $captured = 'search';
        }]);

        $this->assertSame('search', $captured);
    }

    public function test404(): void {
        $not_found = false;

        $handled = $this->dispatch('/missing', ['only-this' => static function (): void {
        }], static function () use (&$not_found): void {
            $not_found = true;
        });

        $this->assertFalse($handled);
        $this->assertTrue($not_found);
    }
}
