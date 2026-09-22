<?php
/* ────────────────────────────────────────────────────
   includes/ajax_auth.php
   Drop-in auth guard for ALL AJAX endpoints.

   Unlike auth.php (which redirects to login.php),
   this file returns a JSON error for unauthenticated
   requests — safe for fetch() / XHR calls.

   Usage (at the very top of every AJAX file):
     ob_start();
     require_once 'includes/ajax_auth.php';

   Provides: $uid, $user_name
──────────────────────────────────────────────────── */

/* Flush any stray output (BOM, whitespace, notices) */
if (ob_get_level()) ob_end_clean();

/* Always send JSON — must come before any output */
header('Content-Type: application/json; charset=utf-8');

/* Suppress PHP notices/warnings that would corrupt JSON */
error_reporting(0);
ini_set('display_errors', '0');

/* Start session safely */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Auth check — return JSON, never HTML */
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated','auth'=>false]);
    exit();
}

$uid       = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name']  ?? 'Aspirant';