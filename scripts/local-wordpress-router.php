<?php

declare(strict_types=1);

$request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$request_path = (string) (parse_url($request_uri, PHP_URL_PATH) ?? '/');
$wordpress_root = dirname(__DIR__) . '/.wordpress';

if ('/wp-admin' === $request_path) {
    $query = (string) (parse_url($request_uri, PHP_URL_QUERY) ?? '');
    $location = '/wp-admin/' . ('' !== $query ? '?' . $query : '');

    header('Location: ' . $location, true, 301);
    exit;
}

$filesystem_path = $wordpress_root . rawurldecode($request_path);

if (is_file($filesystem_path) || is_dir($filesystem_path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $wordpress_root . '/index.php';

return true;
