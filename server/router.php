<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve the JSON result
if ($uri === '/api/result') {
    $file = getenv('PHPSTAN_UI_RESULT');

    if (!$file) {
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpstan-ui-result.json';
    }

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-cache');

    if (!file_exists($file)) {
        echo json_encode(['error' => 'Result file not found.']);
        exit;
    }

    echo file_get_contents($file);
    exit;
}

// Serve static assets from /ui/
if ($uri !== '/' && file_exists(__DIR__ . '/../ui' . $uri)) {
    return false;
}

// Everything else → index.html
$indexFile = __DIR__ . '/../ui/index.html';

if (!file_exists($indexFile)) {
    http_response_code(404);
    echo 'UI not found.';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
readfile($indexFile);
exit;
