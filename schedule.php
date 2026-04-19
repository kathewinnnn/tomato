<?php
session_start();
require_once 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username    = htmlspecialchars($_SESSION['username'] ?? 'User',  ENT_QUOTES, 'UTF-8');
$role        = htmlspecialchars($_SESSION['role']     ?? 'Farmer', ENT_QUOTES, 'UTF-8');
$active_page = 'scheduling';

$preselect_date = '';
if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $preselect_date = htmlspecialchars($_GET['date']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Scheduling — Tomato Cultivation System</title>

  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@300;400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/schedule.css"/>

  <style>
    /* ── TYPE GRID ── */
    .type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

    .type-btn {
      position: relative;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 8px; padding: 14px 8px 13px;
      border-radius: var(--radius-md); border: 2px solid var(--border);
      background: #fff; cursor: pointer; font-family: var(--font-body);
      font-size: .75rem; font-weight: 700; color: var(--text-muted);
      transition: all .18s cubic-bezier(.4,0,.2,1); user-select: none;
    }
    .type-btn:hover {
      border-color: var(--hc); color: var(--hc); background: var(--hp);
      transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.08);
    }
    .type-btn:active { transform: translateY(0); box-shadow: none; }

    .tb-icon {
      width: 40px; height: 40px; border-radius: 11px;
      background: var(--border); display: flex; align-items: center;
      justify-content: center; font-size: 16px; color: var(--text-muted);
      transition: background .18s, color .18s, transform .18s;
    }
    .type-btn:hover .tb-icon { background: var(--hp); color: var(--hc); transform: scale(1.08); }
    .tb-label { font-size: .73rem; font-weight: 700; line-height: 1; }
    .tb-check {
      position: absolute; top: 7px; right: 7px;
      width: 17px; height: 17px; border-radius: 50%;
      border: 2px solid var(--border); background: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 8px; color: transparent; transition: all .18s;
    }

    /* Selected states */
    .type-btn.sel-irr { border-color: var(--water); background: var(--water-pale); }
    .type-btn.sel-irr .tb-icon  { background: rgba(21,117,168,.14); color: var(--water); }
    .type-btn.sel-irr .tb-label { color: var(--water); }
    .type-btn.sel-irr .tb-check { border-color: var(--water); background: var(--water); color: #fff; }

    .type-btn.sel-frt { border-color: var(--solar); background: var(--solar-pale); }
    .type-btn.sel-frt .tb-icon  { background: rgba(184,125,0,.14); color: var(--solar); }
    .type-btn.sel-frt .tb-label { color: var(--solar); }
    .type-btn.sel-frt .tb-check { border-color: var(--solar); background: var(--solar); color: #fff; }

    .type-btn.sel-har { border-color: var(--red); background: var(--red-pale); }
    .type-btn.sel-har .tb-icon  { background: rgba(214,48,49,.14); color: var(--red); }
    .type-btn.sel-har .tb-label { color: var(--red); }
    .type-btn.sel-har .tb-check { border-color: var(--red); background: var(--red); color: #fff; }

    .type-btn.sel-mnt { border-color: var(--green-mid); background: var(--green-pale); }
    .type-btn.sel-mnt .tb-icon  { background: rgba(45,134,83,.14); color: var(--green-mid); }
    .type-btn.sel-mnt .tb-label { color: var(--green-mid); }
    .type-btn.sel-mnt .tb-check { border-color: var(--green-mid); background: var(--green-mid); color: #fff; }

    /* ── LOCATION DISPLAY (read-only, Farm only) ── */
    .location-display {
      display: flex; align-items: center; gap: 8px;
      border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      padding: 8px 11px; background: var(--sand);
      font-size: .84rem; color: var(--text-muted); font-weight: 600;
    }
    .location-display i { color: var(--green-mid); font-size: .85rem; }

    /* ── TOAST ── */
    #toast {
      position: fixed; bottom: 28px; left: 50%;
      transform: translateX(-50%) translateY(20px);
      background: var(--green-dark); color: #fff;
      padding: 10px 22px; border-radius: 30px;
      font-size: .82rem; font-weight: 600;
      box-shadow: var(--shadow-md); z-index: 9999;
      opacity: 0; transition: opacity .25s, transform .25s; white-space: nowrap;
    }
    #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    #toast.error { background: var(--red); }

    /* ── FOOTER ── */
    footer {
      background: var(--card); border-top: 1px solid var(--border);
      padding: 16px 32px; display: flex; align-items: center;
      justify-content: space-between; font-size: .73rem; color: var(--text-muted);
    }
  </style>
</head>
<body>
<div class="layout">

  <?php include 'sidebar.php'; ?>

  <div class="main">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-title"><i class="fas fa-calendar-days"></i>&nbsp;Farm Scheduling</div>
      <div class="topbar-clock" id="clock">—</div>
    </div>

    <!-- PAGE -->
    <div class="page-content">

      <div class="page-hero">
        <div class="page-hero-badge"><i class="fas fa-calendar-check" style="margin-right:6px;"></i>Irrigation &amp; Fertilizer Planner</div>
        <h1>Farm <span>Schedule</span></h1>
        <p>Plan and manage irrigation cycles, fertilization, harvests, and maintenance tasks. Click any date to add or edit scheduled tasks.</p>
      </div>

      <div class="sched-layout">

        <!-- LEFT: Calendar + Upcoming -->
        <div class="cal-col">

          <div class="cal-card">
            <div class="cal-header">
              <div class="cal-month-label" id="cal-month-label">—</div>
              <div class="cal-nav-btns">
                <button class="cal-nav-btn today-btn" onclick="goToday()">Today</button>
                <button class="cal-nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="cal-nav-btn" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
            <div class="cal-body">
              <div class="cal-weekdays">
                <div class="cal-wd">Sun</div><div class="cal-wd">Mon</div><div class="cal-wd">Tue</div>
                <div class="cal-wd">Wed</div><div class="cal-wd">Thu</div><div class="cal-wd">Fri</div>
                <div class="cal-wd">Sat</div>
              </div>
              <div class="cal-days-grid" id="cal-days-grid"></div>
            </div>
            <div class="cal-legend">
              <div class="legend-item"><div class="legend-dot" style="background:var(--water);"></div>Irrigation</div>
              <div class="legend-item"><div class="legend-dot" style="background:var(--solar);"></div>Fertilization</div>
              <div class="legend-item"><div class="legend-dot" style="background:var(--red);"></div>Harvest</div>
              <div class="legend-item"><div class="legend-dot" style="background:var(--green-mid);"></div>Maintenance</div>
              <div class="legend-item" style="margin-left:auto;font-family:var(--font-mono);font-size:.68rem;">
                <i class="fas fa-circle-plus" style="margin-right:4px;color:var(--green-mid);"></i>Click date to add
              </div>
            </div>
          </div>

          <!-- UPCOMING -->
          <div class="detail-card" style="margin-top:18px;">
            <div class="detail-head">
              <div class="detail-head-icon" style="background:var(--water-pale);color:var(--water);"><i class="fas fa-list-check"></i></div>
              <div>
                <div class="detail-title">Upcoming Tasks</div>
                <div class="detail-subtitle" id="upcoming-count">Next 14 days</div>
              </div>
            </div>
            <div class="detail-body" id="upcoming-list">
              <div class="empty-state"><div class="empty-icon">⏳</div><div class="empty-title">Loading…</div></div>
            </div>
          </div>

        </div>

        <!-- RIGHT: Detail Panel -->
        <div class="detail-panel">

          <!-- Day events -->
          <div class="detail-card">
            <div class="detail-head">
              <div class="detail-head-icon" style="background:var(--green-pale);color:var(--green-mid);"><i class="fas fa-calendar-day"></i></div>
              <div>
                <div class="detail-title" id="selected-day-label">Select a date</div>
                <div class="detail-subtitle" id="selected-day-count">Click a date to see tasks</div>
              </div>
            </div>
            <div class="detail-body">
              <div id="day-events-list">
                <div class="empty-state">
                  <div class="empty-icon">📅</div>
                  <div class="empty-title">No date selected</div>
                  <div class="empty-desc">Click any date on the calendar to view and manage tasks</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Add Task Form -->
          <div class="detail-card">
            <div class="detail-head">
              <div class="detail-head-icon" style="background:var(--solar-pale);color:var(--solar);"><i class="fas fa-plus"></i></div>
              <div>
                <div class="detail-title">Add New Task</div>
                <div class="detail-subtitle" id="add-form-date-label">Select a date first</div>
              </div>
            </div>
            <div class="detail-body">

              <!-- Task Type -->
              <div class="form-group">
                <label class="form-label">Task Type</label>
                <div class="type-grid" id="add-type-grid">
                  <button type="button" class="type-btn sel-irr" id="type-irrigation"
                    data-sel="sel-irr" style="--hc:var(--water);--hp:var(--water-pale);"
                    onclick="selectType(this)">
                    <span class="tb-check"><i class="fas fa-check"></i></span>
                    <span class="tb-icon"><i class="fas fa-droplet"></i></span>
                    <span class="tb-label">Irrigation</span>
                  </button>
                  <button type="button" class="type-btn" id="type-fertilization"
                    data-sel="sel-frt" style="--hc:var(--solar);--hp:var(--solar-pale);"
                    onclick="selectType(this)">
                    <span class="tb-check"><i class="fas fa-check"></i></span>
                    <span class="tb-icon"><i class="fas fa-flask"></i></span>
                    <span class="tb-label">Fertilization</span>
                  </button>
                  <button type="button" class="type-btn" id="type-harvest"
                    data-sel="sel-har" style="--hc:var(--red);--hp:var(--red-pale);"
                    onclick="selectType(this)">
                    <span class="tb-check"><i class="fas fa-check"></i></span>
                    <span class="tb-icon"><i class="fas fa-basket-shopping"></i></span>
                    <span class="tb-label">Harvest</span>
                  </button>
                  <button type="button" class="type-btn" id="type-maintenance"
                    data-sel="sel-mnt" style="--hc:var(--green-mid);--hp:var(--green-pale);"
                    onclick="selectType(this)">
                    <span class="tb-check"><i class="fas fa-check"></i></span>
                    <span class="tb-icon"><i class="fas fa-wrench"></i></span>
                    <span class="tb-label">Maintenance</span>
                  </button>
                </div>
              </div>

              <!-- Task Name -->
              <div class="form-group">
                <label class="form-label">Task Name</label>
                <input type="text" class="form-input" id="task-name" placeholder="e.g. Morning Irrigation" maxlength="120"/>
              </div>

              <!-- Time -->
              <div class="form-group">
                <label class="form-label">Time</label>
                <input type="time" class="form-input" id="task-time" value="06:00"/>
              </div>

              <!-- Location (Farm only) -->
              <div class="form-group">
                <label class="form-label">Location</label>
                <div class="location-display">
                  <i class="fas fa-location-dot"></i> Farm
                </div>
                <input type="hidden" id="task-zone" value="Farm"/>
              </div>

              <!-- Notes -->
              <div class="form-group">
                <label class="form-label">Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <textarea class="form-textarea" id="task-notes" placeholder="Additional details, duration, amounts…"></textarea>
              </div>

              <div class="btn-row">
                <button type="button" class="btn btn-primary" id="add-btn" onclick="addEvent()"><i class="fas fa-plus"></i> Add Task</button>
                <button type="button" class="btn btn-ghost" onclick="clearForm()"><i class="fas fa-xmark"></i></button>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    <footer>
      <span>© 2026 Solar IoT Farm System — Schedule Planner</span>
      <span style="color:var(--green-mid);font-weight:700;"><i class="fas fa-database" style="margin-right:4px;"></i>Tasks saved to database</span>
    </footer>
  </div>
</div>

<!-- ═══════════════ EDIT MODAL ═══════════════ -->
<div class="modal-overlay" id="edit-modal" onclick="handleBackdropClick(event)">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-head-icon" id="modal-type-icon" style="background:var(--water-pale);color:var(--water);">
        <i class="fas fa-droplet"></i>
      </div>
      <div>
        <div class="modal-title">Edit Task</div>
        <div class="modal-subtitle" id="modal-date-label">—</div>
      </div>
      <button type="button" class="modal-close" onclick="closeModal()" title="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">

      <!-- Task Type -->
      <div class="form-group">
        <label class="form-label">Task Type</label>
        <div class="type-grid" id="edit-type-grid">
          <button type="button" class="type-btn" id="edit-type-irrigation"
            data-sel="sel-irr" data-pale="var(--water-pale)" data-color="var(--water)" data-icon="fa-droplet"
            style="--hc:var(--water);--hp:var(--water-pale);"
            onclick="selectEditType(this)">
            <span class="tb-check"><i class="fas fa-check"></i></span>
            <span class="tb-icon"><i class="fas fa-droplet"></i></span>
            <span class="tb-label">Irrigation</span>
          </button>
          <button type="button" class="type-btn" id="edit-type-fertilization"
            data-sel="sel-frt" data-pale="var(--solar-pale)" data-color="var(--solar)" data-icon="fa-flask"
            style="--hc:var(--solar);--hp:var(--solar-pale);"
            onclick="selectEditType(this)">
            <span class="tb-check"><i class="fas fa-check"></i></span>
            <span class="tb-icon"><i class="fas fa-flask"></i></span>
            <span class="tb-label">Fertilization</span>
          </button>
          <button type="button" class="type-btn" id="edit-type-harvest"
            data-sel="sel-har" data-pale="var(--red-pale)" data-color="var(--red)" data-icon="fa-basket-shopping"
            style="--hc:var(--red);--hp:var(--red-pale);"
            onclick="selectEditType(this)">
            <span class="tb-check"><i class="fas fa-check"></i></span>
            <span class="tb-icon"><i class="fas fa-basket-shopping"></i></span>
            <span class="tb-label">Harvest</span>
          </button>
          <button type="button" class="type-btn" id="edit-type-maintenance"
            data-sel="sel-mnt" data-pale="var(--green-pale)" data-color="var(--green-mid)" data-icon="fa-wrench"
            style="--hc:var(--green-mid);--hp:var(--green-pale);"
            onclick="selectEditType(this)">
            <span class="tb-check"><i class="fas fa-check"></i></span>
            <span class="tb-icon"><i class="fas fa-wrench"></i></span>
            <span class="tb-label">Maintenance</span>
          </button>
        </div>
      </div>

      <!-- Task Name -->
      <div class="form-group">
        <label class="form-label">Task Name</label>
        <input type="text" class="form-input" id="edit-name" placeholder="Task name" maxlength="120"/>
      </div>

      <!-- Time -->
      <div class="form-group">
        <label class="form-label">Time</label>
        <input type="time" class="form-input" id="edit-time"/>
      </div>

      <!-- Location (Farm only) -->
      <div class="form-group">
        <label class="form-label">Location</label>
        <div class="location-display">
          <i class="fas fa-location-dot"></i> Farm
        </div>
        <input type="hidden" id="edit-zone" value="Farm"/>
      </div>

      <!-- Reschedule Date -->
      <div class="form-group">
        <label class="form-label">Date <span style="font-weight:400;text-transform:none;letter-spacing:0;">(reschedule)</span></label>
        <input type="date" class="form-input" id="edit-date"/>
      </div>

      <!-- Notes -->
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
        <textarea class="form-textarea" id="edit-notes" placeholder="Additional details, duration, amounts…"></textarea>
      </div>

    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-save"   onclick="saveEdit()">       <i class="fas fa-floppy-disk"></i> Save Changes</button>
      <button type="button" class="btn btn-delete" onclick="deleteFromModal()"><i class="fas fa-trash-can"></i> Delete</button>
      <button type="button" class="btn btn-ghost"  onclick="closeModal()" style="margin-left:auto;">Cancel</button>
    </div>
  </div>
</div>

<!-- ═══════════════ DELETE CONFIRMATION MODAL ═══════════════ -->
<div class="modal-overlay" id="delete-modal" onclick="handleDeleteBackdropClick(event)">
  <div class="modal" style="max-width: 380px;">
    <div class="modal-head" style="background: var(--red-pale); border-bottom-color: rgba(214,48,49,0.15);">
      <div class="modal-head-icon" style="background: var(--red-pale); color: var(--red);">
        <i class="fas fa-triangle-exclamation"></i>
      </div>
      <div>
        <div class="modal-title">Delete Task</div>
        <div class="modal-subtitle" id="delete-task-name">—</div>
      </div>
      <button type="button" class="modal-close" onclick="closeDeleteModal()" title="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="text-align: center; padding: 24px 20px;">
      <p style="margin: 0 0 8px; font-size: 0.95rem; color: var(--text);">Are you sure you want to delete this task?</p>
      <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">This action cannot be undone.</p>
    </div>
    <div class="modal-footer" style="justify-content: center; gap: 10px;">
      <button type="button" class="btn btn-delete" onclick="confirmDeleteEvent()">
        <i class="fas fa-trash-can"></i> Delete
      </button>
      <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast"></div>

<script>
/* ── Clock ── */
function updateClock() {
  const n = new Date();
  document.getElementById('clock').textContent =
    n.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) + ' · ' +
    n.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock(); setInterval(updateClock, 1000);

/* ── Toast ── */
let toastTimer;
function showToast(msg, isError = false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'show' + (isError ? ' error' : '');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { t.className = ''; }, 2800);
}

const NOW      = new Date();
const todayKey = fmtKey(NOW.getFullYear(), NOW.getMonth() + 1, NOW.getDate());
let calDate    = new Date(NOW.getFullYear(), NOW.getMonth(), 1);
let events     = {};
let selectedDate = null;
let selectedType = 'irrigation';
let editType     = 'irrigation';
let editingKey   = null;
let editingId    = null;
let isSaving     = false;

const ALL_SEL_CLASSES = ['sel-irr','sel-frt','sel-har','sel-mnt'];

const TC = {
  irrigation:    { color:'var(--water)',     pale:'var(--water-pale)',  icon:'fa-droplet',        selClass:'sel-irr', pill:'irrigation',    badge:'badge-irr', label:'Irrigation'   },
  fertilization: { color:'var(--solar)',     pale:'var(--solar-pale)',  icon:'fa-flask',           selClass:'sel-frt', pill:'fertilization', badge:'badge-spr', label:'Fertilization'},
  harvest:       { color:'var(--red)',       pale:'var(--red-pale)',    icon:'fa-basket-shopping', selClass:'sel-har', pill:'harvest',       badge:'badge-har', label:'Harvest'      },
  maintenance:   { color:'var(--green-mid)', pale:'var(--green-pale)', icon:'fa-wrench',          selClass:'sel-mnt', pill:'maintenance',   badge:'badge-mnt', label:'Maintenance'  },
};
const TYPES = Object.keys(TC);

function fmtKey(y, m, d) {
  return y + '-' + String(m).padStart(2,'0') + '-' + String(d).padStart(2,'0');
}

function loadSchedules(cb) {
  const y = calDate.getFullYear(), m = calDate.getMonth() + 1;
  const lastD = new Date(y, m, 0).getDate();
  const start = fmtKey(y, m, 1);
  const end   = fmtKey(y, m, lastD);

  fetch('get_schedules.php?start=' + start + '&end=' + end)
    .then(r => r.json())
    .then(data => {
      for (let d = 1; d <= lastD; d++) delete events[fmtKey(y, m, d)];
      data.forEach(s => {
        const key = s.schedule_date;
        if (!events[key]) events[key] = [];
        events[key].push({
          id   : s.id,
          type : s.task_type,
          name : s.task_name,
          time : s.task_time ? s.task_time.slice(0,5) : '00:00',
          zone : 'Farm',
          notes: s.task_notes || ''
        });
      });
      renderCalendar();
      if (selectedDate) renderDayPanel();
      renderUpcoming();
      if (cb) cb();
    })
    .catch(() => {
      renderCalendar();
      if (selectedDate) renderDayPanel();
      renderUpcoming();
    });
}

/* ══════════ CALENDAR ══════════ */
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function renderCalendar() {
  const y = calDate.getFullYear(), mo = calDate.getMonth();
  document.getElementById('cal-month-label').textContent = MONTHS[mo] + ' ' + y;
  const grid     = document.getElementById('cal-days-grid');
  grid.innerHTML = '';
  const firstDay = new Date(y, mo, 1).getDay();
  const daysInM  = new Date(y, mo + 1, 0).getDate();
  const prevDays = new Date(y, mo,     0).getDate();

  for (let i = firstDay - 1; i >= 0; i--)
    renderCell(grid, prevDays - i, fmtKey(y, mo, prevDays - i), true);

  for (let d = 1; d <= daysInM; d++)
    renderCell(grid, d, fmtKey(y, mo + 1, d), false);

  const rem = (firstDay + daysInM) % 7;
  if (rem > 0) for (let d = 1; d <= 7 - rem; d++)
    renderCell(grid, d, fmtKey(y, mo + 2, d), true);
}

function renderCell(grid, d, key, otherMonth) {
  const cell = document.createElement('div');
  const cellDate = new Date(key + 'T00:00:00');
  const today = new Date(); today.setHours(0,0,0,0);
  const isPast = cellDate < today;

  cell.className = 'cal-cell' + (otherMonth ? ' other-month' : '') + (isPast ? ' past-date' : '');
  if (key === todayKey && !otherMonth) cell.classList.add('today');
  if (key === selectedDate)            cell.classList.add('selected');

  const evs = events[key] || [];
  let pillsHTML = '';
  evs.slice(0, 3).forEach(ev => {
    const safe = ev.name.length > 14 ? ev.name.slice(0, 13) + '…' : ev.name;
    pillsHTML += `<div class="cal-event-pill ${TC[ev.type].pill}"><i class="fas ${TC[ev.type].icon}"></i> ${safe}</div>`;
  });
  if (evs.length > 3)
    pillsHTML += `<div class="cal-event-pill maintenance">+${evs.length - 3} more</div>`;

  cell.innerHTML = `
    <div class="cal-cell-day">
      <span>${d}</span>
      ${key === todayKey && !otherMonth ? '<span class="cal-today-dot"></span>' : ''}
    </div>
    <div class="cal-cell-events">${pillsHTML}</div>
    <div class="cal-add-hint">+ Add task</div>`;

  cell.onclick = () => selectDate(key);
  grid.appendChild(cell);
}

/* ══════════ DATE SELECTION ══════════ */
function selectDate(key) {
  const cellDate = new Date(key + 'T00:00:00');
  const today = new Date(); today.setHours(0,0,0,0);
  if (cellDate < today) { showToast('Cannot select past dates.', true); return; }

  selectedDate = key;
  renderCalendar();
  renderDayPanel();
  const d = new Date(key + 'T00:00:00');
  document.getElementById('add-form-date-label').textContent =
    d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});
  document.getElementById('selected-day-label').textContent =
    d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric'});
}

/* ══════════ DAY PANEL ══════════ */
function renderDayPanel() {
  const list = document.getElementById('day-events-list');
  const evs  = events[selectedDate] || [];
  document.getElementById('selected-day-count').textContent =
    evs.length === 0 ? 'No tasks scheduled' : evs.length + ' task' + (evs.length !== 1 ? 's' : '') + ' scheduled';

  if (evs.length === 0) {
    list.innerHTML = `<div class="empty-state">
      <div class="empty-icon">🌱</div>
      <div class="empty-title">No tasks this day</div>
      <div class="empty-desc">Use the form below to schedule irrigation, fertilization, or other farm work.</div>
    </div>`;
    return;
  }

  list.innerHTML = '';
  evs.forEach(ev => {
    const cfg  = TC[ev.type];
    const item = document.createElement('div');
    item.className = 'event-item';
    item.innerHTML = `
      <div class="event-dot" style="background:${cfg.color};"></div>
      <div class="event-body">
        <div class="event-title">${escHtml(ev.name)}</div>
        <div class="event-meta">
          <span class="upcoming-badge ${cfg.badge}">${cfg.label}</span>
          Farm${ev.notes ? ' · ' + escHtml(ev.notes) : ''}
        </div>
        <div class="event-time"><i class="fas fa-clock" style="margin-right:4px;font-size:.65rem;"></i>${ev.time}</div>
      </div>
      <div class="event-actions">
        <button type="button" class="btn-edit-sm"   title="Edit"   onclick="openEditModal('${selectedDate}', ${ev.id})"><i class="fas fa-pen"></i></button>
        <button type="button" class="btn-danger-sm" title="Delete" onclick="confirmDelete('${selectedDate}', ${ev.id}, '${escHtml(ev.name)}')"><i class="fas fa-trash-can"></i></button>
      </div>`;
    list.appendChild(item);
  });
}

function renderUpcoming() {
  const list = document.getElementById('upcoming-list');
  const upcoming = [];
  for (let i = 0; i <= 14; i++) {
    const d = new Date(NOW.getFullYear(), NOW.getMonth(), NOW.getDate() + i);
    const key = fmtKey(d.getFullYear(), d.getMonth() + 1, d.getDate());
    if (events[key]) events[key].forEach(ev => upcoming.push({key, date: d, ev}));
  }
  upcoming.sort((a, b) => a.key.localeCompare(b.key) || a.ev.time.localeCompare(b.ev.time));
  document.getElementById('upcoming-count').textContent =
    'Next 14 days · ' + upcoming.length + ' task' + (upcoming.length !== 1 ? 's' : '');

  if (upcoming.length === 0) {
    list.innerHTML = `<div class="empty-state"><div class="empty-icon">📆</div><div class="empty-title">No upcoming tasks</div><div class="empty-desc">Schedule your first task above.</div></div>`;
    return;
  }

  list.innerHTML = '';
  upcoming.forEach(({key, date, ev}) => {
    const cfg      = TC[ev.type];
    const dayLabel = key === todayKey ? 'Today' : date.toLocaleDateString('en-US',{month:'short',day:'numeric'});
    const item     = document.createElement('div');
    item.className = 'upcoming-item';
    item.innerHTML = `
      <div class="event-dot" style="background:${cfg.color};flex-shrink:0;"></div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:.84rem;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(ev.name)}</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">${dayLabel} · ${ev.time} · Farm</div>
      </div>
      <div style="display:flex;gap:5px;align-items:center;flex-shrink:0;">
        <span class="upcoming-badge ${cfg.badge}"><i class="fas ${cfg.icon}" style="margin-right:3px;"></i>${cfg.label}</span>
        <button type="button" class="btn-edit-sm" style="padding:4px 8px;font-size:.7rem;" title="Edit" onclick="openEditModal('${key}', ${ev.id})">
          <i class="fas fa-pen"></i>
        </button>
      </div>`;
    list.appendChild(item);
  });
}

/* ══════════ TYPE SELECTION ══════════ */
function selectType(btn) {
  const grid = document.getElementById('add-type-grid');
  grid.querySelectorAll('.type-btn').forEach(b => {
    ALL_SEL_CLASSES.forEach(c => b.classList.remove(c));
  });
  btn.classList.add(btn.dataset.sel);

  // Map sel class back to type string
  const selToType = { 'sel-irr':'irrigation', 'sel-frt':'fertilization', 'sel-har':'harvest', 'sel-mnt':'maintenance' };
  selectedType = selToType[btn.dataset.sel];
}

function selectEditType(btn) {
  const grid = document.getElementById('edit-type-grid');
  grid.querySelectorAll('.type-btn').forEach(b => {
    ALL_SEL_CLASSES.forEach(c => b.classList.remove(c));
  });
  btn.classList.add(btn.dataset.sel);

  const selToType = { 'sel-irr':'irrigation', 'sel-frt':'fertilization', 'sel-har':'harvest', 'sel-mnt':'maintenance' };
  editType = selToType[btn.dataset.sel];

  // Update modal header icon
  const icon = document.getElementById('modal-type-icon');
  if (icon) {
    icon.style.background = btn.dataset.pale;
    icon.style.color      = btn.dataset.color;
    icon.innerHTML        = '<i class="fas ' + btn.dataset.icon + '"></i>';
  }
}

/* ══════════ ADD EVENT ══════════ */
function addEvent() {
  if (!selectedDate) { showToast('Please select a date on the calendar first.', true); return; }

  const today = new Date(); today.setHours(0,0,0,0);
  const selected = new Date(selectedDate + 'T00:00:00');
  if (selected < today) { showToast('Cannot schedule tasks on past dates.', true); return; }

  const name = document.getElementById('task-name').value.trim();
  if (!name) { document.getElementById('task-name').focus(); showToast('Task name is required.', true); return; }
  if (isSaving) return;
  isSaving = true;

  const addBtn = document.getElementById('add-btn');
  addBtn.disabled = true;
  addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

  const body = new URLSearchParams({
    action : 'add',
    date   : selectedDate,
    type   : selectedType,
    name   : name,
    time   : document.getElementById('task-time').value || '06:00',
    zone   : 'Farm',
    notes  : document.getElementById('task-notes').value.trim()
  });

  fetch('save_schedule.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (!events[selectedDate]) events[selectedDate] = [];
        events[selectedDate].push({
          id   : data.id,
          type : selectedType,
          name : name,
          time : document.getElementById('task-time').value || '06:00',
          zone : 'Farm',
          notes: document.getElementById('task-notes').value.trim()
        });
        events[selectedDate].sort((a, b) => a.time.localeCompare(b.time));
        clearForm();
        renderCalendar();
        renderDayPanel();
        renderUpcoming();
        showToast('✓ Task added successfully!');
      } else {
        showToast('Error: ' + (data.error || 'Could not save task.'), true);
      }
    })
    .catch(() => showToast('Network error. Please try again.', true))
    .finally(() => {
      isSaving = false;
      addBtn.disabled = false;
      addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Task';
    });
}

/* ══════════ DELETE ══════════ */
let deleteTargetKey = null;
let deleteTargetId  = null;

function confirmDelete(key, id, name) {
  deleteTargetKey = key;
  deleteTargetId  = id;
  document.getElementById('delete-task-name').textContent = name;
  document.getElementById('delete-modal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
  document.getElementById('delete-modal').classList.remove('open');
  document.body.style.overflow = '';
  deleteTargetKey = null;
  deleteTargetId  = null;
}

function handleDeleteBackdropClick(e) {
  if (e.target === document.getElementById('delete-modal')) closeDeleteModal();
}

function confirmDeleteEvent() {
  if (deleteTargetKey === null || deleteTargetId === null) return;
  const key = deleteTargetKey;
  const id  = deleteTargetId;
  closeDeleteModal();
  deleteEvent(key, id);
}

function deleteEvent(key, id) {
  const body = new URLSearchParams({ action:'delete', id: String(id) });
  fetch('save_schedule.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
    .then(r => r.json())
    .then(data => {
      if (data.success || data.deleted) {
        if (events[key] && Array.isArray(events[key])) {
          events[key] = events[key].filter(e => Number(e.id) !== Number(id));
          if (events[key].length === 0) delete events[key];
        }
        renderCalendar();
        renderDayPanel();
        renderUpcoming();
        showToast('Task deleted.');
      } else {
        showToast('Error: ' + (data.error || 'Could not delete task.'), true);
      }
    })
    .catch(() => showToast('Network error.', true));
}

function clearForm() {
  document.getElementById('task-name').value  = '';
  document.getElementById('task-time').value  = '06:00';
  document.getElementById('task-notes').value = '';
}

function changeMonth(delta) {
  calDate.setMonth(calDate.getMonth() + delta);
  loadSchedules();
}

function goToday() {
  calDate = new Date(NOW.getFullYear(), NOW.getMonth(), 1);
  loadSchedules(() => selectDate(todayKey));
}

/* ══════════ EDIT MODAL ══════════ */
function openEditModal(key, id) {
  const ev = (events[key] || []).find(e => e.id === id);
  if (!ev) return;
  editingKey = key;
  editingId  = id;

  document.getElementById('edit-name').value  = ev.name;
  document.getElementById('edit-time').value  = ev.time;
  document.getElementById('edit-date').value  = key;
  document.getElementById('edit-notes').value = ev.notes || '';

  // Select the matching type button
  const grid = document.getElementById('edit-type-grid');
  const typeToSel = { irrigation:'sel-irr', fertilization:'sel-frt', harvest:'sel-har', maintenance:'sel-mnt' };
  grid.querySelectorAll('.type-btn').forEach(b => {
    ALL_SEL_CLASSES.forEach(c => b.classList.remove(c));
    if (b.dataset.sel === typeToSel[ev.type]) {
      b.classList.add(b.dataset.sel);
      // Update modal header icon
      const icon = document.getElementById('modal-type-icon');
      icon.style.background = b.dataset.pale;
      icon.style.color      = b.dataset.color;
      icon.innerHTML        = '<i class="fas ' + b.dataset.icon + '"></i>';
    }
  });
  editType = ev.type;

  const d = new Date(key + 'T00:00:00');
  document.getElementById('modal-date-label').textContent =
    d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});

  document.getElementById('edit-modal').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('edit-name').focus(), 250);
}

function closeModal() {
  document.getElementById('edit-modal').classList.remove('open');
  document.body.style.overflow = '';
  editingKey = null; editingId = null;
}

function handleBackdropClick(e) {
  if (e.target === document.getElementById('edit-modal')) closeModal();
}

function saveEdit() {
  if (editingKey === null || editingId === null) return;

  const newDate = document.getElementById('edit-date').value || editingKey;
  const editDate = new Date(newDate + 'T00:00:00');
  const today = new Date(); today.setHours(0,0,0,0);
  if (editDate < today) { showToast('Cannot reschedule to a past date.', true); return; }

  const name = document.getElementById('edit-name').value.trim();
  if (!name) { document.getElementById('edit-name').focus(); showToast('Task name is required.', true); return; }

  const time  = document.getElementById('edit-time').value || '06:00';
  const notes = document.getElementById('edit-notes').value.trim();

  const body = new URLSearchParams({
    action: 'update',
    id    : editingId,
    date  : newDate,
    type  : editType,
    name, time,
    zone  : 'Farm',
    notes
  });

  fetch('save_schedule.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (events[editingKey]) {
          events[editingKey] = events[editingKey].filter(e => e.id !== editingId);
          if (events[editingKey].length === 0) delete events[editingKey];
        }
        if (!events[newDate]) events[newDate] = [];
        events[newDate].push({ id: editingId, type: editType, name, time, zone: 'Farm', notes });
        events[newDate].sort((a, b) => a.time.localeCompare(b.time));

        if (newDate !== editingKey) {
          const [py, pm] = newDate.split('-').map(Number);
          calDate = new Date(py, pm - 1, 1);
          selectedDate = newDate;
        }

        closeModal();
        renderCalendar();
        renderDayPanel();
        renderUpcoming();
        showToast('✓ Task updated successfully!');
      } else {
        showToast('Error: ' + (data.error || 'Could not update task.'), true);
      }
    })
    .catch(() => showToast('Network error.', true));
}

function deleteFromModal() {
  if (editingKey === null || editingId === null) return;
  const ev   = (events[editingKey] || []).find(e => e.id === editingId);
  const name = ev ? ev.name : 'this task';
  closeModal();
  setTimeout(() => confirmDelete(editingKey, editingId, name), 100);
}

/* ══════════ KEYBOARD SHORTCUTS ══════════ */
document.addEventListener('keydown', e => {
  const editModal   = document.getElementById('edit-modal');
  const deleteModal = document.getElementById('delete-modal');

  if (editModal.classList.contains('open')) {
    if (e.key === 'Escape') closeModal();
    if (e.key === 'Enter' && e.ctrlKey) saveEdit();
  }
  if (deleteModal.classList.contains('open')) {
    if (e.key === 'Escape') closeDeleteModal();
    if (e.key === 'Enter')  confirmDeleteEvent();
  }
});

document.addEventListener('click', e => {
  const sidebar = document.getElementById('sidebar');
  if (sidebar && !sidebar.contains(e.target) && !e.target.closest('.mobile-toggle'))
    sidebar.classList.remove('open');
});

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

/* ══════════ INIT ══════════ */
// Pre-select irrigation on load
document.getElementById('type-irrigation').classList.add('sel-irr');

const preselectDate = '<?= $preselect_date ?>';
if (preselectDate) {
  const [py, pm] = preselectDate.split('-').map(Number);
  calDate = new Date(py, pm - 1, 1);
  loadSchedules(() => selectDate(preselectDate));
} else {
  loadSchedules(() => selectDate(todayKey));
}
</script>
</body>
</html>