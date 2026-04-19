<?php
session_start();
require 'tomato_db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($_SESSION['role'] ?? 'Farmer', ENT_QUOTES, 'UTF-8');

if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action == 'add'){
        $name     = $_POST['name'];
        $email    = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $uname    = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users(name,email,username,password) VALUES(?,?,?,?)");
        $stmt->bind_param("ssss", $name, $email, $uname, $password);
        $stmt->execute();
        echo "User Added"; exit;

    } elseif($action == 'edit'){
        $id    = $_POST['id'];
        $name  = $_POST['name'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $uname = trim($_POST['username']);
        if(!empty($_POST['password'])){
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, username=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $uname, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, username=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $uname, $id);
        }
        $stmt->execute();
        echo "User Updated"; exit;

    } elseif($action == 'delete'){
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo "User Deleted"; exit;

    } elseif($action == 'fetch'){
        $search      = $_POST['search'] ?? '';
        $page        = max(1, intval($_POST['page'] ?? 1));
        $limit       = intval($_POST['limit'] ?? 8);
        $sort_column = in_array($_POST['sort_column'] ?? '', ['id','name','email','username']) ? $_POST['sort_column'] : 'id';
        $sort_order  = ($_POST['sort_order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $offset      = ($page - 1) * $limit;
        $where = "";
        if($search != ""){
            $s = $conn->real_escape_string($search);
            $where = "WHERE name LIKE '%$s%' OR email LIKE '%$s%' OR username LIKE '%$s%'";
        }
        $totalResult = $conn->query("SELECT COUNT(*) as total FROM users $where");
        $totalRows   = $totalResult->fetch_assoc()['total'];
        $totalPages  = ceil($totalRows / $limit);
        $sql    = "SELECT * FROM users $where ORDER BY $sort_column $sort_order LIMIT $offset,$limit";
        $result = $conn->query($sql);
        $users  = [];
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) $users[] = $row;
        }
        echo json_encode(['users'=>$users,'totalPages'=>$totalPages,'currentPage'=>$page,'totalRows'=>$totalRows]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Users — Tomato Cultivation System</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/mis.css">
  <script src="notifications.js"></script>

  <!-- jsPDF + autoTable for PDF export -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

  <style>
    @media print {
      @page {
        size: A4 portrait;
        margin: 0 0 0 0 !important;
      }

      html, body {
        margin: 0 0 0 0 !important;
        padding: 0 0 0 0 !important;
        height: auto !important;
        background: #fff !important;
        font-family: 'Segoe UI', Arial, sans-serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      body * { visibility: hidden !important; }

      #print-wrapper,
      #print-wrapper * { visibility: visible !important; }

      #print-wrapper {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
        background: #fff !important;
        display: block !important;
        visibility: visible !important;
        z-index: 9999 !important;
      }

      .print-header {
        text-align: center;
        padding: 4mm 8mm 2mm 8mm !important;
        margin: 0 !important;
        border-bottom: 2px solid #2D8653;
      }
      .print-header h1 { font-size:16pt; font-weight:700; color:#1B5E3B; margin:0; }
      .print-header p  { font-size:8pt; color:#666; margin:2px 0 0 0; }

      .print-info {
        display:flex; justify-content:space-between;
        padding:1mm 8mm !important; font-size:7pt; color:#888;
      }

      .print-table-wrap { width:100%; padding:1mm 8mm 4mm 8mm !important; }

      #print-table { width:100%; border-collapse:collapse; font-size:8pt; }
      #print-table thead tr { background:#2D8653 !important; }
      #print-table thead th {
        color:#fff !important; font-weight:600; padding:6px 5px;
        text-align:left; border:1px solid #1B4332; font-size:7pt;
        text-transform:uppercase; letter-spacing:0.3px;
      }
      #print-table thead th:nth-child(1) { width:30px; text-align:center; }
      #print-table thead th:nth-child(2) { width:26%; }
      #print-table thead th:nth-child(3) { width:34%; }
      #print-table thead th:nth-child(4) { width:20%; }
      #print-table thead th:nth-child(5) { width:10%; text-align:center; }

      #print-table tbody tr { page-break-inside:avoid; }
      #print-table tbody tr:nth-child(even) { background:#F5F8F6 !important; }
      #print-table tbody td {
        padding:5px 4px; border:1px solid #CCC;
        color:#333; vertical-align:middle;
      }
      #print-table tbody td:nth-child(1) { text-align:center; font-family:monospace; color:#666; }
      #print-table tbody td:nth-child(2) { font-weight:600; }
      #print-table tbody td:nth-child(3) { word-break:break-all; }
      #print-table tbody td:nth-child(4) { font-family:monospace; color:#2D8653; }
      #print-table tbody td:nth-child(5) { text-align:center; }

      .print-badge {
        display:inline-block; padding:1px 6px; border-radius:8px;
        font-size:6pt; font-weight:600;
        background:#EAF7F0; color:#2D8653; border:1px solid #2D8653;
      }

      .print-footer {
        margin-top:6px; padding:4mm 8mm !important;
        border-top:1px solid #CCC; text-align:center;
        font-size:6pt; color:#999;
      }
    }
  </style>
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <?php $active_page = 'users'; include 'sidebar.php'; ?>

  <div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-title">
        <i class="fas fa-users"></i>
        User Management
      </div>
      <div class="search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" class="search-input" placeholder="Search users…" id="topbarSearch" />
      </div>
      <div class="topbar-clock" id="clock">—</div>
    </div>

    <div class="page-content">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div class="page-header-bg">👤</div>
        <div class="page-header-tag">System Administration</div>
        <h1>Manage <span>Users</span></h1>
        <p>Add, edit, and manage system accounts for the Solar IoT Tomato Cultivation platform.</p>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row" id="stats-row">
        <div class="stat-card green">
          <div class="stat-top"><div class="stat-icon green"><i class="fas fa-users"></i></div><span class="stat-badge up">Total</span></div>
          <div class="stat-value green" id="stat-total">—</div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-top"><div class="stat-icon blue"><i class="fas fa-user-check"></i></div><span class="stat-badge info">Active</span></div>
          <div class="stat-value blue" id="stat-active">—</div>
          <div class="stat-label">Active Accounts</div>
        </div>
        <div class="stat-card solar">
          <div class="stat-top"><div class="stat-icon solar"><i class="fas fa-user-shield"></i></div></div>
          <div class="stat-value solar">1</div>
          <div class="stat-label">Admin Roles</div>
        </div>
        <div class="stat-card red">
          <div class="stat-top"><div class="stat-icon red"><i class="fas fa-calendar-plus"></i></div></div>
          <div class="stat-value red" style="font-size:1.6rem;" id="stat-date">—</div>
          <div class="stat-label">Last Updated</div>
        </div>
      </div>

      <!-- USER TABLE CARD -->
      <div style="animation:fadeUp 0.5s ease 0.15s both;">
        <div class="section-head">
          <div class="section-icon green"><i class="fas fa-users"></i></div>
          <div class="section-label">User Accounts</div>
          <div class="section-meta" id="user-count-meta">Loading…</div>
        </div>

        <div class="card">
          <!-- TOOLBAR -->
          <div class="toolbar">
            <div class="toolbar-search">
              <i class="fas fa-magnifying-glass"></i>
              <input type="text" id="searchInput" placeholder="Search by name, email, or username…" autocomplete="off" />
            </div>
            <div class="toolbar-actions">
              <button class="btn btn-solar btn-sm" onclick="exportCSV()"><i class="fas fa-download"></i> CSV</button>
              <button class="btn btn-danger btn-sm" onclick="exportPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
              <button class="btn btn-ghost btn-sm" onclick="preparePrint()"><i class="fas fa-print"></i> Print</button>
              <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-user-plus"></i> Add User</button>
            </div>
          </div>

          <!-- PRINT-ONLY SECTION (hidden on screen) -->
          <div id="print-wrapper" style="display:none;">
            <div class="print-header">
              <h1>👥 User Accounts List</h1>
              <p>Solar IoT Tomato Cultivation System — User Management</p>
            </div>
            <div class="print-info">
              <span>Generated: <?php echo date('F j, Y \a\t g:i A'); ?></span>
              <span id="print-user-count">Total Users: 0</span>
            </div>
            <div class="print-table-wrap">
              <table id="print-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Username</th>
                    <th>Role</th>
                  </tr>
                </thead>
                <tbody id="print-tbody"></tbody>
              </table>
            </div>
            <div class="print-footer">Solar IoT Tomato Cultivation System — Page 1</div>
          </div>

          <!-- TABLE -->
          <div class="table-wrap">
            <table id="usersTable">
              <colgroup>
                <col> <!-- ID -->
                <col> <!-- Name -->
                <col> <!-- Email -->
                <col> <!-- Username -->
                <col> <!-- Role -->
                <col> <!-- Actions -->
              </colgroup>
              <thead>
                <tr>
                  <th data-col="id">ID <i class="fas fa-sort sort-icon"></i></th>
                  <th data-col="name">Name <i class="fas fa-sort sort-icon"></i></th>
                  <th data-col="email">Email <i class="fas fa-sort sort-icon"></i></th>
                  <th data-col="username">Username <i class="fas fa-sort sort-icon"></i></th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="usersBody">
                <tr><td colspan="6"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading users…</p></div></td></tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div class="pagination-wrap">
            <div class="pagination-info" id="pagination-info">—</div>
            <ul class="pagination" id="pagination"></ul>
          </div>
        </div>
      </div>

    </div><!-- /.page-content -->

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-grid">
        <div>
          <div class="footer-brand"><i class="fas fa-solar-panel"></i>Solar IoT Farm</div>
          <p class="footer-desc">Automated irrigation &amp; fertilization powered by renewable solar energy for sustainable tomato cultivation.</p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <span class="badge badge-green" style="font-size:0.7rem;padding:4px 10px;"><i class="fas fa-wifi" style="margin-right:4px;"></i>IoT Connected</span>
            <span class="badge badge-muted" style="font-size:0.7rem;padding:4px 10px;"><i class="fas fa-users" style="margin-right:4px;"></i>User Admin</span>
          </div>
        </div>
        <div>
          <div class="footer-h">Navigation</div>
          <ul class="footer-links">
            <li><a href="index.php"><i class="fas fa-gauge-high"></i>Dashboard</a></li>
            <li><a href="index.php#irrigation"><i class="fas fa-water"></i>Smart Irrigation</a></li>
            <li><a href="index.php#fertilizer"><i class="fas fa-spray-can"></i>Auto Fertilizing</a></li>
          </ul>
        </div>
        <div>
          <div class="footer-h">System Info</div>
          <ul class="footer-links">
            <li><a href="#"><i class="fas fa-server"></i>Gateway: Online</a></li>
            <li><a href="#"><i class="fas fa-wifi"></i>12 Sensors Active</a></li>
            <li><a href="#"><i class="fas fa-battery-full"></i>Battery: 78%</a></li>
            <li><a href="#"><i class="fas fa-clock"></i>Last Update: Just now</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© 2026 Solar IoT Irrigation System — Sustainable farming powered by solar energy</span>
        <span>Designed for Tomato Cultivation Automation</span>
      </div>
    </footer>

  </div>
</div>

<!-- ADD USER MODAL -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-title"><i class="fas fa-user-plus"></i> Add New User</div>
      <button class="modal-close" onclick="closeModal('addModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><i class="fas fa-id-card"></i>Full Name</label>
          <input type="text" class="form-input" id="add_name" placeholder="e.g. Juan dela Cruz" required />
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-user"></i>Username</label>
          <input type="text" class="form-input" id="add_username" placeholder="e.g. your_un" required />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fas fa-envelope"></i>Email Address</label>
        <input type="email" class="form-input" id="add_email" placeholder="e.g. you@farm.com" required />
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fas fa-lock"></i>Password</label>
        <div class="password-wrapper">
          <input type="password" class="form-input" id="add_password" placeholder="Enter a strong password" required />
          <button type="button" class="password-toggle" onclick="togglePassword('add_password', this)" aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div class="form-hint">Minimum 8 characters recommended.</div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('addModal')"><i class="fas fa-times"></i> Cancel</button>
      <button class="btn btn-primary" onclick="submitAdd()"><i class="fas fa-save"></i> Create User</button>
    </div>
  </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-title"><i class="fas fa-user-edit"></i> Edit User</div>
      <button class="modal-close" onclick="closeModal('editModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="edit_id" />
      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><i class="fas fa-id-card"></i>Full Name</label>
          <input type="text" class="form-input" id="edit_name" placeholder="Full name" required />
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-user"></i>Username</label>
          <input type="text" class="form-input" id="edit_username" placeholder="Username" required />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fas fa-envelope"></i>Email Address</label>
        <input type="email" class="form-input" id="edit_email" placeholder="Email address" required />
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fas fa-lock"></i>New Password</label>
        <div class="password-wrapper">
          <input type="password" class="form-input" id="edit_password" placeholder="Leave blank to keep current" />
          <button type="button" class="password-toggle" onclick="togglePassword('edit_password', this)" aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div class="form-hint">Only fill this if you want to change the password.</div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('editModal')"><i class="fas fa-times"></i> Cancel</button>
      <button class="btn btn-primary" onclick="submitEdit()"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- DELETE CONFIRM -->
<div class="delete-overlay" id="deleteOverlay">
  <div class="delete-box">
    <div class="delete-icon"><i class="fas fa-trash-can"></i></div>
    <div class="delete-title">Delete User?</div>
    <p class="delete-desc">This action is permanent and cannot be undone. Are you sure you want to remove <strong id="delete-username-display">this user</strong> from the system?</p>
    <div class="delete-actions">
      <button class="btn btn-ghost" onclick="closeDeleteModal()"><i class="fas fa-times"></i> Cancel</button>
      <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash-can"></i> Delete</button>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
  /* ── CLOCK ── */
  function updateClock(){
    const now = new Date();
    document.getElementById('clock').textContent =
      now.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) + ' · ' +
      now.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  }
  updateClock(); setInterval(updateClock, 1000);

  /* ── STATE ── */
  let currentPage = 1, currentSort = 'id', currentOrder = 'asc', currentSearch = '';
  let pendingDeleteId = null, totalUsersCount = 0;
  const limit = 8;

  /* ── FETCH USERS ── */
  function fetchUsers(){
    const body = new URLSearchParams({
      action:'fetch', search:currentSearch, page:currentPage,
      limit:limit, sort_column:currentSort, sort_order:currentOrder
    });
    fetch('', { method:'POST', body })
      .then(r => r.json())
      .then(res => {
        totalUsersCount = parseInt(res.totalRows) || 0;
        renderTable(res.users);
        renderPagination(res.totalPages, res.currentPage, res.totalRows);
        updateStats(res.totalRows);
      })
      .catch(() => showToast('Failed to load users.', 'error'));
  }

  function updateStats(total){
    document.getElementById('stat-total').textContent  = total;
    document.getElementById('stat-active').textContent = total;
    const now = new Date();
    document.getElementById('stat-date').textContent =
      now.toLocaleDateString('en-US',{month:'short',day:'numeric'});
    document.getElementById('user-count-meta').textContent = total + ' users total';
  }

  function renderTable(users){
    const tbody = document.getElementById('usersBody');
    if(!users || users.length === 0){
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">
        <i class="fas fa-users-slash"></i><p>No users found. Try a different search term or add a new user.</p>
      </div></td></tr>`;
      return;
    }
    tbody.innerHTML = users.map(u => {
      const initial = (u.name || u.username || '?')[0].toUpperCase();
      const colors = ['#52C78A','#72C9EA','#F5C842','#ff9090','#A07050'];
      const color  = colors[u.id % colors.length];
      return `<tr>
        <td><span style="font-family:var(--font-mono);font-size:0.78rem;color:var(--text-muted);">#${u.id}</span></td>
        <td>
          <div class="avatar-cell">
            <div class="row-avatar" style="background:linear-gradient(135deg,${color},${color}aa)">${initial}</div>
            <div>
              <div class="row-name">${escHtml(u.name)}</div>
            </div>
          </div>
        </td>
        <td style="color:var(--text-muted);font-size:0.84rem;">${escHtml(u.email)}</td>
        <td><span style="font-family:var(--font-mono);font-size:0.82rem;color:var(--green-mid);">@${escHtml(u.username)}</span></td>
        <td><span class="badge badge-green">User</span></td>
        <td>
          <div class="action-cell">
            <button class="btn btn-water btn-xs" onclick="openEditModal(${u.id},'${escAttr(u.name)}','${escAttr(u.email)}','${escAttr(u.username)}')">
              <i class="fas fa-pen"></i> Edit
            </button>
            <button class="btn btn-danger btn-xs" onclick="openDeleteModal(${u.id},'${escAttr(u.username)}')">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  function renderPagination(totalPages, current, totalRows){
    const offset  = (current - 1) * limit;
    const showing = Math.min(offset + limit, totalRows);
    document.getElementById('pagination-info').textContent =
      `Showing ${offset + 1}–${showing} of ${totalRows} users`;

    const ul = document.getElementById('pagination');
    let html = '';
    if(current > 1) html += `<li><a onclick="goPage(${current-1})"><i class="fas fa-chevron-left" style="font-size:0.65rem;"></i></a></li>`;
    const start = Math.max(1, current - 2), end = Math.min(totalPages, current + 2);
    if(start > 1){ html += `<li><a onclick="goPage(1)">1</a></li>`; if(start > 2) html += `<li><span>…</span></li>`; }
    for(let i=start;i<=end;i++) html += `<li class="${i===current?'active':''}"><a onclick="goPage(${i})">${i}</a></li>`;
    if(end < totalPages){ if(end < totalPages-1) html += `<li><span>…</span></li>`; html += `<li><a onclick="goPage(${totalPages})">${totalPages}</a></li>`; }
    if(current < totalPages) html += `<li><a onclick="goPage(${current+1})"><i class="fas fa-chevron-right" style="font-size:0.65rem;"></i></a></li>`;
    ul.innerHTML = html;
  }

  function goPage(p){ currentPage = p; fetchUsers(); }

  /* ── SORT ── */
  document.querySelectorAll('thead th[data-col]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if(currentSort === col) currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
      else { currentSort = col; currentOrder = 'asc'; }
      document.querySelectorAll('thead th').forEach(t => t.classList.remove('sorted'));
      th.classList.add('sorted');
      const icon = th.querySelector('.sort-icon');
      document.querySelectorAll('.sort-icon').forEach(i => { i.className = 'fas fa-sort sort-icon'; });
      icon.className = `fas fa-sort-${currentOrder === 'asc' ? 'up' : 'down'} sort-icon`;
      currentPage = 1; fetchUsers();
    });
  });

  /* ── SEARCH ── */
  let searchTimer;
  function wireSearch(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        currentSearch = el.value.trim();
        currentPage   = 1;
        document.getElementById('searchInput').value   = currentSearch;
        document.getElementById('topbarSearch').value  = currentSearch;
        fetchUsers();
      }, 300);
    });
  }
  wireSearch('searchInput');
  wireSearch('topbarSearch');

  /* ── ADD MODAL ── */
  function openAddModal(){
    document.getElementById('add_name').value     = '';
    document.getElementById('add_email').value    = '';
    document.getElementById('add_username').value = '';
    document.getElementById('add_password').value = '';

    const pw  = document.getElementById('add_password');
    const btn = pw.closest('.password-wrapper').querySelector('.password-toggle i');
    pw.type = 'password';
    btn.className = 'fas fa-eye';
    openModal('addModal');
  }
  function isValidEmail(email){
    const re = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    return re.test(email);
  }
  function submitAdd(){
    const name     = document.getElementById('add_name').value.trim();
    const email    = document.getElementById('add_email').value.trim();
    const username = document.getElementById('add_username').value.trim();
    const password = document.getElementById('add_password').value;
    if(!name || !email || !username || !password){ showToast('Please fill in all fields.','error'); return; }
    if(!isValidEmail(email)){ showToast('Please enter a valid gmail.com email address.','error'); return; }
    const body = new URLSearchParams({action:'add', name, email, username, password});
    fetch('', { method:'POST', body })
      .then(r => r.text())
      .then(() => { closeModal('addModal'); fetchUsers(); showToast('User created successfully!','success'); })
      .catch(() => showToast('Error creating user.','error'));
  }

  /* ── EDIT MODAL ── */
  function openEditModal(id, name, email, username){
    document.getElementById('edit_id').value       = id;
    document.getElementById('edit_name').value     = name;
    document.getElementById('edit_email').value    = email;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_password').value = '';
    const pw  = document.getElementById('edit_password');
    const btn = pw.closest('.password-wrapper').querySelector('.password-toggle i');
    pw.type = 'password';
    btn.className = 'fas fa-eye';
    openModal('editModal');
  }
  function submitEdit(){
    const id       = document.getElementById('edit_id').value;
    const name     = document.getElementById('edit_name').value.trim();
    const email    = document.getElementById('edit_email').value.trim();
    const username = document.getElementById('edit_username').value.trim();
    const password = document.getElementById('edit_password').value;
    if(!name || !email || !username){ showToast('Please fill in required fields.','error'); return; }
    if(!isValidEmail(email)){ showToast('Please enter a valid gmail.com email address.','error'); return; }
    const body = new URLSearchParams({action:'edit', id, name, email, username, password});
    fetch('', { method:'POST', body })
      .then(r => r.text())
      .then(() => { closeModal('editModal'); fetchUsers(); showToast('User updated successfully!','success'); })
      .catch(() => showToast('Error updating user.','error'));
  }

  /* ── DELETE ── */
  function openDeleteModal(id, username){
    pendingDeleteId = id;
    document.getElementById('delete-username-display').textContent = '@' + username;
    document.getElementById('deleteOverlay').classList.add('open');
  }
  function closeDeleteModal(){
    pendingDeleteId = null;
    document.getElementById('deleteOverlay').classList.remove('open');
  }
  function confirmDelete(){
    if(!pendingDeleteId) return;
    const body = new URLSearchParams({action:'delete', id:pendingDeleteId});
    fetch('', { method:'POST', body })
      .then(r => r.text())
      .then(() => { closeDeleteModal(); fetchUsers(); showToast('User deleted.','success'); })
      .catch(() => showToast('Error deleting user.','error'));
  }

  /* ── MODAL HELPERS ── */
  function openModal(id) { document.getElementById(id).classList.add('open'); }
  function closeModal(id){ document.getElementById(id).classList.remove('open'); }

  document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if(e.target === o) o.classList.remove('open'); });
  });
  document.getElementById('deleteOverlay').addEventListener('click', e => {
    if(e.target === document.getElementById('deleteOverlay')) closeDeleteModal();
  });

  /* ── TOAST ── */
  function showToast(msg, type='success'){
    const wrap = document.getElementById('toastWrap');
    const t    = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type==='success'?'circle-check':'circle-exclamation'}"></i> ${msg}`;
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  /* ── CSV EXPORT ── */
  function exportCSV(){
    const rows = [['ID','Name','Email','Username']];
    document.querySelectorAll('#usersBody tr').forEach(tr => {
      const tds = tr.querySelectorAll('td');
      if(tds.length >= 4){
        rows.push([
          tds[0].textContent.replace('#','').trim(),
          tds[1].querySelector('.row-name')?.textContent.trim() || tds[1].textContent.trim(),
          tds[2].textContent.trim(),
          tds[3].textContent.replace('@','').trim()
        ]);
      }
    });
    const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n');
    const a   = document.createElement('a');
    a.href     = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'users_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    showToast('CSV exported!','success');
  }

  /* ── PDF EXPORT ── */
  function exportPDF(){
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const now = new Date();

    // Green header bar
    doc.setFillColor(45, 134, 83);
    doc.rect(0, 0, 210, 24, 'F');

    // Title
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(15);
    doc.setFont('helvetica', 'bold');
    doc.text('User Accounts List', 14, 11);

    // Subtitle
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text('Solar IoT Tomato Cultivation System — User Management', 14, 18);

    // Date (right-aligned)
    const dateStr = now.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
    doc.text('Generated: ' + dateStr, 196, 18, { align: 'right' });

    // Fetch ALL users (limit 1000) for the export
    const body = new URLSearchParams({
      action: 'fetch', search: '', page: 1,
      limit: 1000, sort_column: 'name', sort_order: 'asc'
    });

    fetch('', { method: 'POST', body })
      .then(r => r.json())
      .then(res => {
        const rows = (res.users || []).map(u => [
          '#' + u.id,
          u.name   || '',
          u.email  || '',
          '@' + u.username,
          'User'
        ]);

        doc.autoTable({
          startY: 30,
          head: [['ID', 'Full Name', 'Email Address', 'Username', 'Role']],
          body: rows,
          styles: {
            fontSize: 9,
            cellPadding: 4,
            font: 'helvetica',
            textColor: [40, 40, 40]
          },
          headStyles: {
            fillColor: [45, 134, 83],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            halign: 'left',
            fontSize: 9
          },
          alternateRowStyles: {
            fillColor: [245, 248, 246]
          },
          columnStyles: {
            0: { cellWidth: 16, halign: 'center', textColor: [130, 130, 130], fontStyle: 'normal' },
            1: { fontStyle: 'bold' },
            2: { textColor: [80, 80, 80] },
            3: { textColor: [45, 134, 83] },
            4: { halign: 'center', textColor: [45, 134, 83], fontStyle: 'bold' }
          },
          margin: { left: 14, right: 14 },
          didDrawPage: function(data) {
            // Footer on every page
            const pageCount = doc.internal.getNumberOfPages();
            doc.setFontSize(7);
            doc.setTextColor(160, 160, 160);
            doc.setFont('helvetica', 'normal');
            doc.text(
              'Solar IoT Tomato Cultivation System  •  Page ' + data.pageNumber + ' of ' + pageCount,
              105, 290, { align: 'center' }
            );
          }
        });

        doc.save('users_' + now.toISOString().slice(0, 10) + '.pdf');
        showToast('PDF exported successfully!', 'success');
      })
      .catch(() => showToast('Error generating PDF.', 'error'));
  }

  /* ── ESCAPE HELPERS ── */
  function escHtml(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
  function escAttr(s){ return (s||'').replace(/'/g,"&#39;").replace(/"/g,"&quot;"); }

  /* ── PASSWORD TOGGLE ── */
  function togglePassword(inputId, btn){
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if(input.type === 'password'){
      input.type    = 'text';
      icon.className = 'fas fa-eye-slash';
    } else {
      input.type    = 'password';
      icon.className = 'fas fa-eye';
    }
  }

  /* ── PRINT ── */
  function preparePrint(){
    const body = new URLSearchParams({
      action:'fetch', search:'', page:1,
      limit:1000, sort_column:'name', sort_order:'asc'
    });
    fetch('', { method:'POST', body })
      .then(r => r.json())
      .then(res => {
        const tbody = document.getElementById('print-tbody');
        if(!res.users || res.users.length === 0){
          tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">No users found</td></tr>';
        } else {
          tbody.innerHTML = res.users.map(u => `
            <tr>
              <td>${u.id}</td>
              <td>${escHtml(u.name)}</td>
              <td>${escHtml(u.email)}</td>
              <td>@${escHtml(u.username)}</td>
              <td><span class="print-badge">User</span></td>
            </tr>
          `).join('');
        }
        document.getElementById('print-user-count').textContent = 'Total Users: ' + res.totalRows;
        window.print();
      })
      .catch(() => showToast('Error preparing print data','error'));
  }

  /* ── INIT ── */
  fetchUsers();
</script>
</body>
</html>