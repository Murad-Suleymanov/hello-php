<?php

declare(strict_types=1);

/**
 * Sadə Prometheus metrics dəstəyi.
 *
 * PHP request-per-process işlədiyi üçün sayğacları proseslər arasında
 * paylaşmaq məqsədilə APCu shared memory-dən istifadə olunur.
 */

const METRIC_COUNT_KEY = 'http_requests_total';
const METRIC_SUM_KEY = 'http_request_duration_seconds_sum';
const METRIC_BUCKET_KEY = 'http_request_duration_seconds_bucket';

const LATENCY_BUCKETS = [0.001, 0.01, 0.1, 0.5, 1, 2, 5];

/**
 * Bir request üçün metrics yenilə.
 */
function record_request(string $method, string $endpoint, float $duration): void
{
    if (!function_exists('apcu_inc')) {
        return;
    }

    $labels = $method . '|' . $endpoint;

    apcu_inc(METRIC_COUNT_KEY . '|' . $labels, 1, $ok);
    apcu_inc_float(METRIC_SUM_KEY . '|' . $labels, $duration);

    foreach (LATENCY_BUCKETS as $le) {
        if ($duration <= $le) {
            apcu_inc(METRIC_BUCKET_KEY . '|' . $labels . '|' . $le, 1, $ok);
        }
    }
    apcu_inc(METRIC_BUCKET_KEY . '|' . $labels . '|+Inf', 1, $ok);
}

/**
 * APCu-da float artımı (apcu_inc yalnız integer dəstəkləyir).
 */
function apcu_inc_float(string $key, float $value): void
{
    $current = apcu_fetch($key);
    $current = is_float($current) || is_int($current) ? (float) $current : 0.0;
    apcu_store($key, $current + $value);
}

/**
 * Prometheus exposition formatında bütün metrics-i qaytar.
 */
function render_metrics(): string
{
    $lines = [];

    if (!function_exists('apcu_fetch')) {
        return "# APCu mövcud deyil\n";
    }

    $lines[] = '# HELP http_requests_total Total HTTP request count';
    $lines[] = '# TYPE http_requests_total counter';
    foreach (apcu_iterator(METRIC_COUNT_KEY . '|') as $entry) {
        [, $method, $endpoint] = explode('|', $entry['key']);
        $lines[] = sprintf(
            'http_requests_total{method="%s",endpoint="%s"} %d',
            $method,
            $endpoint,
            $entry['value']
        );
    }

    $lines[] = '# HELP http_request_duration_seconds HTTP request duration in seconds';
    $lines[] = '# TYPE http_request_duration_seconds histogram';
    foreach (apcu_iterator(METRIC_BUCKET_KEY . '|') as $entry) {
        [, $method, $endpoint, $le] = explode('|', $entry['key']);
        $lines[] = sprintf(
            'http_request_duration_seconds_bucket{method="%s",endpoint="%s",le="%s"} %d',
            $method,
            $endpoint,
            $le,
            $entry['value']
        );
    }
    foreach (apcu_iterator(METRIC_SUM_KEY . '|') as $entry) {
        [, $method, $endpoint] = explode('|', $entry['key']);
        $lines[] = sprintf(
            'http_request_duration_seconds_sum{method="%s",endpoint="%s"} %g',
            $method,
            $endpoint,
            $entry['value']
        );
    }
    foreach (apcu_iterator(METRIC_COUNT_KEY . '|') as $entry) {
        [, $method, $endpoint] = explode('|', $entry['key']);
        $lines[] = sprintf(
            'http_request_duration_seconds_count{method="%s",endpoint="%s"} %d',
            $method,
            $endpoint,
            $entry['value']
        );
    }

    return implode("\n", $lines) . "\n";
}

/**
 * APCu iterator-u prefiks üzrə.
 *
 * @return iterable<array{key: string, value: mixed}>
 */
function apcu_iterator(string $prefix): iterable
{
    $quoted = preg_quote($prefix, '/');
    $it = new APCUIterator('/^' . $quoted . '/');
    foreach ($it as $item) {
        yield ['key' => $item['key'], 'value' => $item['value']];
    }
}
