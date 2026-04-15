<?php
session_start();
require_once 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($_SESSION['role'] ?? 'Farmer', ENT_QUOTES, 'UTF-8');
$active_page = 'monitoring';

require_once 'sensor_status.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Monitoring — Tomato Cultivation System</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/monitor.css">
  <script src="notifications.js"></script>

</head>
<body>
<div class="layout">

  <?php include 'sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-title">
        <i class="fas fa-wifi"></i> IoT Sensor Monitoring
      </div>
      <div class="live-badge"><div class="live-dot"></div> Live</div>
      <div class="topbar-clock" id="clock">—</div>
    </div>

    <div class="page-content">

      <!-- PAGE HERO -->
      <div class="page-hero">
        <div class="page-hero-badge">IoT Sensor Network — 12 Nodes Active</div>
        <h1>Sensor <span>Monitoring</span></h1>
        <p>Real-time readings from all IoT nodes across the Farm. Soil moisture, temperature, humidity, solar, and water level — all live.</p>
      </div>

      <!-- QUICK STATS -->
      <div class="page-section">
        <div class="grid-4">
          <div class="stat-card green" style="animation-delay:0.05s;">
            <div class="stat-top">
              <div class="stat-icon-wrap green"><i class="fas fa-droplet"></i></div>
              <span class="stat-badge up">Optimal</span>
            </div>
            <div class="stat-value green">68<span class="stat-unit"> %</span></div>
            <div class="stat-label">Avg Soil Moisture</div>
          </div>
          <div class="stat-card red" style="animation-delay:0.1s;">
            <div class="stat-top">
              <div class="stat-icon-wrap red"><i class="fas fa-temperature-high"></i></div>
              <span class="stat-badge warn">Warm</span>
            </div>
            <div class="stat-value red">28<span class="stat-unit"> °C</span></div>
            <div class="stat-label">Air Temperature</div>
          </div>
          <div class="stat-card solar" style="animation-delay:0.15s;">
            <div class="stat-top">
              <div class="stat-icon-wrap solar"><i class="fas fa-solar-panel"></i></div>
              <span class="stat-badge warn">Peak</span>
            </div>
            <div class="stat-value solar">85<span class="stat-unit"> kW</span></div>
            <div class="stat-label">Solar Output</div>
          </div>
          <div class="stat-card water" style="animation-delay:0.2s;">
            <div class="stat-top">
              <div class="stat-icon-wrap water"><i class="fas fa-water"></i></div>
              <span class="stat-badge info">Good</span>
            </div>
            <div class="stat-value water">84<span class="stat-unit"> %</span></div>
            <div class="stat-label">Water Tank Level</div>
          </div>
        </div>
      </div>

      <!-- ENVIRONMENT SENSORS -->
      <?php
        $env_errors = (int)($sensor_status['ENV-01']['status']==='error')
                    + (int)($sensor_status['ENV-02']['status']==='error')
                    + (int)($sensor_status['ENV-03']['status']==='error');
      ?>
      <div class="page-section">
        <div class="section-head">
          <div class="section-icon red"><i class="fas fa-temperature-high"></i></div>
          <div class="section-label">
            Environment Sensors
            <?php if ($env_errors > 0): ?>
              <span class="section-error-chip"><i class="fas fa-triangle-exclamation"></i> <?= $env_errors ?> Error<?= $env_errors>1?'s':'' ?></span>
            <?php endif; ?>
          </div>
          <div class="section-meta">6 nodes · Farm</div>
        </div>
        <div class="sensor-grid">

          <!-- Temperature & Humidity — ENV-01 -->
          <?php $s = $sensor_status['ENV-01']; $isErr = $s['status']==='error'; ?>
          <div class="sensor-node<?= $isErr?' has-error':'' ?>" style="animation-delay:0.05s;">
            <div class="sensor-node-head">
              <div>
                <div class="sensor-node-id">NODE-ENV-01 · Farm</div>
                <div class="sensor-node-name">Temperature & Humidity</div>
              </div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge-active"><i class="fas fa-circle-check"></i> Active</span>
              <?php endif; ?>
            </div>
            <div class="sensor-node-body">
              <!-- Status bar -->
              <div class="sensor-status-bar <?= $s['status'] ?>">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: Just now</span><?php endif; ?>
              </div>
              <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:6px;">
                <div>
                  <div class="sensor-value-big red">28<span class="sensor-unit">°C</span></div>
                  <div class="sensor-sublabel">Temperature</div>
                </div>
                <div style="padding-bottom:4px;">
                  <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;color:var(--water);">65<span style="font-family:var(--font-mono);font-size:0.8rem;color:var(--text-muted);font-weight:300;">%</span></div>
                  <div class="sensor-sublabel">Humidity</div>
                </div>
              </div>
              <div class="kv-row"><span class="key">Heat Index</span><span class="badge badge-yellow">31°C</span></div>
              <div class="kv-row"><span class="key">Dew Point</span><span class="val">20°C</span></div>
              <div class="kv-row"><span class="key">Last Update</span><span style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">Just now</span></div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Atmospheric Pressure — ENV-02 -->
          <?php $s = $sensor_status['ENV-02']; $isErr = $s['status']==='error'; ?>
          <div class="sensor-node<?= $isErr?' has-error':'' ?>" style="animation-delay:0.1s;">
            <div class="sensor-node-head">
              <div>
                <div class="sensor-node-id">NODE-ENV-02 · Farm</div>
                <div class="sensor-node-name">Atmospheric Pressure</div>
              </div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge-active"><i class="fas fa-circle-check"></i> Active</span>
              <?php endif; ?>
            </div>
            <div class="sensor-node-body">
              <div class="sensor-status-bar <?= $s['status'] ?>">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 30s ago</span><?php endif; ?>
              </div>
              <div class="sensor-value-big green" style="margin-bottom:2px;">1013<span class="sensor-unit">hPa</span></div>
              <div class="sensor-sublabel">Barometric Pressure</div>
              <div class="progress-wrap"><div class="progress-fill green" style="width:68%"></div></div>
              <div class="kv-row"><span class="key">Status</span><span class="badge badge-green">Normal</span></div>
              <div class="kv-row"><span class="key">Trend</span><span class="val">Stable ↔</span></div>
              <div class="kv-row"><span class="key">Last Update</span><span style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">30s ago</span></div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Light & UV — ENV-03 -->
          <?php $s = $sensor_status['ENV-03']; $isErr = $s['status']==='error'; ?>
          <div class="sensor-node<?= $isErr?' has-error':'' ?>" style="animation-delay:0.15s;">
            <div class="sensor-node-head">
              <div>
                <div class="sensor-node-id">NODE-ENV-03 · Rooftop</div>
                <div class="sensor-node-name">Light & UV Index</div>
              </div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge-active"><i class="fas fa-circle-check"></i> Active</span>
              <?php endif; ?>
            </div>
            <div class="sensor-node-body">
              <div class="sensor-status-bar <?= $s['status'] ?>">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 12s ago</span><?php endif; ?>
              </div>
              <div class="sensor-value-big solar" style="margin-bottom:2px;">7.2<span class="sensor-unit">UV</span></div>
              <div class="sensor-sublabel">UV Index</div>
              <div class="progress-wrap"><div class="progress-fill solar" style="width:72%"></div></div>
              <div class="kv-row"><span class="key">Lux Level</span><span class="val">48,200 lx</span></div>
              <div class="kv-row"><span class="key">Daylight</span><span class="val">11h 30m</span></div>
              <div class="kv-row"><span class="key">Forecast</span><span class="badge badge-blue">Partly Cloudy</span></div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

      <!-- SOIL SENSORS -->
      <div class="page-section">
        <div class="section-head">
          <div class="section-icon water"><i class="fas fa-droplet"></i></div>
          <div class="section-label">Soil Moisture Sensors</div>
          <div class="section-meta">Farm monitored</div>
        </div>
        <div class="grid-2">
          <div class="card" style="animation-delay:0.05s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-chart-bar"></i> Moisture Levels</div>
              <span class="live-badge"><div class="live-dot"></div> Live</span>
            </div>
            <div class="card-body">
              <div class="moisture-viz" id="moisture-viz"></div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-align:center;margin-top:4px;"><i class="fas fa-sync fa-spin" style="margin-right:4px;"></i> Updates every 5 seconds</div>
            </div>
          </div>
          <div class="card" style="animation-delay:0.1s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-list"></i> Farm Breakdown</div>
            </div>
            <div class="card-body">
              <?php
              $zones = [
                ['id'=>'SOIL-01','name'=>'Farm','val'=>68,'status'=>'Optimal','badge'=>'badge-green','cls'=>'water'],
              ];
              foreach($zones as $z):
                $sInfo = $sensor_status[$z['id']];
                $zErr  = $sInfo['status'] === 'error';
              ?>
              <div style="margin-bottom:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                  <span style="font-size:0.84rem;font-weight:600;color:var(--text);display:flex;align-items:center;gap:7px;">
                    <?= $z['name'] ?>
                    <?php if ($zErr): ?>
                      <span style="font-family:var(--font-mono);font-size:0.65rem;background:var(--red-pale,#fff0f0);color:var(--red,#e05555);border:1px solid #f5c5c5;border-radius:4px;padding:1px 6px;"><?= htmlspecialchars($sInfo['error_code']) ?></span>
                    <?php endif; ?>
                  </span>
                  <div style="display:flex;align-items:center;gap:6px;">
                    <?php if ($zErr): ?>
                      <span class="badge-error" style="animation:none;font-size:0.65rem;padding:2px 7px;"><i class="fas fa-circle-xmark"></i> Error</span>
                    <?php else: ?>
                      <span class="badge <?= $z['badge'] ?>"><?= $z['status'] ?></span>
                    <?php endif; ?>
                    <span style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);"><?= $z['val'] ?>%</span>
                  </div>
                </div>
                <div class="progress-wrap" style="margin:0;">
                  <div class="progress-fill <?= $zErr ? 'red' : ($z['val'] < 30 ? 'red' : ($z['val'] < 50 ? 'solar' : 'water')) ?>" style="width:<?= $z['val'] ?>%;<?= $zErr?'opacity:0.35':'' ?>"></div>
                </div>
                <?php if ($zErr): ?>
                <div style="font-size:0.7rem;color:var(--red,#e05555);margin-top:3px;display:flex;align-items:flex-start;gap:5px;">
                  <i class="fas fa-triangle-exclamation" style="margin-top:1px;font-size:0.65rem;"></i>
                  <span><?= htmlspecialchars($sInfo['error_note']) ?></span>
                </div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- SOLAR & POWER -->
      <?php
        $pwr_errors = (int)($sensor_status['PWR-01']['status']==='error')
                    + (int)($sensor_status['PWR-02']['status']==='error');
      ?>
      <div class="page-section">
        <div class="section-head">
          <div class="section-icon solar"><i class="fas fa-solar-panel"></i></div>
          <div class="section-label">
            Solar & Power System
            <?php if ($pwr_errors > 0): ?>
              <span class="section-error-chip"><i class="fas fa-triangle-exclamation"></i> <?= $pwr_errors ?> Error<?= $pwr_errors>1?'s':'' ?></span>
            <?php endif; ?>
          </div>
          <div class="section-meta">Battery charging</div>
        </div>
        <div class="grid-3">

          <!-- Solar Output — PWR-01 -->
          <?php $s = $sensor_status['PWR-01']; $isErr = $s['status']==='error'; ?>
          <div class="card<?= $isErr?' has-error':'' ?>" style="animation-delay:0.05s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-solar-panel"></i> Solar Output</div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge badge-yellow">Peak</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="sensor-status-bar <?= $s['status'] ?>">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 8s ago</span><?php endif; ?>
              </div>
              <div style="text-align:center;margin-bottom:14px;">
                <div style="font-family:var(--font-display);font-size:3rem;font-weight:800;color:var(--solar);line-height:1;<?= $isErr?'opacity:0.35':'' ?>">85</div>
                <div style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">kW current output</div>
              </div>
              <div class="progress-wrap"><div class="progress-fill solar" style="width:85%;<?= $isErr?'opacity:0.35':'' ?>"></div></div>
              <div class="kv-row"><span class="key">Daily Total</span><span class="val">420 kWh</span></div>
              <div class="kv-row"><span class="key">Panel Efficiency</span><span class="val">92%</span></div>
              <div class="kv-row"><span class="key">Panel Status</span><span class="badge badge-green">All Online</span></div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Battery Bank — PWR-02 -->
          <?php $s = $sensor_status['PWR-02']; $isErr = $s['status']==='error'; ?>
          <div class="card<?= $isErr?' has-error':'' ?>" style="animation-delay:0.1s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-battery-half"></i> Battery Bank</div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge badge-blue">Charging</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="sensor-status-bar <?= $s['status'] ?>">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 5s ago</span><?php endif; ?>
              </div>
              <div style="text-align:center;margin-bottom:14px;">
                <div style="font-family:var(--font-display);font-size:3rem;font-weight:800;color:var(--water);line-height:1;<?= $isErr?'opacity:0.35':'' ?>">78</div>
                <div style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">% charge level</div>
              </div>
              <div class="progress-wrap"><div class="progress-fill water" style="width:78%;<?= $isErr?'opacity:0.35':'' ?>"></div></div>
              <div class="kv-row"><span class="key">Voltage</span><span class="val <?= $isErr?'':''?>">48.6 V</span></div>
              <div class="kv-row"><span class="key">Charge Rate</span><span class="val">+2.4 kW</span></div>
              <div class="kv-row"><span class="key">Est. Full</span><span class="val">~2h 30m</span></div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Power Consumption -->
          <div class="card" style="animation-delay:0.15s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-bolt"></i> Power Consumption</div>
              <span class="badge-active" style="font-size:0.65rem;padding:2px 7px;"><i class="fas fa-circle-check"></i> Active</span>
            </div>
            <div class="card-body">
              <div class="sensor-status-bar active">
                <i class="fas fa-circle-check"></i>
                <span class="status-label">Sensor Active</span>
                <span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 3s ago</span>
              </div>
              <div style="text-align:center;margin-bottom:14px;">
                <div style="font-family:var(--font-display);font-size:3rem;font-weight:800;color:var(--green-mid);line-height:1;">3.2</div>
                <div style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">kW current draw</div>
              </div>
              <div class="progress-wrap"><div class="progress-fill green" style="width:32%"></div></div>
              <div class="kv-row"><span class="key">Pump Draw</span><span class="val">1.8 kW</span></div>
              <div class="kv-row"><span class="key">Sensors</span><span class="val">0.4 kW</span></div>
              <div class="kv-row"><span class="key">Controller</span><span class="val">1.0 kW</span></div>
            </div>
          </div>

        </div>
      </div>

      <!-- WATER SYSTEM -->
      <div class="page-section">
        <div class="section-head">
          <div class="section-icon water"><i class="fas fa-water"></i></div>
          <div class="section-label">Water & Irrigation System</div>
        </div>
        <div class="grid-3">

          <!-- WATER TANK — WATER-01 -->
          <?php $s = $sensor_status['WATER-01']; $isErr = $s['status']==='error'; ?>
          <div class="card<?= $isErr?' has-error':'' ?>" style="animation-delay:0.05s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-tank-water"></i> Water Tank</div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge badge-green">Good</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="sensor-status-bar <?= $s['status'] ?>" style="margin-bottom:14px;">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 2s ago</span><?php endif; ?>
              </div>
              <div style="display:flex;gap:20px;align-items:center;">
                <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:6px;">
                  <div class="tank-cylinder" id="water-cylinder">
                    <div class="tank-cyl-body">
                      <div class="tank-cyl-fill water-fill" id="water-fill" style="height:84%;<?= $isErr?'opacity:0.35':'' ?>">
                        <div class="tank-wave"></div>
                      </div>
                      <div class="tank-cyl-label">
                        <span id="water-pct" style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:#fff;line-height:1;">84%</span>
                        <span style="font-family:var(--font-mono);font-size:0.65rem;color:rgba(255,255,255,0.75);">840 / 1000 L</span>
                      </div>
                    </div>
                    <div class="tank-cyl-cap top"></div>
                    <div class="tank-cyl-cap bot"></div>
                  </div>
                  <div class="tank-ticks">
                    <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span>
                  </div>
                </div>
                <div style="flex:1;">
                  <div class="kv-row"><span class="key"><i class="fas fa-gauge" style="color:var(--water)"></i> Flow Rate</span><span class="val">15 L/min</span></div>
                  <div class="kv-row"><span class="key"><i class="fas fa-pump-soap" style="color:var(--green-mid)"></i> Pump</span><span class="badge badge-green">Running</span></div>
                  <div class="kv-row"><span class="key"><i class="fas fa-door-open" style="color:var(--water)"></i> Valve</span><span class="badge badge-blue">Open</span></div>
                  <div class="kv-row"><span class="key"><i class="fas fa-clock" style="color:var(--text-muted)"></i> Est. Empty</span><span class="val">~56 min</span></div>
                  <div style="margin-top:12px;">
                    <div style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:6px;">24h Level History</div>
                    <svg id="water-sparkline" width="100%" height="36" viewBox="0 0 200 36" preserveAspectRatio="none" style="display:block;"></svg>
                  </div>
                </div>
              </div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note" style="margin-top:14px;">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- CHEMICAL TANK — CHEM-01 -->
          <?php $s = $sensor_status['CHEM-01']; $isErr = $s['status']==='error'; ?>
          <div class="card<?= $isErr?' has-error':'' ?>" style="animation-delay:0.1s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-flask"></i> Chemical Tank</div>
              <?php if ($isErr): ?>
                <span class="badge-error"><i class="fas fa-circle-xmark"></i> Error</span>
              <?php else: ?>
                <span class="badge badge-green">Adequate</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="sensor-status-bar <?= $s['status'] ?>" style="margin-bottom:14px;">
                <i class="fas fa-<?= $isErr ? 'circle-xmark' : 'circle-check' ?>"></i>
                <span class="status-label"><?= $isErr ? 'Sensor Error' : 'Sensor Active' ?></span>
                <?php if (!$isErr): ?><span style="font-family:var(--font-mono);font-size:0.68rem;opacity:0.7;">Last: 15s ago</span><?php endif; ?>
              </div>
              <div style="display:flex;gap:20px;align-items:center;margin-bottom:16px;">
                <div style="flex-shrink:0;position:relative;width:110px;height:110px;">
                  <svg width="110" height="110" viewBox="0 0 110 110">
                    <circle cx="55" cy="55" r="44" fill="none" stroke="var(--sand)" stroke-width="14"/>
                    <circle cx="55" cy="55" r="44" fill="none" stroke="var(--red)" stroke-width="14"
                      stroke-dasharray="276" stroke-dashoffset="0"
                      stroke-linecap="butt" transform="rotate(-90 55 55)"
                      style="stroke-dasharray: calc(165.6) calc(276 - 165.6);" id="chem-arc-fert"/>
                    <circle cx="55" cy="55" r="44" fill="none" stroke="var(--water)" stroke-width="14"
                      stroke-linecap="butt" transform="rotate(-90 55 55)"
                      style="stroke-dasharray:73.6 202.4;stroke-dashoffset:-165.6;" id="chem-arc-fung"/>
                    <circle cx="55" cy="55" r="44" fill="none" stroke="var(--green-mid)" stroke-width="14"
                      stroke-linecap="butt" transform="rotate(-90 55 55)"
                      style="stroke-dasharray:36.8 239.2;stroke-dashoffset:-239.2;" id="chem-arc-nutr"/>
                  </svg>
                  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <span style="font-family:var(--font-display);font-size:1.5rem;font-weight:800;color:var(--solar);line-height:1;">75</span>
                    <span style="font-family:var(--font-mono);font-size:0.6rem;color:var(--text-muted);">of 100 L</span>
                  </div>
                </div>
                <div style="flex:1;">
                  <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:10px;">Composition</div>
                  <div style="margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:3px;">
                      <span style="display:flex;align-items:center;gap:5px;font-weight:600;color:var(--text);"><span style="width:8px;height:8px;border-radius:50%;background:var(--red);display:inline-block;"></span>Ammonium</span>
                      <span style="font-family:var(--font-mono);font-size:0.75rem;color:var(--text-muted);">45 L</span>
                    </div>
                    <div class="progress-wrap" style="margin:0;height:6px;"><div class="progress-fill red" style="width:45%"></div></div>
                  </div>
                  <div style="margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:3px;">
                      <span style="display:flex;align-items:center;gap:5px;font-weight:600;color:var(--text);"><span style="width:8px;height:8px;border-radius:50%;background:var(--water);display:inline-block;"></span>Complete</span>
                      <span style="font-family:var(--font-mono);font-size:0.75rem;color:var(--text-muted);">20 L</span>
                    </div>
                    <div class="progress-wrap" style="margin:0;height:6px;"><div class="progress-fill water" style="width:20%"></div></div>
                  </div>
                  <div>
                    <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:3px;">
                      <span style="display:flex;align-items:center;gap:5px;font-weight:600;color:var(--text);"><span style="width:8px;height:8px;border-radius:50%;background:var(--green-mid);display:inline-block;"></span>Nutrients</span>
                      <span style="font-family:var(--font-mono);font-size:0.75rem;color:var(--text-muted);">10 L</span>
                    </div>
                    <div class="progress-wrap" style="margin:0;height:6px;"><div class="progress-fill green" style="width:10%"></div></div>
                  </div>
                </div>
              </div>
              <div style="font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:5px;">Total Capacity</div>
              <div style="background:var(--sand);border-radius:10px;height:12px;overflow:hidden;border:1px solid var(--border);position:relative;">
                <div style="height:100%;border-radius:10px;width:45%;background:var(--red);display:inline-block;"></div>
                <div style="height:100%;border-radius:0;width:20%;background:var(--water);display:inline-block;margin-left:-2px;"></div>
                <div style="height:100%;border-radius:0 10px 10px 0;width:10%;background:var(--green-mid);display:inline-block;margin-left:-2px;"></div>
              </div>
              <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:0.68rem;color:var(--text-muted);margin-top:4px;"><span>0 L</span><span style="color:var(--solar);font-weight:600;">75 L used · 25 L empty</span><span>100 L</span></div>
              <?php if ($isErr): ?>
              <div class="sensor-error-note" style="margin-top:14px;">
                <i class="fas fa-triangle-exclamation"></i>
                <div class="sensor-error-note-inner">
                  <span class="sensor-error-code"><?= htmlspecialchars($s['error_code']) ?></span>
                  <div class="sensor-error-text"><?= htmlspecialchars($s['error_note']) ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Active Alerts -->
          <div class="card" style="animation-delay:0.15s;">
            <div class="card-head">
              <div class="card-head-label"><i class="fas fa-bell"></i> Active Alerts</div>
              <span class="badge badge-yellow">
                <?php echo $errCount . ' Error' . ($errCount !== 1 ? 's' : ''); ?>
              </span>
            </div>
            <div class="card-body">
              <?php foreach ($sensor_status as $nodeId => $s): if ($s['status'] !== 'error') continue; ?>
              <div class="alert-item warning" style="margin-bottom:10px;">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                  <div class="alert-item-title"><?= htmlspecialchars($nodeId) ?> — <span style="font-family:var(--font-mono);font-size:0.75em;"><?= htmlspecialchars($s['error_code']) ?></span></div>
                  <div class="alert-item-desc"><?= htmlspecialchars($s['error_note']) ?></div>
                  <div class="alert-item-time">Detected today</div>
                </div>
              </div>
              <?php endforeach; ?>
              <div class="alert-item ok">
                <i class="fas fa-circle-check"></i>
                <div>
                  <div class="alert-item-title"><?= 12 - $errCount ?> Sensors Normal</div>
                  <div class="alert-item-desc">All other nodes operating within range</div>
                  <div class="alert-item-time">Ongoing</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- AUTOMATION CONTROLS -->
      <div class="page-section">
        <div class="section-head">
          <div class="section-icon red"><i class="fas fa-robot"></i></div>
          <div class="section-label">Automation Controls</div>
        </div>
        <div class="grid-2">
          <div class="card" style="animation-delay:0.05s;">
            <div class="card-head"><div class="card-head-label"><i class="fas fa-cogs"></i> System Automation Rules</div></div>
            <div class="card-body">
              <div class="toggle-row">
                <div><div class="toggle-label"><i class="fas fa-water" style="color:var(--water);margin-right:7px;font-size:0.78rem;"></i>Auto-Irrigation</div><div class="toggle-sublabel">Trigger when soil moisture &lt; 40%</div></div>
                <label class="toggle"><input type="checkbox" checked /><span class="toggle-slider"></span></label>
              </div>
              <div class="toggle-row">
                <div><div class="toggle-label"><i class="fas fa-spray-can" style="color:var(--solar);margin-right:7px;font-size:0.78rem;"></i>Auto-Fertilizing</div><div class="toggle-sublabel">Scheduled + Ammonium & Complete fertilizers</div></div>
                <label class="toggle"><input type="checkbox" checked /><span class="toggle-slider"></span></label>
              </div>
              <div class="toggle-row">
                <div><div class="toggle-label"><i class="fas fa-bolt" style="color:var(--green-mid);margin-right:7px;font-size:0.78rem;"></i>Power Management</div><div class="toggle-sublabel">Prioritize solar during peak hours</div></div>
                <label class="toggle"><input type="checkbox" checked /><span class="toggle-slider"></span></label>
              </div>
              <div class="toggle-row">
                <div><div class="toggle-label"><i class="fas fa-bell" style="color:var(--red);margin-right:7px;font-size:0.78rem;"></i>Alert Notifications</div><div class="toggle-sublabel">Notify on sensor anomalies</div></div>
                <label class="toggle"><input type="checkbox" checked /><span class="toggle-slider"></span></label>
              </div>
              <div class="toggle-row">
                <div><div class="toggle-label"><i class="fas fa-chart-line" style="color:var(--water);margin-right:7px;font-size:0.78rem;"></i>Data Logging</div><div class="toggle-sublabel">Record all sensor data every 5 minutes</div></div>
                <label class="toggle"><input type="checkbox" checked /><span class="toggle-slider"></span></label>
              </div>
            </div>
          </div>
          <div class="card" style="animation-delay:0.1s;">
            <div class="card-head"><div class="card-head-label"><i class="fas fa-server"></i> All Sensor Nodes Status</div></div>
            <div class="card-body">
              <?php
              $allSensors = [
                ['id'=>'ENV-01',   'name'=>'Temperature & Humidity', 'zone'=>'Farm'],
                ['id'=>'ENV-02',   'name'=>'Atmospheric Pressure',   'zone'=>'Farm'],
                ['id'=>'SOIL-01',  'name'=>'Soil Moisture',          'zone'=>'Farm'],
                ['id'=>'WATER-01', 'name'=>'Water Level',            'zone'=>'Tank'],
                ['id'=>'CHEM-01',  'name'=>'Chemical Level',         'zone'=>'Tank'],
              ];
              foreach($allSensors as $row):
                $sInfo = $sensor_status[$row['id']];
                $rowErr = $sInfo['status'] === 'error';
              ?>
              <div class="kv-row" style="<?= $rowErr?'background:var(--red-pale,#fff0f0);border-radius:8px;padding:4px 8px;margin-bottom:4px;':'' ?>">
                <span class="key" style="gap:10px;flex-wrap:wrap;">
                  <span style="font-family:var(--font-mono);font-size:0.72rem;background:<?= $rowErr?'#ffe0e0':'var(--sand)' ?>;padding:2px 7px;border-radius:6px;color:<?= $rowErr?'var(--red,#e05555)':'var(--text-muted)' ?>;"><?= $row['id'] ?></span>
                  <span><?= $row['name'] ?> · <?= $row['zone'] ?></span>
                  <?php if ($rowErr): ?>
                    <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--red,#e05555);opacity:0.85;"><?= htmlspecialchars($sInfo['error_code']) ?></span>
                  <?php endif; ?>
                </span>
                <?php if ($rowErr): ?>
                  <span class="badge-error" style="animation:none;font-size:0.65rem;padding:2px 7px;white-space:nowrap;"><i class="fas fa-circle-xmark"></i> Error</span>
                <?php else: ?>
                  <span class="badge badge-green">Online</span>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <footer style="background:#fff;border-top:1px solid var(--border);padding:20px 32px;display:flex;align-items:center;justify-content:space-between;font-size:0.75rem;color:var(--text-muted);">
      <span>© 2026 Solar IoT Farm System — Sensor Monitoring</span>
      <span style="display:flex;gap:8px;">
        <?php if ($errCount > 0): ?>
          <span class="badge-error" style="animation:none;"><i class="fas fa-triangle-exclamation"></i><?= $errCount ?> Sensor Error<?= $errCount>1?'s':'' ?></span>
        <?php endif; ?>
        <span class="live-badge"><div class="live-dot"></div> <?= 12 - $errCount ?> / 12 Online</span>
        <span class="badge badge-yellow"><i class="fas fa-sun" style="margin-right:4px;"></i>Solar Active</span>
      </span>
    </footer>
  </div>
</div>

<script>
  function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
      now.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) + ' · ' +
      now.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  }
  updateClock(); setInterval(updateClock, 1000);

  // Moisture bars
  const zones = [{label:'Farm',val:68,cls:'high'}];
  const viz = document.getElementById('moisture-viz');
  zones.forEach(z => {
    viz.innerHTML += `<div>
      <div class="mbar-wrap" title="${z.label}: ${z.val}%">
        <div class="mbar-fill ${z.cls}" style="height:${z.val}%"></div>
      </div>
      <div class="mbar-label" style="font-size:0.65rem;">${z.label}</div>
    </div>`;
  });
  setInterval(() => {
    document.querySelectorAll('.mbar-fill').forEach(b => {
      const cur = parseFloat(b.style.height);
      b.style.height = Math.min(95, Math.max(5, cur + (Math.random()-0.5)*3)).toFixed(1)+'%';
    });
  }, 5000);

  // Water tank sparkline
  (function() {
    const svg = document.getElementById('water-sparkline');
    if (!svg) return;
    const W = 200, H = 36;
    const data = [92,90,88,87,89,91,88,86,84,83,85,86,84,85,84,85,86,85,84,84,83,84,84,84];
    const min = Math.min(...data) - 3, max = Math.max(...data) + 3;
    const pts = data.map((v, i) => {
      const x = (i / (data.length - 1)) * W;
      const y = H - ((v - min) / (max - min)) * H;
      return `${x},${y}`;
    });
    const area = `M${pts[0]} ` + pts.slice(1).map(p=>`L${p}`).join(' ') + ` L${W},${H} L0,${H} Z`;
    const line = `M${pts[0]} ` + pts.slice(1).map(p=>`L${p}`).join(' ');
    svg.innerHTML = `
      <defs>
        <linearGradient id="sg" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="var(--water)" stop-opacity="0.25"/>
          <stop offset="100%" stop-color="var(--water)" stop-opacity="0"/>
        </linearGradient>
      </defs>
      <path d="${area}" fill="url(#sg)"/>
      <path d="${line}" fill="none" stroke="var(--water)" stroke-width="1.5" stroke-linejoin="round"/>`;
  })();

  document.addEventListener('click', e => {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.contains(e.target) && !e.target.closest('.mobile-toggle')) sidebar.classList.remove('open');
  });
</script>
</body>
</html>