<?php

declare(strict_types=1);

/**
 * Router script for the `php -S` loopback server used by integration tests (see
 * LoopbackServer.php). Since the built-in server runs this script fresh in a new process
 * for every request, per-test route definitions and captured requests can't live in PHP
 * memory shared with the test process -- they're exchanged via two small JSON files instead
 * (a "script" file the test writes routes into, and a "log" file this router appends
 * observed requests to).
 */

$scriptFile = getenv('LOOPBACK_SCRIPT_FILE');
$logFile = getenv('LOOPBACK_LOG_FILE');

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$query = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');
$body = (string) file_get_contents('php://input');

$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with((string) $key, 'HTTP_')) {
        $headers[str_replace('_', '-', substr((string) $key, 5))] = $value;
    }
}
if (isset($_SERVER['CONTENT_TYPE'])) {
    $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
}

// Count how many previous requests hit this exact path, so a scripted list of responses
// for one path can be consumed in order (e.g. polling tests: processing, processing,
// validated) -- the index into that list is just "how many times have we seen this path".
$previousHits = 0;
if (is_file($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (is_array($entry) && ($entry['path'] ?? null) === $path) {
            $previousHits++;
        }
    }
}

$record = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'path' => $path,
    'query' => $query,
    'headers' => $headers,
    'body' => base64_encode($body),
];
file_put_contents($logFile, json_encode($record) . "\n", FILE_APPEND | LOCK_EX);

$script = [];
if (is_file($scriptFile)) {
    $raw = file_get_contents($scriptFile);
    $script = ($raw !== false && $raw !== '') ? (json_decode($raw, true) ?? []) : [];
}

$routeEntry = $script[$path] ?? null;
if ($routeEntry === null) {
    http_response_code(404);
    exit;
}

// $routeEntry is either one {status, body, headers} object, or a list of them (consumed
// one per request, repeating the last entry once the list is exhausted).
if (array_is_list($routeEntry)) {
    $index = min($previousHits, count($routeEntry) - 1);
    $route = $routeEntry[$index];
} else {
    $route = $routeEntry;
}

http_response_code((int) ($route['status'] ?? 200));
$responseHeaders = $route['headers'] ?? ['Content-Type' => 'application/json'];
foreach ($responseHeaders as $name => $value) {
    header("{$name}: {$value}");
}
echo base64_decode((string) ($route['body'] ?? ''));
