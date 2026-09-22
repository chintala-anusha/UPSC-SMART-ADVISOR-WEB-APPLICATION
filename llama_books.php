<?php
/* ═══════════════════════════════════════════════════════════
   llama_books.php
   1. Takes a user prompt (topic / stage / interest)
   2. Asks Llama3 to recommend specific UPSC book titles
   3. Fetches each book from Google Books API
   4. Returns enriched JSON cards ready to render
═══════════════════════════════════════════════════════════ */

/* ── Buffer output so no stray characters break JSON ── */
ob_start();

/* ── Auth (returns JSON 401 if not logged in, never HTML) ── */
require_once 'includes/ajax_auth.php';

/* ── DB ── */
// (not needed for this file — just Llama + Google Books API)

/* ── Input ── */
$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true) ?? [];
$prompt = trim($data['prompt'] ?? '');
$stage  = in_array($data['stage'] ?? '', ['Prelims','Mains','Interview','NCERT','Any'])
          ? $data['stage'] : 'Any';

if (empty($prompt)) {
    echo json_encode(['success'=>false,'message'=>'Prompt is required']);
    exit();
}

/* ── Step 1: Ask Llama3 for book recommendations ── */
$llama_prompt = <<<PROMPT
You are a UPSC expert librarian. The aspirant asks: "{$prompt}"

Recommend exactly 6 specific books for UPSC {$stage} preparation relevant to this query.

Respond ONLY with a valid JSON array — no explanation, no markdown, no extra text.
Format:
[
  {"title": "Exact Book Title", "author": "Author Name", "subject": "Subject/GS Paper", "reason": "One sentence why this book is useful for UPSC"},
  ...
]

Rules:
- Use real, well-known UPSC books only
- Be precise with title and author spelling (used to search Google Books API)
- subject should be one of: History, Polity, Geography, Economy, Environment, Science, Ethics, IR, Current Affairs, Essay, Interview, NCERT, General
- reason should be 10-15 words max
PROMPT;

$llamaResp = callLlama($llama_prompt);
if (!$llamaResp) {
    echo json_encode(['success'=>false,'message'=>'Llama3 is offline. Make sure Ollama is running.','offline'=>true]);
    exit();
}

/* ── Step 2: Parse Llama's JSON response ── */
$books = parseLlamaBooks($llamaResp);
if (empty($books)) {
    echo json_encode(['success'=>false,'message'=>'Could not parse book list from AI response.','raw'=>$llamaResp]);
    exit();
}

/* ── Step 3: Enrich each book with Google Books API data ── */
$enriched = [];
foreach ($books as $b) {
    $query   = urlencode($b['title'] . ' ' . $b['author']);
    $apiUrl  = 'https://www.googleapis.com/books/v1/volumes?q=' . $query . '&maxResults=1';
    $apiData = fetchBookAPI($apiUrl);

    if ($apiData && !empty($apiData['items'][0])) {
        $item = $apiData['items'][0];
        $v    = $item['volumeInfo'] ?? [];
        $enriched[] = [
            'id'          => $item['id'] ?? '',
            'title'       => $v['title'] ?? $b['title'],
            'author'      => implode(', ', $v['authors'] ?? [$b['author']]),
            'publisher'   => $v['publisher'] ?? '',
            'year'        => substr($v['publishedDate'] ?? '', 0, 4),
            'subject'     => $b['subject'],
            'reason'      => $b['reason'],
            'description' => $v['description'] ?? '',
            'cover'       => str_replace('http://', 'https://', $v['imageLinks']['thumbnail'] ?? ''),
            'preview'     => $v['previewLink'] ?? '',
            'embed'       => !empty($item['id']) ? 'https://books.google.com/books?id=' . $item['id'] . '&lpg=PP1&pg=PP1&output=embed' : '',
            'pages'       => $v['pageCount'] ?? '',
            'source'      => 'google',
            'ai_pick'     => true,
        ];
    } else {
        /* Fallback: use Llama's data even without Google enrichment */
        $enriched[] = [
            'id'          => '',
            'title'       => $b['title'],
            'author'      => $b['author'],
            'publisher'   => '',
            'year'        => '',
            'subject'     => $b['subject'],
            'reason'      => $b['reason'],
            'description' => '',
            'cover'       => '',
            'preview'     => 'https://www.google.com/search?q=' . urlencode($b['title'] . ' ' . $b['author'] . ' book'),
            'embed'       => '',
            'pages'       => '',
            'source'      => 'ai',
            'ai_pick'     => true,
        ];
    }
}

echo json_encode([
    'success'  => true,
    'books'    => $enriched,
    'prompt'   => $prompt,
    'stage'    => $stage,
    'count'    => count($enriched),
]);

/* ══════════════════════════════════════════════════════
   FUNCTIONS
══════════════════════════════════════════════════════ */

function callLlama(string $prompt): string|false {
    /* Auto-detect Ollama host */
    $hosts = [
        'http://localhost:11434',
        'http://127.0.0.1:11434',
        'http://host.docker.internal:11434',
    ];

    $host = null;
    foreach ($hosts as $h) {
        $ch = curl_init($h . '/api/tags');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>3, CURLOPT_SSL_VERIFYPEER=>false]);
        $r = curl_exec($ch); $e = curl_errno($ch); curl_close($ch);
        if (!$e && $r) { $host = $h; break; }
    }
    if (!$host) return false;

    $payload = json_encode([
        'model'   => 'llama3:latest',
        'prompt'  => $prompt,
        'stream'  => false,
        'options' => ['num_predict' => 600, 'temperature' => 0.3],
    ]);

    $ch = curl_init($host . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_errno($ch);
    curl_close($ch);

    if ($err || !$resp) return false;
    $d = json_decode($resp, true);
    return $d['response'] ?? false;
}

function parseLlamaBooks(string $raw): array {
    /* Strip markdown fences if present */
    $clean = preg_replace('/```(?:json)?\s*/i', '', $raw);
    $clean = preg_replace('/```/', '', $clean);
    $clean = trim($clean);

    /* Find JSON array */
    $start = strpos($clean, '[');
    $end   = strrpos($clean, ']');
    if ($start === false || $end === false) return [];

    $jsonStr = substr($clean, $start, $end - $start + 1);
    $parsed  = json_decode($jsonStr, true);
    if (!is_array($parsed)) return [];

    /* Validate each item */
    $valid = [];
    foreach ($parsed as $item) {
        if (!empty($item['title']) && !empty($item['author'])) {
            $valid[] = [
                'title'   => trim($item['title']),
                'author'  => trim($item['author']),
                'subject' => trim($item['subject'] ?? 'General'),
                'reason'  => trim($item['reason']  ?? ''),
            ];
        }
    }
    return $valid;
}

function fetchBookAPI(string $url): array|false {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'UPSC-Library/1.0',
    ]);
    $resp = curl_exec($ch);
    $err  = curl_errno($ch);
    curl_close($ch);
    if ($err || !$resp) return false;
    return json_decode($resp, true) ?: false;
}