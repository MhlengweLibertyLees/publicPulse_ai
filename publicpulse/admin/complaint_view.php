<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();
requireRole('admin');
$csrf = csrfToken();
$user = currentUser();

$id = (int)($_GET['id']??0);
if (!$id) redirect('admin/complaints.php');

$complaint = Database::fetchOne("
    SELECT c.*,u.name AS citizen_name,u.email AS citizen_email,u.phone AS citizen_phone,
           cat.name AS category_name,cat.icon AS category_icon,cat.color AS category_color
    FROM complaints c JOIN users u ON u.id=c.user_id JOIN categories cat ON cat.id=c.category_id
    WHERE c.id=?
", [$id]);
if (!$complaint) redirect('admin/complaints.php');

$logs = Database::fetchAll("
    SELECT sl.*,u.name AS changed_by_name FROM status_logs sl
    JOIN users u ON u.id=sl.changed_by WHERE sl.complaint_id=? ORDER BY sl.created_at ASC
", [$id]);

// Handle status update
if ($_SERVER['REQUEST_METHOD']==='POST' && postVal('action')==='update_status') {
    if (!verifyCsrf(postVal('csrf_token'))) {
        $_SESSION['flash']=['msg'=>'Invalid token.','type'=>'error'];
    } else {
        $newStatus  = postVal('new_status');
        $newPriority= postVal('new_priority');
        $note       = postVal('note');
        $assigned   = (int)postVal('assigned_to')?:(null);

        $vs = ['submitted','in_review','in_progress','resolved','closed'];
        $vp = ['low','medium','high','critical'];

        if (in_array($newStatus,$vs,true) && in_array($newPriority,$vp,true)) {
            Database::execute('UPDATE complaints SET status=?,priority=?,assigned_to=?,updated_at=NOW() WHERE id=?',
                [$newStatus,$newPriority,$assigned,$id]);
            Database::execute('INSERT INTO status_logs (complaint_id,changed_by,old_status,new_status,note) VALUES (?,?,?,?,?)',
                [$id,$user['id'],$complaint['status'],$newStatus,$note]);

            // Notify citizen
            Database::execute('INSERT INTO notifications (user_id,complaint_id,type,title,message) VALUES (?,?,?,?,?)',
                [$complaint['user_id'],$id,'status_update',
                 "Your complaint {$complaint['reference_no']} was updated",
                 'Status changed to: '.ucwords(str_replace('_',' ',$newStatus)).($note?" — {$note}":"")]);

            // Notify admins if critical
            if ($newPriority==='critical' && $complaint['priority']!=='critical') {
                $admins = Database::fetchAll("SELECT id FROM users WHERE role='admin' AND is_active=1 AND id!=?",[$user['id']]);
                foreach ($admins as $a) {
                    Database::execute('INSERT INTO notifications (user_id,complaint_id,type,title,message) VALUES (?,?,?,?,?)',
                        [$a['id'],$id,'priority_escalation',
                         "Critical: {$complaint['reference_no']}",
                         "Complaint \"{$complaint['title']}\" escalated to CRITICAL priority."]);
                }
            }

            $_SESSION['flash']=['msg'=>'Complaint updated successfully.','type'=>'success'];
            redirect("admin/complaint_view.php?id={$id}");
        }
    }
}

$admins = Database::fetchAll("SELECT id,name FROM users WHERE role='admin' AND is_active=1 ORDER BY name");

// Ward stats
$wardStats = null;
if ($complaint['ward']) {
    $wardStats = Database::fetchOne("
        SELECT COUNT(*) AS total,
               SUM(priority='critical') AS critical,
               SUM(status IN ('resolved','closed')) AS resolved
        FROM complaints WHERE ward=? AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)
    ",[$complaint['ward']]);
}

echo renderHead('Complaint: '.$complaint['reference_no']);
echo '<div class="app-shell">';
echo renderSidebar('admin_complaints',$user);
echo '<div class="main-content">';
echo renderTopbar(
    htmlspecialchars($complaint['reference_no']),
    htmlspecialchars(mb_substr($complaint['title'],0,55)),
    [['href'=>APP_URL.'/admin/complaints.php','icon'=>'arrow-left','label'=>'Back','type'=>'secondary']]
);
?>
<div class="page-content">
  <?= flashMsg() ?>

  <div class="grid-3-2" style="align-items:start">

    <!-- LEFT -->
    <div style="display:flex;flex-direction:column;gap:var(--space-lg)">

      <!-- Header -->
      <div class="card">
        <div class="card-body">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md)">
            <div style="flex:1">
              <div style="font-family:var(--font-mono);font-size:.7rem;color:var(--primary);margin-bottom:4px;font-weight:600"><?= htmlspecialchars($complaint['reference_no']) ?></div>
              <h2 style="font-size:1.15rem;font-weight:800;letter-spacing:-.02em;line-height:1.3;margin-bottom:10px"><?= htmlspecialchars($complaint['title']) ?></h2>
              <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                <?= statusBadge($complaint['status']) ?>
                <?= priorityBadge($complaint['priority']) ?>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:.8rem;color:var(--text-secondary)">
                  <span style="width:9px;height:9px;border-radius:50%;background:<?= htmlspecialchars($complaint['category_color']) ?>"></span>
                  <?= htmlspecialchars($complaint['category_name']) ?>
                </span>
                <?php if ($complaint['ai_score']!==null):
                  $ac=$complaint['ai_score']>=75?'critical':($complaint['ai_score']>=50?'high':($complaint['ai_score']>=25?'medium':'low'));
                ?>
                  <span class="ai-score <?= $ac ?>"><?= icon('brain','',11) ?> AI Risk: <?= $complaint['ai_score'] ?>/100</span>
                <?php endif; ?>
              </div>
            </div>
            <div style="text-align:right;font-size:.76rem;color:var(--text-muted)">
              <div>Filed: <?= formatDate($complaint['created_at'],'d M Y H:i') ?></div>
              <div>Updated: <?= timeAgo($complaint['updated_at']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('file-text') ?> Description</div></div>
        <div class="card-body">
          <p style="line-height:1.7;color:var(--text-secondary);font-size:.85rem"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
        </div>
      </div>

      <!-- Meta -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('info') ?> Location & Details</div></div>
        <div class="card-body">
          <div class="grid-2" style="gap:var(--space-md)">
            <?php foreach ([
              ['Location',   $complaint['location']??'Not specified'],
              ['Ward',       $complaint['ward']??'Not specified'],
              ['Coordinates',$complaint['latitude']&&$complaint['longitude']?$complaint['latitude'].', '.$complaint['longitude']:'Not captured'],
              ['Assigned To', $complaint['assigned_to']?(Database::fetchOne('SELECT name FROM users WHERE id=?',[$complaint['assigned_to']])['name']??'Unknown'):'Unassigned'],
            ] as [$label,$val]): ?>
              <div style="background:var(--bg-elevated);border-radius:var(--radius-sm);padding:var(--space-md)">
                <div style="font-family:var(--font-mono);font-size:.62rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:4px"><?= $label ?></div>
                <div style="font-size:.84rem;font-weight:500"><?= htmlspecialchars($val) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if ($complaint['image_path']): ?>
            <div style="margin-top:var(--space-md)">
              <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);margin-bottom:8px">Attached Photo</div>
              <img src="<?= UPLOAD_URL.htmlspecialchars($complaint['image_path']) ?>" alt="Complaint photo"
                   style="max-width:100%;max-height:280px;object-fit:cover;border-radius:var(--radius-md);border:1px solid var(--border)">
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Citizen info -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('user') ?> Citizen Details</div></div>
        <div class="card-body">
          <div style="display:flex;align-items:center;gap:var(--space-md)">
            <div class="avatar lg"><?= strtoupper(mb_substr($complaint['citizen_name'],0,1)) ?></div>
            <div>
              <div style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($complaint['citizen_name']) ?></div>
              <div style="font-size:.8rem;color:var(--text-muted)"><?= htmlspecialchars($complaint['citizen_email']) ?></div>
              <?php if ($complaint['citizen_phone']): ?><div style="font-size:.8rem;color:var(--text-muted)"><?= htmlspecialchars($complaint['citizen_phone']) ?></div><?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('activity') ?> Status Timeline</div></div>
        <div class="card-body">
          <ul class="timeline">
            <li class="timeline-item">
              <div class="timeline-dot"><?= icon('plus','',10) ?></div>
              <div class="timeline-content">
                <div class="timeline-status">Complaint Submitted</div>
                <div class="timeline-meta"><?= formatDate($complaint['created_at'],'d M Y H:i') ?> — <?= htmlspecialchars($complaint['citizen_name']) ?></div>
              </div>
            </li>
            <?php foreach ($logs as $log): ?>
              <li class="timeline-item">
                <div class="timeline-dot"><?= icon('arrow-right','',10) ?></div>
                <div class="timeline-content">
                  <div class="timeline-status"><?= statusBadge($log['new_status']) ?></div>
                  <div class="timeline-meta"><?= formatDate($log['created_at'],'d M Y H:i') ?> — <?= htmlspecialchars($log['changed_by_name']) ?></div>
                  <?php if ($log['note']): ?><div class="timeline-note"><?= htmlspecialchars($log['note']) ?></div><?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
              <li class="timeline-item">
                <div class="timeline-dot" style="border-color:var(--border)"><?= icon('clock','',10) ?></div>
                <div class="timeline-content">
                  <div class="timeline-status" style="color:var(--text-muted)">Awaiting review...</div>
                </div>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- RIGHT -->
    <div style="display:flex;flex-direction:column;gap:var(--space-lg)">

      <!-- Update form -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('edit') ?> Update Complaint</div></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action"     value="update_status">

            <div class="form-group">
              <label class="form-label required">Status</label>
              <select name="new_status" class="form-control" required>
                <?php foreach (['submitted'=>'Submitted','in_review'=>'In Review','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= $complaint['status']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label required">Priority</label>
              <select name="new_priority" class="form-control" required>
                <?php foreach (['low','medium','high','critical'] as $p): ?>
                  <option value="<?= $p ?>" <?= $complaint['priority']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Assign To</label>
              <select name="assigned_to" class="form-control">
                <option value="">— Unassigned —</option>
                <?php foreach ($admins as $a): ?>
                  <option value="<?= $a['id'] ?>" <?= (int)$complaint['assigned_to']===(int)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Note / Response to Citizen</label>
              <textarea name="note" class="form-control" data-maxlength="500" placeholder="Describe what action is being taken. This will be visible to the citizen."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
              <?= icon('save') ?> Update Complaint
            </button>
          </form>
        </div>
      </div>

      <!-- Ward Intelligence -->
      <?php if ($wardStats): ?>
        <div class="card">
          <div class="card-header"><div class="card-title"><?= icon('map-pin') ?> Ward Intelligence</div></div>
          <div class="card-body">
            <div style="font-size:.76rem;color:var(--text-muted);margin-bottom:var(--space-md)"><?= htmlspecialchars($complaint['ward']) ?> · Last 30 days</div>
            <div class="grid-3" style="gap:8px;text-align:center">
              <div style="background:var(--bg-elevated);border-radius:var(--radius-md);padding:var(--space-md)">
                <div style="font-size:1.4rem;font-weight:800;color:var(--primary)"><?= $wardStats['total'] ?></div>
                <div style="font-size:.68rem;color:var(--text-muted)">Total</div>
              </div>
              <div style="background:var(--danger-bg);border-radius:var(--radius-md);padding:var(--space-md)">
                <div style="font-size:1.4rem;font-weight:800;color:var(--danger)"><?= $wardStats['critical'] ?></div>
                <div style="font-size:.68rem;color:var(--text-muted)">Critical</div>
              </div>
              <div style="background:var(--success-bg);border-radius:var(--radius-md);padding:var(--space-md)">
                <div style="font-size:1.4rem;font-weight:800;color:var(--success)"><?= $wardStats['resolved'] ?></div>
                <div style="font-size:.68rem;color:var(--text-muted)">Resolved</div>
              </div>
            </div>
            <?php if ((int)$wardStats['total']>=3): ?>
              <div class="insight-card warning" style="margin-top:var(--space-md)">
                <div class="insight-icon"><?= icon('fire') ?></div>
                <div class="insight-body">
                  <div class="insight-title">Hotspot Alert</div>
                  <div class="insight-desc"><?= $wardStats['total'] ?> complaints in this ward in 30 days. Possible infrastructure issue.</div>
                </div>
              </div>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/admin/complaints.php?ward=<?= urlencode($complaint['ward']) ?>"
               class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;margin-top:var(--space-md)">
              <?= icon('inbox') ?> View All Ward Complaints
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Quick actions -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('zap') ?> Quick Actions</div></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
          <a href="<?= APP_URL ?>/admin/complaints.php?category=<?= $complaint['category_id'] ?>" class="btn btn-secondary btn-sm">
            <?= icon('tag') ?> All <?= htmlspecialchars($complaint['category_name']) ?> Complaints
          </a>
          <a href="<?= APP_URL ?>/api/export.php?format=csv&ward=<?= urlencode($complaint['ward']??'') ?>" class="btn btn-secondary btn-sm">
            <?= icon('download') ?> Export Ward Data
          </a>
          <?php if ($complaint['image_path']): ?>
            <a href="<?= UPLOAD_URL.htmlspecialchars($complaint['image_path']) ?>" target="_blank" class="btn btn-secondary btn-sm">
              <?= icon('image') ?> View Full Image
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?= renderFoot() ?>
</div></div>
