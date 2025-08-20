# gorton-sd/router

PHP Router Library

## Overview
Router is a lightweight PHP router class that automatically discovers controllers, registers routes using docblock annotations, and caches routes for fast lookup. Designed for easy integration into your own projects or for use by other developers.

## Features
- Automatic controller discovery in a specified folder
- Route registration via `@url` docblock annotation
- Supports HTTP methods: `get`, `post` (extendable)
- File-based route caching with configurable minimum cache age
- Simple API for dispatching requests

## Controller Requirements
- Each controller must have a docblock with `@url /path`
- Implement methods named `get()`, `post()`, etc. for HTTP actions

Example:
```php
/**
 * @url /example
 */
class ExampleController {
	public function get() {
		echo "GET: Hello from ExampleController!";
	}
	public function post() {
		echo "POST: You posted to ExampleController!";
	}
}
```

## Usage
```php
require 'src/Router2.php';

$router2 = new Router2();
$router2->loadControllers(3600); // Optional: set minimum cache age to 1 hour (3600 seconds)
$router2->run($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

## Route Caching
- Routes are cached to a file (`routes.cache`) for fast lookup.
- Cache is rebuilt if any controller changes and the cache file is older than the minimum age.
- You can adjust the minimum cache age by passing a value (in seconds) to `loadControllers()`.

## Installation
Copy the `src/Router2.php` file and your controllers into your project. Require `Router2.php` as needed.

## License
MIT
