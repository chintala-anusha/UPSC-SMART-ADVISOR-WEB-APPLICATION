<?php
/*
 * save_timetable.php
 * AJAX endpoint — saves timetable plan + exam alerts to DB
 *
 * ── SQL to run ONCE ──────────────────────────────────────────
 *
 * CREATE TABLE IF NOT EXISTS timetables (
 *   id           INT AUTO_INCREMENT PRIMARY KEY,
 *   user_id      INT NOT NULL,
 *   plan_name    VARCHAR(100) DEFAULT 'My Plan',
 *   start_date   DATE NOT NULL,
 *   prelims_date DATE,
 *   mains_date   DATE,
 *   interview_date DATE,
 *   daily_hours  TINYINT DEFAULT 8,
 *   subjects_json TEXT,
 *   schedule_json LONGTEXT,
 *   created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *   INDEX idx_user (user_id)
 * );
 *
 * CREATE TABLE IF NOT EXISTS timetable_alerts (
 *   id           INT AUTO_INCREMENT PRIMARY KEY,
 *   user_id      INT NOT NULL,
 *   timetable_id INT,
 *   alert_type   ENUM('prelims','mains','interview','custom') DEFAULT 'custom',
 *   alert_label  VARCHAR(100),
 *   alert_date   DATE NOT NULL,
 *   days_before  TINYINT DEFAULT 0,
 *   is_dismissed TINYINT DEFAULT 0,
 *   created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   INDEX idx_user_alert (user_id, alert_date)
 * );
 *
 * CREATE TABLE IF NOT EXISTS timetable_progress (
 *   id           INT AUTO_INCREMENT PRIMARY KEY,
 *   user_id      INT NOT NULL,
 *   timetable_id INT,
 *   study_date   DATE NOT NULL,
 *   subject      VARCHAR(100),
 *   completed    TINYINT DEFAULT 0,
 *   hours_done   FLOAT DEFAULT 0,
 *   notes        TEXT,
 *   created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   UNIQUE KEY uniq_user_date_subject (user_id, study_date, subject)
 * );
 * ─────────────────────────────────────────────────────────────
 */

ob_start();
require_once 'includes/ajax_auth.php';
require_once 'includes/db.php';

// $uid is set by ajax_auth.php
if (!$uid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true);
$action = $data['action'] ?? ($_GET['action'] ?? 'save_plan');

/* ═══════════════════════════════════════
   ACTION: save_plan
═══════════════════════════════════════ */
if ($action === 'save_plan') {
    $plan_name     = htmlspecialchars(trim($data['plan_name'] ?? 'My Plan'));
    $start_date    = $data['start_date']    ?? date('Y-m-d');
    $prelims_date  = $data['prelims_date']  ?? null;
    $mains_date    = $data['mains_date']    ?? null;
    $interview_date= $data['interview_date']?? null;
    $daily_hours   = (int)($data['daily_hours'] ?? 8);
    $subjects_json = json_encode($data['subjects'] ?? []);
    $schedule_json = json_encode($data['schedule'] ?? []);

    // Upsert — if user already has a plan with same name, update it
    $check = $conn->prepare("SELECT id FROM timetables WHERE user_id=? AND plan_name=? LIMIT 1");
    $check->bind_param('is', $uid, $plan_name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($existing_id);
        $check->fetch();
        $check->close();

        $stmt = $conn->prepare(
            "UPDATE timetables SET start_date=?,prelims_date=?,mains_date=?,interview_date=?,
             daily_hours=?,subjects_json=?,schedule_json=?,updated_at=NOW()
             WHERE id=? AND user_id=?"
        );
        $stmt->bind_param('ssssissii',
            $start_date,$prelims_date,$mains_date,$interview_date,
            $daily_hours,$subjects_json,$schedule_json,
            $existing_id,$uid
        );
        $stmt->execute();
        $plan_id = $existing_id;
    } else {
        $check->close();
        $stmt = $conn->prepare(
            "INSERT INTO timetables
             (user_id,plan_name,start_date,prelims_date,mains_date,interview_date,daily_hours,subjects_json,schedule_json)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('isssssis s',
            $uid,$plan_name,$start_date,$prelims_date,$mains_date,$interview_date,
            $daily_hours,$subjects_json,$schedule_json
        );
        $stmt->execute();
        $plan_id = $stmt->insert_id;
    }

    // Save alerts
    if (!empty($data['alerts'])) {
        // Clear old alerts for this plan
        $del = $conn->prepare("DELETE FROM timetable_alerts WHERE user_id=? AND timetable_id=?");
        $del->bind_param('ii', $uid, $plan_id);
        $del->execute();

        $astmt = $conn->prepare(
            "INSERT INTO timetable_alerts (user_id,timetable_id,alert_type,alert_label,alert_date,days_before)
             VALUES (?,?,?,?,?,?)"
        );
        foreach ($data['alerts'] as $alert) {
            $atype   = $alert['type']   ?? 'custom';
            $alabel  = htmlspecialchars($alert['label'] ?? '');
            $adate   = $alert['date']   ?? date('Y-m-d');
            $dbefore = (int)($alert['days_before'] ?? 0);
            $astmt->bind_param('iisssi', $uid, $plan_id, $atype, $alabel, $adate, $dbefore);
            $astmt->execute();
        }
    }

    echo json_encode(['success' => true, 'plan_id' => $plan_id, 'message' => 'Timetable saved!']);
    exit();
}

/* ═══════════════════════════════════════
   ACTION: load_plan
═══════════════════════════════════════ */
if ($action === 'load_plan') {
    $stmt = $conn->prepare(
        "SELECT id,plan_name,start_date,prelims_date,mains_date,interview_date,
                daily_hours,subjects_json,schedule_json,updated_at
         FROM timetables WHERE user_id=? ORDER BY updated_at DESC LIMIT 1"
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan   = $result->fetch_assoc();

    if ($plan) {
        $plan['subjects'] = json_decode($plan['subjects_json'], true);
        $plan['schedule'] = json_decode($plan['schedule_json'], true);

        // Load alerts
        $astmt = $conn->prepare(
            "SELECT alert_type,alert_label,alert_date,days_before,is_dismissed
             FROM timetable_alerts WHERE user_id=? AND timetable_id=? ORDER BY alert_date ASC"
        );
        $astmt->bind_param('ii', $uid, $plan['id']);
        $astmt->execute();
        $aresult = $astmt->get_result();
        $plan['alerts'] = [];
        while ($row = $aresult->fetch_assoc()) {
            $plan['alerts'][] = $row;
        }

        echo json_encode(['success' => true, 'plan' => $plan]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No saved plan found']);
    }
    exit();
}

/* ═══════════════════════════════════════
   ACTION: mark_complete
═══════════════════════════════════════ */
if ($action === 'mark_complete') {
    $study_date  = $data['date']    ?? date('Y-m-d');
    $subject     = htmlspecialchars($data['subject'] ?? '');
    $completed   = (int)($data['completed'] ?? 1);
    $hours_done  = (float)($data['hours'] ?? 0);
    $notes       = htmlspecialchars($data['notes'] ?? '');
    $timetable_id= (int)($data['timetable_id'] ?? 0);

    $stmt = $conn->prepare(
        "INSERT INTO timetable_progress (user_id,timetable_id,study_date,subject,completed,hours_done,notes)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE completed=VALUES(completed),hours_done=VALUES(hours_done),notes=VALUES(notes)"
    );
    $stmt->bind_param('iissids', $uid, $timetable_id, $study_date, $subject, $completed, $hours_done, $notes);
    $stmt->execute();

    echo json_encode(['success' => true]);
    exit();
}

/* ═══════════════════════════════════════
   ACTION: dismiss_alert
═══════════════════════════════════════ */
if ($action === 'dismiss_alert') {
    $alert_date = $data['alert_date'] ?? '';
    $stmt = $conn->prepare(
        "UPDATE timetable_alerts SET is_dismissed=1 WHERE user_id=? AND alert_date=?"
    );
    $stmt->bind_param('is', $uid, $alert_date);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);