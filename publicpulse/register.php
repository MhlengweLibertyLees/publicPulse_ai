<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';

sessionStart();
if (isLoggedIn()) redirect('citizen/dashboard.php');

$error = ''; $success = false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrf(postVal('csrf_token'))) { $error='Security token expired.'; }
    else {
        $name=$p=postVal('name'); $email=postVal('email');
        $password=postVal('password'); $confirm=postVal('confirm_password'); $ward=postVal('ward');
        $errs=[];
        if (mb_strlen($name)<2)       $errs[]='Name must be at least 2 characters.';
        if (!validateEmail($email))    $errs[]='Invalid email address.';
        if (!validatePassword($password)) $errs[]='Password must be ≥8 chars with uppercase, number, and symbol.';
        if ($password!==$confirm)      $errs[]='Passwords do not match.';
        if (empty($errs)) {
            if (Database::fetchOne('SELECT id FROM users WHERE email=?',[$email])) $errs[]='Email already registered.';
        }
        if (empty($errs)) {
            Database::execute('INSERT INTO users (name,email,password,role,ward) VALUES (?,?,?,?,?)',
                [$name,$email,hashPassword($password),'citizen',$ward?:null]);
            $success=true;
        } else { $error=implode('<br>',$errs); }
    }
}
$csrf=csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account — PublicPulse AI</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
<script>const PP_URL='<?= APP_URL ?>';const PP_ROLE='citizen';</script>
</head>
<body>
<div class="auth-page">
  <div class="auth-bg"></div>
  <div style="position:absolute;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:48px 48px;opacity:.5;pointer-events:none"></div>
  <div style="width:100%;max-width:460px;padding:20px;position:relative;z-index:1">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="auth-logo-icon"><?= icon('building','',22) ?></div>
        <div><div class="auth-title">Create Account</div><div class="auth-subtitle">Join PublicPulse AI — Voice your concerns</div></div>
      </div>

      <?php if ($success): ?>
        <div style="text-align:center;padding:var(--space-lg) 0">
          <div style="width:56px;height:56px;background:var(--success-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-md);color:var(--success)">
            <?= icon('check-circle','',28) ?>
          </div>
          <h3 style="font-weight:800;margin-bottom:8px">Account Created!</h3>
          <p style="color:var(--text-muted);margin-bottom:var(--space-lg)">You can now sign in and submit complaints.</p>
          <a href="<?= APP_URL ?>/login.php" class="btn btn-primary" style="width:100%;justify-content:center">
            <?= icon('arrow-right') ?> Sign In Now
          </a>
        </div>
      <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= icon('x-circle') ?><div><?= $error ?></div></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <div class="grid-2" style="gap:var(--space-md)">
            <div class="form-group" style="margin:0;grid-column:span 2">
              <label class="form-label required">Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="John Citizen" value="<?= htmlspecialchars(postVal('name')) ?>" required>
            </div>
            <div class="form-group" style="margin:0;grid-column:span 2">
              <label class="form-label required">Email</label>
              <div class="input-group"><span class="input-icon"><?= icon('mail') ?></span>
              <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars(postVal('email')) ?>" required></div>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label required">Password</label>
              <div class="input-group"><span class="input-icon"><?= icon('lock') ?></span>
              <input type="password" name="password" class="form-control" placeholder="Strong password" required></div>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label required">Confirm Password</label>
              <div class="input-group"><span class="input-icon"><?= icon('lock') ?></span>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required></div>
            </div>
            <div class="form-group" style="margin:0;grid-column:span 2">
              <label class="form-label">Your Ward <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
              <select name="ward" class="form-control">
                <option value="">Select your ward...</option>
                <?php for($i=1;$i<=25;$i++): ?><option value="Ward <?= $i ?>">Ward <?= $i ?></option><?php endfor; ?>
              </select>
            </div>
          </div>
          <div style="font-size:.73rem;color:var(--text-muted);margin:var(--space-md) 0">
            Password must contain uppercase, number, and special character (!@#$%).
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            <?= icon('user-plus') ?> Create Account
          </button>
        </form>
        <div class="divider"></div>
        <div style="text-align:center;font-size:.8rem;color:var(--text-muted)">
          Already have an account? <a href="<?= APP_URL ?>/login.php" style="font-weight:600">Sign in</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body></html>
