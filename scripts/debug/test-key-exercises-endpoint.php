<?php
// Test /api/ejercicios-clave?user_id=3 con sesión web (CSRF token + cookie)
$base = 'http://localhost:8000';
$cookieFile = sys_get_temp_dir() . '/gymapp_cookie_' . uniqid() . '.txt';

function req(string $url, array $opts = []) {
    global $cookieFile;
    $ctxOpts = ['http' => [
        'method' => $opts['method'] ?? 'GET',
        'header' => ($opts['header'] ?? '') . "Cookie: " . file_get_contents($cookieFile) . "\r\n",
        'ignore_errors' => true,
        'timeout' => 10,
        'follow_location' => 0,
    ]];
    if (!empty($opts['body'])) {
        $ctxOpts['http']['content'] = $opts['body'];
    }
    $ctx = stream_context_create($ctxOpts);
    $body = @file_get_contents($url, false, $ctx);
    $cookies = [];
    if (!empty($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (stripos($h, 'Set-Cookie:') === 0) {
                $cookies[] = trim(substr($h, 11));
            }
        }
    }
    if ($cookies) {
        $current = file_exists($cookieFile) ? file_get_contents($cookieFile) : '';
        foreach ($cookies as $c) {
            $pair = explode(';', $c)[0];
            [$name, $val] = explode('=', $pair, 2);
            if (stripos($c, 'Max-Age=0') !== false || stripos($c, 'expires=Thu, 01-Jan-1970') !== false) {
                $current = preg_replace('/' . preg_quote($name, '/') . '=[^;]*;? ?/', '', $current);
            } else {
                if (strpos($current, $name . '=') !== false) {
                    $current = preg_replace('/' . preg_quote($name, '/') . '=[^;]*;? ?/', $pair . '; ', $current);
                } else {
                    $current .= $pair . '; ';
                }
            }
        }
        file_put_contents($cookieFile, $current);
    }
    $status = $http_response_header[0] ?? 'N/A';
    return ['status' => $status, 'body' => $body, 'headers' => $http_response_header ?? []];
}

// 1) GET /login para obtener XSRF + session cookie
$r = req($base . '/login', [
    'header' => "Accept: text/html\r\n",
]);
echo "GET /login -> ", $r['status'], PHP_EOL;
// Extraer token CSRF
$body = $r['body'] ?? '';
preg_match('/name="_token"\s+value="([^"]+)"/', $body, $m);
$token = $m[1] ?? '';
echo "CSRF token: ", $token ? 'OK' : 'MISSING', PHP_EOL;

// 2) POST /login
$loginBody = http_build_query(['_token' => $token, 'nick' => 'admin', 'password' => 'password']);
$r = req($base . '/login', [
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: text/html\r\n",
    'body' => $loginBody,
]);
echo "POST /login -> ", $r['status'], PHP_EOL;
foreach (($r['headers'] ?? []) as $h) {
    if (stripos($h, 'Location:') === 0) echo "  ", $h, PHP_EOL;
}

// 3) GET /api/ejercicios-clave?user_id=3
$r = req($base . '/api/ejercicios-clave?user_id=3', [
    'header' => "Accept: application/json\r\n",
]);
echo "GET /api/ejercicios-clave?user_id=3 -> ", $r['status'], PHP_EOL;
echo "BODY: ", substr($r['body'] ?? '', 0, 800), PHP_EOL;

@unlink($cookieFile);
