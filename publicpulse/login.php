<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';

sessionStart();
if (isLoggedIn()) {
    redirect(match($_SESSION['user_role']??'citizen'){'admin'=>'admin/dashboard.php','analyst'=>'analyst/dashboard.php',default=>'citizen/dashboard.php'});
}

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verifyCsrf(postVal('csrf_token'))) {
        $error = 'Security token expired. Please refresh.';
    } else {
        $email    = postVal('email');
        $password = postVal('password');
        if (!$email || !$password) {
            $error = 'Please enter your email and password.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $u = Database::fetchOne('SELECT * FROM users WHERE email=? AND is_active=1', [$email]);
            if ($u && verifyPassword($password, $u['password'])) {
                login($u);
                redirect(match($u['role']){'admin'=>'admin/dashboard.php','analyst'=>'analyst/dashboard.php',default=>'citizen/dashboard.php'});
            } else {
                $error = 'Incorrect email or password.';
                sleep(1);
            }
        }
    }
}
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In — PublicPulse AI</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
<script>const PP_URL='<?= APP_URL ?>';const PP_ROLE='citizen';</script>
</head>
<body>
<div class="auth-page">
  <div class="auth-bg"></div>

  <!-- Decorative grid -->
  <div style="position:absolute;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:48px 48px;opacity:.5;pointer-events:none"></div>

  <div style="width:100%;max-width:440px;padding:20px;position:relative;z-index:1">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="auth-logo-icon"><?= icon('building','',22) ?></div>
        <div>
          <div class="auth-title">PublicPulse AI</div>
          <div class="auth-subtitle">Smart Public Service Intelligence Platform</div>
        </div>
      </div>

      <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:4px">Welcome back</h2>
      <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:var(--space-lg)">Sign in to your account to continue</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= icon('x-circle') ?><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-group">
          <label class="form-label required">Email Address</label>
          <div class="input-group">
            <span class="input-icon"><?= icon('mail') ?></span>
            <input type="email" name="email" class="form-control" placeholder="you@example.com"
                   value="<?= htmlspecialchars(postVal('email')) ?>" autocomplete="email" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required">Password</label>
          <div class="input-group">
            <span class="input-icon"><?= icon('lock') ?></span>
            <input type="password" name="password" id="pwdField" class="form-control"
                   placeholder="Your password" autocomplete="current-password" style="padding-right:40px" required>
            <button type="button" class="input-icon-right" onclick="togglePwd()" title="Show/hide password">
              <?= icon('eye') ?>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px" id="submitBtn">
          <?= icon('arrow-right') ?> Sign In
        </button>
      </form>

      <div class="divider"></div>

      <div style="display:flex;align-items:center;justify-content:space-between;font-size:.8rem">
        <span style="color:var(--text-muted)">Don't have an account?</span>
        <a href="<?= APP_URL ?>/register.php" style="font-weight:600">Create account</a>
      </div>

      <!-- Demo credentials -->
      
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
function togglePwd(){
  const f=document.getElementById('pwdField');
  f.type=f.type==='password'?'text':'password';
}
function fillLogin(email){
  document.querySelector('[name="email"]').value=email;
  document.querySelector('[name="password"]').value='password';
  toast('Demo credentials filled — click Sign In','info');
}
document.getElementById('loginForm').addEventListener('submit',()=>{
  const b=document.getElementById('submitBtn');
  b.innerHTML='<span class="loader"></span> Signing in...';
  b.disabled=true;
});
</script>
</body></html>
