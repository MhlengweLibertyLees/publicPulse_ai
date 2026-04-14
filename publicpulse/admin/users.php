<?php
/**
 * PublicPulse AI — Admin: Manage Users
 * Full CRUD: create, edit role, activate/deactivate, delete
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('admin');
$csrf = csrfToken();
$user = currentUser();

$errors  = [];
$success = '';

/* ── Handle POST actions ──────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf(postVal('csrf_token'))) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = postVal('action');

        /* ── Create user ─── */
        if ($action === 'create') {
            $name     = postVal('name');
            $email    = postVal('email');
            $password = postVal('password');
            $role     = postVal('role');
            $ward     = postVal('ward');

            if (mb_strlen($name) < 2)      $errors[] = 'Name must be at least 2 characters.';
            if (!validateEmail($email))     $errors[] = 'Invalid email address.';
            if (!validatePassword($password)) $errors[] = 'Password must be ≥8 chars with uppercase, number, and symbol.';
            if (!in_array($role, ['citizen','admin','analyst'], true)) $errors[] = 'Invalid role.';

            if (empty($errors)) {
                $exists = Database::fetchOne('SELECT id FROM users WHERE email=?', [$email]);
                if ($exists) {
                    $errors[] = 'A user with this email already exists.';
                } else {
                    Database::execute(
                        'INSERT INTO users (name,email,password,role,ward) VALUES (?,?,?,?,?)',
                        [$name, $email, hashPassword($password), $role, $ward ?: null]
                    );
                    $_SESSION['flash'] = ['msg' => "User '{$name}' created successfully.", 'type' => 'success'];
                    redirect('admin/users.php');
                }
            }
        }

        /* ── Update user ─── */
        if ($action === 'update') {
            $uid  = (int)postVal('uid');
            $name = postVal('name');
            $role = postVal('role');
            $ward = postVal('ward');
            $newPwd = postVal('new_password');

            if ($uid === (int)$user['id'] && $role !== 'admin') {
                $errors[] = 'You cannot change your own role.';
            } elseif (!in_array($role, ['citizen','admin','analyst'], true)) {
                $errors[] = 'Invalid role.';
            } elseif (mb_strlen($name) < 2) {
                $errors[] = 'Name too short.';
            } else {
                if ($newPwd) {
                    if (!validatePassword($newPwd)) {
                        $errors[] = 'New password must be ≥8 chars with uppercase, number, and symbol.';
                    } else {
                        Database::execute(
                            'UPDATE users SET name=?,role=?,ward=?,password=?,updated_at=NOW() WHERE id=?',
                            [$name, $role, $ward ?: null, hashPassword($newPwd), $uid]
                        );
                    }
                } else {
                    Database::execute(
                        'UPDATE users SET name=?,role=?,ward=?,updated_at=NOW() WHERE id=?',
                        [$name, $role, $ward ?: null, $uid]
                    );
                }
                if (empty($errors)) {
                    $_SESSION['flash'] = ['msg' => 'User updated successfully.', 'type' => 'success'];
                    redirect('admin/users.php');
                }
            }
        }

        /* ── Toggle active ─── */
        if ($action === 'toggle_active') {
            $uid    = (int)postVal('uid');
            $active = (int)postVal('is_active');
            if ($uid === (int)$user['id']) {
                $_SESSION['flash'] = ['msg' => 'You cannot deactivate your own account.', 'type' => 'error'];
            } else {
                $newActive = $active ? 0 : 1;
                Database::execute('UPDATE users SET is_active=? WHERE id=?', [$newActive, $uid]);
                $msg = $newActive ? 'User activated.' : 'User deactivated.';
                $_SESSION['flash'] = ['msg' => $msg, 'type' => 'success'];
            }
            redirect('admin/users.php');
        }

        /* ── Delete user ─── */
        if ($action === 'delete') {
            $uid = (int)postVal('uid');
            if ($uid === (int)$user['id']) {
                $_SESSION['flash'] = ['msg' => 'You cannot delete your own account.', 'type' => 'error'];
            } else {
                Database::execute('DELETE FROM users WHERE id=?', [$uid]);
                $_SESSION['flash'] = ['msg' => 'User deleted.', 'type' => 'success'];
            }
            redirect('admin/users.php');
        }
    }
}

/* ── Fetch users with filters ─────────────────────────────── */
$search   = getVal('search');
$roleF    = getVal('role');
$activeF  = getVal('active');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page-1)*$perPage;

$where  = 'WHERE 1=1';
$params = [];
if ($search)  { $where .= ' AND (name LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleF)   { $where .= ' AND role=?';      $params[] = $roleF; }
if ($activeF !== '') { $where .= ' AND is_active=?'; $params[] = (int)$activeF; }

$total     = (int)Database::fetchScalar("SELECT COUNT(*) FROM users {$where}", $params);
$totalPages= (int)ceil($total/$perPage);
$users     = Database::fetchAll(
    "SELECT u.*, (SELECT COUNT(*) FROM complaints WHERE user_id=u.id) AS complaint_count
     FROM users u {$where} ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Fetch a single user for edit modal
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editUser = Database::fetchOne('SELECT id,name,email,role,ward,is_active FROM users WHERE id=?', [(int)$_GET['edit']]);
}

// Stats
$totalUsers    = (int)Database::fetchScalar('SELECT COUNT(*) FROM users');
$totalAdmins   = (int)Database::fetchScalar("SELECT COUNT(*) FROM users WHERE role='admin'");
$totalAnalysts = (int)Database::fetchScalar("SELECT COUNT(*) FROM users WHERE role='analyst'");
$totalCitizens = (int)Database::fetchScalar("SELECT COUNT(*) FROM users WHERE role='citizen'");

echo renderHead('Manage Users');
echo '<div class="app-shell">';
echo renderSidebar('admin_users', $user);
echo '<div class="main-content">';
echo renderTopbar('Manage Users', 'Create, edit, and manage user accounts', [
    ['href' => '#createModal', 'icon' => 'user-plus', 'label' => 'Add User', 'type' => 'primary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= icon('x-circle') ?><div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <div class="page-title">User Management</div>
      <div class="page-subtitle"><?= $total ?> users found</div>
    </div>
    <div class="page-actions">
      <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">
        <?= icon('user-plus') ?> Add New User
      </button>
    </div>
  </div>

  <!-- Stats row -->
  <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:var(--space-lg)">
    <div class="kpi-card blue"><div class="kpi-header"><div class="kpi-label">Total Users</div><div class="kpi-icon"><?= icon('users') ?></div></div><div class="kpi-value"><?= $totalUsers ?></div></div>
    <div class="kpi-card red"><div class="kpi-header"><div class="kpi-label">Admins</div><div class="kpi-icon"><?= icon('shield') ?></div></div><div class="kpi-value"><?= $totalAdmins ?></div></div>
    <div class="kpi-card purple"><div class="kpi-header"><div class="kpi-label">Analysts</div><div class="kpi-icon"><?= icon('chart-line') ?></div></div><div class="kpi-value"><?= $totalAnalysts ?></div></div>
    <div class="kpi-card green"><div class="kpi-header"><div class="kpi-label">Citizens</div><div class="kpi-icon"><?= icon('user') ?></div></div><div class="kpi-value"><?= $totalCitizens ?></div></div>
  </div>

  <!-- Filters -->
  <form method="GET">
    <div class="filter-bar">
      <div class="input-group">
        <span class="input-icon"><?= icon('search') ?></span>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email..." style="min-width:220px;padding-left:32px">
      </div>
      <select name="role">
        <option value="">All Roles</option>
        <?php foreach (['citizen','admin','analyst'] as $r): ?>
          <option value="<?= $r ?>" <?= $roleF===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="active">
        <option value="">All Status</option>
        <option value="1" <?= $activeF==='1'?'selected':'' ?>>Active</option>
        <option value="0" <?= $activeF==='0'?'selected':'' ?>>Inactive</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('filter') ?> Filter</button>
      <?php if ($search||$roleF||$activeF!==''): ?>
        <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-secondary btn-sm"><?= icon('x') ?> Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Users Table -->
  <div class="card">
    <div class="card-body no-pad">
      <?php if (empty($users)): ?>
        <div class="empty-state">
          <div class="empty-icon"><?= icon('users') ?></div>
          <h3>No users found</h3>
          <p>Try adjusting your search or filters.</p>
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Ward</th>
              <th>Complaints</th>
              <th>Status</th>
              <th>Joined</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u):
              $roleBadge = match($u['role']) {
                'admin'    => '<span class="badge badge-red">Admin</span>',
                'analyst'  => '<span class="badge badge-purple">Analyst</span>',
                default    => '<span class="badge badge-blue">Citizen</span>',
              };
              $activeBadge = $u['is_active']
                ? '<span class="badge badge-green">Active</span>'
                : '<span class="badge badge-gray">Inactive</span>';
            ?>
              <tr>
                <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:.72rem"><?= $u['id'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:9px">
                    <div class="avatar sm"><?= strtoupper(mb_substr($u['name'],0,1)) ?></div>
                    <span style="font-weight:600"><?= htmlspecialchars($u['name']) ?></span>
                    <?php if ($u['id'] == $user['id']): ?><span class="badge badge-teal" style="font-size:.6rem">You</span><?php endif; ?>
                  </div>
                </td>
                <td style="color:var(--text-secondary);font-size:.8rem"><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $roleBadge ?></td>
                <td style="font-size:.8rem;color:var(--text-secondary)"><?= htmlspecialchars($u['ward'] ?? '—') ?></td>
                <td style="font-family:var(--font-mono);font-size:.8rem;text-align:center"><?= $u['complaint_count'] ?></td>
                <td><?= $activeBadge ?></td>
                <td style="font-size:.75rem;color:var(--text-muted)"><?= formatDate($u['created_at'],'d M Y') ?></td>
                <td style="text-align:right">
                  <div style="display:flex;gap:5px;justify-content:flex-end">
                    <button onclick="openEdit(<?= htmlspecialchars(json_encode(['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'ward'=>$u['ward']??'','is_active'=>$u['is_active']])) ?>)"
                            class="btn btn-secondary btn-sm" title="Edit">
                      <?= icon('edit') ?>
                    </button>
                    <?php if ($u['id'] != $user['id']): ?>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action"    value="toggle_active">
                        <input type="hidden" name="uid"       value="<?= $u['id'] ?>">
                        <input type="hidden" name="is_active" value="<?= $u['is_active'] ?>">
                        <button type="submit" class="btn <?= $u['is_active'] ? 'btn-warning' : 'btn-success' ?> btn-sm"
                                title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                onclick="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')">
                          <?= icon($u['is_active'] ? 'eye-off' : 'eye') ?>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" title="Delete"
                                onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>', this)">
                          <?= icon('trash') ?>
                        </button>
                      </form>
                    <?php else: ?>
                      <span style="font-size:.72rem;color:var(--text-muted)">N/A</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($p=1; $p<=$totalPages; $p++):
        $q = http_build_query(array_filter(['search'=>$search,'role'=>$roleF,'active'=>$activeF,'page'=>$p]));
      ?>
        <a href="?<?= $q ?>" class="page-link <?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ── CREATE USER MODAL ─────────────────────────────────── -->
<div class="modal-overlay" id="createModal" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><?= icon('user-plus') ?> Create New User</span>
      <button onclick="document.getElementById('createModal').style.display='none'" class="btn btn-secondary btn-sm" style="padding:3px 8px">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:var(--space-md)">
        <div class="form-group" style="margin:0">
          <label class="form-label required">Full Name</label>
          <input type="text" name="name" class="form-control" placeholder="John Citizen" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label required">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label required">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min 8 chars, uppercase, number, symbol" required>
          <div class="form-hint">Must contain uppercase, number, and special character</div>
        </div>
        <div class="grid-2" style="gap:var(--space-md)">
          <div class="form-group" style="margin:0">
            <label class="form-label required">Role</label>
            <select name="role" class="form-control" required>
              <option value="citizen">Citizen</option>
              <option value="analyst">Data Analyst</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Ward</label>
            <select name="ward" class="form-control">
              <option value="">None</option>
              <?php for ($i=1;$i<=25;$i++): ?><option value="Ward <?= $i ?>">Ward <?= $i ?></option><?php endfor; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="document.getElementById('createModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('user-plus') ?> Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT USER MODAL ────────────────────────────────────── -->
<div class="modal-overlay" id="editModal" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><?= icon('edit') ?> Edit User</span>
      <button onclick="document.getElementById('editModal').style.display='none'" class="btn btn-secondary btn-sm" style="padding:3px 8px">&times;</button>
    </div>
    <form method="POST" id="editForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="uid"    id="editUid">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:var(--space-md)">
        <div class="form-group" style="margin:0">
          <label class="form-label required">Full Name</label>
          <input type="text" name="name" id="editName" class="form-control" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Email <span style="color:var(--text-muted);font-weight:400">(read-only)</span></label>
          <input type="email" id="editEmail" class="form-control" disabled>
        </div>
        <div class="grid-2" style="gap:var(--space-md)">
          <div class="form-group" style="margin:0">
            <label class="form-label required">Role</label>
            <select name="role" id="editRole" class="form-control" required>
              <option value="citizen">Citizen</option>
              <option value="analyst">Data Analyst</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Ward</label>
            <select name="ward" id="editWard" class="form-control">
              <option value="">None</option>
              <?php for ($i=1;$i<=25;$i++): ?><option value="Ward <?= $i ?>">Ward <?= $i ?></option><?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">New Password <span style="color:var(--text-muted);font-weight:400">(leave blank to keep current)</span></label>
          <input type="password" name="new_password" class="form-control" placeholder="Only fill to change password">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('save') ?> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE hidden form -->
<form method="POST" id="deleteForm" style="display:none">
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="uid" id="deleteUid">
</form>

<?= renderFoot() ?>

<script>
// Auto-open create modal if there were errors
<?php if (!empty($errors)): ?>
document.getElementById('createModal').style.display = 'flex';
<?php endif; ?>

function openEdit(data) {
  document.getElementById('editUid').value   = data.id;
  document.getElementById('editName').value  = data.name;
  document.getElementById('editEmail').value = data.email;
  document.getElementById('editRole').value  = data.role;
  document.getElementById('editWard').value  = data.ward || '';
  document.getElementById('editModal').style.display = 'flex';
}

function deleteUser(uid, name, btn) {
  confirmAction(`Delete user "${name}"? All their data will be removed.`, () => {
    document.getElementById('deleteUid').value = uid;
    document.getElementById('deleteForm').submit();
  });
}
</script>
</div></div>
