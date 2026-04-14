<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('citizen');
$user = currentUser();
$uid  = $user['id'];

$total    = (int)Database::fetchScalar('SELECT COUNT(*) FROM complaints WHERE user_id=?',[$uid]);
$open     = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE user_id=? AND status NOT IN ('resolved','closed')",[$uid]);
$resolved = (int)Database::fetchScalar("SELECT COUNT(*) FROM complaints WHERE user_id=? AND status='resolved'",[$uid]);
$unread   = (int)Database::fetchScalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0',[$uid]);

$recent = Database::fetchAll("
    SELECT c.id,c.reference_no,c.title,c.status,c.priority,c.updated_at,c.created_at,
           cat.name AS category_name,cat.icon AS cat_icon,cat.color AS cat_color
    FROM complaints c JOIN categories cat ON cat.id=c.category_id
    WHERE c.user_id=? ORDER BY c.updated_at DESC LIMIT 6
",[$uid]);

echo renderHead('My Dashboard');
echo '<div class="app-shell">';
echo renderSidebar('citizen_dashboard',$user);
echo '<div class="main-content">';
echo renderTopbar('My Dashboard','Your complaint overview',[
    ['href'=>APP_URL.'/citizen/submit.php','icon'=>'plus','label'=>'Submit Complaint','type'=>'primary'],
]);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <?php if ($unread > 0): ?>
    <div class="alert alert-info" style="margin-bottom:var(--space-lg)">
      <?= icon('bell') ?>
      <div>You have <strong><?= $unread ?> unread notification<?= $unread>1?'s':'' ?></strong>.
        <a href="<?= APP_URL ?>/citizen/notifications.php" style="font-weight:600;color:var(--primary);margin-left:6px">View now</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <div class="page-title">Welcome back, <?= htmlspecialchars(explode(' ',$user['name'])[0]) ?></div>
      <div class="page-subtitle">Track your complaints and receive updates · <?= date('d F Y') ?></div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="kpi-card blue">
      <div class="kpi-header"><div class="kpi-label">Total Filed</div><div class="kpi-icon"><?= icon('inbox') ?></div></div>
      <div class="kpi-value" data-counter="<?= $total ?>"><?= $total ?></div>
      <div class="kpi-change"><?= icon('activity') ?> All time</div>
    </div>
    <div class="kpi-card yellow">
      <div class="kpi-header"><div class="kpi-label">In Progress</div><div class="kpi-icon"><?= icon('clock') ?></div></div>
      <div class="kpi-value" data-counter="<?= $open ?>"><?= $open ?></div>
      <div class="kpi-change"><?= icon('activity') ?> Being processed</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-header"><div class="kpi-label">Resolved</div><div class="kpi-icon"><?= icon('check-circle') ?></div></div>
      <div class="kpi-value" data-counter="<?= $resolved ?>"><?= $resolved ?></div>
      <div class="kpi-change up"><?= icon('trending-up') ?> Completed</div>
    </div>
    <div class="kpi-card purple">
      <div class="kpi-header"><div class="kpi-label">Notifications</div><div class="kpi-icon"><?= icon('bell') ?></div></div>
      <div class="kpi-value" data-counter="<?= $unread ?>"><?= $unread ?></div>
      <div class="kpi-change"><?= icon('bell') ?> Unread</div>
    </div>
  </div>

  <div class="grid-3-2" style="align-items:start">
    <!-- Recent complaints -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('clock') ?> Recent Complaints</div>
        <a href="<?= APP_URL ?>/citizen/complaints.php" style="font-size:.75rem;color:var(--primary);font-weight:600">View all</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="empty-state">
          <div class="empty-icon"><?= icon('inbox') ?></div>
          <h3>No complaints yet</h3>
          <p>Submit your first complaint to report issues in your community.</p>
          <a href="<?= APP_URL ?>/citizen/submit.php" class="btn btn-primary" style="margin-top:var(--space-md)"><?= icon('plus') ?> Submit Complaint</a>
        </div>
      <?php else: ?>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($recent as $c): ?>
            <a href="<?= APP_URL ?>/citizen/complaint_detail.php?id=<?= $c['id'] ?>" class="complaint-card">
              <div style="display:flex;align-items:center;gap:12px">
                <div style="width:38px;height:38px;border-radius:var(--radius-sm);background:<?= htmlspecialchars($c['cat_color']) ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= htmlspecialchars($c['cat_color']) ?>">
                  <?= icon($c['cat_icon']??'tag') ?>
                </div>
                <div style="flex:1;min-width:0">
                  <div style="font-family:var(--font-mono);font-size:.65rem;color:var(--primary);font-weight:600"><?= htmlspecialchars($c['reference_no']) ?></div>
                  <div style="font-weight:700;font-size:.87rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px"><?= htmlspecialchars($c['title']) ?></div>
                  <div style="font-size:.73rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($c['category_name']) ?> · Updated <?= timeAgo($c['updated_at']) ?></div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
                  <?= statusBadge($c['status']) ?>
                  <?= priorityBadge($c['priority']) ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right panel -->
    <div style="display:flex;flex-direction:column;gap:var(--space-lg)">
      <!-- Quick actions -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('zap') ?> Quick Actions</div></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ([
            ['href'=>APP_URL.'/citizen/submit.php',       'icon'=>'plus-circle', 'label'=>'Submit New Complaint',  'color'=>'var(--primary)'],
            ['href'=>APP_URL.'/citizen/track.php',        'icon'=>'search',      'label'=>'Track by Reference',    'color'=>'var(--accent)'],
            ['href'=>APP_URL.'/citizen/complaints.php',   'icon'=>'list',        'label'=>'View All My Complaints','color'=>'var(--success)'],
            ['href'=>APP_URL.'/citizen/notifications.php','icon'=>'bell',        'label'=>'View Notifications',    'color'=>'var(--warning)'],
          ] as $action): ?>
            <a href="<?= $action['href'] ?>" style="display:flex;align-items:center;gap:10px;padding:10px var(--space-md);background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-md);text-decoration:none;color:var(--text-primary);font-size:.83rem;font-weight:600;transition:all var(--transition)"
               onmouseover="this.style.borderColor='var(--primary-light)';this.style.background='var(--primary-bg)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--bg-elevated)'">
              <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:<?= $action['color'] ?>18;display:flex;align-items:center;justify-content:center;color:<?= $action['color'] ?>;flex-shrink:0">
                <?= icon($action['icon']) ?>
              </div>
              <?= $action['label'] ?>
              <span style="margin-left:auto;color:var(--text-muted)"><?= icon('chevron-right','',13) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- How it works -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('info') ?> How It Works</div></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
          <?php foreach ([
            ['1','Submit','Report any public service issue in your area with photos and location.'],
            ['2','Track','Use your reference number to track progress at any time.'],
            ['3','Resolved','Receive notifications as staff review and resolve your complaint.'],
          ] as [$num,$title,$desc]): ?>
            <div style="display:flex;gap:12px;align-items:flex-start">
              <div style="width:28px;height:28px;border-radius:50%;background:var(--primary-bg);border:2px solid var(--primary-border);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:var(--primary);flex-shrink:0"><?= $num ?></div>
              <div>
                <div style="font-weight:700;font-size:.83rem"><?= $title ?></div>
                <div style="font-size:.76rem;color:var(--text-muted);margin-top:2px"><?= $desc ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?= renderFoot() ?>
</div></div>
