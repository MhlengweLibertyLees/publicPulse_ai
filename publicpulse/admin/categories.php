<?php
/**
 * PublicPulse AI — Admin: Categories Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('admin');
$csrf   = csrfToken();
$user   = currentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf(postVal('csrf_token'))) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = postVal('action');

        if ($action === 'create') {
            $name  = postVal('name');
            $color = postVal('color') ?: '#3b82f6';
            $desc  = postVal('description');
            $icon  = postVal('icon_name') ?: 'circle';
            if (mb_strlen($name) < 2) { $errors[] = 'Name must be at least 2 characters.'; }
            if (empty($errors)) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-', $name));
                $exists = Database::fetchOne('SELECT id FROM categories WHERE slug=?', [$slug]);
                if ($exists) $errors[] = 'A category with a similar name already exists.';
                else {
                    Database::execute('INSERT INTO categories (name,slug,icon,color,description) VALUES (?,?,?,?,?)',
                        [$name, $slug, $icon, $color, $desc]);
                    $_SESSION['flash'] = ['msg'=>"Category '{$name}' created.", 'type'=>'success'];
                    redirect('admin/categories.php');
                }
            }
        }

        if ($action === 'update') {
            $id    = (int)postVal('cid');
            $name  = postVal('name');
            $color = postVal('color') ?: '#3b82f6';
            $desc  = postVal('description');
            $icon  = postVal('icon_name') ?: 'circle';
            $active= (int)postVal('is_active');
            if (mb_strlen($name) < 2) $errors[] = 'Name too short.';
            if (empty($errors)) {
                Database::execute('UPDATE categories SET name=?,icon=?,color=?,description=?,is_active=? WHERE id=?',
                    [$name, $icon, $color, $desc, $active, $id]);
                $_SESSION['flash'] = ['msg'=>'Category updated.', 'type'=>'success'];
                redirect('admin/categories.php');
            }
        }

        if ($action === 'toggle') {
            $id  = (int)postVal('cid');
            $cur = (int)postVal('is_active');
            Database::execute('UPDATE categories SET is_active=? WHERE id=?', [$cur?0:1, $id]);
            $_SESSION['flash'] = ['msg'=>'Category status updated.', 'type'=>'success'];
            redirect('admin/categories.php');
        }
    }
}

$categories = Database::fetchAll("
    SELECT c.*, (SELECT COUNT(*) FROM complaints WHERE category_id=c.id) AS complaint_count
    FROM categories c ORDER BY c.name
");

$iconOptions = [
    'droplet'=>'Water','bolt'=>'Electricity','road'=>'Roads','trash-2'=>'Waste',
    'shield'=>'Safety','heart'=>'Health','home'=>'Housing','book'=>'Education',
    'zap'=>'Energy','globe'=>'Environment','server'=>'Infrastructure','activity'=>'General',
];

echo renderHead('Categories');
echo '<div class="app-shell">';
echo renderSidebar('admin_categories', $user);
echo '<div class="main-content">';
echo renderTopbar('Categories', 'Manage complaint categories');
?>
<div class="page-content">
  <?= flashMsg() ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= icon('x-circle') ?><div><?= implode('<br>',array_map('htmlspecialchars',$errors)) ?></div></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <div class="page-title">Complaint Categories</div>
      <div class="page-subtitle"><?= count($categories) ?> categories configured</div>
    </div>
    <div class="page-actions">
      <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">
        <?= icon('plus') ?> Add Category
      </button>
    </div>
  </div>

  <div class="card">
    <div class="card-body no-pad">
      <table class="data-table">
        <thead>
          <tr><th>Icon</th><th>Name</th><th>Description</th><th>Complaints</th><th>Status</th><th style="text-align:right">Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:<?= htmlspecialchars($cat['color']) ?>22;display:flex;align-items:center;justify-content:center;color:<?= htmlspecialchars($cat['color']) ?>">
                  <?= icon($cat['icon'] ?? 'tag') ?>
                </div>
              </td>
              <td style="font-weight:700"><?= htmlspecialchars($cat['name']) ?></td>
              <td style="color:var(--text-secondary);font-size:.8rem;max-width:260px"><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
              <td style="font-family:var(--font-mono);text-align:center">
                <a href="<?= APP_URL ?>/admin/complaints.php?category=<?= $cat['id'] ?>" style="font-weight:700;color:var(--primary)">
                  <?= $cat['complaint_count'] ?>
                </a>
              </td>
              <td><?= $cat['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' ?></td>
              <td style="text-align:right">
                <div style="display:flex;gap:5px;justify-content:flex-end">
                  <button onclick="openEditCat(<?= htmlspecialchars(json_encode($cat)) ?>)" class="btn btn-secondary btn-sm">
                    <?= icon('edit') ?>
                  </button>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action"    value="toggle">
                    <input type="hidden" name="cid"       value="<?= $cat['id'] ?>">
                    <input type="hidden" name="is_active" value="<?= $cat['is_active'] ?>">
                    <button type="submit" class="btn <?= $cat['is_active']?'btn-warning':'btn-success' ?> btn-sm"
                            onclick="return confirm('<?= $cat['is_active']?'Deactivate':'Activate' ?> this category?')">
                      <?= icon($cat['is_active']?'eye-off':'eye') ?>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><?= icon('plus') ?> New Category</span>
      <button onclick="document.getElementById('createModal').style.display='none'" class="btn btn-secondary btn-sm" style="padding:3px 8px">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action"     value="create">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:var(--space-md)">
        <div class="form-group" style="margin:0">
          <label class="form-label required">Category Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Water & Sanitation" required>
        </div>
        <div class="grid-2" style="gap:var(--space-md)">
          <div class="form-group" style="margin:0">
            <label class="form-label">Icon</label>
            <select name="icon_name" class="form-control">
              <?php foreach ($iconOptions as $k=>$v): ?>
                <option value="<?= $k ?>"><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Color</label>
            <input type="color" name="color" class="form-control" value="#3b82f6" style="height:38px;padding:4px 6px;cursor:pointer">
          </div>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this category"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="document.getElementById('createModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('plus') ?> Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editCatModal" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><?= icon('edit') ?> Edit Category</span>
      <button onclick="document.getElementById('editCatModal').style.display='none'" class="btn btn-secondary btn-sm" style="padding:3px 8px">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action"     value="update">
      <input type="hidden" name="cid"        id="editCatId">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:var(--space-md)">
        <div class="form-group" style="margin:0">
          <label class="form-label required">Name</label>
          <input type="text" name="name" id="editCatName" class="form-control" required>
        </div>
        <div class="grid-2" style="gap:var(--space-md)">
          <div class="form-group" style="margin:0">
            <label class="form-label">Icon</label>
            <select name="icon_name" id="editCatIcon" class="form-control">
              <?php foreach ($iconOptions as $k=>$v): ?>
                <option value="<?= $k ?>"><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Color</label>
            <input type="color" name="color" id="editCatColor" class="form-control" style="height:38px;padding:4px 6px;cursor:pointer">
          </div>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Description</label>
          <textarea name="description" id="editCatDesc" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Status</label>
          <select name="is_active" id="editCatActive" class="form-control">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="document.getElementById('editCatModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('save') ?> Save</button>
      </div>
    </form>
  </div>
</div>

<?= renderFoot() ?>
<script>
function openEditCat(cat) {
  document.getElementById('editCatId').value      = cat.id;
  document.getElementById('editCatName').value    = cat.name;
  document.getElementById('editCatIcon').value    = cat.icon;
  document.getElementById('editCatColor').value   = cat.color;
  document.getElementById('editCatDesc').value    = cat.description || '';
  document.getElementById('editCatActive').value  = cat.is_active;
  document.getElementById('editCatModal').style.display = 'flex';
}
<?php if (!empty($errors)): ?>
document.getElementById('createModal').style.display='flex';
<?php endif; ?>
</script>
</div></div>
