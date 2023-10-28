<?php
/**
 * This file is part of the phpMDocs.
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 */

declare(strict_types=1);

namespace RobiNN\Pmd;

/**
 * Original code by Bram(us) Van Damme <bramus@bram.us> https://github.com/bramus/router - MIT
 */
class Router {
    /**
     * @var callable The function to be executed when no route has been matched.
     */
    protected $not_found_callback;
    /**
     * @var array<string, mixed> The route patterns and their handling functions.
     */
    private array $after_routes = [];

    /**
     * @var array<string, mixed> The before middleware route patterns and their handling functions.
     */
    private array $before_routes = [];

    /**
     * @var string Current base route, used for (sub)route mounting.
     */
    private string $base_route = '';

    /**
     * @var string The Server Base Path for Router Execution.
     */
    private string $server_base_path = '';

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods.
     *
     * @param string $methods Allowed methods, | delimited.
     * @param string $pattern A route pattern such as /about/system.
     */
    public function match(string $methods, string $pattern, mixed $callback): void {
        $pattern = $this->base_route.'/'.trim($pattern, '/');
        $pattern = $this->base_route ? rtrim($pattern, '/') : $pattern;

        foreach (explode('|', $methods) as $method) {
            $this->after_routes[$method][] = [
                'pattern' => $pattern,
                'fn'      => $callback,
            ];
        }
    }

    /**
     * Shorthand for a route accessed using GET.
     *
     * @param string $pattern A route pattern such as /about/system.
     */
    public function get(string $pattern, mixed $callback): void {
        $this->match('GET', $pattern, $callback);
    }

    /**
     * Get all request headers.
     *
     * @return array<string, mixed>
     */
    public function getRequestHeaders(): array {
        $headers = [];

        // If getallheaders() is available, use that
        if (function_exists('getallheaders')) {
            $headers = (array) getallheaders();
        }

        // Method getallheaders() not available or went wrong: manually extract 'm
        foreach ($_SERVER as $name => $value) {
            if (($name === 'CONTENT_TYPE') || ($name === 'CONTENT_LENGTH') || str_starts_with($name, 'HTTP_')) {
                $name_ = ucwords(strtolower(str_replace('_', ' ', substr($name, 5))));
                $headers[str_replace([' ', 'Http'], ['-', 'HTTP'], $name_)] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get the request method used, taking overrides into account.
     */
    public function getRequestMethod(): string {
        // Take the method as found in $_SERVER
        $method = $_SERVER['REQUEST_METHOD'];

        // If it's a HEAD request override it to GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            ob_start();
            $method = 'GET';
        } // If it's a POST request, check for a method override header
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                $method = $headers['X-HTTP-Method-Override'];
            }
        }

        return $method;
    }

    /**
     * Execute the router: Loop all defined before middlewares and routes,
     * and execute the handling function if a match was found.
     *
     * @param ?callable $callback Function to be executed after a matching route was handled (= after router middleware).
     */
    public function run(?callable $callback = null): bool {
        // Define which method we need to handle
        $requested_method = $this->getRequestMethod();

        // Handle all before middlewares
        if (isset($this->before_routes[$requested_method])) {
            $this->handle($this->before_routes[$requested_method]);
        }

        // Handle all routes
        $num_handled = 0;
        if (isset($this->after_routes[$requested_method])) {
            $num_handled = $this->handle($this->after_routes[$requested_method], true);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($num_handled === 0) {
            $this->trigger404();
        } // If a route was handled, perform the finish callback (if any)
        elseif ($callback && is_callable($callback)) {
            $callback();
        }

        // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            ob_end_clean();
        }

        // Return true if a route was handled, false otherwise
        return $num_handled !== 0;
    }

    public function set404(mixed $callback): void {
        $this->not_found_callback = $callback;
    }

    public function trigger404(): void {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');

        if (is_callable($this->not_found_callback)) {
            $this->invoke($this->not_found_callback);
        }
    }

    /**
     * Define the current relative URI.
     */
    public function getCurrentUri(): string {
        // Get the current Request URI and remove a rewrite base path
        // from it (= allows one to run the router in a sub folder)
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));

        // Don't take query params into account on the URL
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/'.trim($uri, '/');// Remove trailing slash + enforce a slash at the start
    }

    /**
     * Return server base Path, and define it if isn't defined.
     */
    public function getBasePath(): string {
        return $this->server_base_path;
    }

    /**
     * Explicilty sets the server base path. To be used when your entry script path differs from your entry URLs.
     *
     * @see https://github.com/bramus/router/issues/82#issuecomment-466956078
     */
    public function setBasePath(string $base_path): void {
        $this->server_base_path = $base_path;
    }

    /**
     * Replace all curly braces matches {} into word patterns (like Laravel).
     * Checks if there is a routing match.
     *
     * @param ?array<int, mixed> $matches
     */
    private function patternMatches(string $pattern, string $uri, ?array &$matches): bool {
        // Replace all curly braces matches {} into word patterns (like Laravel)
        $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern);

        // we may have a match!
        return (bool) preg_match_all('#^'.$pattern.'$#', $uri, $matches, PREG_OFFSET_CAPTURE);
    }

    /**
     * Handle a set of routes: if a match is found, execute the related handling function.
     *
     * @param array<string, mixed> $routes         Collection of route patterns and their handling functions
     * @param bool                 $quit_after_run Does the handle function need to quit after one route was matched?
     *
     * @return int The number of routes handled
     */
    private function handle(array $routes, bool $quit_after_run = false): int {
        $num_handled = 0; // Counter to keep track of the number of routes we've handled

        foreach ($routes as $route) {
            // is there a valid match?
            if ($this->patternMatches($route['pattern'], $this->getCurrentUri(), $matches)) {
                // Call the handling function with the URL parameters if the desired input is callable
                $this->invoke($route['fn'], $this->extractMatchedUrlParams($matches));

                $num_handled++;

                // If we need to quit, then quit
                if ($quit_after_run) {
                    break;
                }
            }
        }

        // Return the number of routes handled
        return $num_handled;
    }

    /**
     * Extract matched url params.
     *
     * @param array<int, mixed> $matches
     *
     * @return array<int, string|null>
     */
    private function extractMatchedUrlParams(array $matches): array {
        // Rework matches to only contain the matches, not the orig string
        $matches = array_slice($matches, 1);

        // Extract the matched URL parameters (and only the parameters)
        return array_map(static function ($match, $index) use ($matches) {
            // We have the following parameter: take the substring from the current param
            // position until the next one's position (thank you PREG_OFFSET_CAPTURE)
            if (isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0]) && ($matches[$index + 1][0][1] > -1)) {
                return trim(substr((string) $match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
            }

            return isset($match[0][0]) && $match[0][1] !== -1 ? trim((string) $match[0][0], '/') : null;
        }, $matches, array_keys($matches));
    }

    /**
     * @param array<int, string|null> $params
     */
    private function invoke(mixed $callback, array $params = []): void {
        if (is_string($callback) && method_exists($callback, 'show')) {
            $callback = [new $callback(), 'show'];
        }

        if (is_callable($callback)) {
            call_user_func_array($callback, $params);
        } else {
            echo 'Function is not callable';
        }
    }
}
