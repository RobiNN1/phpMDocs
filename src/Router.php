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

namespace RobiNN\Docs;

/**
 * @author    Bram(us) Van Damme <bramus@bram.us>
 * @copyright Copyright (c), 2013 Bram(us) Van Damme
 * @license   MIT public license
 *
 * @link      https://github.com/bramus/router
 */
class Router {
    /**
     * @var array The route patterns and their handling functions
     */
    private array $afterRoutes = [];

    /**
     * @var array The before middleware route patterns and their handling functions
     */
    private array $beforeRoutes = [];

    /**
     * @var callable The function to be executed when no route has been matched
     */
    protected $notFoundCallback;

    /**
     * @var string Current base route, used for (sub)route mounting
     */
    private string $baseRoute = '';

    /**
     * @var string The Server Base Path for Router Execution
     */
    private string $serverBasePath = '';

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods.
     *
     * @param string $methods  Allowed methods, | delimited
     * @param string $pattern  A route pattern such as /about/system
     * @param mixed  $callback Callback
     */
    public function match(string $methods, string $pattern, mixed $callback): void {
        $pattern = $this->baseRoute.'/'.trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

        foreach (explode('|', $methods) as $method) {
            $this->afterRoutes[$method][] = [
                'pattern' => $pattern,
                'fn'      => $callback,
            ];
        }
    }

    /**
     * Shorthand for a route accessed using GET.
     *
     * @param string $pattern  A route pattern such as /about/system
     * @param mixed  $callback Callback
     */
    public function get(string $pattern, mixed $callback): void {
        $this->match('GET', $pattern, $callback);
    }

    /**
     * Get all request headers.
     *
     * @return array The request headers
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
     *
     * @return string The Request method to handle
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
     * Execute the router: Loop all defined before middleware's and routes, and execute the handling function if a match was found.
     *
     * @param ?callable $callback Function to be executed after a matching route was handled (= after router middleware)
     */
    public function run(?callable $callback = null): bool {
        // Define which method we need to handle
        $requestedMethod = $this->getRequestMethod();

        // Handle all before middlewares
        if (isset($this->beforeRoutes[$requestedMethod])) {
            $this->handle($this->beforeRoutes[$requestedMethod]);
        }

        // Handle all routes
        $numHandled = 0;
        if (isset($this->afterRoutes[$requestedMethod])) {
            $numHandled = $this->handle($this->afterRoutes[$requestedMethod], true);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($numHandled === 0) {
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
        return $numHandled !== 0;
    }

    /**
     * Set the 404 handling function.
     *
     * @param mixed $callback The function to be executed
     */
    public function set404(mixed $callback): void {
        $this->notFoundCallback = $callback;
    }

    /**
     * Triggers 404 response.
     *
     * @return void
     */
    public function trigger404(): void {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');

        if (is_callable($this->notFoundCallback)) {
            $this->invoke($this->notFoundCallback);
        }
    }

    /**
     * Replace all curly braces matches {} into word patterns (like Laravel)
     * Checks if there is a routing match.
     *
     * @param string $pattern
     * @param string $uri
     * @param ?array $matches
     *
     * @return bool
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
     * @param array $routes       Collection of route patterns and their handling functions
     * @param bool  $quitAfterRun Does the handle function need to quit after one route was matched?
     *
     * @return int The number of routes handled
     */
    private function handle(array $routes, bool $quitAfterRun = false): int {
        $numHandled = 0; // Counter to keep track of the number of routes we've handled

        foreach ($routes as $route) {
            // is there a valid match?
            if ($this->patternMatches($route['pattern'], $this->getCurrentUri(), $matches)) {
                // Call the handling function with the URL parameters if the desired input is callable
                $this->invoke($route['fn'], $this->extractMatchedUrlParams($matches));

                $numHandled++;

                // If we need to quit, then quit
                if ($quitAfterRun) {
                    break;
                }
            }
        }

        // Return the number of routes handled
        return $numHandled;
    }

    /**
     * Extract matched url params.
     *
     * @param array $matches
     *
     * @return array
     */
    private function extractMatchedUrlParams(array $matches): array {
        // Rework matches to only contain the matches, not the orig string
        $matches = array_slice($matches, 1);

        // Extract the matched URL parameters (and only the parameters)
        return array_map(static function ($match, $index) use ($matches) {
            // We have the following parameter: take the substring from the current param
            // position until the next one's position (thank you PREG_OFFSET_CAPTURE)
            if (
                isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0]) && ($matches[$index + 1][0][1] > -1)
            ) {
                return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
            }

            return isset($match[0][0]) && $match[0][1] !== -1 ? trim($match[0][0], '/') : null;
        }, $matches, array_keys($matches));
    }

    /**
     * @param mixed $callback
     * @param array $params
     *
     * @return void
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

    /**
     * Define the current relative URI.
     */
    public function getCurrentUri(): string {
        // Get the current Request URI and remove a rewrite base path from it (= allows one to run the router in a sub folder)
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));

        // Don't take query params into account on the URL
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
        return '/'.trim($uri, '/');
    }

    /**
     * Return server base Path, and define it if isn't defined.
     */
    public function getBasePath(): string {
        return $this->serverBasePath;
    }

    /**
     * Explicilty sets the server base path. To be used when your entry script path differs from your entry URLs.
     *
     * @see https://github.com/bramus/router/issues/82#issuecomment-466956078
     *
     * @param string $serverBasePath
     */
    public function setBasePath(string $serverBasePath): void {
        $this->serverBasePath = $serverBasePath;
    }
}
