<?php
ob_start();
require_once 'includes/ajax_auth.php';
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']);
    exit();
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$score      = (float)($data['score']      ?? 0);
$total      = (int)  ($data['total']      ?? 0);
$attempted  = (int)  ($data['attempted']  ?? 0);
$correct    = (int)  ($data['correct']    ?? 0);
$wrong      = (int)  ($data['wrong']      ?? 0);
$skipped    = (int)  ($data['skipped']    ?? 0);
$time_taken = (int)  ($data['time_taken'] ?? 0);
$year_filter= htmlspecialchars($data['year'] ?? '');
$answers    = json_encode($data['answers'] ?? []);
$module     = 'MockTest';

$stmt = $conn->prepare(
    'INSERT INTO progress
     (user_id,module,score,total,attempted,correct,wrong,skipped,time_taken,year_filter,answers_json)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'DB prepare failed: '.$conn->error]);
    exit();
}

$stmt->bind_param('isdiiiiiiss',
    $uid,$module,$score,$total,$attempted,$correct,$wrong,$skipped,$time_taken,$year_filter,$answers
);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'insert_id'=>$stmt->insert_id,
        'score'=>$score,'correct'=>$correct,'wrong'=>$wrong,'skipped'=>$skipped]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();