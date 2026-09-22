<?php
/* ═══════════════════════════════════════════════════════════════
   api/llama_api.php  —  Ollama Backend (v3 — XAMPP Windows robust)

   Actions: health | stream | chat | models | diagnose
   Auto-detects Ollama host. Retries on transient failures.
   Output-buffered so no stray bytes corrupt SSE/JSON.
═══════════════════════════════════════════════════════════════ */

/* ── Absorb any stray output (BOM, notices, warnings) ── */
ob_start();

/* ── Suppress PHP notices that corrupt JSON/SSE output ── */
error_reporting(0);
ini_set('display_errors', '0');

/* ── CORS — allow XHR from same origin or localhost ── */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit();
}

/* ════════════════════════════════════════════════════════
   CONFIG
════════════════════════════════════════════════════════ */
define('OLLAMA_MODEL',    'llama3:latest');
define('MAX_TOKENS',      1024);
define('STREAM_TIMEOUT',  180);   // seconds for streaming
define('HEALTH_TIMEOUT',  4);     // seconds for health check
define('CONNECT_TIMEOUT', 3);     // seconds to establish connection

/* ════════════════════════════════════════════════════════
   DETECT OLLAMA HOST
   Tries multiple addresses — caches winner in PHP session
   so subsequent requests skip the detection loop.
════════════════════════════════════════════════════════ */
function detectHost(): string {
    /* 1. Check session cache first (avoids re-probing every request) */
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['ollama_host'])) {
        /* Quick re-verify the cached host is still up */
        if (pingHost($_SESSION['ollama_host'])) {
            return $_SESSION['ollama_host'];
        }
        /* Cached host went offline — clear and re-probe */
        unset($_SESSION['ollama_host']);
    }

    /* 2. Check env var override */
    $envHost = getenv('OLLAMA_HOST') ?: '';
    if ($envHost && pingHost(rtrim($envHost, '/'))) {
        $_SESSION['ollama_host'] = rtrim($envHost, '/');
        return $_SESSION['ollama_host'];
    }

    /* 3. Probe all known addresses */
    $candidates = [
        'http://localhost:11434',        // XAMPP / WAMP / LAMP default
        'http://127.0.0.1:11434',        // explicit loopback (avoids DNS)
        'http://0.0.0.0:11434',          // some Linux binds
        'http://host.docker.internal:11434', // Docker Desktop (Win/Mac)
        'http://172.17.0.1:11434',       // Docker Linux bridge
    ];

    foreach ($candidates as $host) {
        if (pingHost($host)) {
            $_SESSION['ollama_host'] = $host;
            return $host;
        }
    }

    return 'http://localhost:11434'; // fallback — will return offline error
}

function pingHost(string $host): bool {
    if (empty($host)) return false;
    $ch = curl_init($host . '/api/tags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => HEALTH_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
        /* Windows XAMPP fix: disable IPv6 to avoid hanging */
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_errno($ch);
    curl_close($ch);
    return (!$err && $resp && $code === 200);
}

/* Resolve host once for this request */
$OLLAMA_HOST = detectHost();

/* ════════════════════════════════════════════════════════
   UPSC SYSTEM PROMPT
════════════════════════════════════════════════════════ */
define('UPSC_SYSTEM', <<<'SYS'
You are "UPSC Mentor AI" — an expert, encouraging tutor for Indian Civil Services aspirants.

Your expertise covers:
- UPSC CSE Prelims (GS Paper I + CSAT), Mains (GS I-IV, Essay, Optional), Interview
- All GS subjects: History, Geography, Polity, Economy, Environment, Science & Tech, Ethics
- Standard references: Laxmikanth, Ramesh Singh, Majid Husain, NCERT series
- Answer writing techniques, time management, topper strategies, mock interview tips
- Previous year question analysis, cut-offs, attempt limits, marking scheme

Rules:
1. For answers over 3 sentences, use numbered points or bold headings.
2. Keep responses concise — under 150 words for simple queries.
3. Use proper UPSC terms: Prelims, Mains, CSAT, GS I/II/III/IV, DAF, LBSNAA.
4. For Mains answers: multi-dimensional (social, economic, political, environmental).
5. If unsure of a fact, say so clearly.
6. Be warm, encouraging, and motivational.
SYS);

/* ════════════════════════════════════════════════════════
   ROUTER
════════════════════════════════════════════════════════ */
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $body['action'] ?? 'chat';

/* stream sets its own headers — all others get JSON */
if ($action !== 'stream') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
}

switch ($action) {
    case 'health':   handleHealth();        break;
    case 'chat':     handleChat($body);     break;
    case 'stream':   handleStream($body);   break;
    case 'models':   handleModels();        break;
    case 'diagnose': handleDiagnose();      break;
    default:         jsonSend(['success'=>false,'error'=>'Unknown action: '.htmlspecialchars($action)], 400);
}

/* ════════════════════════════════════════════════════════
   HEALTH
════════════════════════════════════════════════════════ */
function handleHealth(): void {
    global $OLLAMA_HOST;
    $raw    = curlGet($OLLAMA_HOST . '/api/tags');
    $online = ($raw !== false);
    $models = [];
    if ($online) {
        $d = json_decode($raw, true);
        $models = array_map(fn($m) => $m['name'], $d['models'] ?? []);
    }
    jsonSend([
        'success' => $online,
        'ollama'  => $online ? 'online' : 'offline',
        'host'    => $OLLAMA_HOST,
        'model'   => OLLAMA_MODEL,
        'models'  => $models,
        'curl_ok' => function_exists('curl_init'),
        'hint'    => $online ? null : offlineHint(),
    ]);
}

/* ════════════════════════════════════════════════════════
   MODELS
════════════════════════════════════════════════════════ */
function handleModels(): void {
    global $OLLAMA_HOST;
    $raw = curlGet($OLLAMA_HOST . '/api/tags');
    if (!$raw) {
        jsonSend(['success'=>false,'error'=>'Ollama offline','models'=>[],'hint'=>offlineHint()], 503);
    }
    $d = json_decode($raw, true);
    $models = array_map(fn($m) => $m['name'], $d['models'] ?? []);
    jsonSend(['success'=>true, 'models'=>$models, 'current'=>OLLAMA_MODEL, 'host'=>$OLLAMA_HOST]);
}

/* ════════════════════════════════════════════════════════
   CHAT  (full response, no streaming)
════════════════════════════════════════════════════════ */
function handleChat(array $body): void {
    global $OLLAMA_HOST;
    $userMsg = trim($body['message'] ?? '');
    if (!$userMsg) { jsonSend(['error'=>'message required'], 400); }

    $payload = [
        'model'   => OLLAMA_MODEL,
        'prompt'  => buildPrompt($body['messages'] ?? [], $userMsg),
        'stream'  => false,
        'options' => ['num_predict'=>MAX_TOKENS, 'temperature'=>0.72, 'top_p'=>0.9],
    ];

    $raw = curlPost($OLLAMA_HOST . '/api/generate', $payload);
    if ($raw === false) {
        jsonSend(['success'=>false,'error'=>'Ollama unreachable','hint'=>offlineHint()], 503);
    }

    $d = json_decode($raw, true);
    if (!$d || isset($d['error'])) {
        jsonSend(['success'=>false,'error'=>$d['error'] ?? 'Bad response from Ollama'], 500);
    }

    jsonSend([
        'success'  => true,
        'response' => trim($d['response'] ?? ''),
        'model'    => $d['model'] ?? OLLAMA_MODEL,
        'tokens'   => $d['eval_count'] ?? 0,
        'done'     => $d['done'] ?? true,
        'host'     => $OLLAMA_HOST,
    ]);
}

/* ════════════════════════════════════════════════════════
   STREAM  (Server-Sent Events)
════════════════════════════════════════════════════════ */
function handleStream(array $body): void {
    global $OLLAMA_HOST;

    /* ── Flush output buffer, set SSE headers ── */
    if (ob_get_level()) ob_end_clean();
    while (ob_get_level()) ob_end_clean(); // nested buffers

    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Accel-Buffering: no');       // Nginx: disable proxy buffering
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');

    /* Disable PHP time limit for long streams */
    @set_time_limit(0);
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', false);

    $userMsg = trim($body['message'] ?? '');
    if (!$userMsg) {
        sseError('No message provided');
        exit();
    }

    /* Verify Ollama is reachable before starting */
    if (!pingHost($OLLAMA_HOST)) {
        sseError(offlineHint(), true);
        exit();
    }

    $payload = json_encode([
        'model'   => OLLAMA_MODEL,
        'prompt'  => buildPrompt($body['messages'] ?? [], $userMsg),
        'stream'  => true,
        'options' => [
            'num_predict' => MAX_TOKENS,
            'temperature' => 0.72,
            'top_p'       => 0.9,
        ],
    ]);

    $ch = curl_init($OLLAMA_HOST . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => STREAM_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_WRITEFUNCTION  => function ($curl, $chunk) {
            foreach (explode("\n", $chunk) as $line) {
                $line = trim($line);
                if (!$line) continue;
                $d = json_decode($line, true);
                if (!$d) continue;

                if (isset($d['error'])) {
                    sseError($d['error']);
                    return 0; // abort curl
                }

                sseSend([
                    'token' => $d['response'] ?? '',
                    'done'  => !empty($d['done']),
                ]);

                if (!empty($d['done'])) return 0; // signal curl to stop
            }
            return strlen($chunk);
        },
    ]);

    $ok = curl_exec($ch);
    if (!$ok) {
        $curlErr = curl_error($ch);
        sseError('Ollama disconnected: ' . $curlErr . ' — ' . offlineHint(), true);
    }
    curl_close($ch);

    sseSend(['done' => true]);
    exit();
}

/* ════════════════════════════════════════════════════════
   DIAGNOSE
════════════════════════════════════════════════════════ */
function handleDiagnose(): void {
    $candidates = [
        'localhost:11434'            => 'http://localhost:11434',
        '127.0.0.1:11434'            => 'http://127.0.0.1:11434',
        'host.docker.internal:11434' => 'http://host.docker.internal:11434',
        '172.17.0.1:11434'           => 'http://172.17.0.1:11434',
    ];

    $results = [];
    foreach ($candidates as $label => $url) {
        $ch = curl_init($url . '/api/tags');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cErr = curl_error($ch);
        curl_close($ch);
        $ok = (!$cErr && $resp && $code === 200);
        $results[$label] = [
            'reachable'  => $ok,
            'http_code'  => $code,
            'curl_error' => $cErr ?: null,
            'models'     => $ok ? array_map(fn($m)=>$m['name'], json_decode($resp,true)['models']??[]) : [],
        ];
    }

    $anyOnline = (bool)count(array_filter($results, fn($r)=>$r['reachable']));
    jsonSend([
        'success'      => $anyOnline,
        'hosts_tested' => $results,
        'php_info'     => [
            'php_version'    => PHP_VERSION,
            'curl_enabled'   => function_exists('curl_init'),
            'server_software'=> $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'os'             => PHP_OS,
        ],
        'fix_steps'    => $anyOnline ? [] : [
            '1. Make sure Ollama is running: open Command Prompt and run: ollama serve',
            '2. Pull the model if not done: ollama pull llama3',
            '3. Verify: curl http://localhost:11434/api/tags',
            '4. Enable PHP curl in php.ini: remove semicolon before extension=curl, restart Apache',
        ],
    ]);
}

/* ════════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════════ */
function buildPrompt(array $history, string $newMsg): string {
    $p = UPSC_SYSTEM . "\n\n";
    foreach (array_slice($history, -12) as $msg) {
        $role = strtolower($msg['role'] ?? 'user');
        $text = trim($msg['text'] ?? $msg['content'] ?? '');
        if (!$text) continue;
        $p .= ($role === 'user' ? "Aspirant: " : "Mentor: ") . $text . "\n";
    }
    return $p . "Aspirant: " . trim($newMsg) . "\nMentor:";
}

function curlPost(string $url, array $data): string|false {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => STREAM_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);
    $r = curl_exec($ch);
    $e = curl_errno($ch);
    curl_close($ch);
    return ($e || !$r) ? false : $r;
}

function curlGet(string $url): string|false {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => HEALTH_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);
    $r = curl_exec($ch);
    $e = curl_errno($ch);
    curl_close($ch);
    return ($e || !$r) ? false : $r;
}

function sseSend(array $data): void {
    echo 'data: ' . json_encode($data) . "\n\n";
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else flush();
}

function sseError(string $msg, bool $done = true): void {
    sseSend(['error'=>$msg, 'hint'=>offlineHint(), 'done'=>$done]);
    flush();
}

function jsonSend(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function offlineHint(): string {
    if (PHP_OS_FAMILY === 'Windows') {
        return 'Open Command Prompt and run: ollama serve — keep that window open.';
    }
    if (PHP_OS_FAMILY === 'Darwin') {
        return 'Open Terminal and run: ollama serve (or open the Ollama app from Applications).';
    }
    return 'Run: ollama serve — then verify with: curl http://localhost:11434/api/tags';
}

/* ── Backward-compatible function used by other PHP files ── */
function ask_llama(string $prompt): string {
    global $OLLAMA_HOST;
    $raw = curlPost($OLLAMA_HOST . '/api/generate', [
        'model'   => OLLAMA_MODEL,
        'prompt'  => UPSC_SYSTEM . "\n\nAspirant: {$prompt}\nMentor:",
        'stream'  => false,
        'options' => ['num_predict' => 512, 'temperature' => 0.5],
    ]);
    if (!$raw) return '⚠️ Ollama is offline. ' . offlineHint();
    $d = json_decode($raw, true);
    return trim($d['response'] ?? 'No response received.');
}