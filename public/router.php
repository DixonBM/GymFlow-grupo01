<?php
// Router para: php -S localhost:8000 -t public public/router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/index.php';
