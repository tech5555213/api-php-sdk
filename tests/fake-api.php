<?php
// Fake Loamhill API for offline tests: echoes what it received.
header('Content-Type: application/json');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = file_get_contents('php://input');

if ($path === '/v1/empty') {
    echo json_encode(['state' => 0, 'result' => []]);
    exit;
}
if ($path === '/v1/fail') {
    http_response_code(422);
    echo json_encode(['state' => 1, 'errors' => ['order_id' => ['validation.alpha_dash']]]);
    exit;
}
echo json_encode(['state' => 0, 'result' => [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $path,
    'query' => $_GET,
    'body' => $body,
    'merchant' => isset($_SERVER['HTTP_MERCHANT']) ? $_SERVER['HTTP_MERCHANT'] : null,
    'userId' => isset($_SERVER['HTTP_USERID']) ? $_SERVER['HTTP_USERID'] : null,
    'sign' => isset($_SERVER['HTTP_SIGN']) ? $_SERVER['HTTP_SIGN'] : null,
]]);
