<?php

declare(strict_types=1);

/**
 * Hello PHP — sadə PHP tətbiqi, Docker ilə hazır.
 *
 * PHP built-in web server üçün front controller / router.
 * İşə salma:  php -S 0.0.0.0:8080 index.php
 */

require __DIR__ . '/metrics.php';

$start = microtime(true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/**
 * JSON cavab göndər.
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Routing
if ($path === '/' && $method === 'GET') {
    json_response(['message' => 'Hello PHP!', 'status' => 'working']);
} elseif ($path === '/health' && $method === 'GET') {
    json_response(['status' => 'healthy']);
} elseif ($path === '/api/hello' && $method === 'GET') {
    $name = $_GET['name'] ?? 'World';
    json_response(['message' => "Hello, {$name}!"]);
} elseif (preg_match('#^/items/(\d+)$#', $path, $m) && $method === 'GET') {
    $q = $_GET['q'] ?? null;
    json_response(['item_id' => (int) $m[1], 'q' => $q]);
} elseif ($path === '/metrics' && $method === 'GET') {
    header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
    echo render_metrics();
} else {
    json_response(['error' => 'Not Found'], 404);
}

// Hər request üçün metrics topla
$duration = microtime(true) - $start;
record_request($method, $path, $duration);
