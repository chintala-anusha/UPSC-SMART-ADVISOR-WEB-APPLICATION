<?php
/* ═══════════════════════════════════════════════════════════
   save_coach_session.php
   Saves each AI Coach interaction to the coach_sessions table.

   ── Run this SQL once ──────────────────────────────────────
   CREATE TABLE IF NOT EXISTS coach_sessions (
     id         INT AUTO_INCREMENT PRIMARY KEY,
     user_id    INT NOT NULL,
     stage      ENUM('Prelims','Mains','Interview') DEFAULT 'Prelims',
     topic      VARCHAR(255),
     query      TEXT,
     response   TEXT,
     tokens     INT DEFAULT 0,
     duration   INT DEFAULT 0,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX idx_user  (user_id),
     INDEX idx_stage (stage),
     INDEX idx_date  (created_at)
   );
   ──────────────────────────────────────────────────────────
═══════════════════════════════════════════════════════════ */
ob_start();
require_once 'includes/ajax_auth.php';
require_once 'includes/db.php';
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$stage    = in_array($body['stage'] ?? '', ['Prelims','Mains','Interview']) ? $body['stage'] : 'Prelims';
$topic    = htmlspecialchars(trim($body['topic']    ?? ''), ENT_QUOTES, 'UTF-8');
$query    = htmlspecialchars(trim($body['query']    ?? ''), ENT_QUOTES, 'UTF-8');
$response = htmlspecialchars(trim($body['response'] ?? ''), ENT_QUOTES, 'UTF-8');
$duration = (int)($body['duration'] ?? 0);
$tokens   = (int)($body['tokens']   ?? 0);

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Query is empty']);
    exit();
}

$stmt = $conn->prepare(
    "INSERT INTO coach_sessions (user_id, stage, topic, query, response, duration, tokens)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param('issssii', $uid, $stage, $topic, $query, $response, $duration, $tokens);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
}

$stmt->close();