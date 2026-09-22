<?php
/* ───────────────────────────────────────────────
   includes/auth.php
   Guards every protected page.
   Include at the very top of any page that
   requires the user to be logged in.
─────────────────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    /* Relative redirect — works in any subfolder on XAMPP/WAMP/Apache */
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $depth  = substr_count(ltrim($script, '/'), '/');
    $prefix = $depth > 1 ? str_repeat('../', $depth - 1) : '';
    header('Location: ' . $prefix . 'login.php');
    exit();
}

/* Convenience shorthand used throughout the app */
$uid          = (int)$_SESSION['user_id'];
$user_name    = $_SESSION['user_name']  ?? 'Aspirant';
$user_email   = $_SESSION['user_email'] ?? '';