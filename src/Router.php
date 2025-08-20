<?php

/**
 * Router - PHP Router Library
 *
 * - Discovers controllers in a specified folder and registers routes
 *   based on @url annotations in each controller's docblock.
 * - Caches routes to a file for fast lookup; cache is rebuilt if controllers change.
 * - User can call loadControllers() to refresh or use the cache as needed.
 *
 * Each controller should:
 *   - Have a docblock with @url /path
 *   - Implement methods named get(), post(), etc. for HTTP actions
 *
 * Example controller:
 * 
 *   * @url /example
 *
 *   class ExampleController {
 *       public function get() { ... }
 *       public function post() { ... }
 *   }
 *
 * Usage:
 *   $router = new Router();
 *   $router->loadControllers(); // optional, refreshes or uses cache
 *   $router->run($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
 */

namespace gortonsd\Router;

class Router {
    private $routes = [];
    private $controllerFolder;
    private $cacheFile = __DIR__ . '/routes.cache';

    public function __construct($controllerFolder = __DIR__ . '/controllers') {
        $this->controllerFolder = $controllerFolder;
        $this->loadControllers();
    }

    /**
     * Loads controllers and manages route cache.
     * If cache is valid, loads from cache. If not, rebuilds and updates cache.
     * Can be called by user to refresh routes if needed.
     * doesn't check for changes in controller files for minimum 1 hour by default.
     */
    public function loadControllers($minCacheAge = 3600) {
        $cacheValid = false;
        if (file_exists($this->cacheFile)) {
            $this->routes = unserialize(file_get_contents($this->cacheFile));
            $cacheValid = true;
        }
        if (!$cacheValid || $this->controllersChanged($minCacheAge)) {
            $this->routes = [];
            foreach (glob($this->controllerFolder . '/*.php') as $file) {
                //require_once $file;
                $contents = file_get_contents($file);
                $namespace = '';
                if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatches)) {
                    $namespace = trim($nsMatches[1]);
                }

                $className = basename($file, '.php');
                $fqcn = $namespace ? $namespace . '\\' . $className : $className;
                echo($fqcn."\r\n");
                if (class_exists($fqcn)) {
                    echo("class found");
                    $reflection = new \ReflectionClass($fqcn);
                    $doc = $reflection->getDocComment();
                    echo($doc."\r\n");
                    if ($doc && preg_match('/@url\s+(\S+)/', $doc, $matches)) {
                        $url = $matches[1];
                        echo($url);
                        foreach (["get", "post"] as $method) {
                            if ($reflection->hasMethod($method)) {
                                $this->routes[strtoupper($method)][$url] = $fqcn;
                            }
                        }
                    }
                }
                else {
                    echo("class not found");
                }
            }
            file_put_contents($this->cacheFile, serialize($this->routes));
        }
    }

    /**
     * Checks if any controller file has changed since the cache was created,
     * but only if the cache file is older than the minimum age (in seconds).
     * 1 hour by default 
     * 
     */
    private function controllersChanged($minCacheAge = 3600) {
        if (!file_exists($this->cacheFile)) return true;
        $cacheTime = filemtime($this->cacheFile);
        // Only check for changes if cache is older than minCacheAge
        if ((time() - $cacheTime) < $minCacheAge) return false;
        foreach (glob($this->controllerFolder . '/*.php') as $file) {
            if (filemtime($file) > $cacheTime) return true;
        }
        return false;
    }

    public function run($method, $uri) {
        $method = strtoupper($method);
        if (isset($this->routes[$method][$uri])) {
            $className = $this->routes[$method][$uri];
            $controller = new $className();
            if (method_exists($controller, strtolower($method))) {
                return $controller->{strtolower($method)}();
            }
        }
        http_response_code(404);
        echo '404 Not Found';
    }
}

