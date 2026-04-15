<?php
session_start();
require_once 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($_SESSION['role'] ?? 'Farmer', ENT_QUOTES, 'UTF-8');

require_once 'sensor_status.php';

$upcoming_schedules = [];
$user_id = $_SESSION['user_id'];
$sched_result = $conn->query("
    SELECT id, schedule_date, task_type, task_name, task_time, task_notes 
    FROM farm_schedules 
    WHERE user_id = $user_id AND schedule_date >= CURDATE() 
    ORDER BY schedule_date, task_time 
    LIMIT 5
");
if ($sched_result && $sched_result->num_rows > 0) {
    while ($row = $sched_result->fetch_assoc()) {
        $upcoming_schedules[] = $row;
    }
}

$dash_cal_months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$dash_current_month = $dash_cal_months[date('n') - 1] . ' ' . date('Y');

$legend_counts = ['irrigation' => 0, 'fertilization' => 0, 'harvest' => 0, 'maintenance' => 0];
$legend_result = $conn->query("SELECT task_type, COUNT(*) as cnt FROM farm_schedules WHERE user_id = $user_id AND schedule_date >= CURDATE() AND schedule_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) GROUP BY task_type");
if ($legend_result && $legend_result->num_rows > 0) {
    while ($row = $legend_result->fetch_assoc()) {
        if (isset($legend_counts[$row['task_type']])) {
            $legend_counts[$row['task_type']] = (int) $row['cnt'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Tomato Cultivation System</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/index.css">
  <script src="notifications.js"></script>

</head>
<body>
<div class="layout">

  <?php
    $params = new \stdClass();
    $params->view = $_GET['view'] ?? 'home';
    $active_page = $params->view === 'dashboard' ? 'dashboard' : 'home';
    include 'sidebar.php';
  ?>

  <div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-title" id="topbar-title">
        <i class="fas fa-seedling"></i>
        Solar IoT Cultivation System
      </div>
      <div class="topbar-clock" id="clock">—</div>
    </div>

    <!-- ══ HOME VIEW ══ -->
    <div class="page-view <?= ($active_page === 'home') ? 'active' : '' ?>" id="view-home">
      <div class="landing-hero">
        <div class="landing-bg-grid"></div>
        <div class="landing-tomato-bg">🍅</div>
        <div class="landing-content">
          <div class="landing-eyebrow">Solar IoT Cultivation</div>
          <h1 class="landing-title">Smart <span class="accent">Tomato</span><br>Farm Control <span class="tomato-emoji">🍅</span></h1>
          <p class="landing-desc">Solar-powered IoT automation for precision irrigation, fertilizer application, and real-time sensor monitoring. Grow smarter, grow greener.</p>
          <div class="landing-stats">
            <div class="landing-stat">
              <div class="landing-stat-num green">12</div>
              <div class="landing-stat-lbl">Sensors Online</div>
            </div>
            <div class="landing-stat-divider"></div>
            <div class="landing-stat">
              <div class="landing-stat-num yellow">85<span style="font-size:1.2rem;">kW</span></div>
              <div class="landing-stat-lbl">Solar Output</div>
            </div>
            <div class="landing-stat-divider"></div>
            <div class="landing-stat">
              <div class="landing-stat-num blue">68<span style="font-size:1.2rem;">%</span></div>
              <div class="landing-stat-lbl">Soil Moisture</div>
            </div>
            <div class="landing-stat-divider"></div>
            <div class="landing-stat">
              <div class="landing-stat-num" style="color:#ff9090;">78<span style="font-size:1.2rem;">%</span></div>
              <div class="landing-stat-lbl">Battery</div>
            </div>
          </div>
          <div class="landing-actions">
            <a href="index.php?view=dashboard" class="btn-landing-primary"><i class="fas fa-gauge-high"></i> Open Dashboard</a>
            <a href="monitor.php" class="btn-landing-ghost"><i class="fas fa-wifi"></i> View Sensors</a>
          </div>
          <div class="landing-features">
            <div class="landing-feature-card">
              <div class="feature-icon solar"><i class="fas fa-solar-panel"></i></div>
              <div class="feature-card-title">Solar Powered</div>
              <div class="feature-card-desc">100% renewable energy with battery backup</div>
            </div>
            <div class="landing-feature-card">
              <div class="feature-icon water"><i class="fas fa-water"></i></div>
              <div class="feature-card-title">Smart Irrigation</div>
              <div class="feature-card-desc">Moisture-triggered automated watering</div>
            </div>
            <div class="landing-feature-card">
              <div class="feature-icon green"><i class="fas fa-spray-can"></i></div>
              <div class="feature-card-title">Auto Fertilizing</div>
              <div class="feature-card-desc">Scheduled fertilizer application (Ammonium & Complete)</div>
            </div>
            <div class="landing-feature-card">
              <div class="feature-icon red"><i class="fas fa-wifi"></i></div>
              <div class="feature-card-title">IoT Network</div>
              <div class="feature-card-desc">12 sensors monitoring farm live</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ DASHBOARD VIEW ══ -->
    <div class="page-view <?= ($active_page === 'dashboard') ? 'active' : '' ?>" id="view-dashboard">
      <div class="page-content">

        <div class="hero" style="margin-bottom:28px;">
          <div class="hero-tomato">🍅</div>
          <div class="hero-tag">Tomato Cultivation — Farm</div>
          <h1>System <span>Overview</span></h1>
          <p class="hero-sub">Live summary of your solar IoT farm: energy, water, automation, and upcoming tasks — all in one place.</p>
          <div class="hero-chips">
            <div class="chip <?= $errCount > 0 ? 'red' : 'green' ?>">
              <i class="fas fa-wifi"></i>
              <?php if ($errCount > 0): ?>
                <?= $activeCount ?> Online · <?= $errCount ?> Error<?= $errCount > 1 ? 's' : '' ?>
              <?php else: ?>
                <?= $totalSensors ?> Sensors Online
              <?php endif; ?>
            </div>
            <div class="chip yellow"><i class="fas fa-solar-panel"></i> Solar Active — 85 kW</div>
            <div class="chip blue"><i class="fas fa-droplet"></i> Moisture 68%</div>
            <div class="chip red"><i class="fas fa-battery-full"></i> Battery 78%</div>
          </div>
        </div>

        <!-- ALERT -->
        <?php if ($errCount > 0): ?>
        <div class="alert-bar" style="margin-bottom:28px;background:var(--red-pale);border-color:rgba(214,48,49,0.25);color:var(--red);">
          <i class="fas fa-triangle-exclamation" style="color:var(--red);"></i>
          <span><strong><?= $errCount ?> Sensor Error<?= $errCount > 1 ? 's' : '' ?> Detected</strong> — <?= $activeCount ?> of <?= $totalSensors ?> sensors reporting normally. Check System Health for details.</span>
          <a href="monitor.php" style="margin-left:auto;font-size:0.78rem;color:var(--red);font-weight:700;text-decoration:none;white-space:nowrap;">View in Monitoring →</a>
        </div>
        <?php else: ?>
        <div class="alert-bar" style="margin-bottom:28px;">
          <i class="fas fa-circle-check"></i>
          <span><strong>System OK</strong> — All <?= $totalSensors ?> sensors reporting normally. Next irrigation cycle in 2h 14m. Battery charging.</span>
        </div>
        <?php endif; ?>

        <div class="page-section">
          <div class="section-head">
            <div class="section-icon solar"><i class="fas fa-chart-line"></i></div>
            <div class="section-label">Key Metrics</div>
            <div class="section-meta">Live · updates every 5s</div>
          </div>
          <div class="grid-4">
            <div class="stat-card solar">
              <div class="stat-top">
                <div class="stat-icon-wrap solar"><i class="fas fa-solar-panel"></i></div>
                <span class="stat-badge warn">↑ +12%</span>
              </div>
              <div class="stat-value solar">85<span class="stat-unit"> kW</span></div>
              <div class="stat-label">Solar Output</div>
            </div>
            <div class="stat-card water">
              <div class="stat-top">
                <div class="stat-icon-wrap water"><i class="fas fa-battery-half"></i></div>
                <span class="stat-badge info">Charging</span>
              </div>
              <div class="stat-value water">78<span class="stat-unit"> %</span></div>
              <div class="stat-label">Battery Level</div>
            </div>
            <div class="stat-card green">
              <div class="stat-top">
                <div class="stat-icon-wrap green"><i class="fas fa-droplet"></i></div>
                <span class="stat-badge up">Optimal</span>
              </div>
              <div class="stat-value green">68<span class="stat-unit"> %</span></div>
              <div class="stat-label">Avg Soil Moisture</div>
            </div>
            <div class="stat-card red">
              <div class="stat-top">
                <div class="stat-icon-wrap red"><i class="fas fa-temperature-high"></i></div>
                <span class="stat-badge warn">Warm</span>
              </div>
              <div class="stat-value red">28<span class="stat-unit"> °C</span></div>
              <div class="stat-label">Temperature</div>
            </div>
          </div>
        </div>

        <div class="page-section">
          <div class="grid-3">

            <!-- System Health Rings -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-label"><i class="fas fa-heart-pulse"></i> System Health</div>
                <?php if ($errCount > 0): ?>
                  <span class="badge badge-red"><i class="fas fa-triangle-exclamation"></i> <?= $errCount ?> Sensor Error<?= $errCount > 1 ? 's' : '' ?></span>
                <?php else: ?>
                  <span class="badge badge-green">All Good</span>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <div class="status-rings">
                  <div class="status-ring-wrap">
                    <svg class="ring-svg" viewBox="0 0 80 80">
                      <circle class="ring-bg" cx="40" cy="40" r="32"/>
                      <circle class="ring-fill" cx="40" cy="40" r="32" stroke="#F5C842"
                        stroke-dasharray="201" stroke-dashoffset="30"/>
                      <text x="40" y="44" text-anchor="middle" class="ring-val" fill="#B87D00">85%</text>
                    </svg>
                    <div class="ring-label">Solar</div>
                  </div>
                  <div class="status-ring-wrap">
                    <svg class="ring-svg" viewBox="0 0 80 80">
                      <circle class="ring-bg" cx="40" cy="40" r="32"/>
                      <circle class="ring-fill" cx="40" cy="40" r="32" stroke="#72C9EA"
                        stroke-dasharray="201" stroke-dashoffset="43"/>
                      <text x="40" y="44" text-anchor="middle" class="ring-val" fill="#1575A8">78%</text>
                    </svg>
                    <div class="ring-label">Battery</div>
                  </div>
                  <div class="status-ring-wrap">
                    <svg class="ring-svg" viewBox="0 0 80 80">
                      <circle class="ring-bg" cx="40" cy="40" r="32"/>
                      <circle class="ring-fill" cx="40" cy="40" r="32" stroke="#52C78A"
                        stroke-dasharray="201" stroke-dashoffset="64"/>
                      <text x="40" y="44" text-anchor="middle" class="ring-val" fill="#2D8653">68%</text>
                    </svg>
                    <div class="ring-label">Moisture</div>
                  </div>
                </div>

                <!-- Sensor online/error count row -->
                <div class="kv-row">
                  <span class="key"><i class="fas fa-wifi" style="color:var(--green-mid)"></i> Sensors</span>
                  <?php if ($errCount > 0): ?>
                    <span style="display:flex;align-items:center;gap:6px;">
                      <span class="badge badge-green"><?= $activeCount ?> Online</span>
                      <span class="badge badge-red"><?= $errCount ?> Error<?= $errCount > 1 ? 's' : '' ?></span>
                    </span>
                  <?php else: ?>
                    <span class="badge badge-green"><?= $totalSensors ?> / <?= $totalSensors ?> Online</span>
                  <?php endif; ?>
                </div>

                <div class="kv-row"><span class="key"><i class="fas fa-water" style="color:var(--water)"></i> Water Tank</span><span class="val">84%</span></div>
                <div class="kv-row"><span class="key"><i class="fas fa-flask" style="color:var(--solar)"></i> Chem Tank</span><span class="val">75 / 100 L</span></div>
                <div class="kv-row"><span class="key"><i class="fas fa-wind" style="color:var(--text-muted)"></i> Pressure</span><span class="badge badge-green">1013 hPa</span></div>

                <!-- Errored sensor list -->
                <?php if ($errCount > 0): ?>
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border);">
                  <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-triangle-exclamation" style="color:var(--red);"></i> Active Sensor Errors
                  </div>
                  <?php foreach ($erroredSensors as $nodeId => $s): ?>
                  <div style="display:flex;align-items:flex-start;gap:9px;margin-bottom:8px;padding:8px 10px;background:var(--red-pale);border:1px solid rgba(214,48,49,0.18);border-left:3px solid var(--red);border-radius:var(--radius-sm);">
                    <i class="fas fa-circle-xmark" style="color:var(--red);font-size:0.78rem;margin-top:2px;flex-shrink:0;"></i>
                    <div style="flex:1;min-width:0;">
                      <div style="display:flex;align-items:center;gap:7px;margin-bottom:3px;flex-wrap:wrap;">
                        <span style="font-family:var(--font-mono);font-size:0.7rem;font-weight:700;color:var(--red);"><?= htmlspecialchars($nodeId) ?></span>
                        <span style="font-family:var(--font-mono);font-size:0.65rem;background:#ffe0e0;color:var(--red);border-radius:4px;padding:1px 6px;"><?= htmlspecialchars($s['error_code']) ?></span>
                      </div>
                      <div style="font-size:0.72rem;color:var(--text-muted);line-height:1.4;"><?= htmlspecialchars($s['error_note']) ?></div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                  <div style="margin-top:6px;text-align:center;">
                    <a href="monitor.php" style="font-size:0.78rem;color:var(--red);font-weight:600;text-decoration:none;">
                      <i class="fas fa-arrow-right" style="margin-right:5px;"></i>View in Monitoring →
                    </a>
                  </div>
                </div>
                <?php endif; ?>

              </div>
            </div>

            <!-- Quick Navigation -->
            <div class="card">
              <div class="card-head"><div class="card-head-label"><i class="fas fa-rocket"></i> Quick Access</div></div>
              <div class="card-body">
                <a href="monitor.php" class="quick-link">
                  <div class="quick-link-icon" style="background:var(--green-pale);color:var(--green-mid);"><i class="fas fa-wifi"></i></div>
                  <div><div class="quick-link-title">IoT Monitoring</div><div class="quick-link-desc">Live sensor readings & alerts</div></div>
                  <i class="fas fa-arrow-right arr"></i>
                </a>
                <a href="schedule.php" class="quick-link">
                  <div class="quick-link-icon" style="background:var(--water-pale);color:var(--water);"><i class="fas fa-calendar-days"></i></div>
                  <div><div class="quick-link-title">Scheduling</div><div class="quick-link-desc">Irrigation & fertilization calendar</div></div>
                  <i class="fas fa-arrow-right arr"></i>
                </a>
                <a href="mis.php" class="quick-link">
                  <div class="quick-link-icon" style="background:var(--red-pale);color:var(--red);"><i class="fas fa-users"></i></div>
                  <div><div class="quick-link-title">Manage Users</div><div class="quick-link-desc">User access &amp; roles</div></div>
                  <i class="fas fa-arrow-right arr"></i>
                </a>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
              <div class="card-head"><div class="card-head-label"><i class="fas fa-clock-rotate-left"></i> Recent Activity <span id="recent-count">(3 days)</span></div></div>
              <div class="card-body" id="recent-activity-body">
                <div class="empty-state">
                  <i class="fas fa-clock" style="font-size:2rem;color:var(--text-muted);margin-bottom:12px;"></i>
                  <p>Run <a href="migrate_schedules_status.php" style="color:var(--green-mid);font-weight:600;">migration script</a> first, then add completed schedules.</p>
                </div>
              </div>
            </div>

            <script>
            function loadRecentActivities() {
              fetch('get_recent_activities.php')
                .then(r => r.json())
                .then(activities => {
                  const body = document.getElementById('recent-activity-body');
                  const countEl = document.getElementById('recent-count');
                  if (activities.length === 0) {
                    body.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="font-size:2rem;color:var(--text-muted);margin-bottom:12px;"></i><p>No completed activities in last 3 days.</p><p><a href="schedule.php" style="color:var(--green-mid);font-weight:600;">→ Schedule more tasks</a></p></div>';
                    countEl.textContent = '(0)';
                    return;
                  }
                  countEl.textContent = `(${activities.length})`;
                  body.innerHTML = activities.map(act => {
                    const colors = {
                      irrigation: 'var(--water)',
                      fertilization: 'var(--solar)',
                      harvest: 'var(--red)',
                      maintenance: 'var(--green-mid)'
                    };
                    const dateStr = new Date(act.date).toLocaleDateString('en-US', {weekday: 'short'}) + ', ' + new Date(act.date).toLocaleDateString('en-US', {month:'short', day:'numeric'});
                    return `
                      <div class="activity-item">
                        <div class="activity-dot" style="background:${colors[act.type] || 'var(--green-mid)'};box-shadow:0 0 6px ${colors[act.type] || 'var(--green-light)'};"></div>
                        <div class="activity-content">
                          <div class="activity-title">${act.name}</div>
                          <div class="activity-desc">${act.notes}</div>
                          <div class="activity-time">${dateStr}, ${act.time}</div>
                        </div>
                      </div>`;
                  }).join('');
                })
                .catch(() => {
                  document.getElementById('recent-activity-body').innerHTML = '<div class="empty-state"><i class="fas fa-triangle-exclamation" style="color:var(--solar);font-size:2rem;margin-bottom:12px;"></i><p>Failed to load activities.</p></div>';
                });
            }
            if (document.getElementById('view-dashboard') && document.getElementById('view-dashboard').classList.contains('active')) {
              loadRecentActivities();
            }
            document.addEventListener('DOMContentLoaded', () => {
              const observer = new MutationObserver(() => {
                if (document.getElementById('view-dashboard')?.classList.contains('active')) {
                  loadRecentActivities();
                }
              });
              observer.observe(document.body, {childList: true, subtree: true, attributes: true});
            });
            </script>

          </div>
        </div>

        <!-- UPCOMING TASKS + CALENDAR -->
        <div class="page-section">
          <div class="grid-2">

            <!-- Upcoming Tasks -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-label"><i class="fas fa-list-check"></i> Upcoming Tasks</div>
                <a href="schedule.php" style="font-size:0.78rem;color:var(--green-mid);text-decoration:none;font-weight:600;">View All →</a>
              </div>
              <div class="card-body">
                <?php if (empty($upcoming_schedules)): ?>
                <div style="text-align:center;padding:20px 0;color:var(--text-muted);">
                  <i class="fas fa-calendar-xmark" style="font-size:1.5rem;margin-bottom:8px;display:block;opacity:0.5;"></i>
                  <p>No upcoming tasks scheduled.</p>
                </div>
                <?php else: ?>
                  <?php foreach ($upcoming_schedules as $sched): ?>
                  <?php 
                    $dotColor = 'var(--green-mid)';
                    $badgeClass = 'badge-green';
                    $badgeLabel = 'Task';
                    if ($sched['task_type'] === 'fertilization') { $dotColor = 'var(--solar)'; $badgeClass = 'badge-yellow'; $badgeLabel = 'Fertilization'; }
                    elseif ($sched['task_type'] === 'irrigation') { $dotColor = 'var(--water)'; $badgeClass = 'badge-blue'; $badgeLabel = 'Irrigation'; }
                    elseif ($sched['task_type'] === 'harvest') { $dotColor = 'var(--red)'; $badgeClass = 'badge-red'; $badgeLabel = 'Harvest'; }
                    elseif ($sched['task_type'] === 'maintenance') { $dotColor = 'var(--solar-lt)'; $badgeClass = 'badge-yellow'; $badgeLabel = 'Maintenance'; }
                    
                    $schedDate = strtotime($sched['schedule_date']);
                    $dateLabel = date('M j, Y', $schedDate);
                    if (date('Y-m-d') === date('Y-m-d', $schedDate)) $dateLabel = 'Today';
                    elseif (date('Y-m-d', strtotime('+1 day')) === date('Y-m-d', $schedDate)) $dateLabel = 'Tomorrow';
                    
                    $timeLabel = date('g:i A', strtotime($sched['task_time']));
                  ?>
                  <div class="activity-item">
                    <div class="activity-dot" style="background:<?= $dotColor ?>;box-shadow:0 0 6px <?= $dotColor ?>;"></div>
                    <div class="activity-content">
                      <div class="activity-title"><?= htmlspecialchars($sched['task_name']) ?></div>
                      <div class="activity-desc"><?= htmlspecialchars($sched['task_notes'] ?: $sched['task_type']) ?></div>
                      <div class="activity-time"><?= $dateLabel ?>, <?= $timeLabel ?></div>
                    </div>
                    <span class="badge <?= $badgeClass ?>" style="margin-top:3px;"><?= $badgeLabel ?></span>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <div style="margin-top:16px;text-align:center;">
                  <a href="schedule.php" style="font-size:0.84rem;color:var(--green-mid);text-decoration:none;font-weight:600;"><i class="fas fa-plus" style="margin-right:6px;"></i>Add New Task</a>
                </div>
              </div>
            </div>

            <!-- Mini Calendar -->
            <div class="card">
              <div class="card-head">
                <div style="display:flex;align-items:center;justify-content:space-between;flex:1;">
                  <div class="card-head-label"><i class="fas fa-calendar"></i> <span id="dash-month-label"><?= $dash_current_month ?></span></div>
                  <div class="cal-nav">
                    <button onclick="dashChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <button onclick="dashChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                  </div>
                </div>
              </div>
              <div class="card-body calendar-body">
                <div class="cal-grid" id="dash-cal-grid"></div>
                <div class="cal-legend">
                  <?php if ($legend_counts['irrigation'] > 0): ?>
                  <div class="legend-item"><i class="fas fa-droplet" style="color:var(--water);"></i> Irrigation (<?= $legend_counts['irrigation'] ?>)</div>
                  <?php endif; ?>
                  <?php if ($legend_counts['fertilization'] > 0): ?>
                  <div class="legend-item"><i class="fas fa-spray-can" style="color:var(--solar);"></i> Fertilization (<?= $legend_counts['fertilization'] ?>)</div>
                  <?php endif; ?>
                  <?php if ($legend_counts['harvest'] > 0): ?>
                  <div class="legend-item"><i class="fas fa-basket-shopping" style="color:var(--red);"></i> Harvest (<?= $legend_counts['harvest'] ?>)</div>
                  <?php endif; ?>
                  <?php if ($legend_counts['maintenance'] > 0): ?>
                  <div class="legend-item"><i class="fas fa-wrench" style="color:var(--solar-lt);"></i> Maintenance (<?= $legend_counts['maintenance'] ?>)</div>
                  <?php endif; ?>
                  <?php if ($legend_counts['irrigation'] == 0 && $legend_counts['fertilization'] == 0 && $legend_counts['harvest'] == 0 && $legend_counts['maintenance'] == 0): ?>
                  <div class="legend-item" style="color:var(--text-muted);">No upcoming tasks</div>
                  <?php endif; ?>
                </div>
                <div style="margin-top:14px;text-align:center;">
                  <a href="schedule.php" style="font-size:0.84rem;color:var(--green-mid);font-weight:600;text-decoration:none;"><i class="fas fa-calendar-plus" style="margin-right:6px;"></i>Open Full Calendar</a>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="page-section">
          <div class="section-head">
            <div class="section-icon water"><i class="fas fa-map-location-dot"></i></div>
            <div class="section-label">Farm Status Overview</div>
            <div class="section-meta">1 Farm Active</div>
          </div>
          <div class="grid-1">
            <?php
            $zones = [
              ['name'=>'Main Farm','soil'=>68,'temp'=>28,'status'=>'Optimal','badge'=>'badge-green','water'=>true,'spray'=>false]
            ];
            foreach($zones as $z): ?>
            <div class="card">
              <div class="card-head">
                <div class="card-head-label"><i class="fas fa-map-pin"></i> <?= $z['name'] ?></div>
                <span class="badge <?= $z['badge'] ?>"><?= $z['status'] ?></span>
              </div>
              <div class="card-body">
                <div class="kv-row"><span class="key"><i class="fas fa-droplet" style="color:var(--water)"></i> Soil Moisture</span><span class="val"><?= $z['soil'] ?>%</span></div>
                <div class="progress-wrap"><div class="progress-fill water" style="width:<?= $z['soil'] ?>%"></div></div>
                <div class="kv-row"><span class="key"><i class="fas fa-temperature-half" style="color:var(--red)"></i> Temperature</span><span class="val"><?= $z['temp'] ?>°C</span></div>
                <div class="kv-row"><span class="key"><i class="fas fa-water" style="color:var(--water)"></i> Irrigation</span><span class="badge <?= $z['water'] ? 'badge-blue' : 'badge-muted' ?>"><?= $z['water'] ? 'Active' : 'Idle' ?></span></div>
                <div class="kv-row"><span class="key"><i class="fas fa-spray-can" style="color:var(--solar)"></i> Fertilizing</span><span class="badge <?= $z['spray'] ? 'badge-yellow' : 'badge-muted' ?>"><?= $z['spray'] ? 'Scheduled' : 'None' ?></span></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ══ GSM SMS ALERTS ══ -->
        <div class="page-section" id="gsm-section">
          <div class="section-head">
            <div class="section-icon" style="background:var(--green-pale);color:var(--green-mid);">
              <i class="fas fa-mobile-screen-button"></i>
            </div>
            <div class="section-label">GSM SMS Alerts</div>
            <div class="section-meta">
              <span class="gsm-pulse-dot"></span> Modem Online · SIM Ready
            </div>
          </div>

          <div class="gsm-grid">

            <!-- COMPOSE CARD -->
            <div class="card">
              <div class="card-head">
                <div class="card-head-label"><i class="fas fa-paper-plane"></i> Compose Message</div>
                <span class="badge badge-green"><i class="fas fa-signal"></i> Signal: Good</span>
              </div>
              <div class="card-body">

                <!-- Modem status bar -->
                <div class="gsm-status-bar">
                  <div class="gsm-signal">
                    <span></span><span></span><span></span><span></span>
                  </div>
                  <div class="gsm-status-info">
                    <strong>SIM800L Active</strong>
                    <span>SMART PH · +63</span>
                  </div>
                  <div class="gsm-modem-rssi">RSSI: -72 dBm</div>
                </div>

                <!-- ── RECIPIENT MODE TABS ── -->
                <div class="compose-group">
                  <label class="compose-label"><i class="fas fa-address-book"></i> &nbsp;Send To</label>
                  <div class="gsm-mode-tabs">
                    <button type="button" class="gsm-mode-tab active" id="tab-saved" onclick="gsmSetMode('saved')">
                      <i class="fas fa-users"></i> Saved Contacts
                    </button>
                    <button type="button" class="gsm-mode-tab" id="tab-manual" onclick="gsmSetMode('manual')">
                      <i class="fas fa-keyboard"></i> Unsaved Number
                    </button>
                    <button type="button" class="gsm-mode-tab" id="tab-broadcast" onclick="gsmSetMode('broadcast')">
                      <i class="fas fa-tower-broadcast"></i> Broadcast All
                    </button>
                  </div>
                </div>

                <!-- SAVED CONTACTS panel -->
                <div class="gsm-recipient-panel visible" id="panel-saved">
                  <div class="compose-group" style="margin-bottom:13px;">
                    <select class="compose-select" id="gsm-recipient">
                      <option value="">— Select recipient —</option>
                      <optgroup label="👤 Farm Staff">
                        <option value="+639171234567">Katherine Guzman (Farm Lead) · +63917-123-4567</option>
                        <option value="+639281234567">Raffy Romero (Technician) · +63928-123-4567</option>
                        <option value="+639051234567">Samantha Lumpaodan (Irrigator) · +63905-123-4567</option>
                      </optgroup>
                      <optgroup label="📡 Farm Groups">
                        <option value="zone-a">Farm Alert Group (3 members)</option>
                      </optgroup>
                    </select>
                  </div>
                </div>

                <!-- MANUAL NUMBER panel -->
                <div class="gsm-manual-wrap" id="panel-manual">
                  <div class="compose-group" style="margin-bottom:6px;">
                    <div class="gsm-tags-box" id="gsm-tags-box" onclick="document.getElementById('gsm-tag-input').focus()">
                      <input
                        type="text"
                        id="gsm-tag-input"
                        class="gsm-tag-input"
                        placeholder="+63917… then press Enter or comma"
                        autocomplete="off"
                      />
                    </div>
                    <div class="gsm-manual-hint">
                      <i class="fas fa-circle-info"></i>
                      Type a number and press <strong>Enter</strong> or <strong>,</strong> to add. These numbers are not saved.
                    </div>
                  </div>
                </div>

                <!-- BROADCAST panel -->
                <div class="gsm-recipient-panel" id="panel-broadcast">
                  <div class="gsm-broadcast-banner visible">
                    <i class="fas fa-tower-broadcast"></i>
                    <div>
                      <strong>Broadcast to All Staff</strong><br>
                      <span>Message will be sent to all 5 registered contacts simultaneously.</span>
                    </div>
                  </div>
                  <div class="gsm-broadcast-recipients visible" style="margin-bottom:13px;">
                    <span class="gsm-bc-pill">Katherine Guzman</span>
                    <span class="gsm-bc-pill">Raffy Romero</span>
                    <span class="gsm-bc-pill">Samantha Lumpaodan</span>
                    <span class="gsm-bc-pill">Farm Group</span>
                  </div>
                </div>

                <!-- Priority -->
                <div class="compose-group">
                  <label class="compose-label"><i class="fas fa-flag"></i> &nbsp;Priority</label>
                  <select class="compose-select" id="gsm-priority">
                    <option value="normal">🟢 Normal</option>
                    <option value="urgent">🟡 Urgent</option>
                    <option value="critical">🔴 Critical Alert</option>
                  </select>
                </div>

                <!-- Quick templates -->
                <div class="compose-group">
                  <label class="compose-label"><i class="fas fa-bolt"></i> &nbsp;Quick Templates</label>
                  <div class="quick-msg-chips">
                    <span class="quick-chip" onclick="gsmSetMsg('ALERT: Soil moisture dropped below 40%. Irrigation triggered automatically.')">
                      <i class="fas fa-droplet"></i> Low Moisture
                    </span>
                    <span class="quick-chip" onclick="gsmSetMsg('NOTICE: Fertilization scheduled for tomorrow at 6:00 AM. Prepare the chemical tank.')">
                      <i class="fas fa-spray-can"></i> Fertilization Notice
                    </span>
                    <span class="quick-chip" onclick="gsmSetMsg('ALERT: Battery level critical at 15%. Solar panels may need inspection.')">
                      <i class="fas fa-battery-quarter"></i> Low Battery
                    </span>
                    <span class="quick-chip" onclick="gsmSetMsg('INFO: All systems normal. Daily system health check passed. Next irrigation in 2h.')">
                      <i class="fas fa-circle-check"></i> System OK
                    </span>
                    <span class="quick-chip" onclick="gsmSetMsg('HARVEST REMINDER: Tomatoes ready for harvest. Schedule picking crew for tomorrow.')">
                      <i class="fas fa-basket-shopping"></i> Harvest
                    </span>
                  </div>
                </div>

                <!-- Message textarea -->
                <div class="compose-group">
                  <label class="compose-label"><i class="fas fa-comment"></i> &nbsp;Message</label>
                  <textarea
                    class="compose-textarea"
                    id="gsm-msg"
                    placeholder="Type your SMS message here…"
                    oninput="gsmUpdateCounter(this)"
                    maxlength="320"
                  ></textarea>
                  <div class="gsm-char-counter" id="gsm-char-count">0 / 160 chars · 1 SMS</div>
                </div>

                <button class="btn-gsm-send" id="gsm-send-btn" onclick="gsmSend()">
                  <i class="fas fa-paper-plane"></i> Send SMS
                </button>

              </div>
            </div>

            <div class="gsm-right-col">

              <!-- TODAY'S SMS STATS -->
              <div class="card">
                <div class="card-head">
                  <div class="card-head-label"><i class="fas fa-chart-bar"></i> Today's SMS Stats</div>
                  <span class="badge badge-muted"><?= date('M j, Y') ?></span>
                </div>
                <div class="card-body">
                  <div class="sms-stats-grid">
                    <div class="sms-stat-box">
                      <div class="sms-stat-val green" id="gsm-sent-count">0</div>
                      <div class="sms-stat-lbl">Sent</div>
                    </div>
                    <div class="sms-stat-box">
                      <div class="sms-stat-val green" id="gsm-delivered-count">0</div>
                      <div class="sms-stat-lbl">Delivered</div>
                    </div>
                    <div class="sms-stat-box">
                      <div class="sms-stat-val red" id="gsm-failed-count">0</div>
                      <div class="sms-stat-lbl">Failed</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sent Log -->
              <div class="card" style="flex:1;">
                <div class="card-head">
                  <div class="card-head-label"><i class="fas fa-clock-rotate-left"></i> Recent Sent</div>
                  <span class="badge badge-blue">Last 24h</span>
                </div>
                <div class="card-body" id="gsm-sent-log">
                </div>
              </div>

              <!-- Modem Info -->
              <div class="card">
                <div class="card-head">
                  <div class="card-head-label"><i class="fas fa-microchip"></i> Modem Info</div>
                  <span class="badge badge-green"><span class="gsm-pulse-dot" style="width:6px;height:6px;margin-right:2px;"></span> Online</span>
                </div>
                <div class="card-body">
                  <div class="modem-row">
                    <span class="modem-key"><i class="fas fa-sim-card"></i> Module</span>
                    <span class="modem-val">SIM800L v2.0</span>
                  </div>
                  <div class="modem-row">
                    <span class="modem-key"><i class="fas fa-network-wired"></i> Network</span>
                    <span class="modem-val">SMART Telecom PH</span>
                  </div>
                  <div class="modem-row">
                    <span class="modem-key"><i class="fas fa-signal"></i> Signal</span>
                    <span class="modem-val">-72 dBm (Good)</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>

      <footer style="background:#fff;border-top:1px solid var(--border);padding:20px 32px;display:flex;align-items:center;justify-content:space-between;font-size:0.75rem;color:var(--text-muted);">
        <span>© 2026 Solar IoT Farm System — Tomato Cultivation Automation</span>
        <span style="display:flex;gap:8px;">
          <span class="badge badge-green"><i class="fas fa-wifi" style="margin-right:4px;"></i>IoT Connected</span>
          <span class="badge badge-yellow"><i class="fas fa-sun" style="margin-right:4px;"></i>Solar Active</span>
          <span class="badge badge-green"><i class="fas fa-mobile-screen-button" style="margin-right:4px;"></i>GSM Ready</span>
        </span>
      </footer>
    </div>

  </div>
</div>

<div class="gsm-toast" id="gsm-toast">
  <i class="fas fa-check-circle"></i>
  <span id="gsm-toast-msg">SMS sent successfully!</span>
</div>

<script>
  // ── Clock ──
  function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
      now.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) + ' · ' +
      now.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  }
  updateClock(); setInterval(updateClock, 1000);

  // ── Mini calendar ──
  let dashCalDate = new Date();
  dashCalDate.setDate(1);
  function dashChangeMonth(d) { dashCalDate.setMonth(dashCalDate.getMonth() + d); generateDashCal(); }
  function generateDashCal() {
    const grid = document.getElementById('dash-cal-grid');
    if (!grid) return;
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('dash-month-label').textContent = months[dashCalDate.getMonth()] + ' ' + dashCalDate.getFullYear();
    grid.innerHTML = '';
    ['S','M','T','W','T','F','S'].forEach(d => {
      const h = document.createElement('div'); h.className='cal-dh'; h.textContent=d; grid.appendChild(h);
    });
    
    const today = new Date();
    const currentMonth = dashCalDate.getMonth();
    const currentYear = dashCalDate.getFullYear();
    
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    const prevMonth = new Date(currentYear, currentMonth, 0);
    const daysInPrevMonth = prevMonth.getDate();
    
    const monthStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0');
    const endDay = new Date(currentYear, currentMonth + 1, 0).getDate();
    const endStr = monthStr + '-' + String(endDay).padStart(2, '0');
    
    fetch('get_schedules.php?start=' + monthStr + '-01&end=' + endStr)
      .then(r => r.json())
      .then(schedules => {
        const waterDays = new Set();
        const fertilizationDays = new Set();
        const harvestDays = new Set();
        const maintenanceDays = new Set();
        
        schedules.forEach(s => {
          const day = parseInt(s.schedule_date.split('-')[2], 10);
          if (s.task_type === 'irrigation') waterDays.add(day);
          else if (s.task_type === 'fertilization') fertilizationDays.add(day);
          else if (s.task_type === 'harvest') harvestDays.add(day);
          else if (s.task_type === 'maintenance') maintenanceDays.add(day);
        });
        
        for (let i = 0; i < firstDay; i++) {
          const el = document.createElement('div');
          el.className = 'cal-day';
          el.style.background = 'transparent';
          el.style.cursor = 'default';
          grid.appendChild(el);
        }
        
        for (let d = 1; d <= daysInMonth; d++) {
          const el = document.createElement('div');
          el.className = 'cal-day';
          el.textContent = d;
          
          const isToday = today.getDate() === d && today.getMonth() === currentMonth && today.getFullYear() === currentYear;
          if (isToday) el.classList.add('today');
          
          if (waterDays.has(d)) { el.classList.add('water'); const ic = document.createElement('i'); ic.className = 'fas fa-droplet cal-day-icon'; el.appendChild(ic); }
          if (fertilizationDays.has(d)) { el.classList.add('fertilization'); const ic = document.createElement('i'); ic.className = 'fas fa-spray-can cal-day-icon'; el.appendChild(ic); }
          if (harvestDays.has(d)) { el.classList.add('harvest'); const ic = document.createElement('i'); ic.className = 'fas fa-basket-shopping cal-day-icon'; el.appendChild(ic); }
          if (maintenanceDays && maintenanceDays.has(d)) { el.classList.add('maintenance'); const ic = document.createElement('i'); ic.className = 'fas fa-wrench cal-day-icon'; el.appendChild(ic); }
          
          el.onclick = () => window.location.href = 'schedule.php?date=' + monthStr + '-' + String(d).padStart(2,'0');
          grid.appendChild(el);
        }
      })
      .catch(() => {
        for (let i = 0; i < firstDay; i++) {
          const el = document.createElement('div');
          el.className = 'cal-day';
          el.style.background = 'transparent';
          el.style.cursor = 'default';
          grid.appendChild(el);
        }
        
        // Current month days
        for (let d = 1; d <= daysInMonth; d++) {
          const el = document.createElement('div');
          el.className = 'cal-day';
          el.textContent = d;
          
          const isToday = today.getDate() === d && today.getMonth() === currentMonth && today.getFullYear() === currentYear;
          if (isToday) el.classList.add('today');
          
          el.onclick = () => window.location.href = 'schedule.php?date=' + monthStr + '-' + String(d).padStart(2,'0');
          grid.appendChild(el);
        }
      });
  }
  generateDashCal();

  document.addEventListener('click', e => {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.contains(e.target) && !e.target.closest('.mobile-toggle')) {
      sidebar.classList.remove('open');
    }
  });

  let gsmCurrentMode = 'saved';

  function gsmSetMode(mode) {
    gsmCurrentMode = mode;
    ['saved','manual','broadcast'].forEach(m => {
      document.getElementById('tab-' + m).classList.remove('active', 'active-broadcast');
    });
    const activeTab = document.getElementById('tab-' + mode);
    if (mode === 'broadcast') {
      activeTab.classList.add('active-broadcast');
    } else {
      activeTab.classList.add('active');
    }
    document.getElementById('panel-saved').classList.toggle('visible', mode === 'saved');
    document.getElementById('panel-manual').classList.toggle('visible', mode === 'manual');
    document.getElementById('panel-broadcast').classList.toggle('visible', mode === 'broadcast');

    const btn = document.getElementById('gsm-send-btn');
    if (mode === 'broadcast') {
      btn.classList.add('broadcast-mode');
      btn.innerHTML = '<i class="fas fa-tower-broadcast"></i> Send Broadcast to All';
    } else {
      btn.classList.remove('broadcast-mode');
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send SMS';
    }
  }

  const manualNumbers = [];

  function gsmAddTag(number) {
    const raw = number.trim().replace(/\s+/g,'');
    if (!raw) return;
    if (!/^[+0-9]/.test(raw) || raw.length < 11) {
      gsmShowToast('⚠️ Invalid number: ' + raw, true);
      return;
    }
    if (manualNumbers.includes(raw)) return;
    manualNumbers.push(raw);

    const box   = document.getElementById('gsm-tags-box');
    const input = document.getElementById('gsm-tag-input');
    const tag   = document.createElement('span');
    tag.className     = 'gsm-tag';
    tag.dataset.number = raw;
    tag.innerHTML     = `${raw} <button class="gsm-tag-remove" onclick="gsmRemoveTag('${raw}', this)" title="Remove"><i class="fas fa-xmark"></i></button>`;
    box.insertBefore(tag, input);
    input.value       = '';
    input.placeholder = 'Add another…';
  }

  function gsmRemoveTag(number, btn) {
    const idx = manualNumbers.indexOf(number);
    if (idx > -1) manualNumbers.splice(idx, 1);
    btn.closest('.gsm-tag').remove();
    if (manualNumbers.length === 0) {
      document.getElementById('gsm-tag-input').placeholder = '+63917… then press Enter or comma';
    }
  }

  function buildLogItem(id, recipient, msg, time, isBroadcast) {
    const item      = document.createElement('div');
    item.className  = 'gsm-log-item';
    item.dataset.id = id || '';
    item.dataset.recipient = recipient || '';

    const truncated   = msg.length > 48 ? msg.substring(0, 48) + '…' : msg;
    const iconClass   = isBroadcast ? 'broadcast' : 'ok';
    const iconName    = isBroadcast ? 'tower-broadcast' : 'check';
    const badgeClass  = isBroadcast ? 'badge-red' : 'badge-green';
    const badgeLabel  = isBroadcast ? 'Broadcast' : 'Delivered';

    item.innerHTML = `
      <div class="gsm-log-icon ${iconClass}">
        <i class="fas fa-${iconName}"></i>
      </div>
      <div class="gsm-log-meta" style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:5px;margin-bottom:3px;">
  <span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted);">To:</span>
  <span style="font-size:0.78rem;font-weight:600;color:var(--text-primary);">${recipient}</span>
</div>
<div class="gsm-log-msg"
             title="Click to expand"
             style="font-size:0.78rem;color:var(--text-muted);cursor:pointer;line-height:1.4;word-break:break-word;"
        >${truncated}</div>
        <div class="gsm-log-time" style="font-size:0.7rem;color:var(--text-muted);margin-top:3px;">${time}</div>

        <!-- Expand panel: full message + edit + actions -->
        <div class="gsm-expand-panel" style="display:none;margin-top:10px;padding:10px 12px;background:var(--green-pale);border:1px solid rgba(0,0,0,0.07);border-radius:var(--radius-sm);">
          <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:6px;">
            <i class="fas fa-pen-to-square" style="margin-right:4px;"></i>Full Message — click to edit
          </div>
          <textarea
            class="compose-textarea gsm-edit-area"
            style="width:100%;font-size:0.8rem;min-height:72px;margin-bottom:10px;resize:vertical;box-sizing:border-box;"
          >${msg}</textarea>
          <div style="display:flex;gap:8px;">
            <button
              onclick="gsmSaveEdit(this)"
              class="gsm-save-btn"
              style="flex:1;padding:7px 10px;background:var(--water);color:#fff;border:none;border-radius:var(--radius-sm);font-size:0.78rem;cursor:pointer;font-weight:600;">
              <i class="fas fa-floppy-disk"></i> Save
            </button>
            <button
              onclick="gsmResend(this)"
              style="padding:7px 10px;background:var(--green-mid);color:#fff;border:none;border-radius:var(--radius-sm);font-size:0.78rem;cursor:pointer;font-weight:600;">
              <i class="fas fa-paper-plane"></i> Copy
            </button>
            <button
              onclick="gsmDeleteLog(this)"
              style="padding:7px 14px;background:var(--red-pale);color:var(--red);border:1px solid rgba(214,48,49,0.25);border-radius:var(--radius-sm);font-size:0.78rem;cursor:pointer;font-weight:600;">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>
      <div class="gsm-log-right" style="flex-shrink:0;">
        <span class="badge ${badgeClass}">${badgeLabel}</span>
      </div>
    `;

    const msgEl   = item.querySelector('.gsm-log-msg');
    const panel   = item.querySelector('.gsm-expand-panel');
    msgEl.addEventListener('click', function () {
      const open = panel.style.display !== 'none';
      if (open) {
        panel.style.display = 'none';
        this.textContent    = truncated;
        this.title          = 'Click to expand';
      } else {
        panel.style.display = 'block';
        this.textContent    = '▲ tap to collapse';
        this.title          = 'Click to collapse';
      }
    });

    return item;
  }

  function loadGsmMessages() {
    fetch('get_gsm_messages.php')
      .then(res => { if (!res.ok) throw new Error('Network error'); return res.json(); })
      .then(messages => {
        const log = document.getElementById('gsm-sent-log');
        if (!log || !messages || messages.length === 0) return;

        const todayStr = new Date().toLocaleDateString('en-CA'); 
        let todayCount = 0;

        messages.forEach(m => {
          const sentDate  = new Date(m.sent_at);
          const dateStr   = sentDate.toLocaleDateString('en-CA');
          if (dateStr === todayStr) todayCount++;

          const time = sentDate.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
          const item = buildLogItem(m.id, m.recipients, m.message, time, false);
          log.appendChild(item);
        });

        document.getElementById('gsm-sent-count').textContent      = todayCount;
        document.getElementById('gsm-delivered-count').textContent  = todayCount;
        
        const sidebarBadge = document.getElementById('sidebar-gsm-badge');
        if (sidebarBadge) sidebarBadge.textContent = todayCount;
      })
      .catch(err => console.error('Failed to load GSM messages:', err));
  }

  function gsmResend(btn) {
    const panel  = btn.closest('.gsm-expand-panel');
    const edited = panel.querySelector('.gsm-edit-area').value.trim();
    if (!edited) { gsmShowToast('⚠️ Message is empty.', true); return; }

    const ta = document.getElementById('gsm-msg');
    ta.value = edited;
    gsmUpdateCounter(ta);
    ta.focus();
    ta.scrollIntoView({ behavior: 'smooth', block: 'center' });
    gsmShowToast('Message copied to composer — edit and resend.');
  }

  function gsmDeleteLog(btn) {
  const item = btn.closest('.gsm-log-item');
  const id   = item.dataset.id;

  if (!id) {
    item.style.transition = 'opacity 0.25s, transform 0.25s';
    item.style.opacity    = '0';
    item.style.transform  = 'translateX(20px)';
    setTimeout(() => item.remove(), 260);
    gsmShowToast('Message removed.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

  fetch('delete_gsm.php', {
    method : 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body   : 'id=' + encodeURIComponent(id)
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      item.style.transition = 'opacity 0.25s, transform 0.25s';
      item.style.opacity    = '0';
      item.style.transform  = 'translateX(20px)';
      setTimeout(() => item.remove(), 260);

      const sentEl  = document.getElementById('gsm-sent-count');
      const delivEl = document.getElementById('gsm-delivered-count');
      sentEl.textContent  = Math.max(0, parseInt(sentEl.textContent)  - 1);
      delivEl.textContent = Math.max(0, parseInt(delivEl.textContent) - 1);

      const sidebarBadge = document.getElementById('sidebar-gsm-badge');
      if (sidebarBadge) sidebarBadge.textContent = sentEl.textContent;

      gsmShowToast('Message permanently deleted.');
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
      gsmShowToast('⚠️ Delete failed: ' + (data.error || 'Unknown error'), true);
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
    gsmShowToast('⚠️ Network error — message not deleted.', true);
  });
}

  function gsmSaveEdit(btn) {
  const panel  = btn.closest('.gsm-expand-panel');
  const item   = btn.closest('.gsm-log-item');
  const id     = item.dataset.id;
  const edited = panel.querySelector('.gsm-edit-area').value.trim();

  if (!edited) { gsmShowToast('⚠️ Message is empty.', true); return; }

  if (!id) {
    gsmShowToast('⚠️ This message is still being saved — wait a moment and try again.', true);
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

  fetch('save_gsm.php', {
    method : 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body   : 'id='         + encodeURIComponent(id)
           + '&message='    + encodeURIComponent(edited)
           + '&recipients=' + encodeURIComponent(item.dataset.recipient || '')
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      const truncated = edited.length > 48 ? edited.substring(0, 48) + '…' : edited;
      const msgEl     = item.querySelector('.gsm-log-msg');
      msgEl.textContent = truncated;
      msgEl.title       = 'Click to expand';

      panel.querySelector('.gsm-edit-area').value = edited;
      panel.style.display = 'none';

      gsmShowToast('Message updated in database.');
    } else {
      gsmShowToast('⚠️ ' + (data.error || 'Update failed'), true);
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save';
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save';
    gsmShowToast('⚠️ Network error — changes not saved.', true);
  });
}

  document.addEventListener('DOMContentLoaded', () => {
    loadGsmMessages();

    const input = document.getElementById('gsm-tag-input');
    if (!input) return;

    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        gsmAddTag(input.value);
      }
      if (e.key === 'Backspace' && input.value === '' && manualNumbers.length > 0) {
        const last = manualNumbers[manualNumbers.length - 1];
        const tags = document.querySelectorAll('#gsm-tags-box .gsm-tag');
        if (tags.length) gsmRemoveTag(last, tags[tags.length - 1].querySelector('.gsm-tag-remove'));
      }
    });

    input.addEventListener('paste', e => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      text.split(/[\s,;]+/).forEach(n => { if (n.trim()) gsmAddTag(n); });
    });
  });

  function gsmSetMsg(text) {
    const ta = document.getElementById('gsm-msg');
    ta.value = text;
    gsmUpdateCounter(ta);
    ta.focus();
  }

  function gsmUpdateCounter(el) {
    const len     = el.value.length;
    const sms     = Math.ceil(len / 160) || 1;
    const counter = document.getElementById('gsm-char-count');
    counter.textContent = `${len} / 160 chars · ${sms} SMS`;
    counter.className   = 'gsm-char-counter' + (len > 320 ? ' over' : len > 140 ? ' warn' : '');
  }

  function gsmSend() {
    const msgEl = document.getElementById('gsm-msg');
    const btn   = document.getElementById('gsm-send-btn');
    const msg   = msgEl.value.trim();
    let recipientLabel = '';
    let recipientCount = 1;

    if (gsmCurrentMode === 'saved') {
      const sel = document.getElementById('gsm-recipient');
      if (!sel.value) { gsmShowToast('⚠️ Please select a recipient.', true); return; }
      recipientLabel = sel.options[sel.selectedIndex].text.split(' · ')[0].trim();
    } else if (gsmCurrentMode === 'manual') {
      const raw = document.getElementById('gsm-tag-input').value.trim();
      if (raw) gsmAddTag(raw);
      if (manualNumbers.length === 0) { gsmShowToast('⚠️ Add at least one number.', true); return; }
      recipientLabel = manualNumbers.length === 1
        ? manualNumbers[0]
        : manualNumbers[0] + ' +' + (manualNumbers.length - 1) + ' more';
      recipientCount = manualNumbers.length;
    } else if (gsmCurrentMode === 'broadcast') {
      recipientLabel = 'All Staff Broadcast';
      recipientCount = 5;
    }

    if (!msg) { gsmShowToast('⚠️ Message cannot be empty.', true); return; }

    btn.classList.add('sending');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    btn.disabled  = true;

    setTimeout(() => {
      const isBroadcast = gsmCurrentMode === 'broadcast';
      const time        = new Date().toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});

      const log  = document.getElementById('gsm-sent-log');
      const item = buildLogItem(null, recipientLabel, msg, time, isBroadcast);
      item.style.cssText = 'opacity:0;transform:translateY(-8px);transition:opacity 0.3s,transform 0.3s;';
      log.prepend(item);
      requestAnimationFrame(() => {
        item.style.opacity   = '1';
        item.style.transform = 'none';
      });

      const sentEl  = document.getElementById('gsm-sent-count');
      const delivEl = document.getElementById('gsm-delivered-count');
      sentEl.textContent  = parseInt(sentEl.textContent)  + recipientCount;
      delivEl.textContent = parseInt(delivEl.textContent) + recipientCount;

      const sidebarBadge = document.getElementById('sidebar-gsm-badge');
      if (sidebarBadge) sidebarBadge.textContent = sentEl.textContent;

      fetch('save_gsm.php', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : 'message=' + encodeURIComponent(msg) + '&recipients=' + encodeURIComponent(recipientLabel)
      })
      .then(r => r.json())
      .then(data => {
        if (data && data.id) item.dataset.id = data.id;
      })
      .catch(() => {});

      msgEl.value = '';
      document.getElementById('gsm-char-count').textContent = '0 / 160 chars · 1 SMS';

      if (gsmCurrentMode === 'manual') {
        manualNumbers.length = 0;
        const box = document.getElementById('gsm-tags-box');
        box.querySelectorAll('.gsm-tag').forEach(t => t.remove());
        document.getElementById('gsm-tag-input').placeholder = '+63917… then press Enter or comma';
      }

      btn.classList.remove('sending', 'broadcast-mode');
      if (gsmCurrentMode === 'broadcast') {
        btn.classList.add('broadcast-mode');
        btn.innerHTML = '<i class="fas fa-tower-broadcast"></i> Send Broadcast to All';
      } else {
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send SMS';
      }
      btn.disabled = false;

      gsmShowToast(isBroadcast
        ? `Broadcast sent to ${recipientCount} contacts!`
        : `SMS sent to ${recipientLabel}!`
      );
    }, 1600);
  }

  function gsmShowToast(msg, isErr = false) {
    const toast = document.getElementById('gsm-toast');
    const span  = document.getElementById('gsm-toast-msg');
    span.textContent = msg;
    toast.querySelector('i').className = isErr
      ? 'fas fa-triangle-exclamation'
      : 'fas fa-check-circle';
    toast.style.background = isErr ? '#4a1a1a' : '#1e2e22';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3200);
  }
</script>
</body>
</html>