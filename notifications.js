(function () {
  if (document.getElementById('notif-style')) return;
  const style = document.createElement('style');
  style.id = 'notif-style';
  style.textContent = `
    #notif-root {
      position: fixed; top: 20px; right: 20px;
      z-index: 99999;
      display: flex; flex-direction: column; gap: 10px;
      width: 340px; pointer-events: none;
    }
    .notif {
      background: #fff;
      border-radius: 12px;
      border: 0.5px solid rgba(0,0,0,0.12);
      border-left-width: 4px;
      padding: 14px 16px 14px 14px;
      display: flex; gap: 12px; align-items: flex-start;
      pointer-events: all;
      animation: notifIn 0.28s cubic-bezier(0.22,1,0.36,1);
      position: relative; overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    @keyframes notifIn {
      from { opacity: 0; transform: translateX(32px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    .notif.out {
      animation: notifOut 0.22s ease forwards;
    }
    @keyframes notifOut {
      to { opacity: 0; transform: translateX(40px); max-height: 0; padding: 0; margin: 0; }
    }
    /* Border colours */
    .notif-error   { border-left-color: #E24B4A; }
    .notif-warn    { border-left-color: #BA7517; }
    .notif-success { border-left-color: #3B6D11; }
    .notif-info    { border-left-color: #185FA5; }
    /* Icon circles */
    .notif-icon {
      width: 28px; height: 28px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 14px;
    }
    .notif-error  .notif-icon { background: #FCEBEB; color: #A32D2D; }
    .notif-warn   .notif-icon { background: #FAEEDA; color: #854F0B; }
    .notif-success .notif-icon { background: #EAF3DE; color: #3B6D11; }
    .notif-info   .notif-icon { background: #E6F1FB; color: #185FA5; }
    /* Text */
    .notif-body { flex: 1; min-width: 0; }
    .notif-label {
      font-size: 10px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.08em; margin-bottom: 2px;
    }
    .notif-error  .notif-label { color: #A32D2D; }
    .notif-warn   .notif-label { color: #854F0B; }
    .notif-success .notif-label { color: #3B6D11; }
    .notif-info   .notif-label { color: #185FA5; }
    .notif-title { font-size: 13px; font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }
    .notif-desc  { font-size: 12px; color: #666; line-height: 1.45; }
    .notif-time  { font-size: 11px; color: #aaa; margin-top: 5px; font-family: 'Fira Code', monospace; }
    /* Close button */
    .notif-close {
      background: none; border: none; cursor: pointer;
      padding: 2px 4px; color: #bbb; font-size: 12px;
      flex-shrink: 0; border-radius: 4px; line-height: 1;
    }
    .notif-close:hover { color: #333; background: #f0f0f0; }
    /* Progress bar */
    .notif-progress {
      position: absolute; bottom: 0; left: 0; height: 2px;
      background: currentColor; opacity: 0.2;
      animation: notifBar linear forwards;
    }
    .notif-error  .notif-progress { color: #E24B4A; }
    .notif-warn   .notif-progress { color: #BA7517; }
    .notif-success .notif-progress { color: #3B6D11; }
    .notif-info   .notif-progress { color: #185FA5; }
    @keyframes notifBar { from { width: 100%; } to { width: 0%; } }

    /* Responsive — stack below 400px screen */
    @media (max-width: 420px) {
      #notif-root { width: calc(100vw - 24px); right: 12px; top: 12px; }
    }
  `;
  document.head.appendChild(style);

  const root = document.createElement('div');
  root.id = 'notif-root';
  document.body.appendChild(root);
})();

const NOTIF_ICONS = {
  error:   '⚠',
  warn:    '⚠',
  success: '✓',
  info:    'ℹ'
};
const NOTIF_LABELS = {
  error:   'Alert',
  warn:    'Warning',
  success: 'Success',
  info:    'Info'
};

/**
 * Show a notification toast.
 *
 * @param {'error'|'warn'|'success'|'info'} type
 * @param {string} title
 * @param {string} desc  
 * @param {number} duration 
 */
function showNotif(type, title, desc, duration) {
  duration = (duration === undefined) ? 5000 : duration;

  const root = document.getElementById('notif-root');
  const now = new Date();
  const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

  const n = document.createElement('div');
  n.className = 'notif notif-' + type;
  n.innerHTML =
    '<div class="notif-icon">' + NOTIF_ICONS[type] + '</div>' +
    '<div class="notif-body">' +
      '<div class="notif-label">' + NOTIF_LABELS[type] + '</div>' +
      '<div class="notif-title">' + escHtml(title) + '</div>' +
      '<div class="notif-desc">' + escHtml(desc) + '</div>' +
      '<div class="notif-time">' + timeStr + '</div>' +
    '</div>' +
    '<button class="notif-close" onclick="dismissNotif(this.parentElement)" title="Dismiss">✕</button>' +
    (duration > 0
      ? '<div class="notif-progress" style="animation-duration:' + duration + 'ms"></div>'
      : '');

  root.prepend(n);

  if (duration > 0) {
    setTimeout(function () { dismissNotif(n); }, duration);
  }
  return n;
}

function dismissNotif(n) {
  if (!n || n.classList.contains('out')) return;
  n.classList.add('out');
  setTimeout(function () { if (n && n.parentNode) n.parentNode.removeChild(n); }, 240);
}

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}

/* Sensor / environment */
const FarmAlerts = {
  highTemp:       function(zone, val) { showNotif('error',   'High Temperature',      'Zone ' + zone + ' at ' + val + '°C — above safe limit (35°C).'); },
  lowMoisture:    function(zone, val) { showNotif('warn',    'Low Soil Moisture',     'Zone ' + zone + ' at ' + val + '% — irrigation threshold reached.'); },
  criticalMoisture:function(zone,val){ showNotif('error',   'Critical Dry Soil',     'Zone ' + zone + ' at ' + val + '% — well below minimum 25%.'); },
  highHumidity:   function(zone, val) { showNotif('warn',    'High Humidity',         'Zone ' + zone + ' at ' + val + '% — risk of fungal growth.'); },
  sensorOffline:  function(nodeId)   { showNotif('info',    'Sensor Offline',        nodeId + ' stopped reporting. Check connection.'); },

  /* Power */
  lowBattery:     function(pct)      { showNotif('warn',    'Low Battery',           'Battery at ' + pct + '% — solar output may be insufficient.'); },
  criticalBattery:function(pct)      { showNotif('error',   'Critical Battery',      'Battery at ' + pct + '% — system may shut down soon.'); },
  batteryCharged: function()         { showNotif('success', 'Battery Fully Charged', 'Solar bank at 100% — switched to supply mode.'); },

  /* Water / chemical */
  waterLow:       function(pct)      { showNotif('warn',    'Water Tank Low',        'Tank at ' + pct + '% — plan a refill soon.'); },
  waterCritical:  function(pct)      { showNotif('error',   'Water Tank Critical',   'Tank at ' + pct + '% — immediate refill required.'); },
  chemLow:        function(chemical) { showNotif('warn',    'Chemical Tank Low',     (chemical || 'Chemical') + ' reserve low — reorder soon.'); },

  /* Automation events */
  irrigationDone: function(zone, litres){ showNotif('success','Irrigation Complete',  'Zone ' + zone + ' — ' + litres + ' L dispensed successfully.'); },
  sprayDone:      function(zone, type)  { showNotif('success','Spray Completed',       type + ' applied to Zone ' + zone + '.'); },
  sprayScheduled: function(zone, time)  { showNotif('info',   'Spray Reminder',        'Zone ' + zone + ' spray starts at ' + time + '.'); },
  harvestReady:   function(zone)        { showNotif('info',   'Harvest Ready',         'Zone ' + zone + ' — tomatoes ready for harvest!'); },

  /* Scheduling */
  taskAdded:      function(name)     { showNotif('success', 'Task Scheduled',        '"' + name + '" added to the farm calendar.'); },
  taskDeleted:    function(name)     { showNotif('info',    'Task Removed',          '"' + name + '" was removed from the schedule.'); },

  /* Users */
  userAdded:      function(uname)    { showNotif('success', 'User Created',          'New account for ' + uname + ' added successfully.'); },
  userUpdated:    function(uname)    { showNotif('success', 'User Updated',          uname + "'s account has been updated."); },
  userDeleted:    function(uname)    { showNotif('info',    'User Deleted',          '@' + uname + ' was removed from the system.'); },
};