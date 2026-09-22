<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

include 'includes/db.php';

$error = '';

if (isset($_POST['register'])) {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $conf  = $_POST['confirm']    ?? '';

    if (empty($name) || empty($email) || empty($pass)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $conf) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $chk = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'This email is already registered.';
            $chk->close();
        } else {
            $chk->close();
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hash);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header('Location: login.php?status=success');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — UPSC AI Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--v:#7c3aed;--vd:#5b21b6;--vl:#f0eaff;--ink:#1a1523;--m:#6b6579;--bd:rgba(0,0,0,.08);--s:#fff;--pg:#f4f2f8;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#1a0533,#3d0f6b,#6b1fa8);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;}
.card{background:var(--s);border-radius:24px;padding:2.75rem 2.25rem;width:100%;max-width:420px;box-shadow:0 32px 80px rgba(0,0,0,.3);}
.logo{width:52px;height:52px;border-radius:15px;background:var(--vl);color:var(--v);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;}
.logo svg{width:26px;height:26px;}
h1{font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:800;text-align:center;color:var(--ink);margin-bottom:.35rem;}
.sub{text-align:center;font-size:.85rem;color:var(--m);margin-bottom:1.75rem;}
.alert-err{padding:.75rem 1rem;border-radius:10px;font-size:.82rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.alert-err svg{width:16px;height:16px;flex-shrink:0;}
.field{margin-bottom:1rem;}
.field label{display:block;font-size:.78rem;font-weight:600;color:var(--ink);margin-bottom:.4rem;}
.field input{width:100%;padding:.7rem 1rem;border-radius:11px;border:1.5px solid var(--bd);background:var(--pg);font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--ink);outline:none;transition:border-color .2s;}
.field input:focus{border-color:var(--v);background:#fff;}
.btn{width:100%;padding:.8rem;border-radius:12px;border:none;background:linear-gradient(135deg,var(--vd),var(--v));color:#fff;font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;transition:opacity .2s;margin-top:.35rem;}
.btn:hover{opacity:.9;}
.foot{text-align:center;font-size:.8rem;color:var(--m);margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--bd);}
.foot a{color:var(--v);font-weight:600;}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
    </div>
    <h1>Create Account</h1>
    <p class="sub">Join UPSC AI Portal — it's free</p>

    <?php if ($error): ?>
    <div class="alert-err">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="post">
        <div class="field">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Your full name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min. 6 characters" required>
        </div>
        <div class="field">
            <label>Confirm Password</label>
            <input type="password" name="confirm" placeholder="Repeat password" required>
        </div>
        <button type="submit" name="register" class="btn">Create Account</button>
    </form>

    <div class="foot">
        Already have an account? <a href="login.php">Sign in</a>
    </div>
</div>
</body>
</html>