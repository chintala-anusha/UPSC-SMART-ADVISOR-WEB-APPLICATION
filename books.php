<?php
/* ═══════════════════════════════════════════════════════════
   books.php  —  UPSC Digital Library
   • Curated books for Prelims / Mains / Interview
   • Live Google Books + Open Library API search
   • In-page book reader (Google Books embed + Open Library read)
   • No external CSS frameworks — pure custom CSS
═══════════════════════════════════════════════════════════ */
session_start();
include 'includes/auth.php';   // guards page, sets $uid
include 'includes/db.php';     // provides $conn

/* ── Load user's saved bookmarks from DB ── */
$bookmarks = [];
$bq = $conn->prepare('SELECT book_id FROM book_bookmarks WHERE user_id=?');
$bq->bind_param('i', $uid); $bq->execute();
$bq_res = $bq->get_result();
while ($brow = $bq_res->fetch_assoc()) $bookmarks[] = $brow['book_id'];
$bq->close();
$bookmarks_json = json_encode($bookmarks);

/* ── API fetch helper ── */
function fetchAPI(string $url): array|false {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'UPSC-Library/1.0',
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_errno($ch);
    curl_close($ch);
    if ($err || !$resp) return false;
    return json_decode($resp, true) ?: false;
}

/* ── Free book filter: only keep items with full view or public domain ── */
function isFreeBook(array $item): bool {
    $v = $item['volumeInfo']  ?? [];
    $a = $item['accessInfo']  ?? [];
    $s = $item['saleInfo']    ?? [];

    // Google Books free access flags
    $viewability = $a['viewability'] ?? '';
    $epub_free   = $a['epub']['isAvailable']     ?? false;
    $pdf_free    = $a['pdf']['isAvailable']       ?? false;
    $epub_dl     = ($a['epub']['downloadLink']    ?? '') !== '';
    $pdf_dl      = ($a['pdf']['downloadLink']     ?? '') !== '';
    $webReader   = ($a['webReaderLink']           ?? '') !== '';
    $country     = $a['country']                 ?? '';
    $saletype    = $s['saleability']              ?? '';

    // Accept if: full view, free downloadable epub/pdf, or public domain
    if (in_array($viewability, ['ALL_PAGES', 'PARTIAL'])) return true;
    if ($epub_free || $pdf_free || $epub_dl || $pdf_dl)   return true;
    if ($saletype === 'FREE')                              return true;

    return false;
}

/* ── Free curated fetch: always show curated (may be partial view) ── */
function isCuratedFreeOK(array $item): bool {
    $a = $item['accessInfo'] ?? [];
    $v = $a['viewability']   ?? '';
    // For curated books, accept PARTIAL view too (most standard references have it)
    return in_array($v, ['ALL_PAGES', 'PARTIAL', 'NO_PAGES']);
}

/* ── Curated book catalogue ── */
$CATALOGUE = [
    'prelims' => [
        'label' => 'Prelims',
        'color' => 'blue',
        'icon'  => 'circle',
        'books' => [
            ['title'=>'Indian Polity',                    'author'=>'M. Laxmikanth',          'subject'=>'Polity',     'query'=>'Indian+Polity+Laxmikanth'],
            ['title'=>'India\'s Ancient Past',            'author'=>'R.S. Sharma',             'subject'=>'History',    'query'=>'India+Ancient+Past+RS+Sharma'],
            ['title'=>'A Brief History of Modern India',  'author'=>'Rajiv Ahir (Spectrum)',   'subject'=>'History',    'query'=>'Brief+History+Modern+India+Spectrum'],
            ['title'=>'Indian Economy',                   'author'=>'Ramesh Singh',             'subject'=>'Economy',    'query'=>'Indian+Economy+Ramesh+Singh'],
            ['title'=>'Certificate Physical Geography',   'author'=>'G.C. Leong',              'subject'=>'Geography',  'query'=>'Certificate+Physical+Geography+Leong'],
            ['title'=>'Geography of India',               'author'=>'Majid Husain',             'subject'=>'Geography',  'query'=>'Geography+India+Majid+Husain'],
            ['title'=>'Environment & Ecology',            'author'=>'Shankar IAS Academy',     'subject'=>'Environment','query'=>'Environment+Ecology+Shankar+IAS'],
            ['title'=>'Indian Art & Culture',             'author'=>'Nitin Singhania',          'subject'=>'Culture',    'query'=>'Indian+Art+Culture+Nitin+Singhania'],
            ['title'=>'Science & Technology',             'author'=>'Ravi Agrahari',            'subject'=>'Science',    'query'=>'Science+Technology+UPSC+Ravi+Agrahari'],
            ['title'=>'NCERT History Class 12',           'author'=>'NCERT',                   'subject'=>'History',    'query'=>'NCERT+Themes+Indian+History+Class+12'],
            ['title'=>'NCERT Geography Class 11',         'author'=>'NCERT',                   'subject'=>'Geography',  'query'=>'NCERT+Fundamentals+Physical+Geography+11'],
            ['title'=>'India Year Book 2024',             'author'=>'Publications Division',    'subject'=>'Current',    'query'=>'India+Year+Book+2024'],
        ],
    ],
    'mains' => [
        'label' => 'Mains',
        'color' => 'teal',
        'icon'  => 'file',
        'books' => [
            ['title'=>'India\'s Foreign Policy',          'author'=>'Rajiv Sikri',             'subject'=>'IR',         'query'=>'India+Foreign+Policy+Rajiv+Sikri'],
            ['title'=>'Ethics, Integrity & Aptitude',     'author'=>'G. Subba Rao',            'subject'=>'Ethics',     'query'=>'Ethics+Integrity+Aptitude+UPSC+Subba+Rao'],
            ['title'=>'Lexicon for Ethics',               'author'=>'Chronicle Publications',  'subject'=>'Ethics',     'query'=>'Lexicon+Ethics+UPSC+Chronicle'],
            ['title'=>'Economic Survey 2023-24',          'author'=>'Ministry of Finance',     'subject'=>'Economy',    'query'=>'Economic+Survey+India+2024'],
            ['title'=>'Internal Security',                'author'=>'Ashok Kumar',             'subject'=>'Security',   'query'=>'Internal+Security+India+Ashok+Kumar+UPSC'],
            ['title'=>'Disaster Management',              'author'=>'IIPA / NIDM',             'subject'=>'DM',         'query'=>'Disaster+Management+India+UPSC'],
            ['title'=>'Social Justice',                   'author'=>'Unique Publications',     'subject'=>'GS II',      'query'=>'Social+Justice+India+UPSC+mains'],
            ['title'=>'Indian Society',                   'author'=>'NCERT Sociology XII',     'subject'=>'Sociology',  'query'=>'NCERT+Indian+Society+Sociology+XII'],
            ['title'=>'Constitution of India',            'author'=>'P.M. Bakshi',             'subject'=>'Polity',     'query'=>'Constitution+India+PM+Bakshi'],
            ['title'=>'21st Century IR',                  'author'=>'Pavneet Singh',           'subject'=>'IR',         'query'=>'International+Relations+21st+century+Pavneet+Singh'],
            ['title'=>'Essay Writing for Civil Services', 'author'=>'Arihant Experts',         'subject'=>'Essay',      'query'=>'Essay+Writing+Civil+Services+Arihant'],
            ['title'=>'Budget 2024-25 Analysis',          'author'=>'MRUNAL / PIB',            'subject'=>'Economy',    'query'=>'Union+Budget+India+2024+analysis'],
        ],
    ],
    'interview' => [
        'label' => 'Interview',
        'color' => 'amber',
        'icon'  => 'person',
        'books' => [
            ['title'=>'IAS Mains Interview',              'author'=>'Access Publishing',       'subject'=>'Interview',  'query'=>'IAS+Mains+Interview+Access+Publishing'],
            ['title'=>'Personality Development',          'author'=>'Swett Marden',            'subject'=>'Personality','query'=>'Personality+Development+Swett+Marden'],
            ['title'=>'Wings of Fire',                    'author'=>'A.P.J. Abdul Kalam',      'subject'=>'Motivation', 'query'=>'Wings+of+Fire+Kalam'],
            ['title'=>'The Monk Who Sold His Ferrari',    'author'=>'Robin Sharma',            'subject'=>'Mindset',    'query'=>'Monk+Sold+Ferrari+Robin+Sharma'],
            ['title'=>'India After Gandhi',               'author'=>'Ramachandra Guha',        'subject'=>'History',    'query'=>'India+After+Gandhi+Ramachandra+Guha'],
            ['title'=>'Discovery of India',               'author'=>'Jawaharlal Nehru',        'subject'=>'History',    'query'=>'Discovery+of+India+Nehru'],
            ['title'=>'From Bureaucracy to Governance',  'author'=>'Vinay Kumar',             'subject'=>'Governance', 'query'=>'Bureaucracy+Governance+India+Vinay+Kumar'],
            ['title'=>'Current Affairs Today',            'author'=>'Pratiyogita Darpan',      'subject'=>'Current',    'query'=>'Pratiyogita+Darpan+Current+Affairs'],
            ['title'=>'Indian Express Epaper',            'author'=>'Indian Express',          'subject'=>'News',       'query'=>'Indian+Express+editorial+collection'],
            ['title'=>'The Hindu Editorials',             'author'=>'The Hindu',               'subject'=>'News',       'query'=>'Hindu+editorial+analysis+UPSC'],
        ],
    ],
    'ncert' => [
        'label' => 'NCERT',
        'color' => 'violet',
        'icon'  => 'book',
        'books' => [
            ['title'=>'NCERT History Class 6',            'author'=>'NCERT',  'subject'=>'History',   'query'=>'NCERT+Our+Pasts+History+Class+6'],
            ['title'=>'NCERT History Class 7',            'author'=>'NCERT',  'subject'=>'History',   'query'=>'NCERT+Our+Pasts+History+Class+7'],
            ['title'=>'NCERT History Class 8',            'author'=>'NCERT',  'subject'=>'History',   'query'=>'NCERT+Our+Pasts+History+Class+8'],
            ['title'=>'NCERT History Class 9',            'author'=>'NCERT',  'subject'=>'History',   'query'=>'NCERT+India+World+Texts+History+9'],
            ['title'=>'NCERT History Class 10',           'author'=>'NCERT',  'subject'=>'History',   'query'=>'NCERT+India+World+Texts+History+10'],
            ['title'=>'NCERT History Class 11',           'author'=>'NCERT',  'subject'=>'History',   'query'=>'NCERT+Themes+World+History+Class+11'],
            ['title'=>'NCERT Polity Class 11',            'author'=>'NCERT',  'subject'=>'Polity',    'query'=>'NCERT+Political+Theory+Class+11'],
            ['title'=>'NCERT Polity Class 12',            'author'=>'NCERT',  'subject'=>'Polity',    'query'=>'NCERT+Politics+India+Class+12'],
            ['title'=>'NCERT Economy Class 11',           'author'=>'NCERT',  'subject'=>'Economy',   'query'=>'NCERT+Indian+Economic+Development+11'],
            ['title'=>'NCERT Economy Class 12',           'author'=>'NCERT',  'subject'=>'Economy',   'query'=>'NCERT+Macroeconomics+Class+12'],
            ['title'=>'NCERT Geography Class 12',         'author'=>'NCERT',  'subject'=>'Geography', 'query'=>'NCERT+India+People+Economy+Geography+12'],
            ['title'=>'NCERT Biology Class 12',           'author'=>'NCERT',  'subject'=>'Science',   'query'=>'NCERT+Biology+Class+12'],
        ],
    ],
];

/* ── Active tab + search ── */
$activeTab  = $_GET['tab']    ?? 'prelims';
if (!array_key_exists($activeTab, $CATALOGUE)) $activeTab = 'prelims';
$searchQ    = trim($_GET['search'] ?? '');
$searchResults = [];
$olResults     = [];

/* ── Fetch curated book data from Google Books for active tab ── */
$curatedData = [];
if (empty($searchQ)) {
    foreach ($CATALOGUE[$activeTab]['books'] as $idx => $b) {
        if ($idx >= 6) { $curatedData[] = ['manual' => $b]; continue; }
        // &filter=free-ebooks fetches only freely available books
        $url  = 'https://www.googleapis.com/books/v1/volumes?q=' . $b['query']
              . '&maxResults=3&filter=free-ebooks';
        $resp = fetchAPI($url);
        // Try free-ebooks first, fall back to partial if none found
        if ($resp && !empty($resp['items'])) {
            $curatedData[] = ['api' => $resp['items'][0], 'meta' => $b];
        } else {
            // Fall back: any viewable result for this curated book
            $url2  = 'https://www.googleapis.com/books/v1/volumes?q=' . $b['query'] . '&maxResults=1';
            $resp2 = fetchAPI($url2);
            if ($resp2 && !empty($resp2['items'][0])) {
                $curatedData[] = ['api' => $resp2['items'][0], 'meta' => $b];
            } else {
                $curatedData[] = ['manual' => $b];
            }
        }
    }
}

/* ── Live search — free books only ── */
if (!empty($searchQ)) {
    // Google Books: free-ebooks + partial filter
    $gUrl = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($searchQ)
          . '&maxResults=20&filter=free-ebooks';
    $gData = fetchAPI($gUrl);
    if ($gData && !empty($gData['items'])) {
        // Already filtered by Google, but double-check with our own filter
        $searchResults = array_filter($gData['items'], 'isFreeBook');
        $searchResults = array_values($searchResults);
    }
    // If free-ebooks returns nothing, try partial view
    if (empty($searchResults)) {
        $gUrl2 = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($searchQ)
               . '&maxResults=20&filter=partial';
        $gData2 = fetchAPI($gUrl2);
        if ($gData2 && !empty($gData2['items'])) {
            $searchResults = array_filter($gData2['items'], 'isFreeBook');
            $searchResults = array_values($searchResults);
        }
    }

    // Open Library: public domain books on archive.org
    $olUrl  = 'https://openlibrary.org/search.json?q=' . urlencode($searchQ)
            . '&limit=16&fields=key,title,author_name,cover_i,first_publish_year,'
            . 'publisher,subject,number_of_pages_median,ia,has_fulltext';
    $olData = fetchAPI($olUrl);
    if ($olData && !empty($olData['docs'])) {
        // Only keep books with archive.org full text available
        $olResults = array_filter($olData['docs'], function($doc) {
            return !empty($doc['has_fulltext']) || !empty($doc['ia']);
        });
        $olResults = array_values(array_slice($olResults, 0, 12));
    }
}

/* ── Helper: build a unified book object ── */
function buildBook(array $entry): array {
    if (isset($entry['api'])) {
        $v = $entry['api']['volumeInfo'] ?? [];
        return [
            'id'          => $entry['api']['id'] ?? '',
            'title'       => $v['title'] ?? 'Unknown Title',
            'author'      => implode(', ', $v['authors'] ?? ['Unknown Author']),
            'publisher'   => $v['publisher'] ?? '',
            'year'        => substr($v['publishedDate'] ?? '', 0, 4),
            'subject'     => $entry['meta']['subject'] ?? '',
            'description' => $v['description'] ?? '',
            'cover'       => str_replace('http://', 'https://', $v['imageLinks']['thumbnail'] ?? ''),
            'preview'     => $v['previewLink'] ?? '',
            'embed'       => isset($entry['api']['id']) ? 'https://books.google.com/books?id=' . $entry['api']['id'] . '&lpg=PP1&pg=PP1&output=embed' : '',
            'pages'       => $v['pageCount'] ?? '',
            'source'      => 'google',
        ];
    }
    // manual fallback
    $m = $entry['manual'];
    return [
        'id'=>'', 'title'=>$m['title'], 'author'=>$m['author'],
        'publisher'=>'', 'year'=>'', 'subject'=>$m['subject'],
        'description'=>'', 'cover'=>'', 'preview'=>'',
        'embed'=>'', 'pages'=>'', 'source'=>'manual',
    ];
}

function buildGoogleBook(array $item): array {
    $v = $item['volumeInfo'] ?? [];
    $a = $item['accessInfo'] ?? [];
    $s = $item['saleInfo']   ?? [];
    $id = $item['id'] ?? '';

    // Best embed: webReaderLink > embed > preview
    $webReader  = $a['webReaderLink'] ?? '';
    $epubLink   = $a['epub']['downloadLink'] ?? '';
    $pdfLink    = $a['pdf']['downloadLink']  ?? '';
    $previewLink= $v['previewLink'] ?? '';
    $embed      = $id ? 'https://books.google.com/books?id=' . $id . '&lpg=PP1&pg=PP1&output=embed' : '';

    // For the Read button — best in-page URL
    $readUrl = $webReader ?: $embed ?: $previewLink;

    return [
        'id'          => $id,
        'title'       => $v['title'] ?? 'Unknown Title',
        'author'      => implode(', ', $v['authors'] ?? ['Unknown Author']),
        'publisher'   => $v['publisher'] ?? '',
        'year'        => substr($v['publishedDate'] ?? '', 0, 4),
        'subject'     => '',
        'description' => $v['description'] ?? '',
        'cover'       => str_replace('http://', 'https://', $v['imageLinks']['thumbnail'] ?? ''),
        'preview'     => $previewLink,
        'embed'       => $readUrl,
        'epub'        => $epubLink,
        'pdf'         => $pdfLink,
        'pages'       => $v['pageCount'] ?? '',
        'viewability' => $a['viewability'] ?? '',
        'free'        => isFreeBook($item),
        'source'      => 'google',
    ];
}

function buildOLBook(array $doc): array {
    $key  = $doc['key'] ?? '';
    $ia   = $doc['ia'][0] ?? '';  // Internet Archive identifier
    // Prefer archive.org embed if available (fully readable)
    $readUrl = $ia
        ? 'https://archive.org/embed/' . $ia . '?view=theater'
        : ($key ? 'https://openlibrary.org' . $key . '?layout=embed' : '');
    $preview = $ia
        ? 'https://archive.org/details/' . $ia
        : ($key ? 'https://openlibrary.org' . $key : '');
    return [
        'id'          => $key,
        'ia'          => $ia,
        'title'       => $doc['title'] ?? 'Unknown Title',
        'author'      => $doc['author_name'][0] ?? 'Unknown Author',
        'publisher'   => $doc['publisher'][0] ?? '',
        'year'        => $doc['first_publish_year'] ?? '',
        'subject'     => $doc['subject'][0] ?? '',
        'description' => '',
        'cover'       => isset($doc['cover_i']) ? 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-M.jpg' : '',
        'preview'     => $preview,
        'embed'       => $readUrl,
        'epub'        => $ia ? 'https://archive.org/download/' . $ia . '/' . $ia . '.epub' : '',
        'pdf'         => '',
        'pages'       => $doc['number_of_pages_median'] ?? '',
        'viewability' => 'ALL_PAGES',
        'free'        => true,
        'source'      => 'openlibrary',
    ];
}

$SUBJECT_COLORS = [
    'Polity'=>'violet','History'=>'coral','Geography'=>'teal','Economy'=>'amber',
    'Environment'=>'green','Science'=>'blue','Culture'=>'pink','Ethics'=>'pink',
    'IR'=>'blue','Security'=>'red','Essay'=>'violet','Current'=>'amber',
    'NCERT'=>'teal','Motivation'=>'coral','Personality'=>'green','News'=>'blue',
    'GS II'=>'teal','Sociology'=>'violet','DM'=>'amber','Governance'=>'blue',
    'Mindset'=>'green','default'=>'muted',
];

function subjectColor(string $s): string {
    global $SUBJECT_COLORS;
    return $SUBJECT_COLORS[$s] ?? $SUBJECT_COLORS['default'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Digital Library — UPSC Command Center</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">

<style>
:root{
    --grad-start:#1a0533;--grad-mid:#3d0f6b;--grad-end:#6b1fa8;
    --violet:#7c3aed;--violet-lt:#f0eaff;--violet-dk:#5b21b6;
    --coral:#ff6b4a;--coral-lt:#fff0ed;
    --blue:#3b82f6;--blue-lt:#eff5ff;
    --teal:#0fb98a;--teal-lt:#e6faf4;
    --amber:#f59e0b;--amber-lt:#fffbeb;
    --green:#16a34a;--green-lt:#f0fdf4;
    --red:#ef4444;--red-lt:#fef2f2;
    --pink:#ec4899;--pink-lt:#fdf2f8;
    --muted:#6b6579;--muted-lt:#f4f2f8;
    --ink:#1a1523;--border:rgba(0,0,0,0.07);
    --surface:#fff;--page:#f4f2f8;
    --ease:cubic-bezier(0.2,0.8,0.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
html{scroll-behavior:smooth;}
body{font-family:'DM Sans',sans-serif;background:var(--page);color:var(--ink);min-height:100vh;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}

/* ── HERO ── */
.hero{
    background:linear-gradient(135deg,var(--grad-start) 0%,var(--grad-mid) 50%,var(--grad-end) 100%);
    position:relative;padding:5rem 2rem 7rem;text-align:center;color:#fff;overflow:hidden;
}
.hero::before{
    content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-55%);
    width:600px;height:380px;
    background:radial-gradient(ellipse,rgba(160,80,255,.28) 0%,transparent 70%);
    pointer-events:none;
}
.hero-inner{position:relative;z-index:2;max-width:700px;margin:0 auto;}
.back-btn{
    position:absolute;top:1.75rem;left:1.75rem;z-index:50;
    width:46px;height:46px;border-radius:13px;
    display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);
    backdrop-filter:blur(12px);color:#fff;transition:all .25s var(--ease);
}
.back-btn:hover{background:rgba(255,255,255,.25);transform:translateY(-2px);}
.back-btn svg{width:20px;height:20px;}
.pre-badge{
    display:inline-block;font-family:'Syne',sans-serif;
    font-size:.7rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);
    border-radius:100px;padding:.35rem 1.1rem;margin-bottom:1.2rem;
}
.hero h1{
    font-family:'Syne',sans-serif;font-size:clamp(2rem,5vw,3.2rem);
    font-weight:800;line-height:1.12;letter-spacing:-.02em;margin-bottom:.8rem;
}
.hero p{font-size:1rem;font-weight:300;opacity:.82;max-width:480px;margin:0 auto 2rem;}

/* Search bar in hero */
.hero-search{
    display:flex;gap:.6rem;max-width:540px;margin:0 auto;position:relative;z-index:2;
}
.hero-search input{
    flex:1;padding:.75rem 1.1rem;border-radius:13px;border:none;
    background:rgba(255,255,255,.15);backdrop-filter:blur(10px);
    color:#fff;font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;
    border:1px solid rgba(255,255,255,.25);transition:background .2s;
}
.hero-search input::placeholder{color:rgba(255,255,255,.6);}
.hero-search input:focus{background:rgba(255,255,255,.22);}
.hero-search button{
    padding:.75rem 1.4rem;border-radius:13px;border:none;
    background:rgba(255,255,255,.18);color:#fff;
    font-family:'Syne',sans-serif;font-size:.85rem;font-weight:700;cursor:pointer;
    transition:all .2s;white-space:nowrap;display:flex;align-items:center;gap:.4rem;
}
.hero-search button:hover{background:rgba(255,255,255,.28);}
.hero-search button svg{width:16px;height:16px;}

.wave{position:absolute;bottom:-1px;left:0;width:100%;line-height:0;z-index:3;}
.wave svg{display:block;width:100%;height:80px;}

/* ── AI RECOMMENDATIONS PANEL ── */
.ai-panel{
    background:linear-gradient(135deg,var(--grad-start),var(--grad-end));
    padding:1.25rem 1.5rem;
}
.ai-panel-inner{max-width:1160px;margin:0 auto;}
.ai-row{display:flex;gap:.65rem;align-items:center;flex-wrap:wrap;}
.ai-stage-sel{
    padding:.55rem .9rem;border-radius:10px;border:1px solid rgba(255,255,255,.25);
    background:rgba(255,255,255,.12);color:#fff;font-family:'DM Sans',sans-serif;
    font-size:.82rem;cursor:pointer;outline:none;
    appearance:none;-webkit-appearance:none;min-width:120px;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right .5rem center;background-size:16px;
    padding-right:2rem;
}
.ai-stage-sel option{background:#3d0f6b;color:#fff;}
.ai-inp{
    flex:1;min-width:220px;padding:.55rem 1rem;border-radius:10px;
    border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);
    color:#fff;font-family:'DM Sans',sans-serif;font-size:.85rem;outline:none;
    transition:background .2s;
}
.ai-inp::placeholder{color:rgba(255,255,255,.55);}
.ai-inp:focus{background:rgba(255,255,255,.2);}
.ai-go-btn{
    display:flex;align-items:center;gap:.45rem;padding:.55rem 1.2rem;
    border-radius:10px;border:none;background:#fff;color:var(--violet);
    font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;
    cursor:pointer;transition:all .2s;white-space:nowrap;flex-shrink:0;
}
.ai-go-btn:hover{background:var(--violet-lt);transform:translateY(-1px);}
.ai-go-btn:disabled{opacity:.5;cursor:wait;transform:none;}
.ai-go-btn svg{width:15px;height:15px;}
.ai-quick{display:flex;gap:.4rem;margin-top:.65rem;flex-wrap:wrap;}
.ai-qbtn{
    padding:.28rem .75rem;border-radius:100px;border:1px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);
    font-family:'DM Sans',sans-serif;font-size:.72rem;cursor:pointer;transition:all .15s;
}
.ai-qbtn:hover{background:rgba(255,255,255,.2);color:#fff;}

/* AI results section */
.ai-results-wrap{
    max-width:1160px;margin:0 auto;padding:1.25rem 1.5rem;
    display:none;
}
.ai-results-wrap.show{display:block;}
.ai-results-hd{
    display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;
}
.ai-results-hd h3{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;}
.ai-results-hd p{font-size:.8rem;color:var(--muted);}
.ai-badge{
    display:inline-flex;align-items:center;gap:.35rem;
    background:var(--violet-lt);color:var(--violet);
    border-radius:100px;padding:.25rem .75rem;font-size:.7rem;font-weight:600;
}
.ai-badge svg{width:12px;height:12px;}
.ai-loading{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:2.5rem 1rem;gap:.85rem;color:var(--muted);
}
.ai-loading .ai-spin{
    width:40px;height:40px;border-radius:50%;
    border:3px solid var(--violet-lt);border-top-color:var(--violet);
    animation:spin .75s linear infinite;
}
.ai-loading p{font-size:.85rem;}
.ai-loading .ai-thinking{
    font-size:.75rem;font-family:'DM Mono',monospace;color:var(--violet);
    opacity:.7;animation:blink 1.2s infinite;
}
.ai-error{
    background:var(--red-lt);border:1px solid rgba(239,68,68,.2);
    border-radius:13px;padding:1rem 1.25rem;font-size:.85rem;color:var(--red);
    display:flex;align-items:center;gap:.65rem;
}
.ai-error svg{width:18px;height:18px;flex-shrink:0;}

/* AI reason pill on each card */
.ai-reason{
    display:flex;align-items:flex-start;gap:.4rem;
    background:var(--violet-lt);border-radius:8px;padding:.4rem .65rem;
    font-size:.72rem;color:var(--violet);line-height:1.45;margin-top:.5rem;
}
.ai-reason svg{width:12px;height:12px;flex-shrink:0;margin-top:.1rem;}

/* ── CATEGORY TABS ── */
.cat-nav{    background:var(--surface);border-bottom:1px solid var(--border);
    display:flex;justify-content:center;gap:0;overflow-x:auto;
    position:sticky;top:0;z-index:40;
    box-shadow:0 2px 12px rgba(0,0,0,.06);
}
.cat-btn{
    padding:1rem 1.6rem;border:none;background:transparent;
    font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:500;
    color:var(--muted);cursor:pointer;transition:all .2s;
    display:flex;align-items:center;gap:.5rem;white-space:nowrap;
    border-bottom:2.5px solid transparent;position:relative;top:1px;
}
.cat-btn .cdot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.cat-btn:hover{color:var(--ink);}
.cat-btn.active{font-weight:600;border-bottom-color:currentColor;}

.cat-btn.c-blue  {color:var(--blue);}
.cat-btn.c-teal  {color:var(--teal);}
.cat-btn.c-amber {color:var(--amber);}
.cat-btn.c-violet{color:var(--violet);}

/* ── MAIN ── */
.main{max-width:1200px;margin:0 auto;padding:2.5rem 1.5rem 5rem;}

/* ── SEARCH RESULTS HEADING ── */
.results-heading{
    display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;
    padding-bottom:1rem;border-bottom:1px solid var(--border);
}
.results-heading h2{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;flex:1;}
.results-source{
    font-size:.72rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
    border-radius:8px;padding:.3rem .75rem;
}
.src-google{background:var(--blue-lt);color:var(--blue);}
.src-ol    {background:var(--teal-lt);color:var(--teal);}

/* ── BOOK GRID ── */
.books-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
    gap:1.1rem;margin-bottom:3rem;
}

/* ── BOOK CARD ── */
.book-card{
    background:var(--surface);border-radius:18px;border:1px solid var(--border);
    overflow:hidden;box-shadow:0 3px 14px rgba(0,0,0,.05);
    transition:transform .25s var(--ease),box-shadow .25s var(--ease),border-color .25s var(--ease);
    cursor:pointer;display:flex;flex-direction:column;
    opacity:0;animation:fadeUp .5s var(--ease) forwards;
}
.book-card:hover{transform:translateY(-7px);box-shadow:0 18px 40px rgba(0,0,0,.12);border-color:rgba(124,58,237,.2);}

.book-cover{
    width:100%;aspect-ratio:2/3;background:var(--page);
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;position:relative;
}
.book-cover img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease;}
.book-card:hover .book-cover img{transform:scale(1.04);}
.book-cover-placeholder{
    width:100%;height:100%;display:flex;flex-direction:column;
    align-items:center;justify-content:center;gap:.5rem;padding:1rem;
    font-size:.75rem;color:var(--muted);text-align:center;
}
.cover-ico{
    width:48px;height:48px;border-radius:13px;
    display:flex;align-items:center;justify-content:center;margin-bottom:.25rem;
}
.cover-ico svg{width:24px;height:24px;}

/* Source badge on cover */
.cover-source{
    position:absolute;top:.5rem;left:.5rem;
    font-size:.6rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
    border-radius:6px;padding:.2rem .5rem;
}
.cs-google{background:rgba(59,130,246,.15);color:var(--blue);}
.cs-ol    {background:rgba(15,185,138,.15);color:var(--teal);}
.cs-manual{background:rgba(124,58,237,.15);color:var(--violet);}

.book-body{padding:1rem;flex:1;display:flex;flex-direction:column;}
.book-subject{
    font-size:.65rem;font-weight:600;letter-spacing:.09em;text-transform:uppercase;
    border-radius:6px;padding:.2rem .55rem;display:inline-block;margin-bottom:.5rem;align-self:flex-start;
}

/* subject colours */
.sc-violet {background:var(--violet-lt);color:var(--violet);}
.sc-coral  {background:var(--coral-lt); color:var(--coral);}
.sc-blue   {background:var(--blue-lt);  color:var(--blue);}
.sc-teal   {background:var(--teal-lt);  color:var(--teal);}
.sc-amber  {background:var(--amber-lt); color:var(--amber);}
.sc-green  {background:var(--green-lt); color:var(--green);}
.sc-pink   {background:var(--pink-lt);  color:var(--pink);}
.sc-red    {background:var(--red-lt);   color:var(--red);}
.sc-muted  {background:var(--muted-lt); color:var(--muted);}

.book-title{
    font-family:'Syne',sans-serif;font-size:.88rem;font-weight:700;
    color:var(--ink);line-height:1.3;margin-bottom:.3rem;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.book-author{font-size:.75rem;color:var(--muted);margin-bottom:.25rem;}
.book-meta{font-size:.7rem;color:var(--muted);display:flex;gap:.4rem;flex-wrap:wrap;}

.book-actions{
    display:flex;gap:.5rem;margin-top:.85rem;padding-top:.75rem;
    border-top:1px solid var(--border);
}
.bk-btn{
    flex:1;padding:.4rem .5rem;border-radius:9px;border:1px solid var(--border);
    background:transparent;font-family:'DM Sans',sans-serif;font-size:.72rem;font-weight:500;
    color:var(--muted);cursor:pointer;transition:all .2s;
    display:flex;align-items:center;justify-content:center;gap:.3rem;
}
.bk-btn svg{width:12px;height:12px;}
.bk-btn:hover{border-color:var(--violet);color:var(--violet);background:var(--violet-lt);}
.bk-btn.read-btn{background:var(--violet-lt);color:var(--violet);border-color:rgba(124,58,237,.3);}
.bk-btn.read-btn:hover{background:var(--violet);color:#fff;}

/* ── Download button ── */
.bk-btn.dl-btn{background:var(--green-lt);color:var(--green);border-color:rgba(22,163,74,.3);}
.bk-btn.dl-btn:hover{background:var(--green);color:#fff;}
.bk-btn.dl-btn.loading{opacity:.6;cursor:wait;}

/* ── PDF Preview Modal ── */
.pdf-overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(6px);
    z-index:2000;display:none;align-items:center;justify-content:center;padding:1rem;
}
.pdf-overlay.open{display:flex;}
.pdf-box{
    background:var(--surface);border-radius:22px;width:100%;max-width:860px;
    max-height:94vh;display:flex;flex-direction:column;overflow:hidden;
    box-shadow:0 32px 80px rgba(0,0,0,.4);animation:fadeUp .3s var(--ease) both;
}
.pdf-header{
    padding:1rem 1.25rem;border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:.85rem;flex-shrink:0;
    background:linear-gradient(135deg,var(--grad-start),var(--grad-end));color:#fff;
    border-radius:22px 22px 0 0;
}
.pdf-cover{width:44px;height:58px;border-radius:7px;object-fit:cover;flex-shrink:0;background:rgba(255,255,255,.15);}
.pdf-title-wrap{flex:1;min-width:0;}
.pdf-title{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pdf-author{font-size:.75rem;opacity:.75;margin-top:.15rem;}
.pdf-hbtns{display:flex;gap:.45rem;flex-shrink:0;}
.pdf-hbtn{
    display:flex;align-items:center;gap:.35rem;padding:.4rem .85rem;
    border-radius:8px;border:1px solid rgba(255,255,255,.25);
    background:rgba(255,255,255,.12);color:#fff;cursor:pointer;
    font-family:'DM Sans',sans-serif;font-size:.75rem;font-weight:500;
    transition:all .2s;text-decoration:none;
}
.pdf-hbtn:hover{background:rgba(255,255,255,.25);}
.pdf-hbtn.confirm-dl{background:var(--green);border-color:var(--green);}
.pdf-hbtn.confirm-dl:hover{background:#15803d;}
.pdf-hbtn svg{width:14px;height:14px;}
.pdf-close-btn{
    width:36px;height:36px;border-radius:9px;border:1px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.1);color:#fff;cursor:pointer;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;
}
.pdf-close-btn:hover{background:rgba(239,68,68,.7);}
.pdf-close-btn svg{width:16px;height:16px;}

/* preview area */
.pdf-preview-area{flex:1;overflow:hidden;position:relative;background:#e5e7eb;min-height:400px;}
.pdf-frame{width:100%;height:100%;border:none;}
.pdf-loading{
    position:absolute;inset:0;display:flex;flex-direction:column;
    align-items:center;justify-content:center;gap:.75rem;
    background:#f4f2f8;font-size:.85rem;color:var(--muted);
}
.pdf-loading .spin{
    width:36px;height:36px;border:3px solid var(--border);
    border-top-color:var(--violet);border-radius:50%;animation:spin .8s linear infinite;
}
.pdf-no-preview{
    display:none;flex-direction:column;align-items:center;justify-content:center;
    height:100%;gap:.85rem;color:var(--muted);text-align:center;padding:2rem;
}
.pdf-no-preview svg{width:52px;height:52px;opacity:.25;}
.pdf-no-preview h4{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:var(--ink);}
.pdf-no-preview p{font-size:.82rem;max-width:320px;line-height:1.6;}

/* footer bar */
.pdf-footer{
    padding:.75rem 1.25rem;border-top:1px solid var(--border);
    display:flex;align-items:center;gap:.85rem;flex-shrink:0;background:var(--surface);
}
.pdf-pages{font-size:.78rem;color:var(--muted);font-family:'DM Mono',monospace;flex:1;}
.pdf-source-badge{
    font-size:.68rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
    border-radius:7px;padding:.22rem .6rem;
}
.pdf-dl-progress{
    display:none;align-items:center;gap:.65rem;font-size:.78rem;color:var(--green);
    background:var(--green-lt);border-radius:8px;padding:.4rem .85rem;
}
.pdf-dl-progress.show{display:flex;}
.pdf-dl-progress svg{width:14px;height:14px;animation:spin .8s linear infinite;}

@keyframes spin{to{transform:rotate(360deg);}}

/* stagger animation */
.book-card:nth-child(1) {animation-delay:.03s}
.book-card:nth-child(2) {animation-delay:.06s}
.book-card:nth-child(3) {animation-delay:.09s}
.book-card:nth-child(4) {animation-delay:.12s}
.book-card:nth-child(5) {animation-delay:.15s}
.book-card:nth-child(6) {animation-delay:.18s}
.book-card:nth-child(7) {animation-delay:.21s}
.book-card:nth-child(8) {animation-delay:.24s}
.book-card:nth-child(n+9){animation-delay:.27s}

/* ── SECTION LABEL ── */
.section-label{
    display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;margin-top:2rem;
}
.section-label h3{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;}
.section-line{flex:1;height:1px;background:var(--border);}
.section-count{
    font-size:.72rem;font-weight:600;background:var(--page);color:var(--muted);
    border-radius:8px;padding:.25rem .65rem;
}

/* ── BOOK READER MODAL ── */
.modal-overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(6px);
    z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem;
}
.modal-overlay.open{display:flex;}
.modal-box{
    background:var(--surface);border-radius:22px;width:100%;max-width:940px;
    max-height:92vh;display:flex;flex-direction:column;overflow:hidden;
    box-shadow:0 32px 80px rgba(0,0,0,.35);
    animation:fadeUp .3s var(--ease) both;
}
.modal-header{
    padding:1.1rem 1.5rem;border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:1rem;flex-shrink:0;
}
.modal-cover{
    width:48px;height:64px;border-radius:8px;object-fit:cover;flex-shrink:0;background:var(--page);
}
.modal-title-wrap{flex:1;min-width:0;}
.modal-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.modal-author{font-size:.8rem;color:var(--muted);}
.modal-header-actions{display:flex;gap:.5rem;flex-shrink:0;}
.mh-btn{
    width:36px;height:36px;border-radius:10px;border:1px solid var(--border);
    background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;
    color:var(--muted);transition:all .2s;
}
.mh-btn:hover{background:var(--page);color:var(--ink);}
.mh-btn.close-btn:hover{background:var(--red-lt);color:var(--red);}
.mh-btn svg{width:16px;height:16px;}

.modal-tabs{
    display:flex;gap:0;border-bottom:1px solid var(--border);flex-shrink:0;
}
.mtab{
    padding:.65rem 1.1rem;border:none;background:transparent;
    font-family:'DM Sans',sans-serif;font-size:.78rem;font-weight:500;
    color:var(--muted);cursor:pointer;transition:all .2s;
    border-bottom:2px solid transparent;position:relative;top:1px;
}
.mtab:hover{color:var(--ink);}
.mtab.active{color:var(--violet);border-bottom-color:var(--violet);font-weight:600;}

.modal-body{flex:1;overflow:hidden;position:relative;}
.modal-pane{width:100%;height:100%;display:none;}
.modal-pane.active{display:flex;flex-direction:column;}

/* Reader iframe */
.reader-frame{
    flex:1;border:none;background:var(--page);
}

/* Info pane */
.info-pane{padding:1.5rem;overflow-y:auto;height:100%;}
.info-cover-row{display:flex;gap:1.25rem;margin-bottom:1.5rem;align-items:flex-start;}
.info-cover-img{width:100px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.12);}
.info-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;flex:1;}
.meta-chip{background:var(--page);border-radius:10px;padding:.6rem .85rem;}
.meta-chip .mc-l{font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.2rem;}
.meta-chip .mc-v{font-size:.82rem;font-weight:500;color:var(--ink);}
.info-desc{font-size:.85rem;color:var(--muted);line-height:1.65;}
.info-desc h4{font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;color:var(--ink);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.08em;}

/* No reader message */
.no-reader{
    display:none;flex-direction:column;align-items:center;justify-content:center;
    height:100%;gap:.75rem;color:var(--muted);text-align:center;padding:2rem;
}
.no-reader svg{width:48px;height:48px;opacity:.25;}
.no-reader h4{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:var(--ink);}
.no-reader p{font-size:.82rem;max-width:320px;line-height:1.6;}
.no-reader-actions{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center;margin-top:.25rem;}
.nr-btn{
    display:inline-flex;align-items:center;gap:.4rem;
    border-radius:100px;padding:.5rem 1.1rem;font-size:.8rem;font-weight:500;
    transition:all .2s;cursor:pointer;text-decoration:none;border:none;
    font-family:'DM Sans',sans-serif;
}
.nr-btn.primary{background:var(--violet);color:#fff;}
.nr-btn.primary:hover{background:var(--violet-dk);}
.nr-btn.secondary{background:var(--page);border:1px solid var(--border);color:var(--ink);}
.nr-btn.secondary:hover{border-color:var(--violet);color:var(--violet);}
.nr-btn svg{width:13px;height:13px;}

/* Reader loading spinner */
.reader-loading{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    height:100%;gap:.75rem;color:var(--muted);
}
.rl-spin{
    width:38px;height:38px;border-radius:50%;
    border:3px solid var(--violet-lt);border-top-color:var(--violet);
    animation:spin .75s linear infinite;
}
.reader-loading p{font-size:.82rem;}

/* Source switcher bar */
.src-bar{
    display:flex;align-items:center;gap:.6rem;padding:.5rem .85rem;
    background:var(--page);border-bottom:1px solid var(--border);flex-shrink:0;flex-wrap:wrap;
}
.src-bar-lbl{font-size:.72rem;color:var(--muted);white-space:nowrap;}
.src-btns{display:flex;gap:.35rem;flex-wrap:wrap;}
.src-btn{
    padding:.28rem .7rem;border-radius:100px;border:1px solid var(--border);
    background:var(--surface);font-size:.72rem;color:var(--muted);cursor:pointer;
    transition:all .15s;font-family:'DM Sans',sans-serif;
}
.src-btn:hover{border-color:var(--violet);color:var(--violet);}
.src-btn.active{background:var(--violet);border-color:var(--violet);color:#fff;}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}

@media(max-width:700px){
    .books-grid{grid-template-columns:repeat(2,1fr);}
    .modal-box{max-height:95vh;border-radius:18px 18px 0 0;margin-top:auto;width:100%;}
    .info-meta-grid{grid-template-columns:1fr;}
}
@media(max-width:400px){
    .books-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<!-- ── HERO ── -->
<header class="hero">
    <a href="dashboard.php" class="back-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div class="hero-inner">
        <span class="pre-badge">UPSC Digital Library</span>
        <h1>Digital Library</h1>
        <p>Curated books for every stage — search, preview and read inside the app.</p>
        <form method="GET" class="hero-search">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
            <input type="text" name="search" placeholder="Search any book, author, topic…"
                   value="<?php echo htmlspecialchars($searchQ); ?>" autocomplete="off">
            <button type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
        </form>
    </div>
    <div class="wave">
        <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,40 C180,80 360,0 540,40 C720,80 900,0 1080,40 C1260,80 1380,20 1440,40 L1440,80 L0,80 Z" fill="#f4f2f8"/>
        </svg>
    </div>
</header>

<!-- ── AI RECOMMENDATIONS PANEL ── -->
<div class="ai-panel">
    <div class="ai-panel-inner">
        <div class="ai-row">
            <select class="ai-stage-sel" id="ai-stage">
                <option value="Any">All Stages</option>
                <option value="Prelims">Prelims</option>
                <option value="Mains">Mains</option>
                <option value="Interview">Interview</option>
                <option value="NCERT">NCERT</option>
            </select>
            <input class="ai-inp" id="ai-inp" type="text"
                   placeholder="Ask AI: books for Ethics GS IV, or Geography Mains…"
                   maxlength="200" onkeydown="if(event.key==='Enter') askAI()">
            <button class="ai-go-btn" id="ai-go-btn" onclick="askAI()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85"/></svg>
                Ask AI
            </button>
        </div>
        <div class="ai-quick">
            <button class="ai-qbtn" onclick="quickAsk('books for Prelims GS History')">📚 Prelims History</button>
            <button class="ai-qbtn" onclick="quickAsk('best books for Ethics GS IV Mains')">⚖️ Ethics GS IV</button>
            <button class="ai-qbtn" onclick="quickAsk('must read books for UPSC interview personality test')">🎤 Interview Prep</button>
            <button class="ai-qbtn" onclick="quickAsk('NCERT books list for UPSC preparation')">📖 NCERT List</button>
            <button class="ai-qbtn" onclick="quickAsk('current affairs and newspaper books for UPSC')">📰 Current Affairs</button>
            <button class="ai-qbtn" onclick="quickAsk('optional subject Public Administration books UPSC Mains')">🏛️ Public Admin</button>
        </div>
    </div>
</div>

<!-- AI Results area -->
<div class="ai-results-wrap" id="ai-results-wrap">
    <div class="ai-results-hd">
        <span class="ai-badge">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/></svg>
            AI Picks
        </span>
        <div>
            <h3 id="ai-results-title">Recommendations</h3>
            <p id="ai-results-sub"></p>
        </div>
        <button onclick="clearAI()" style="margin-left:auto;background:none;border:1px solid var(--border);border-radius:8px;padding:.3rem .75rem;font-size:.75rem;color:var(--muted);cursor:pointer;">✕ Clear</button>
    </div>
    <div id="ai-results-body"></div>
</div>

<!-- ── CATEGORY TABS ── -->
<nav class="cat-nav">
    <?php
    $catColors = ['prelims'=>'c-blue','mains'=>'c-teal','interview'=>'c-amber','ncert'=>'c-violet'];
    $catDots   = ['prelims'=>'var(--blue)','mains'=>'var(--teal)','interview'=>'var(--amber)','ncert'=>'var(--violet)'];
    foreach ($CATALOGUE as $key => $cat):
        $isActive = ($key === $activeTab && empty($searchQ));
    ?>
    <a href="?tab=<?php echo $key; ?>" class="cat-btn <?php echo $catColors[$key]; ?> <?php echo $isActive?'active':''; ?>">
        <span class="cdot" style="background:<?php echo $catDots[$key]; ?>"></span>
        <?php echo $cat['label']; ?>
    </a>
    <?php endforeach; ?>
    <?php if (!empty($searchQ)): ?>
    <a href="?tab=<?php echo htmlspecialchars($activeTab); ?>" class="cat-btn" style="color:var(--muted);margin-left:auto;">
        ✕ Clear Search
    </a>
    <?php endif; ?>
</nav>

<!-- ── MAIN CONTENT ── -->
<main class="main">

<?php if (!empty($searchQ)): ?>

    <!-- ── SEARCH RESULTS ── -->
    <?php if (!empty($searchResults)): ?>
    <div class="results-heading">
        <h2>Results for "<?php echo htmlspecialchars($searchQ); ?>"</h2>
        <span class="results-source src-google">Google Books</span>
    </div>
    <div class="books-grid">
        <?php foreach ($searchResults as $item):
            $b = buildGoogleBook($item);
        ?>
        <div class="book-card"
             data-id="<?php echo htmlspecialchars($b['id']); ?>"
             data-title="<?php echo htmlspecialchars($b['title']); ?>"
             data-author="<?php echo htmlspecialchars($b['author']); ?>"
             data-cover="<?php echo htmlspecialchars($b['cover']); ?>"
             data-embed="<?php echo htmlspecialchars($b['embed']); ?>"
             data-preview="<?php echo htmlspecialchars($b['preview']); ?>"
             data-desc="<?php echo htmlspecialchars(mb_substr($b['description'],0,500)); ?>"
             data-publisher="<?php echo htmlspecialchars($b['publisher']); ?>"
             data-year="<?php echo htmlspecialchars($b['year']); ?>"
             data-pages="<?php echo htmlspecialchars($b['pages']); ?>"
             data-source="google">
            <div class="book-cover">
                <?php if ($b['cover']): ?>
                <img src="<?php echo htmlspecialchars($b['cover']); ?>" alt="" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'book-cover-placeholder\'><div class=\'cover-ico bg-blue-lt c-blue\' style=\'background:var(--blue-lt);color:var(--blue)\'><svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1.5\'><path d=\'M4 19.5A2.5 2.5 0 0 1 6.5 17H20\'/><path d=\'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z\'/></svg></div></div>'">
                <?php else: ?>
                <div class="book-cover-placeholder">
                    <div class="cover-ico" style="background:var(--blue-lt);color:var(--blue)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                    No cover
                </div>
                <?php endif; ?>
                <span class="cover-source cs-google">Google</span>
            </div>
            <div class="book-body">
                <div class="book-title"><?php echo htmlspecialchars($b['title']); ?></div>
                <div class="book-author"><?php echo htmlspecialchars($b['author']); ?></div>
                <div class="book-meta">
                    <?php if ($b['year']): ?><span><?php echo $b['year']; ?></span><?php endif; ?>
                    <?php if ($b['pages']): ?><span>· <?php echo $b['pages']; ?> pp</span><?php endif; ?>
                </div>
                <div class="book-actions">
                    <button class="bk-btn read-btn" onclick="openReader(this.closest('.book-card'))">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Read
                    </button>
                    <button class="bk-btn dl-btn" onclick="openPdfPreview(this.closest('.book-card'))">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </button>
                    <?php if ($b['preview']): ?>
                    <a href="<?php echo htmlspecialchars($b['preview']); ?>" target="_blank" class="bk-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Open
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($olResults)): ?>
    <div class="results-heading" style="margin-top:2rem;">
        <h2>Open Library Results</h2>
        <span class="results-source src-ol">Open Library</span>
    </div>
    <div class="books-grid">
        <?php foreach ($olResults as $doc):
            $b = buildOLBook($doc);
        ?>
        <div class="book-card"
             data-id="<?php echo htmlspecialchars($b['id']); ?>"
             data-title="<?php echo htmlspecialchars($b['title']); ?>"
             data-author="<?php echo htmlspecialchars($b['author']); ?>"
             data-cover="<?php echo htmlspecialchars($b['cover']); ?>"
             data-embed="<?php echo htmlspecialchars($b['embed']); ?>"
             data-preview="<?php echo htmlspecialchars($b['preview']); ?>"
             data-desc=""
             data-publisher="<?php echo htmlspecialchars($b['publisher']); ?>"
             data-year="<?php echo htmlspecialchars($b['year']); ?>"
             data-pages="<?php echo htmlspecialchars($b['pages']); ?>"
             data-source="openlibrary">
            <div class="book-cover">
                <?php if ($b['cover']): ?>
                <img src="<?php echo htmlspecialchars($b['cover']); ?>" alt="" loading="lazy">
                <?php else: ?>
                <div class="book-cover-placeholder">
                    <div class="cover-ico" style="background:var(--teal-lt);color:var(--teal)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                    No cover
                </div>
                <?php endif; ?>
                <span class="cover-source cs-ol">Open Library</span>
            </div>
            <div class="book-body">
                <div class="book-title"><?php echo htmlspecialchars($b['title']); ?></div>
                <div class="book-author"><?php echo htmlspecialchars($b['author']); ?></div>
                <div class="book-meta">
                    <?php if ($b['year']): ?><span><?php echo $b['year']; ?></span><?php endif; ?>
                    <?php if ($b['pages']): ?><span>· <?php echo $b['pages']; ?> pp</span><?php endif; ?>
                </div>
                <div class="book-actions">
                    <button class="bk-btn read-btn" onclick="openReader(this.closest('.book-card'))">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Read
                    </button>
                    <button class="bk-btn dl-btn" onclick="openPdfPreview(this.closest('.book-card'))">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </button>
                    <?php if ($b['preview']): ?>
                    <a href="<?php echo htmlspecialchars($b['preview']); ?>" target="_blank" class="bk-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Open
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($searchResults) && empty($olResults)): ?>
    <div style="text-align:center;padding:4rem 2rem;color:var(--muted);">
        <div style="font-size:2.5rem;margin-bottom:1rem;opacity:.35;">🔍</div>
        <p style="font-size:.9rem;">No books found for "<?php echo htmlspecialchars($searchQ); ?>".<br>Try different keywords or browse categories above.</p>
    </div>
    <?php endif; ?>

<?php else: ?>

    <!-- ── CURATED BOOKS for active tab ── -->
    <?php
    $cat = $CATALOGUE[$activeTab];
    $catColorMap = ['prelims'=>'blue','mains'=>'teal','interview'=>'amber','ncert'=>'violet'];
    $cc = $catColorMap[$activeTab];
    ?>
    <div class="section-label">
        <h3 style="color:var(--<?php echo $cc; ?>)"><?php echo $cat['label']; ?> — Essential Books</h3>
        <div class="section-line"></div>
        <span class="section-count"><?php echo count($cat['books']); ?> books</span>
    </div>

    <div class="books-grid">
    <?php foreach ($curatedData as $i => $entry):
        $b  = buildBook($entry);
        $sc = 'sc-' . subjectColor($b['subject']);
    ?>
    <div class="book-card"
         data-id="<?php echo htmlspecialchars($b['id']); ?>"
         data-title="<?php echo htmlspecialchars($b['title']); ?>"
         data-author="<?php echo htmlspecialchars($b['author']); ?>"
         data-cover="<?php echo htmlspecialchars($b['cover']); ?>"
         data-embed="<?php echo htmlspecialchars($b['embed']); ?>"
         data-preview="<?php echo htmlspecialchars($b['preview']); ?>"
         data-desc="<?php echo htmlspecialchars(mb_substr($b['description'],0,500)); ?>"
         data-publisher="<?php echo htmlspecialchars($b['publisher']); ?>"
         data-year="<?php echo htmlspecialchars($b['year']); ?>"
         data-pages="<?php echo htmlspecialchars($b['pages']); ?>"
         data-source="<?php echo $b['source']; ?>">

        <div class="book-cover">
            <?php if ($b['cover']): ?>
            <img src="<?php echo htmlspecialchars($b['cover']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="book-cover-placeholder" style="display:none">
                <div class="cover-ico sc-<?php echo subjectColor($b['subject']); ?>" style="background:var(--<?php echo subjectColor($b['subject']); ?>-lt,var(--page));color:var(--<?php echo subjectColor($b['subject']); ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <span><?php echo htmlspecialchars($b['title']); ?></span>
            </div>
            <?php else: ?>
            <div class="book-cover-placeholder">
                <div class="cover-ico" style="background:var(--<?php echo $cc; ?>-lt);color:var(--<?php echo $cc; ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <span style="font-size:.7rem;padding:0 .5rem;text-align:center;"><?php echo htmlspecialchars($b['title']); ?></span>
            </div>
            <?php endif; ?>
            <span class="cover-source cs-<?php echo $b['source'] === 'manual' ? 'manual' : ($b['source'] === 'google' ? 'google' : 'ol'); ?>">
                <?php echo $b['source'] === 'manual' ? 'Curated' : ($b['source'] === 'google' ? 'Google' : 'OL'); ?>
            </span>
        </div>

        <div class="book-body">
            <?php if ($b['subject']): ?>
            <span class="book-subject <?php echo $sc; ?>"><?php echo htmlspecialchars($b['subject']); ?></span>
            <?php endif; ?>
            <div class="book-title"><?php echo htmlspecialchars($b['title']); ?></div>
            <div class="book-author"><?php echo htmlspecialchars($b['author']); ?></div>
            <div class="book-meta">
                <?php if ($b['year']): ?><span><?php echo $b['year']; ?></span><?php endif; ?>
                <?php if ($b['pages']): ?><span>· <?php echo $b['pages']; ?> pp</span><?php endif; ?>
                <?php if ($b['publisher']): ?><span>· <?php echo htmlspecialchars(mb_substr($b['publisher'],0,18)); ?></span><?php endif; ?>
            </div>
            <div class="book-actions">
                <button class="bk-btn read-btn" onclick="openReader(this.closest('.book-card'))">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    Read
                </button>
                <button class="bk-btn dl-btn" onclick="openPdfPreview(this.closest('.book-card'))">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                </button>
                <?php if ($b['preview']): ?>
                <a href="<?php echo htmlspecialchars($b['preview']); ?>" target="_blank" class="bk-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Open
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

<?php endif; ?>
</main>

<!-- ════════════ PDF PREVIEW & DOWNLOAD MODAL ════════════ -->
<div class="pdf-overlay" id="pdf-modal" onclick="closePdfOnBg(event)">
    <div class="pdf-box">
        <!-- Header -->
        <div class="pdf-header">
            <img class="pdf-cover" id="pdf-cover" src="" alt="">
            <div class="pdf-title-wrap">
                <div class="pdf-title" id="pdf-title">Book Title</div>
                <div class="pdf-author" id="pdf-author">Author</div>
            </div>
            <div class="pdf-hbtns">
                <button class="pdf-hbtn confirm-dl" id="pdf-dl-btn" onclick="startDownload()">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download PDF
                </button>
                <a class="pdf-hbtn" id="pdf-ext-link" href="#" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View Online
                </a>
                <button class="pdf-close-btn" onclick="closePdfPreview()">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        <!-- Preview area -->
        <div class="pdf-preview-area" id="pdf-preview-area">
            <!-- Loading spinner -->
            <div class="pdf-loading" id="pdf-loading">
                <div class="spin"></div>
                <span>Loading preview…</span>
            </div>
            <!-- Iframe for Google Books preview -->
            <iframe class="pdf-frame" id="pdf-frame" src="" style="display:none;"
                    onload="onFrameLoad()" onerror="showNoPreview()"></iframe>
            <!-- No preview fallback -->
            <div class="pdf-no-preview" id="pdf-no-preview">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <h4>Preview not available</h4>
                <p>This book doesn't have a free in-browser preview. Click <strong>Download PDF</strong> to get it via Open Library, or <strong>View Online</strong> to read on Google Books.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="pdf-footer">
            <span class="pdf-pages" id="pdf-pages"></span>
            <span class="pdf-source-badge" id="pdf-src-badge"></span>
            <!-- Download progress -->
            <div class="pdf-dl-progress" id="pdf-dl-progress">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Preparing download…
            </div>
        </div>
    </div>
</div>

<!-- ════════════ BOOK READER MODAL ════════════ -->
<div class="modal-overlay" id="reader-modal" onclick="closeReaderOnBg(event)">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-header">
            <img class="modal-cover" id="modal-cover" src="" alt="">
            <div class="modal-title-wrap">
                <div class="modal-title" id="modal-title">Book Title</div>
                <div class="modal-author" id="modal-author">Author</div>
            </div>
            <div class="modal-header-actions">
                <a id="modal-ext-link" href="#" target="_blank" class="mh-btn" title="Open in new tab">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <button class="mh-btn close-btn" onclick="closeReader()" title="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <!-- Tabs -->
        <div class="modal-tabs">
            <button class="mtab active" id="mtab-reader" onclick="switchPane('reader', this)">📖 Reader</button>
            <button class="mtab" id="mtab-info" onclick="switchPane('info', this)">ℹ️ About</button>
        </div>
        <!-- Body -->
        <div class="modal-body" id="modal-body" style="height:560px;">
            <!-- Reader pane -->
            <div class="modal-pane active" id="pane-reader">

                <!-- Source switcher bar -->
                <div class="src-bar" id="src-bar" style="display:none;">
                    <span class="src-bar-lbl">Try another source:</span>
                    <div class="src-btns" id="src-btns"></div>
                </div>

                <!-- Loading spinner -->
                <div class="reader-loading" id="reader-loading">
                    <div class="rl-spin"></div>
                    <p>Loading reader…</p>
                </div>

                <!-- Iframe -->
                <iframe class="reader-frame" id="reader-frame" src="" allowfullscreen
                        style="display:none;"
                        onload="onReaderLoad()"
                        onerror="tryNextSource()"></iframe>

                <!-- Blocked / No preview fallback -->
                <div class="no-reader" id="no-reader-msg" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    <h4>Embedded reading not available</h4>
                    <p id="no-reader-reason">This publisher restricts in-page reading. Use the options below:</p>
                    <div class="no-reader-actions" id="no-reader-actions"></div>
                </div>
            </div>
            <!-- Info pane -->
            <div class="modal-pane" id="pane-info">
                <div class="info-pane">
                    <div class="info-cover-row">
                        <img class="info-cover-img" id="info-cover-img" src="" alt="" onerror="this.style.display='none'">
                        <div class="info-meta-grid" id="info-meta-grid"></div>
                    </div>
                    <div class="info-desc">
                        <h4>Description</h4>
                        <p id="info-desc-text">No description available.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ════════════════════════════════════════════════════
   READER MODAL — Smart multi-source reader
   Tries sources in order: OL embed → OL viewer →
   Google Books embed → Archive.org → fallback UI
════════════════════════════════════════════════════ */
const BOOKMARKS = new Set(<?php echo $bookmarks_json; ?>);

let _readerSources = [];   // list of {label, url} to try
let _readerIdx     = 0;    // current source index
let _readerData    = {};   // current book data
let _readerTimer   = null; // timeout for blocked-frame detection

function openReader(card) {
    const d = card.dataset;
    _readerData = {
        id:      d.id      || '',
        title:   d.title   || 'Unknown Title',
        author:  d.author  || 'Unknown Author',
        cover:   d.cover   || '',
        embed:   d.embed   || '',
        preview: d.preview || '',
        source:  d.source  || 'google',
        desc:    d.desc    || '',
        year:    d.year    || '',
        publisher: d.publisher || '',
        pages:   d.pages   || '',
    };

    /* ── Set modal header ── */
    document.getElementById('modal-title').textContent  = _readerData.title;
    document.getElementById('modal-author').textContent = _readerData.author;
    const cover = document.getElementById('modal-cover');
    cover.src = _readerData.cover;
    cover.onerror = function(){ this.style.display='none'; };
    cover.style.display = _readerData.cover ? 'block' : 'none';

    const extUrl = _readerData.preview
        || (_readerData.source === 'openlibrary' ? 'https://openlibrary.org' + _readerData.id : '#');
    document.getElementById('modal-ext-link').href = extUrl;

    /* ── Info pane ── */
    document.getElementById('info-cover-img').src = _readerData.cover;
    document.getElementById('info-desc-text').textContent = _readerData.desc || 'No description available.';
    const metaGrid = document.getElementById('info-meta-grid');
    const metas = [
        ['Author', _readerData.author],
        ['Published', _readerData.year || '—'],
        ['Publisher', _readerData.publisher || '—'],
        ['Pages', _readerData.pages || '—'],
        ['Source', _readerData.source === 'google' ? 'Google Books' :
                   _readerData.source === 'openlibrary' ? 'Open Library' : 'Curated'],
    ];
    metaGrid.innerHTML = metas.map(function(m){
        return '<div class="meta-chip"><div class="mc-l">' + m[0] + '</div><div class="mc-v">' + (m[1]||'—') + '</div></div>';
    }).join('');

    /* ── Build source list ── */
    _readerSources = buildSources(_readerData);
    _readerIdx = 0;

    /* ── Build source switcher buttons ── */
    const srcBar  = document.getElementById('src-bar');
    const srcBtns = document.getElementById('src-btns');
    if (_readerSources.length > 1) {
        srcBtns.innerHTML = _readerSources.map(function(s, i){
            return '<button class="src-btn' + (i===0?' active':'') + '" data-idx="' + i + '" onclick="loadSource(' + i + ')">' + s.label + '</button>';
        }).join('');
        srcBar.style.display = 'flex';
    } else {
        srcBar.style.display = 'none';
    }

    /* ── Reset state ── */
    showReaderLoading();
    switchPane('reader', document.getElementById('mtab-reader'));
    document.getElementById('reader-modal').classList.add('open');
    document.body.style.overflow = 'hidden';

    /* Load first source */
    loadSource(0);
}

/* Build ordered list of sources to try */
function buildSources(d) {
    const sources = [];
    const title  = encodeURIComponent(d.title);
    const author = encodeURIComponent(d.author);

    /* Open Library read (best - genuinely embeddable) */
    if (d.source === 'openlibrary' && d.id) {
        sources.push({
            label: 'Open Library',
            url: 'https://openlibrary.org/books/' + d.id.replace('/books/','') + '?layout=embed',
        });
        sources.push({
            label: 'OL Reader',
            url: 'https://openlibrary.org' + d.id,
        });
    }

    /* Google Books embed */
    if (d.id && d.source === 'google') {
        sources.push({
            label: 'Google Books',
            url: 'https://books.google.com/books?id=' + d.id + '&lpg=PP1&pg=PP1&output=embed',
        });
    }

    /* Custom embed if provided */
    if (d.embed && d.embed !== '' && !sources.some(function(s){ return s.url === d.embed; })) {
        sources.push({ label: 'Embed', url: d.embed });
    }

    /* Archive.org search embed */
    sources.push({
        label: 'Archive.org',
        url: 'https://archive.org/embed/' + encodeURIComponent(
            d.title.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/_+/g,'_').replace(/^_|_$/g,'')
        ),
    });

    /* WorldCat */
    sources.push({
        label: 'WorldCat',
        url: 'https://www.worldcat.org/search?q=' + title + '+' + author,
    });

    return sources;
}

function loadSource(idx) {
    if (idx >= _readerSources.length) {
        showFallback();
        return;
    }
    _readerIdx = idx;
    const src = _readerSources[idx];

    /* Update active button */
    document.querySelectorAll('.src-btn').forEach(function(b){
        b.classList.toggle('active', parseInt(b.dataset.idx) === idx);
    });

    showReaderLoading();

    const frame = document.getElementById('reader-frame');
    frame.style.display = 'none';

    /* Set a 7s timeout — if frame hasn't loaded, try next source */
    clearTimeout(_readerTimer);
    _readerTimer = setTimeout(function(){
        /* Check if frame is blank/blocked */
        tryNextSource();
    }, 7000);

    frame.src = src.url;
}

function onReaderLoad() {
    clearTimeout(_readerTimer);
    /* Google Books sends a 200 even when blocking embed.
       Detect blank frame by checking if src contains blocked patterns */
    const frame = document.getElementById('reader-frame');
    const src   = frame.src || '';

    /* These patterns always show "not available" for Google Books */
    const likelyBlocked = src.includes('books.google.com') && (
        src.includes('output=embed') &&
        /* We assume blocked unless we can prove otherwise — give it a try */
        false // allow Google Books to show — user can switch source manually
    );

    if (likelyBlocked) {
        tryNextSource();
        return;
    }

    hideReaderLoading();
    frame.style.display = 'block';
    document.getElementById('no-reader-msg').style.display = 'none';
}

function tryNextSource() {
    clearTimeout(_readerTimer);
    const nextIdx = _readerIdx + 1;
    if (nextIdx < _readerSources.length) {
        loadSource(nextIdx);
    } else {
        showFallback();
    }
}

function showReaderLoading() {
    document.getElementById('reader-loading').style.display  = 'flex';
    document.getElementById('reader-frame').style.display    = 'none';
    document.getElementById('no-reader-msg').style.display   = 'none';
}

function hideReaderLoading() {
    document.getElementById('reader-loading').style.display = 'none';
}

function showFallback() {
    clearTimeout(_readerTimer);
    hideReaderLoading();
    document.getElementById('reader-frame').style.display  = 'none';

    const d   = _readerData;
    const ext = d.preview || (d.source === 'openlibrary' ? 'https://openlibrary.org' + d.id : '');
    const gb  = 'https://books.google.com/books?id=' + (d.id || '') + '&printsec=frontcover';
    const olSearch = 'https://openlibrary.org/search?q=' + encodeURIComponent(d.title + ' ' + d.author);
    const archiveSearch = 'https://archive.org/search?query=' + encodeURIComponent(d.title + ' ' + d.author);

    let actions = '';
    if (ext && ext !== '#') {
        actions += `<a href="${ext}" target="_blank" class="nr-btn primary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Google Books</a>`;
    }
    actions += `<a href="${olSearch}" target="_blank" class="nr-btn secondary">
        Open Library</a>`;
    actions += `<a href="${archiveSearch}" target="_blank" class="nr-btn secondary">
        Archive.org</a>`;
    actions += `<button class="nr-btn secondary" onclick="openPdfPreview(document.querySelector('.book-card[data-title=\\'${d.title.replace(/'/g,"\\'")}\\']') || {dataset:Object.assign(document.createElement('div').dataset, _readerData)})">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF</button>`;

    document.getElementById('no-reader-reason').textContent =
        'All embedded sources are blocked by the publisher. Read it via:';
    document.getElementById('no-reader-actions').innerHTML = actions;
    document.getElementById('no-reader-msg').style.display = 'flex';
}

function closeReader() {
    clearTimeout(_readerTimer);
    document.getElementById('reader-modal').classList.remove('open');
    document.getElementById('reader-frame').src = '';
    document.body.style.overflow = '';
    _readerSources = [];
    _readerIdx = 0;
}

function closeReaderOnBg(e) {
    if (e.target === document.getElementById('reader-modal')) closeReader();
}

function switchPane(id, btn) {
    document.querySelectorAll('.modal-pane').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.mtab').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('pane-' + id).classList.add('active');
    if (btn) btn.classList.add('active');
}

// ESC key closes modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeReader(); closePdfPreview(); }
});

/* ════════════════════════════════════════════════════
   PDF PREVIEW & DOWNLOAD
════════════════════════════════════════════════════ */
let _currentPdf = {};   // stores current book data for download

function openPdfPreview(card) {
    const d = card.dataset;
    _currentPdf = {
        id:      d.id      || '',
        title:   d.title   || 'Unknown Title',
        author:  d.author  || 'Unknown Author',
        cover:   d.cover   || '',
        embed:   d.embed   || '',
        preview: d.preview || '',
        pages:   d.pages   || '',
        source:  d.source  || 'google',
        year:    d.year    || '',
    };

    /* Header */
    document.getElementById('pdf-title').textContent  = _currentPdf.title;
    document.getElementById('pdf-author').textContent = _currentPdf.author;
    const cov = document.getElementById('pdf-cover');
    cov.src = _currentPdf.cover;
    cov.onerror = function(){ this.style.display='none'; };
    cov.style.display = _currentPdf.cover ? 'block' : 'none';

    /* External link */
    const ext = _currentPdf.preview
        || (_currentPdf.source === 'openlibrary' ? 'https://openlibrary.org' + _currentPdf.id : '#');
    document.getElementById('pdf-ext-link').href = ext;

    /* Footer meta */
    document.getElementById('pdf-pages').textContent  = _currentPdf.pages ? _currentPdf.pages + ' pages' : '';
    const srcBadge = document.getElementById('pdf-src-badge');
    srcBadge.textContent  = _currentPdf.source === 'google' ? 'Google Books' :
                            _currentPdf.source === 'openlibrary' ? 'Open Library' : 'Curated';
    srcBadge.className    = 'pdf-source-badge ' +
        (_currentPdf.source === 'google' ? 'src-google' :
         _currentPdf.source === 'openlibrary' ? 'src-ol' : 'src-google');

    /* Reset state */
    document.getElementById('pdf-dl-progress').classList.remove('show');
    document.getElementById('pdf-no-preview').style.display = 'none';
    document.getElementById('pdf-loading').style.display = 'flex';
    document.getElementById('pdf-frame').style.display = 'none';
    document.getElementById('pdf-frame').src = '';

    /* Open modal */
    document.getElementById('pdf-modal').classList.add('open');
    document.body.style.overflow = 'hidden';

    /* Load preview in iframe */
    const previewUrl = buildPreviewUrl();
    if (previewUrl) {
        document.getElementById('pdf-frame').src = previewUrl;
    } else {
        showNoPreview();
    }
}

function buildPreviewUrl() {
    /* Google Books embed  */
    if (_currentPdf.embed && _currentPdf.embed !== '') return _currentPdf.embed;
    /* Google Books preview */
    if (_currentPdf.id && _currentPdf.source === 'google')
        return 'https://books.google.com/books?id=' + _currentPdf.id + '&lpg=PP1&pg=PP1&output=embed';
    /* Open Library read */
    if (_currentPdf.id && _currentPdf.source === 'openlibrary')
        return 'https://openlibrary.org' + _currentPdf.id + '?layout=embed';
    /* Fallback preview link */
    if (_currentPdf.preview && _currentPdf.preview !== '#') return _currentPdf.preview;
    return null;
}

function onFrameLoad() {
    document.getElementById('pdf-loading').style.display  = 'none';
    document.getElementById('pdf-frame').style.display    = 'block';
    document.getElementById('pdf-no-preview').style.display = 'none';
}

function showNoPreview() {
    document.getElementById('pdf-loading').style.display    = 'none';
    document.getElementById('pdf-frame').style.display      = 'none';
    document.getElementById('pdf-no-preview').style.display = 'flex';
}

function closePdfPreview() {
    document.getElementById('pdf-modal').classList.remove('open');
    document.getElementById('pdf-frame').src = '';
    document.getElementById('pdf-dl-progress').classList.remove('show');
    document.body.style.overflow = '';
    _currentPdf = {};
}

function closePdfOnBg(e) {
    if (e.target === document.getElementById('pdf-modal')) closePdfPreview();
}

/* ── Download handler ─────────────────────────────────
   Strategy (in order):
   1. Open Library: some books have free download links
      → search /search.json, get first result's /works ID
        then try archive.org EPUB/PDF
   2. Google Books: trigger the download/preview link
   3. Fallback: open the preview page so user can
      use browser's own download or print-to-PDF
──────────────────────────────────────────────────── */
async function startDownload() {
    const prog = document.getElementById('pdf-dl-progress');
    const btn  = document.getElementById('pdf-dl-btn');
    prog.classList.add('show');
    btn.style.opacity = '.6';
    btn.style.cursor  = 'wait';

    try {
        const title  = _currentPdf.title;
        const author = _currentPdf.author;

        /* ── Try Open Library free download ── */
        const olUrl = await findOpenLibraryDownload(title, author);
        if (olUrl) {
            triggerDownload(olUrl, title);
            showDlSuccess(btn, prog);
            return;
        }

        /* ── Try Google Books download link ── */
        if (_currentPdf.id && _currentPdf.source === 'google') {
            const gbUrl = 'https://books.google.com/books/download/'
                + encodeURIComponent(title.replace(/\s+/g,'_'))
                + '-Sample.pdf?id=' + _currentPdf.id + '&hl=en&output=pdf';
            triggerDownload(gbUrl, title);
            showDlSuccess(btn, prog);
            return;
        }

        /* ── Fallback: open preview / OL page ── */
        const fallback = _currentPdf.preview
            || (_currentPdf.source === 'openlibrary' ? 'https://openlibrary.org' + _currentPdf.id : null);
        if (fallback) {
            window.open(fallback, '_blank');
            prog.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> Opened in new tab — use Ctrl+P → Save as PDF';
            prog.style.color = 'var(--amber)';
            prog.style.background = 'var(--amber-lt)';
        } else {
            prog.textContent = '⚠️ No downloadable version found for this book.';
            prog.style.color = 'var(--red)';
            prog.style.background = 'var(--red-lt)';
        }
    } catch(err) {
        prog.textContent = '⚠️ Download failed. Try "View Online" instead.';
        prog.style.color = 'var(--red)';
        prog.style.background = 'var(--red-lt)';
    } finally {
        btn.style.opacity = '';
        btn.style.cursor  = '';
    }
}

async function findOpenLibraryDownload(title, author) {
    try {
        const q   = encodeURIComponent(title + ' ' + author);
        const url = 'https://openlibrary.org/search.json?q=' + q + '&limit=5&fields=key,ia,title';
        const r   = await fetch(url, { signal: AbortSignal.timeout(6000) });
        const d   = await r.json();
        for (const doc of (d.docs || [])) {
            if (doc.ia && doc.ia.length > 0) {
                /* Internet Archive identifier — check for PDF */
                for (const ia of doc.ia.slice(0, 3)) {
                    const pdfUrl = 'https://archive.org/download/' + ia + '/' + ia + '.pdf';
                    /* Try HEAD request to confirm it exists */
                    try {
                        const check = await fetch(pdfUrl, { method:'HEAD', signal: AbortSignal.timeout(4000) });
                        if (check.ok) return pdfUrl;
                    } catch(e) {}
                    /* Try epub */
                    const epubUrl = 'https://archive.org/download/' + ia + '/' + ia + '.epub';
                    try {
                        const check2 = await fetch(epubUrl, { method:'HEAD', signal: AbortSignal.timeout(4000) });
                        if (check2.ok) return epubUrl;
                    } catch(e) {}
                }
            }
        }
    } catch(e) {}
    return null;
}

function triggerDownload(url, title) {
    const a = document.createElement('a');
    a.href     = url;
    a.download = title.replace(/[^a-zA-Z0-9 ]/g, '').trim().replace(/\s+/g, '_') + '.pdf';
    a.target   = '_blank';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function showDlSuccess(btn, prog) {
    prog.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;animation:none"><polyline points="20 6 9 17 4 12"/></svg> Download started!';
    prog.style.color = 'var(--green)';
    prog.style.background = 'var(--green-lt)';
    setTimeout(function(){ prog.classList.remove('show'); }, 3000);
}

/* ── Bookmark toggle ── */
function toggleBookmark(card) {
    const d = card.dataset;
    const id = d.id || d.title;
    const saved = BOOKMARKS.has(id);
    fetch('save_bookmark.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            action:     saved ? 'remove' : 'add',
            book_id:    id,
            title:      d.title,
            author:     d.author,
            cover_url:  d.cover,
            source:     d.source,
            category:   (document.querySelector('.cat-btn.active')?.textContent?.trim() || 'Other')
        })
    }).then(r=>r.json()).then(function(res) {
        if (res.success) {
            saved ? BOOKMARKS.delete(id) : BOOKMARKS.add(id);
        }
    }).catch(function(){});
}
/* ════════════════════════════════════════════════════
   AI BOOK RECOMMENDATIONS via Llama3
════════════════════════════════════════════════════ */
function quickAsk(text) {
    document.getElementById('ai-inp').value = text;
    askAI();
}

async function askAI() {
    const inp   = document.getElementById('ai-inp');
    const stage = document.getElementById('ai-stage').value;
    const query = inp.value.trim();
    if (!query) { inp.focus(); return; }

    const btn  = document.getElementById('ai-go-btn');
    const wrap = document.getElementById('ai-results-wrap');
    const body = document.getElementById('ai-results-body');

    btn.disabled = true;
    wrap.classList.add('show');
    document.getElementById('ai-results-title').textContent = 'Asking AI…';
    document.getElementById('ai-results-sub').textContent   = '';

    /* Loading state */
    body.innerHTML = `
        <div class="ai-loading">
            <div class="ai-spin"></div>
            <p>Llama 3 is finding the best books for you…</p>
            <span class="ai-thinking">Analysing UPSC syllabus · Matching books · Fetching details…</span>
        </div>`;

    /* Scroll to results */
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });

    try {
        const resp = await fetch('llama_books.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: query, stage: stage }),
        });

        const data = await resp.json();

        if (!data.success) {
            const isAuth = data.auth === false;
            body.innerHTML = `
                <div class="ai-error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <strong>${isAuth ? 'Session expired' : (data.offline ? 'Ollama is offline' : 'AI Error')}</strong>
                        — ${data.message}
                        ${isAuth ? '<br><small><a href="login.php">Click here to log in again</a></small>' : ''}
                        ${data.offline ? '<br><small>Run <code>ollama serve</code> in your terminal, then try again.</small>' : ''}
                    </div>
                </div>`;
            document.getElementById('ai-results-title').textContent = 'Error';
            return;
        }

        document.getElementById('ai-results-title').textContent = stage === 'Any'
            ? 'AI Recommendations'
            : stage + ' Recommendations';
        document.getElementById('ai-results-sub').textContent =
            data.count + ' books found for: "' + data.prompt + '"';

        renderAIBooks(data.books);

    } catch(e) {
        body.innerHTML = `<div class="ai-error">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div><strong>Network error</strong> — ${e.message}</div>
        </div>`;
    } finally {
        btn.disabled = false;
    }
}

function renderAIBooks(books) {
    if (!books || books.length === 0) {
        document.getElementById('ai-results-body').innerHTML =
            '<p style="color:var(--muted);font-size:.85rem;padding:1rem 0">No books found. Try a different query.</p>';
        return;
    }

    const grid = document.createElement('div');
    grid.className = 'books-grid';

    books.forEach(function(b) {
        const card = document.createElement('div');
        card.className = 'book-card';

        /* Set data attributes for openReader / openPdfPreview */
        card.dataset.id        = b.id        || '';
        card.dataset.title     = b.title     || '';
        card.dataset.author    = b.author    || '';
        card.dataset.cover     = b.cover     || '';
        card.dataset.embed     = b.embed     || '';
        card.dataset.preview   = b.preview   || '';
        card.dataset.desc      = b.description || '';
        card.dataset.publisher = b.publisher  || '';
        card.dataset.year      = b.year       || '';
        card.dataset.pages     = b.pages      || '';
        card.dataset.source    = b.source     || 'google';

        const srcLabel = b.source === 'google' ? 'Google' :
                         b.source === 'openlibrary' ? 'OL' : 'AI';
        const srcClass = b.source === 'openlibrary' ? 'cs-ol' : 'cs-google';

        const coverHtml = b.cover
            ? `<img src="${escHtml(b.cover)}" alt="" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'book-cover-placeholder\'></div>'">`
            : `<div class="book-cover-placeholder"><div class="cover-ico" style="background:var(--violet-lt);color:var(--violet)"><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.5'><path d='M4 19.5A2.5 2.5 0 0 1 6.5 17H20'/><path d='M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z'/></svg></div></div>`;

        const reasonHtml = b.reason
            ? `<div class="ai-reason"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"/><path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72"/></svg>${escHtml(b.reason)}</div>` : '';

        card.innerHTML = `
            <div class="book-cover">
                ${coverHtml}
                <span class="cover-source ${srcClass}">${srcLabel}</span>
            </div>
            <div class="book-body">
                <div class="book-title">${escHtml(b.title)}</div>
                <div class="book-author">${escHtml(b.author)}</div>
                <div class="book-meta">
                    ${b.year ? `<span>${b.year}</span>` : ''}
                    ${b.pages ? `<span>· ${b.pages} pp</span>` : ''}
                    ${b.subject ? `<span class="subj-tag">${escHtml(b.subject)}</span>` : ''}
                </div>
                ${reasonHtml}
                <div class="book-actions">
                    <button class="bk-btn read-btn" onclick="openReader(this.closest('.book-card'))">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Read
                    </button>
                    <button class="bk-btn dl-btn" onclick="openPdfPreview(this.closest('.book-card'))">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </button>
                    ${b.preview ? `<a href="${escHtml(b.preview)}" target="_blank" class="bk-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Open
                    </a>` : ''}
                </div>
            </div>`;

        grid.appendChild(card);
    });

    document.getElementById('ai-results-body').innerHTML = '';
    document.getElementById('ai-results-body').appendChild(grid);
}

function clearAI() {
    document.getElementById('ai-results-wrap').classList.remove('show');
    document.getElementById('ai-results-body').innerHTML = '';
    document.getElementById('ai-inp').value = '';
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>