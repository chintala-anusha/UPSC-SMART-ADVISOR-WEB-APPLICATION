<?php
/* ─────────────────────────────────────────────
   save_chat_history.php
   Called by ai_chat.php after every AI response.
   Saves user + assistant turns to chat_history.
───────────────────────────────────────────── */
ob_start();
require_once 'includes/ajax_auth.php';
require_once 'includes/db.php';

$raw  = file_get_contents('php://input');
$turns = json_decode($raw, true);

if (empty($turns) || !is_array($turns)) {
    echo json_encode(['success'=>false,'message'=>'No data']);
    exit();
}

$stmt = $conn->prepare(
    'INSERT INTO chat_history (user_id, role, message, session_ref)
     VALUES (?, ?, ?, ?)'
);

$saved = 0;
foreach ($turns as $t) {
    $role    = in_array($t['role'] ?? '', ['user','assistant']) ? $t['role'] : 'user';
    $message = trim($t['message'] ?? '');
    $ref     = substr(preg_replace('/[^a-zA-Z0-9\-]/', '', $t['session_ref'] ?? ''), 0, 36);
    if (empty($message)) continue;

    $stmt->bind_param('isss', $uid, $role, $message, $ref);
    if ($stmt->execute()) $saved++;
}
$stmt->close();

echo json_encode(['success'=>true,'saved'=>$saved]);