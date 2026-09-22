<?php
/* ─────────────────────────────────────────────
   profile.php
   Reads/writes profile_details WHERE user_id = $uid
   Uses prepared statements — no SQL injection
───────────────────────────────────────────── */
include 'includes/auth.php';
include 'includes/db.php';

$success_msg = '';
$error_msg   = '';

/* ── Fetch existing profile for this user ── */
$stmt = $conn->prepare('SELECT * FROM profile_details WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user_exists  = !empty($row);
$existing_id  = $row['id']                ?? null;

// Field defaults
$full_name          = $row['full_name']           ?? $user_name;
$dob                = $row['dob']                 ?? '';
$phone              = $row['phone']               ?? '';
$attempts           = $row['attempts']            ?? 0;
$optional_subject   = $row['optional_subject']    ?? '';
$target_year        = $row['target_year']         ?? '';
$home_state         = $row['home_state']          ?? '';
$bio                = $row['bio']                 ?? '';
$rating_history     = $row['rating_history']      ?? 1;
$rating_reasoning   = $row['rating_reasoning']    ?? 1;
$rating_politics    = $row['rating_politics']     ?? 1;
$rating_geography   = $row['rating_geography']    ?? 1;
$rating_economy     = $row['rating_economy']      ?? 1;
$rating_environment = $row['rating_environment']  ?? 1;
$rating_science     = $row['rating_science']      ?? 1;
$rating_ethics      = $row['rating_ethics']       ?? 1;

/* ── Handle form submit ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name          = trim($_POST['full_name']          ?? '');
    $dob                = $_POST['dob']                     ?? '';
    $phone              = trim($_POST['phone']              ?? '');
    $attempts           = (int)($_POST['attempts']          ?? 0);
    $optional_subject   = trim($_POST['optional_subject']   ?? '');
    $target_year        = trim($_POST['target_year']        ?? '');
    $home_state         = trim($_POST['home_state']         ?? '');
    $bio                = trim($_POST['bio']                ?? '');
    $rating_history     = (int)($_POST['rating_history']    ?? 1);
    $rating_reasoning   = (int)($_POST['rating_reasoning']  ?? 1);
    $rating_politics    = (int)($_POST['rating_politics']   ?? 1);
    $rating_geography   = (int)($_POST['rating_geography']  ?? 1);
    $rating_economy     = (int)($_POST['rating_economy']    ?? 1);
    $rating_environment = (int)($_POST['rating_environment']?? 1);
    $rating_science     = (int)($_POST['rating_science']    ?? 1);
    $rating_ethics      = (int)($_POST['rating_ethics']     ?? 1);

    if ($user_exists) {
        $sql = 'UPDATE profile_details SET
            full_name=?, dob=?, phone=?, attempts=?,
            optional_subject=?, target_year=?, home_state=?, bio=?,
            rating_history=?, rating_reasoning=?, rating_politics=?,
            rating_geography=?, rating_economy=?, rating_environment=?,
            rating_science=?, rating_ethics=?
            WHERE id=? AND user_id=?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssississiiiiiiii ii',
            $full_name,$dob,$phone,$attempts,
            $optional_subject,$target_year,$home_state,$bio,
            $rating_history,$rating_reasoning,$rating_politics,
            $rating_geography,$rating_economy,$rating_environment,
            $rating_science,$rating_ethics,
            $existing_id,$uid
        );
    } else {
        $sql = 'INSERT INTO profile_details
            (user_id,full_name,dob,phone,attempts,
             optional_subject,target_year,home_state,bio,
             rating_history,rating_reasoning,rating_politics,
             rating_geography,rating_economy,rating_environment,
             rating_science,rating_ethics)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssississiiiiiiii',
            $uid,$full_name,$dob,$phone,$attempts,
            $optional_subject,$target_year,$home_state,$bio,
            $rating_history,$rating_reasoning,$rating_politics,
            $rating_geography,$rating_economy,$rating_environment,
            $rating_science,$rating_ethics
        );
    }

    if ($stmt && $stmt->execute()) {
        $success_msg = 'Profile saved successfully!';
        $user_exists = true;
        if (!$existing_id) $existing_id = $conn->insert_id;
        // Also update users table phone/home_state
        $conn->prepare('UPDATE users SET phone=?, home_state=? WHERE id=?')
             ->execute() ;
    } else {
        $error_msg = 'Error saving profile: ' . $conn->error;
    }
    if ($stmt) $stmt->close();
}

/* ── Subject ratings config ── */
$subjects = [
    ['key'=>'rating_history',     'label'=>'History',             'val'=>$rating_history],
    ['key'=>'rating_politics',    'label'=>'Polity & Governance', 'val'=>$rating_politics],
    ['key'=>'rating_geography',   'label'=>'Geography',           'val'=>$rating_geography],
    ['key'=>'rating_economy',     'label'=>'Economy',             'val'=>$rating_economy],
    ['key'=>'rating_environment', 'label'=>'Environment',         'val'=>$rating_environment],
    ['key'=>'rating_science',     'label'=>'Science & Tech',      'val'=>$rating_science],
    ['key'=>'rating_ethics',      'label'=>'Ethics (GS IV)',      'val'=>$rating_ethics],
    ['key'=>'rating_reasoning',   'label'=>'Reasoning (CSAT)',    'val'=>$rating_reasoning],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile — UPSC Command Center</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{--grad:linear-gradient(135deg,#1a0533,#3d0f6b,#6b1fa8);--v:#7c3aed;--vl:#f0eaff;--ink:#1a1523;--m:#6b6579;--bd:rgba(0,0,0,.08);--s:#fff;--pg:#f4f2f8;--ease:cubic-bezier(.2,.8,.2,1);}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased;}
body{font-family:'DM Sans',sans-serif;background:var(--pg);color:var(--ink);min-height:100vh;}
a{text-decoration:none;color:inherit;}
.hero{background:var(--grad);height:190px;position:relative;}
.back-btn{position:absolute;top:1.5rem;left:1.5rem;width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;color:#fff;transition:all .2s;}
.back-btn:hover{background:rgba(255,255,255,.25);transform:translateY(-2px);}
.wrap{max-width:780px;margin:-90px auto 3rem;padding:0 1.25rem;position:relative;z-index:10;}
.card{background:var(--s);border-radius:22px;padding:2.5rem 2rem;box-shadow:0 12px 40px rgba(0,0,0,.08);}
.card-hd{text-align:center;margin-bottom:2rem;}
.avatar{width:72px;height:72px;border-radius:50%;background:var(--vl);color:var(--v);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;}
.card-hd h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:.3rem;}
.card-hd p{font-size:.85rem;color:var(--m);}
.alert{padding:.75rem 1rem;border-radius:10px;font-size:.82rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;}
.alert-ok {background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}
.alert-err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.section-title{font-family:'Syne',sans-serif;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--v);border-bottom:1px solid var(--bd);padding-bottom:.5rem;margin:0 0 1.25rem;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem;}
.span2{grid-column:span 2;}
.field label{display:block;font-size:.75rem;font-weight:600;color:var(--ink);margin-bottom:.35rem;}
.field input,.field select,.field textarea{width:100%;padding:.65rem .9rem;border-radius:11px;border:1.5px solid var(--bd);background:var(--pg);font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--ink);outline:none;transition:border-color .2s;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--v);background:#fff;}
.field textarea{resize:vertical;min-height:80px;}
/* Star rating */
.rating-row{display:flex;align-items:center;justify-content:space-between;background:var(--pg);padding:.75rem 1rem;border-radius:12px;margin-bottom:.65rem;}
.rating-row label{font-size:.82rem;font-weight:500;}
.stars{display:flex;flex-direction:row-reverse;gap:3px;}
.stars input{display:none;}
.stars label{font-size:1.3rem;color:#cbd5e0;cursor:pointer;transition:color .15s;margin-bottom:0;}
.stars label:hover,.stars label:hover~label,.stars input:checked~label{color:#f59e0b;}
.stars label::before{content:"\F586";font-family:"bootstrap-icons";}
.stars input:checked~label::before{content:"\F584";}
.submit-btn{width:100%;padding:.85rem;border-radius:13px;border:none;background:var(--grad);color:#fff;font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:all .22s var(--ease);margin-top:.5rem;}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(124,58,237,.3);}
@media(max-width:600px){.grid2,.grid3{grid-template-columns:1fr;}.span2{grid-column:span 1;}}
</style>
</head>
<body>

<div class="hero">
    <a href="dashboard.php" class="back-btn">
        <i class="bi bi-arrow-left" style="font-size:1.1rem"></i>
    </a>
</div>

<div class="wrap">
    <div class="card">
        <div class="card-hd">
            <div class="avatar"><?php echo strtoupper(substr($user_name,0,1)); ?></div>
            <h1><?php echo $user_exists ? 'Edit Profile' : 'Complete Your Profile'; ?></h1>
            <p><?php echo htmlspecialchars($user_email); ?></p>
        </div>

        <?php if ($success_msg): ?>
        <div class="alert alert-ok"><i class="bi bi-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="alert alert-err"><i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST">

            <!-- Personal Details -->
            <div class="section-title">Aspirant Details</div>
            <div class="grid2" style="margin-bottom:1rem">
                <div class="field span2">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required placeholder="Your full name">
                </div>
                <div class="field">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo $dob; ?>">
                </div>
                <div class="field">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+91 XXXXX XXXXX">
                </div>
                <div class="field">
                    <label>UPSC Attempts</label>
                    <input type="number" name="attempts" min="0" max="20" value="<?php echo $attempts; ?>">
                </div>
                <div class="field">
                    <label>Target Year</label>
                    <input type="number" name="target_year" min="2025" max="2035" value="<?php echo $target_year; ?>" placeholder="2026">
                </div>
                <div class="field">
                    <label>Home State</label>
                    <input type="text" name="home_state" value="<?php echo htmlspecialchars($home_state); ?>" placeholder="e.g. Andhra Pradesh">
                </div>
                <div class="field">
                    <label>Optional Subject</label>
                    <input type="text" name="optional_subject" value="<?php echo htmlspecialchars($optional_subject); ?>" placeholder="e.g. Public Administration">
                </div>
                <div class="field span2">
                    <label>About / Bio</label>
                    <textarea name="bio" placeholder="Brief description about your preparation…"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
            </div>

            <!-- Subject Ratings -->
            <div class="section-title" style="margin-top:1.5rem">Subject Self-Assessment</div>
            <?php foreach ($subjects as $s): ?>
            <div class="rating-row">
                <label><?php echo $s['label']; ?></label>
                <div class="stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="<?php echo $s['key'].'-'.$i; ?>"
                           name="<?php echo $s['key']; ?>" value="<?php echo $i; ?>"
                           <?php echo ($s['val'] == $i) ? 'checked' : ''; ?>>
                    <label for="<?php echo $s['key'].'-'.$i; ?>"></label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">
                <?php echo $user_exists ? 'Update Profile' : 'Save Profile'; ?>
            </button>
        </form>
    </div>
</div>

</body>
</html>