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

/**
 * hello-csharp servisinin baza URL-i (env ilə override olunur).
 */
function csharp_service_url(): string
{
    return getenv('CSHARP_SERVICE_URL') ?: 'http://hello-csharp-main-svc.hello-csharp:8080/';
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
} elseif ($path === '/csharp' && $method === 'GET') {
    // hello-csharp servisinə (namespace: hello-csharp) müraciət et
    $url = csharp_service_url();
    $body = fetch_url($url);
    if ($body === null) {
        json_response(['error' => 'hello-csharp servisinə çatmaq mümkün olmadı', 'upstream' => $url], 502);
    } else {
        $decoded = json_decode($body, true);
        json_response(['upstream' => $url, 'response' => $decoded ?? $body]);
    }
} elseif ($path === '/aggregate' && $method === 'GET') {
    // php-nin öz salamını hello-csharp servisinin cavabı ilə birləşdir
    $name = $_GET['name'] ?? 'World';
    $csharpUrl = rtrim(csharp_service_url(), '/') . '/api/hello?name=' . urlencode($name);
    $body = fetch_url($csharpUrl);
    $csharp = $body === null ? null : (json_decode($body, true) ?? $body);
    json_response([
        'name' => $name,
        'php' => "Hello, {$name}!",
        'csharp' => $csharp,
        'csharp_ok' => $body !== null,
    ]);
} elseif ($path === '/metrics' && $method === 'GET') {
    header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
    echo render_metrics();
} else {
    json_response(['error' => 'Not Found'], 404);
}

// Hər request üçün metrics topla
$duration = microtime(true) - $start;
record_request($method, $path, $duration);
