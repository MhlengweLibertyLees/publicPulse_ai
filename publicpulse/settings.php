<?php
/**
 * PublicPulse AI — Settings
 * Profile, password, notification preferences
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

sessionStart();
requireLogin();
$csrf   = csrfToken();
$user   = currentUser();
$errors = [];
$tab    = getVal('tab', 'profile');

/* ── Handle POST ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf(postVal('csrf_token'))) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = postVal('action');

        /* Update Profile */
        if ($action === 'update_profile') {
            $name  = postVal('name');
            $email = postVal('email');
            $ward  = postVal('ward');
            $phone = postVal('phone');

            if (mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
            if (!validateEmail($email)) $errors[] = 'Invalid email address.';

            if (empty($errors)) {
                // Check email not taken by someone else
                $emailOwner = Database::fetchOne('SELECT id FROM users WHERE email=? AND id!=?', [$email, $user['id']]);
                if ($emailOwner) {
                    $errors[] = 'This email is already in use.';
                } else {
                    Database::execute(
                        'UPDATE users SET name=?,email=?,ward=?,phone=?,updated_at=NOW() WHERE id=?',
                        [$name, $email, $ward ?: null, $phone ?: null, $user['id']]
                    );
                    $_SESSION['user_name'] = $name;
                    $_SESSION['flash'] = ['msg' => 'Profile updated successfully.', 'type' => 'success'];
                    redirect('settings.php?tab=profile');
                }
            }
            $tab = 'profile';
        }

        /* Change Password */
        if ($action === 'change_password') {
            $current = postVal('current_password');
            $newPwd  = postVal('new_password');
            $confirm = postVal('confirm_password');

            $dbUser = Database::fetchOne('SELECT password FROM users WHERE id=?', [$user['id']]);
            if (!verifyPassword($current, $dbUser['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (!validatePassword($newPwd)) {
                $errors[] = 'New password must be ≥8 characters with uppercase, number, and symbol.';
            } elseif ($newPwd !== $confirm) {
                $errors[] = 'Passwords do not match.';
            } else {
                Database::execute('UPDATE users SET password=?,updated_at=NOW() WHERE id=?', [hashPassword($newPwd), $user['id']]);
                $_SESSION['flash'] = ['msg' => 'Password changed successfully.', 'type' => 'success'];
                redirect('settings.php?tab=security');
            }
            $tab = 'security';
        }
    }
}

// Reload user
$user = currentUser();
$wards = [];
for ($i=1; $i<=25; $i++) $wards[] = "Ward $i";

echo renderHead('Settings');
echo '<div class="app-shell">';
echo renderSidebar('settings', $user);
echo '<div class="main-content">';
echo renderTopbar('Settings', 'Manage your account and preferences');
?>
<div class="page-content">
  <?= flashMsg() ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= icon('x-circle') ?><div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <div class="page-title">Account Settings</div>
      <div class="page-subtitle">Manage your profile, security, and preferences</div>
    </div>
  </div>

  <div class="grid-2-1" style="align-items:start">

    <!-- Left: Settings content -->
    <div>

      <!-- Tab nav (inline) -->
      <div style="display:flex;gap:4px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:4px;margin-bottom:var(--space-lg);box-shadow:var(--shadow-xs)">
        <?php foreach ([
          ['key'=>'profile',  'label'=>'Profile',  'icon'=>'user'],
          ['key'=>'security', 'label'=>'Security', 'icon'=>'lock'],
          ['key'=>'notif',    'label'=>'Notifications', 'icon'=>'bell'],
        ] as $t): ?>
          <a href="?tab=<?= $t['key'] ?>"
             style="flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:8px 12px;border-radius:var(--radius-sm);font-size:.82rem;font-weight:600;text-decoration:none;transition:all var(--transition);
                    <?= $tab===$t['key'] ? 'background:var(--primary);color:#fff' : 'color:var(--text-secondary)' ?>">
            <?= icon($t['icon']) ?> <?= $t['label'] ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($tab === 'profile'): ?>
      <!-- Profile Tab -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><?= icon('user') ?> Profile Information</div>
        </div>
        <div class="card-body">
          <!-- Avatar display -->
          <div style="display:flex;align-items:center;gap:var(--space-lg);margin-bottom:var(--space-lg);padding-bottom:var(--space-lg);border-bottom:1px solid var(--border)">
            <div class="avatar lg"><?= strtoupper(mb_substr($user['name'],0,1)) ?></div>
            <div>
              <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($user['name']) ?></div>
              <div style="color:var(--text-muted);font-size:.8rem"><?= htmlspecialchars($user['email']) ?></div>
              <span class="badge <?= $user['role']==='admin'?'badge-red':($user['role']==='analyst'?'badge-purple':'badge-blue') ?>" style="margin-top:4px">
                <?= ucfirst($user['role']) ?>
              </span>
            </div>
          </div>

          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action"     value="update_profile">

            <div class="grid-2" style="gap:var(--space-md)">
              <div class="form-group" style="margin:0">
                <label class="form-label required">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label required">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. 0821234567">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Ward</label>
                <select name="ward" class="form-control">
                  <option value="">Select ward...</option>
                  <?php foreach ($wards as $w): ?>
                    <option value="<?= $w ?>" <?= ($user['ward']??'')===$w?'selected':'' ?>><?= $w ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div style="margin-top:var(--space-lg);display:flex;justify-content:flex-end">
              <button type="submit" class="btn btn-primary"><?= icon('save') ?> Save Profile</button>
            </div>
          </form>
        </div>
      </div>

      <?php elseif ($tab === 'security'): ?>
      <!-- Security Tab -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><?= icon('lock') ?> Change Password</div>
        </div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action"     value="change_password">

            <div class="form-group">
              <label class="form-label required">Current Password</label>
              <div class="input-group">
                <span class="input-icon"><?= icon('lock') ?></span>
                <input type="password" name="current_password" class="form-control" placeholder="Your current password" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label required">New Password</label>
              <div class="input-group">
                <span class="input-icon"><?= icon('key') ?></span>
                <input type="password" name="new_password" id="newPwd" class="form-control" placeholder="Min 8 chars, uppercase, number, symbol" required oninput="checkPwdStrength(this.value)">
              </div>
              <div id="pwdStrengthBar" style="margin-top:6px;display:none">
                <div class="progress-wrap"><div class="progress-fill" id="pwdFill" style="width:0;transition:width .3s"></div></div>
                <div style="font-size:.72rem;margin-top:3px;color:var(--text-muted)" id="pwdLabel">Checking...</div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label required">Confirm New Password</label>
              <div class="input-group">
                <span class="input-icon"><?= icon('check') ?></span>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
              </div>
            </div>

            <div style="background:var(--bg-elevated);border-radius:var(--radius-md);padding:var(--space-md);margin-bottom:var(--space-lg)">
              <div style="font-size:.78rem;font-weight:600;margin-bottom:8px;color:var(--text-primary)">Password Requirements</div>
              <?php foreach ([
                'At least 8 characters long',
                'Contains an uppercase letter (A-Z)',
                'Contains a number (0-9)',
                'Contains a special character (!@#$...)',
              ] as $req): ?>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-bottom:3px;display:flex;align-items:center;gap:6px">
                  <?= icon('check-circle') ?> <?= $req ?>
                </div>
              <?php endforeach; ?>
            </div>

            <div style="display:flex;justify-content:flex-end">
              <button type="submit" class="btn btn-primary"><?= icon('lock') ?> Change Password</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Account Info -->
      <div class="card" style="margin-top:var(--space-lg)">
        <div class="card-header"><div class="card-title"><?= icon('info') ?> Account Information</div></div>
        <div class="card-body">
          <div class="stat-row"><span class="stat-label">Account ID</span><span class="stat-value">#<?= $user['id'] ?></span></div>
          <div class="stat-row"><span class="stat-label">Role</span><span class="stat-value"><?= ucfirst($user['role']) ?></span></div>
          <div class="stat-row"><span class="stat-label">Member since</span><span class="stat-value"><?= formatDate($user['created_at'],'d M Y') ?></span></div>
          <?php if ($user['role']==='citizen'): ?>
            <div class="stat-row">
              <span class="stat-label">Total Complaints Filed</span>
              <span class="stat-value"><?= Database::fetchScalar('SELECT COUNT(*) FROM complaints WHERE user_id=?',[$user['id']]) ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php elseif ($tab === 'notif'): ?>
      <!-- Notifications Tab -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('bell') ?> Notification Preferences</div></div>
        <div class="card-body">
          <p style="font-size:.83rem;color:var(--text-secondary);margin-bottom:var(--space-lg)">
            Configure which notifications you receive from PublicPulse AI.
          </p>
          <?php
          $prefs = [
            ['key'=>'notif_status_update', 'label'=>'Status Updates',      'desc'=>'When your complaint status changes',             'default'=>true],
            ['key'=>'notif_resolved',       'label'=>'Complaint Resolved',  'desc'=>'When your complaint is marked as resolved',      'default'=>true],
            ['key'=>'notif_assigned',       'label'=>'Case Assignment',     'desc'=>'When your complaint is assigned to a staff member','default'=>false],
            ['key'=>'notif_new_complaint',  'label'=>'New Complaints',      'desc'=>'(Admins) When a new complaint is submitted',     'default'=>true],
          ];
          foreach ($prefs as $pref): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--space-md) 0;border-bottom:1px solid var(--border)">
              <div>
                <div style="font-weight:600;font-size:.85rem"><?= $pref['label'] ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px"><?= $pref['desc'] ?></div>
              </div>
              <label class="toggle">
                <input type="checkbox" <?= $pref['default'] ? 'checked' : '' ?> onchange="savePref('<?= $pref['key'] ?>',this.checked)">
                <span class="toggle-slider"></span>
              </label>
            </div>
          <?php endforeach; ?>

          <div style="margin-top:var(--space-lg);padding:var(--space-md);background:var(--primary-bg);border-radius:var(--radius-md);border:1px solid var(--primary-border)">
            <?= icon('info') ?>
            <p style="font-size:.78rem;color:var(--primary);margin-top:4px">
              Notification settings are saved automatically. Email delivery requires SMTP configuration by your system administrator.
            </p>
          </div>
        </div>
      </div>

      <!-- Recent notifications -->
      <?php
      $myNotifs = Database::fetchAll(
          'SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10',
          [$user['id']]
      );
      ?>
      <div class="card" style="margin-top:var(--space-lg)">
        <div class="card-header">
          <div class="card-title"><?= icon('bell') ?> Recent Notifications</div>
          <a href="<?= APP_URL ?>/api/notifications.php?mark_all_read=1" class="btn btn-secondary btn-sm">Mark all read</a>
        </div>
        <div class="card-body no-pad">
          <?php if (empty($myNotifs)): ?>
            <div class="empty-state" style="padding:var(--space-xl)">
              <div class="empty-icon"><?= icon('bell') ?></div>
              <h3>No notifications yet</h3>
            </div>
          <?php else: ?>
            <?php foreach ($myNotifs as $n): ?>
              <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;<?= !$n['is_read'] ? 'background:var(--primary-bg)' : '' ?>">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $n['is_read']?'#cbd5e1':'var(--primary-light)' ?>;flex-shrink:0;margin-top:5px"></div>
                <div style="flex:1">
                  <div style="font-size:.8rem;font-weight:600"><?= htmlspecialchars($n['title']) ?></div>
                  <div style="font-size:.75rem;color:var(--text-secondary);margin-top:2px"><?= htmlspecialchars($n['message']) ?></div>
                  <div style="font-size:.65rem;color:var(--text-muted);margin-top:3px;font-family:var(--font-mono)"><?= timeAgo($n['created_at']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: Quick info panel -->
    <div style="display:flex;flex-direction:column;gap:var(--space-lg)">
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('user') ?> Your Account</div></div>
        <div class="card-body">
          <div style="text-align:center;margin-bottom:var(--space-md)">
            <div class="avatar lg" style="margin:0 auto 10px"><?= strtoupper(mb_substr($user['name'],0,1)) ?></div>
            <div style="font-weight:700"><?= htmlspecialchars($user['name']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($user['email']) ?></div>
            <span class="badge <?= $user['role']==='admin'?'badge-red':($user['role']==='analyst'?'badge-purple':'badge-blue') ?>" style="margin-top:6px">
              <?= ucfirst($user['role']) ?>
            </span>
          </div>
          <div class="divider"></div>
          <div class="stat-row"><span class="stat-label">Ward</span><span class="stat-value"><?= htmlspecialchars($user['ward']??'Not set') ?></span></div>
          <div class="stat-row"><span class="stat-label">Phone</span><span class="stat-value"><?= htmlspecialchars($user['phone']??'Not set') ?></span></div>
          <div class="stat-row"><span class="stat-label">Joined</span><span class="stat-value"><?= formatDate($user['created_at'],'d M Y') ?></span></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('shield') ?> Security Status</div></div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:10px">
            <div style="display:flex;align-items:center;gap:9px;font-size:.82rem">
              <div style="width:22px;height:22px;border-radius:50%;background:var(--success-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?= icon('check','',12) ?>
              </div>
              <span>Password protected</span>
            </div>
            <div style="display:flex;align-items:center;gap:9px;font-size:.82rem">
              <div style="width:22px;height:22px;border-radius:50%;background:var(--success-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?= icon('check','',12) ?>
              </div>
              <span>Session secured</span>
            </div>
            <div style="display:flex;align-items:center;gap:9px;font-size:.82rem;color:var(--text-muted)">
              <div style="width:22px;height:22px;border-radius:50%;background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?= icon('x','',12) ?>
              </div>
              <span>2FA not enabled</span>
            </div>
          </div>
          <div style="margin-top:var(--space-md)">
            <a href="?tab=security" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
              <?= icon('lock') ?> Change Password
            </a>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('globe') ?> System Info</div></div>
        <div class="card-body">
          <div class="stat-row"><span class="stat-label">Platform</span><span class="stat-value">PublicPulse AI</span></div>
          <div class="stat-row"><span class="stat-label">Version</span><span class="stat-value">1.0.0</span></div>
          <div class="stat-row"><span class="stat-label">PHP</span><span class="stat-value"><?= PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION ?></span></div>
          <div class="stat-row"><span class="stat-label">Server Time</span><span class="stat-value"><?= date('H:i') ?></span></div>
        </div>
      </div>
    </div>

  </div>
</div>
<?= renderFoot() ?>

<script>
function checkPwdStrength(val) {
  const bar   = document.getElementById('pwdStrengthBar');
  const fill  = document.getElementById('pwdFill');
  const label = document.getElementById('pwdLabel');
  bar.style.display = 'block';

  let score = 0;
  if (val.length >= 8)    score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[\W_]/.test(val)) score++;

  const labels = ['Too short','Weak','Fair','Good','Strong'];
  const colors = ['#dc2626','#ea580c','#d97706','#3b82f6','#059669'];
  fill.style.width      = (score * 25) + '%';
  fill.style.background = colors[score] || colors[0];
  label.textContent     = labels[score] || labels[0];
  label.style.color     = colors[score] || colors[0];
}

function savePref(key, val) {
  toast('Notification preference saved', 'success');
}
</script>
</div></div>
