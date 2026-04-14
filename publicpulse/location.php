<?php
/**
 * PublicPulse AI — Location Intelligence Map
 * Interactive map with complaint pins, clustering, filters, ward heatmap
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/analytics.php';
require_once __DIR__ . '/includes/layout.php';

sessionStart();
requireRole(['admin', 'analyst', 'citizen']);
$user = currentUser();

// Build complaint data for map
$statusF   = getVal('status');
$priorityF = getVal('priority');
$categoryF = getVal('category');

$where  = 'WHERE 1=1';
$params = [];
if ($statusF)   { $where .= ' AND c.status=?';      $params[] = $statusF; }
if ($priorityF) { $where .= ' AND c.priority=?';    $params[] = $priorityF; }
if ($categoryF) { $where .= ' AND c.category_id=?'; $params[] = (int)$categoryF; }

// Complaints WITH coordinates
$mappedComplaints = Database::fetchAll("
    SELECT c.id, c.reference_no, c.title, c.status, c.priority,
           c.latitude, c.longitude, c.location, c.ward, c.created_at,
           cat.name AS category_name, cat.color AS category_color, cat.icon AS category_icon
    FROM complaints c JOIN categories cat ON cat.id=c.category_id
    {$where}
    AND c.latitude IS NOT NULL AND c.longitude IS NOT NULL
    ORDER BY c.created_at DESC
", $params);

// ALL complaints for ward stats (even those without GPS)
$allComplaints = Database::fetchAll("
    SELECT c.id, c.reference_no, c.title, c.status, c.priority,
           c.latitude, c.longitude, c.location, c.ward, c.created_at,
           cat.name AS category_name, cat.color AS category_color
    FROM complaints c JOIN categories cat ON cat.id=c.category_id
    {$where}
    ORDER BY c.created_at DESC
    LIMIT 500
", $params);

$categories = Database::fetchAll('SELECT id,name FROM categories WHERE is_active=1 ORDER BY name');
$wardSummary= Analytics::getWardSummary();

$totalMapped = count($mappedComplaints);
$totalAll    = count($allComplaints);

// Status colors for markers
$statusColors = [
    'submitted'   => '#3b82f6',
    'in_review'   => '#d97706',
    'in_progress' => '#7c3aed',
    'resolved'    => '#059669',
    'closed'      => '#94a3b8',
];

$priorityColors = [
    'critical' => '#dc2626',
    'high'     => '#ea580c',
    'medium'   => '#3b82f6',
    'low'      => '#94a3b8',
];

echo renderHead('Location Map', [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
    'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
]);
echo '<div class="app-shell">';
echo renderSidebar('location', $user);
echo '<div class="main-content">';
echo renderTopbar('Location Intelligence', 'Interactive complaint map with GPS coordinates', [
    ['href' => APP_URL.'/admin/complaints.php', 'icon' => 'inbox',    'label' => 'All Complaints', 'type' => 'secondary'],
    ['href' => APP_URL.'/api/export.php?format=csv', 'icon' => 'download', 'label' => 'Export',   'type' => 'secondary'],
]);
?>
<div class="page-content" style="padding-bottom:0">
  <?= flashMsg() ?>

  <div class="page-header" style="margin-bottom:var(--space-md)">
    <div>
      <div class="page-title">Location Intelligence Map</div>
      <div class="page-subtitle">
        <?= $totalMapped ?> complaints with GPS coordinates · <?= $totalAll ?> total matching
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;font-size:.78rem">
      <span class="live-dot"></span>
      <span style="color:var(--success);font-weight:600">Live Map</span>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" id="mapFilters">
    <div class="filter-bar" style="margin-bottom:var(--space-md)">
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach(['submitted','in_review','in_progress','resolved','closed'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="priority" onchange="this.form.submit()">
        <option value="">All Priorities</option>
        <?php foreach(['critical','high','medium','low'] as $p): ?>
          <option value="<?= $p ?>" <?= $priorityF===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="category" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (int)$categoryF===(int)$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if($statusF||$priorityF||$categoryF): ?>
        <a href="<?= APP_URL ?>/location.php" class="btn btn-secondary btn-sm"><?= icon('x') ?> Clear</a>
      <?php endif; ?>
      <div style="margin-left:auto;display:flex;gap:6px">
        <button type="button" id="btnCluster" class="btn btn-primary btn-sm" onclick="toggleClustering()">
          <?= icon('layers') ?> Cluster On
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="map.locate({setView:true,maxZoom:14})">
          <?= icon('crosshairs') ?> My Location
        </button>
      </div>
    </div>
  </form>

  <!-- Map + Sidebar layout -->
  <div style="display:grid;grid-template-columns:1fr 300px;gap:var(--space-md);height:calc(100vh - 280px);min-height:480px">

    <!-- Map -->
    <div class="card" style="overflow:hidden;border-radius:var(--radius-lg)">
      <div id="map" style="width:100%;height:100%;min-height:480px"></div>
    </div>

    <!-- Right panel -->
    <div style="display:flex;flex-direction:column;gap:var(--space-md);overflow-y:auto">

      <!-- Legend -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('info') ?> Legend</div></div>
        <div class="card-body" style="padding:var(--space-md)">
          <div style="font-size:.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">By Priority</div>
          <?php foreach(['critical'=>'#dc2626','high'=>'#ea580c','medium'=>'#3b82f6','low'=>'#94a3b8'] as $p=>$col): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:.8rem">
              <div style="width:12px;height:12px;border-radius:50%;background:<?= $col ?>;flex-shrink:0;border:2px solid #fff;box-shadow:0 0 0 1px <?= $col ?>"></div>
              <?= ucfirst($p) ?>
            </div>
          <?php endforeach; ?>
          <div class="divider" style="margin:10px 0"></div>
          <div style="font-size:.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Map Controls</div>
          <div style="font-size:.76rem;color:var(--text-secondary);line-height:1.7">
            <div>• Click marker to view complaint</div>
            <div>• Cluster groups nearby complaints</div>
            <div>• Click cluster to zoom in</div>
            <div>• Use filters to narrow results</div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('chart-bar') ?> Map Stats</div></div>
        <div class="card-body" style="padding:var(--space-md)">
          <div class="stat-row"><span class="stat-label">GPS Pinned</span><span class="stat-value"><?= $totalMapped ?></span></div>
          <div class="stat-row"><span class="stat-label">No GPS</span><span class="stat-value"><?= $totalAll - $totalMapped ?></span></div>
          <?php
          $byStat = array_count_values(array_column($mappedComplaints,'status'));
          foreach(['submitted','in_review','in_progress','resolved','closed'] as $s):
            if(!isset($byStat[$s])||!$byStat[$s]) continue;
          ?>
            <div class="stat-row">
              <span class="stat-label"><?= statusBadge($s) ?></span>
              <span class="stat-value"><?= $byStat[$s] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Ward hotspots -->
      <?php if (!empty($wardSummary)): ?>
      <div class="card">
        <div class="card-header"><div class="card-title"><?= icon('fire') ?> Ward Hotspots</div></div>
        <div class="card-body no-pad">
          <?php foreach(array_slice($wardSummary,0,8) as $w):
            $heatPct = min(100, (int)(($w['critical']/$w['total'])*70 + ($w['total']/30)*30));
            $col = $heatPct >= 60 ? 'var(--danger)' : ($heatPct >= 30 ? 'var(--warning)' : 'var(--success)');
          ?>
            <div style="padding:8px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
              <div style="flex:1;min-width:0">
                <div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($w['ward']) ?></div>
                <div class="progress-wrap" style="height:4px;margin-top:4px"><div class="progress-fill" style="width:<?= $heatPct ?>%;background:<?= $col ?>"></div></div>
              </div>
              <div style="text-align:right;flex-shrink:0">
                <div style="font-family:var(--font-mono);font-size:.78rem;font-weight:700"><?= $w['total'] ?></div>
                <?php if($w['critical']>0): ?><div style="font-size:.65rem;color:var(--danger);font-family:var(--font-mono)"><?= $w['critical'] ?> crit</div><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Unmapped complaints -->
      <?php
      $unmapped = array_filter($allComplaints, fn($c) => !$c['latitude'] && !$c['longitude']);
      if (!empty($unmapped)):
      ?>
      <div class="card">
        <div class="card-header">
          <div class="card-title"><?= icon('map-pin') ?> No GPS (<?= count($unmapped) ?>)</div>
        </div>
        <div class="card-body" style="padding:var(--space-sm)">
          <div style="max-height:200px;overflow-y:auto">
            <?php foreach(array_slice($unmapped,0,10) as $c): ?>
              <a href="<?= APP_URL ?>/admin/complaint_view.php?id=<?= $c['id'] ?>"
                 style="display:block;padding:7px 8px;font-size:.75rem;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text-primary);border-radius:4px;transition:background var(--transition)"
                 onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
                <div style="font-weight:600;font-family:var(--font-mono);color:var(--primary);font-size:.68rem"><?= htmlspecialchars($c['reference_no']) ?></div>
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($c['title']) ?></div>
                <div style="color:var(--text-muted);font-size:.68rem"><?= htmlspecialchars($c['location']??'No location set') ?></div>
              </a>
            <?php endforeach; ?>
            <?php if(count($unmapped)>10): ?>
              <div style="padding:8px;text-align:center;font-size:.75rem;color:var(--text-muted)">+<?= count($unmapped)-10 ?> more</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /right panel -->
  </div><!-- /grid -->

  <div style="height:var(--space-xl)"></div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// ── Map Data ────────────────────────────────────────────────────────
const complaints = <?= json_encode($mappedComplaints) ?>;
const priorityColors = <?= json_encode($priorityColors) ?>;
const statusColors   = <?= json_encode($statusColors) ?>;

// ── Init Map ─────────────────────────────────────────────────────────
const map = L.map('map', { zoomControl: true, scrollWheelZoom: true }).setView([-25.7479, 28.2293], 10);

// Tile layer — OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  maxZoom: 19,
}).addTo(map);

// ── Marker Cluster ──────────────────────────────────────────────────
let clusterGroup  = L.markerClusterGroup({ maxClusterRadius: 50, showCoverageOnHover: false });
let markersLayer  = L.layerGroup();
let usingCluster  = true;
let allMarkers    = [];

function makeIcon(color) {
  return L.divIcon({
    className: '',
    html: `<div style="width:14px;height:14px;border-radius:50%;background:${color};border:2.5px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.3)"></div>`,
    iconSize: [14,14], iconAnchor: [7,7], popupAnchor: [0,-7]
  });
}

function buildPopup(c) {
  const statusLabel = c.status.replace(/_/g,' ').replace(/\b\w/g,ch=>ch.toUpperCase());
  const priColor    = priorityColors[c.priority]  || '#94a3b8';
  const viewUrl     = PP_ROLE === 'citizen'
    ? `${PP_URL}/citizen/complaint_detail.php?id=${c.id}`
    : `${PP_URL}/admin/complaint_view.php?id=${c.id}`;

  return `
    <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:220px;max-width:260px">
      <div style="font-size:.68rem;color:#3b82f6;font-weight:700;font-family:'JetBrains Mono',monospace;margin-bottom:4px">${c.reference_no}</div>
      <div style="font-size:.85rem;font-weight:700;color:#0f172a;margin-bottom:6px;line-height:1.3">${c.title}</div>
      <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px">
        <span style="padding:2px 8px;border-radius:10px;font-size:.65rem;font-weight:700;font-family:monospace;background:${priColor}22;color:${priColor};text-transform:uppercase">${c.priority}</span>
        <span style="padding:2px 8px;border-radius:10px;font-size:.65rem;font-weight:600;font-family:monospace;background:#f1f5f9;color:#64748b;text-transform:uppercase">${statusLabel}</span>
      </div>
      <div style="font-size:.75rem;color:#64748b;margin-bottom:2px">
        <strong>Category:</strong> ${c.category_name}
      </div>
      ${c.location ? `<div style="font-size:.75rem;color:#64748b;margin-bottom:2px"><strong>Location:</strong> ${c.location}</div>` : ''}
      ${c.ward     ? `<div style="font-size:.75rem;color:#64748b;margin-bottom:8px"><strong>Ward:</strong> ${c.ward}</div>` : ''}
      <div style="font-size:.68rem;color:#94a3b8;margin-bottom:10px">${new Date(c.created_at).toLocaleDateString('en-ZA',{year:'numeric',month:'short',day:'numeric'})}</div>
      <a href="${viewUrl}" style="display:block;text-align:center;padding:6px 12px;background:#1d4ed8;color:#fff;border-radius:6px;font-size:.76rem;font-weight:600;text-decoration:none">View Complaint</a>
    </div>`;
}

function buildMarkers() {
  clusterGroup.clearLayers();
  markersLayer.clearLayers();
  allMarkers = [];

  complaints.forEach(c => {
    if (!c.latitude || !c.longitude) return;
    const col = priorityColors[c.priority] || '#94a3b8';
    const marker = L.marker([parseFloat(c.latitude), parseFloat(c.longitude)], { icon: makeIcon(col) });
    marker.bindPopup(buildPopup(c), { maxWidth: 280, className: 'leaflet-popup-pp' });
    allMarkers.push(marker);
    clusterGroup.addLayer(marker);
    markersLayer.addLayer(marker.bindPopup(buildPopup(c)));
  });

  if (usingCluster) {
    map.addLayer(clusterGroup);
  } else {
    map.addLayer(markersLayer);
  }

  // Fit bounds if markers exist
  if (allMarkers.length > 0) {
    const group = usingCluster ? clusterGroup : markersLayer;
    const bounds = group.getBounds ? group.getBounds() : null;
    if (bounds && bounds.isValid()) map.fitBounds(bounds, { padding: [30,30] });
  }
}

function toggleClustering() {
  usingCluster = !usingCluster;
  const btn = document.getElementById('btnCluster');
  if (usingCluster) {
    map.removeLayer(markersLayer);
    map.addLayer(clusterGroup);
    btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg> Cluster On`;
  } else {
    map.removeLayer(clusterGroup);
    map.addLayer(markersLayer);
    btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg> Cluster Off`;
    btn.style.background = 'var(--bg-surface)';
    btn.style.color = 'var(--text-secondary)';
    btn.style.borderColor = 'var(--border)';
  }
}

// Leaflet popup custom style
const style = document.createElement('style');
style.textContent = `.leaflet-popup-pp .leaflet-popup-content-wrapper{border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.15);border:1px solid #e2e8f0}.leaflet-popup-pp .leaflet-popup-content{margin:14px}.leaflet-popup-pp .leaflet-popup-tip{background:#fff}`;
document.head.appendChild(style);

// ── No complaints notice ────────────────────────────────────────────
if (complaints.length === 0) {
  const notice = document.createElement('div');
  notice.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px 32px;text-align:center;z-index:1000;box-shadow:0 4px 24px rgba(0,0,0,.1)';
  notice.innerHTML = '<div style="font-size:2rem;margin-bottom:8px">📍</div><h3 style="font-weight:700;margin-bottom:6px;font-size:.95rem">No GPS Complaints</h3><p style="color:#64748b;font-size:.82rem">No complaints with GPS coordinates match your current filters.</p>';
  document.getElementById('map').appendChild(notice);
}

// ── Initialize ──────────────────────────────────────────────────────
buildMarkers();

// User location
map.on('locationfound', e => {
  L.circle(e.latlng, { color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.15, radius: e.accuracy }).addTo(map);
  L.marker(e.latlng, {
    icon: L.divIcon({
      className: '',
      html: '<div style="width:16px;height:16px;border-radius:50%;background:#3b82f6;border:3px solid #fff;box-shadow:0 0 0 2px #3b82f6"></div>',
      iconSize: [16,16], iconAnchor: [8,8]
    })
  }).addTo(map).bindPopup('<strong>Your location</strong>').openPopup();
});
map.on('locationerror', () => { toast('Could not get your location. Check browser permissions.', 'warning'); });
</script>

<?= renderFoot() ?>
</div></div>
