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

// PHP's built-in server ignores Range headers and sends media whole, so a looping video refetched the entire file on
// every loop and stalled. Video and audio files are served here with byte-range (206) support, as a real web server does.
$media_types = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime', 'm4v' => 'video/mp4', 'mp3' => 'audio/mpeg'];
$extension = strtolower(pathinfo($filesystem_path, PATHINFO_EXTENSION));
// Only files that resolve inside the WordPress root (no "/../" escapes) are served here.
$real_root = realpath($wordpress_root);
$real_path = isset($media_types[$extension]) ? realpath($filesystem_path) : false;
if ($real_root !== false && $real_path !== false && str_starts_with($real_path, $real_root . DIRECTORY_SEPARATOR) && is_file($real_path)) {
    $filesystem_path = $real_path;
    $size = (int) filesize($filesystem_path);
    $start = 0;
    $end = $size - 1;
    $range = (string) ($_SERVER['HTTP_RANGE'] ?? '');
    if (preg_match('/^bytes=(\d*)-(\d*)$/', $range, $match) && ($match[1] !== '' || $match[2] !== '')) {
        if ($match[1] === '') {
            $start = max(0, $size - (int) $match[2]);
        } else {
            $start = (int) $match[1];
            $end = $match[2] !== '' ? min((int) $match[2], $size - 1) : $size - 1;
        }
        if ($start > $end || $start >= $size) {
            header('Content-Range: bytes */' . $size, true, 416);
            exit;
        }
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    header('Content-Type: ' . $media_types[$extension]);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($end - $start + 1));
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) filemtime($filesystem_path)) . ' GMT');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        exit;
    }
    $handle = fopen($filesystem_path, 'rb');
    if ($handle) {
        fseek($handle, $start);
        $remaining = $end - $start + 1;
        while ($remaining > 0 && !feof($handle)) {
            $chunk = (string) fread($handle, (int) min(1048576, $remaining));
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }
        fclose($handle);
    }
    exit;
}

if (is_file($filesystem_path) || is_dir($filesystem_path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $wordpress_root . '/index.php';

return true;
