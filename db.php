<?php
/* ───────────────────────────────────────────────
   includes/db.php
   Single shared database connection.
   Included by every PHP file that needs the DB.
   Change credentials here once — applies everywhere.
─────────────────────────────────────────────── */
if (isset($conn)) return; // prevent double-include

$host    = 'localhost';
$db_user = 'root';   // ← your XAMPP MySQL username
$db_pass = '';       // ← your XAMPP MySQL password (blank by default)
$db_name = 'upsc_ai';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'DB connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset('utf8mb4');