<?php
// filepath: c:\Users\zeron\Documents\XAMPP\htdocs\Elephpant-Terminal\api\weekly_returns.php

header('Content-Type: application/json; charset=utf-8');

function flattenCountrySymbolTree(array $node, array &$out = []): array {
    foreach ($node as $key => $value) {
        // New flat format: "Country" => ["isin" => "...", "symbol" => "..."]
        if (is_array($value) && isset($value['symbol']) && is_string($value['symbol']) && $value['symbol'] !== '') {
            $out[$key] = $value['symbol'];
            continue;
        }

        // Backward compatibility with old nested/grouped tree
        if (is_string($value) && $value !== '') {
            $out[$key] = $value; // leaf: Country => Symbol
        } elseif (is_array($value)) {
            flattenCountrySymbolTree($value, $out);
        }
    }
    return $out;
}

function readJsonFile(string $path): ?array {
    if (!is_file($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function writeJsonFile(string $dir, string $path, array $payload): void {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $tmp = $path . '.tmp';
    @file_put_contents($tmp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    @rename($tmp, $path);
}

function fetchPeriodReturn(string $symbol, int $sessionsBack, bool $debug = false): ?array {
    $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($symbol) . '?range=6mo&interval=1d';

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 15,
            'header'  => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n"
        ]
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) return null;

    $data = json_decode($json, true);
    $closes = $data['chart']['result'][0]['indicators']['quote'][0]['close'] ?? null;
    if (!is_array($closes)) return null;

    $vals = array_values(array_filter($closes, static fn($v) => is_numeric($v)));
    $needed = $sessionsBack + 1;
    if (count($vals) < $needed) return null;

    $lastIndex = count($vals) - 1;
    $prevIndex = $lastIndex - $sessionsBack;

    $last = (float)$vals[$lastIndex];
    $prev = (float)$vals[$prevIndex];
    if ($prev == 0.0) return null;

    $pct = (($last - $prev) / $prev) * 100.0;
    if (abs($pct) < 0.0000005) $pct = 0.0; // avoid -0

    return $debug
        ? ['value' => round($pct, 6), 'last' => $last, 'prev' => $prev]
        : ['value' => round($pct, 6)];
}

// ---- Config ----
$symbolsJsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'countries.json';
$symbolsPhpFile  = __DIR__ . DIRECTORY_SEPARATOR . 'country_symbols.php';
$cacheDir        = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
$cacheFile       = $cacheDir . DIRECTORY_SEPARATOR . 'returns_cache.json';
$calcVersion     = 'v4';

// period selector
$period = strtolower(trim((string)($_GET['period'] ?? 'weekly')));
$periodMap = [
    'daily'   => 1,
    'weekly'  => 5,
    'monthly' => 21,
];
if (!isset($periodMap[$period])) $period = 'weekly';

$sessionsBack = $periodMap[$period];
$ttlSeconds   = max(60, (int)($_GET['ttl'] ?? 3600)); // default 1h
$force        = isset($_GET['force']) && $_GET['force'] === '1';
$debug        = isset($_GET['debug']) && $_GET['debug'] === '1';

// load country symbols
$countryToSymbol = [];
$parsedJson = readJsonFile($symbolsJsonFile);
if (is_array($parsedJson)) {
    $countryToSymbol = flattenCountrySymbolTree($parsedJson);
}
if (!$countryToSymbol && is_file($symbolsPhpFile)) {
    $tmp = require $symbolsPhpFile;
    if (is_array($tmp)) $countryToSymbol = $tmp;
}
if (!$countryToSymbol) {
    http_response_code(500);
    echo json_encode(['error' => 'No valid symbols found in countries.json or country_symbols.php'], JSON_UNESCAPED_UNICODE);
    exit;
}

// load cache
$cache = readJsonFile($cacheFile);
if (!is_array($cache)) $cache = ['calcVersion' => $calcVersion, 'periods' => []];
if (($cache['calcVersion'] ?? null) !== $calcVersion) {
    $cache = ['calcVersion' => $calcVersion, 'periods' => []];
}

$periodCache = $cache['periods'][$period] ?? null;
$now = time();

if (
    !$force &&
    is_array($periodCache) &&
    isset($periodCache['fetchedAt'], $periodCache['data']) &&
    (($now - (int)$periodCache['fetchedAt']) < $ttlSeconds)
) {
    echo json_encode([
        'period'       => $period,
        'sessionsBack' => $sessionsBack,
        'asOf'         => $periodCache['asOf'] ?? null,
        'cached'       => true,
        'stale'        => false,
        'cacheAgeSec'  => $now - (int)$periodCache['fetchedAt'],
        'ttlSec'       => $ttlSeconds,
        'data'         => $periodCache['data'],
        'debug'        => $debug ? ($periodCache['debug'] ?? null) : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// fetch fresh
$data = [];
$meta = [];
$okCount = 0;

foreach ($countryToSymbol as $country => $symbol) {
    $res = fetchPeriodReturn($symbol, $sessionsBack, $debug);
    $val = $res['value'] ?? null;
    $data[$country] = $val;
    if ($debug) $meta[$country] = ['symbol' => $symbol, 'raw' => $res];
    if ($val !== null) $okCount++;
}

if ($okCount > 0) {
    $entry = [
        'asOf'         => gmdate('c'),
        'fetchedAt'    => $now,
        'sessionsBack' => $sessionsBack,
        'data'         => $data
    ];
    if ($debug) $entry['debug'] = $meta;

    $cache['periods'][$period] = $entry;
    writeJsonFile($cacheDir, $cacheFile, $cache);

    echo json_encode([
        'period'       => $period,
        'sessionsBack' => $sessionsBack,
        'asOf'         => $entry['asOf'],
        'cached'       => false,
        'stale'        => false,
        'cacheAgeSec'  => 0,
        'ttlSec'       => $ttlSeconds,
        'data'         => $data,
        'debug'        => $debug ? $meta : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// fallback stale cache for this period
if (is_array($periodCache) && isset($periodCache['data'])) {
    echo json_encode([
        'period'       => $period,
        'sessionsBack' => $sessionsBack,
        'asOf'         => $periodCache['asOf'] ?? null,
        'cached'       => true,
        'stale'        => true,
        'cacheAgeSec'  => isset($periodCache['fetchedAt']) ? ($now - (int)$periodCache['fetchedAt']) : null,
        'ttlSec'       => $ttlSeconds,
        'data'         => $periodCache['data'],
        'debug'        => $debug ? ($periodCache['debug'] ?? null) : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(503);
echo json_encode([
    'error'  => 'Unable to fetch returns and no cache available for requested period.',
    'period' => $period
], JSON_UNESCAPED_UNICODE);