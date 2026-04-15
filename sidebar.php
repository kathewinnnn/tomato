<?php
$active_page = $active_page ?? 'home';
function nav_active(string $page, string $active_page): string {
    return $page === $active_page ? ' active' : '';
}
?>

<button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
  <i class="fas fa-bars"></i>
</button>

<nav class="sidebar" id="sidebar">

  <!-- LOGO -->
  <div class="sidebar-logo">
    <div class="logo-tomato">🍅</div>
    <div class="logo-text">
      <div class="logo-title">Tomato<span> IoT</span></div>
      <div class="logo-sub">Farm System v3.1</div>
    </div>
  </div>

  <!-- NAV LINKS -->
  <div class="sidebar-nav">

    <div class="nav-section-label">Overview</div>

    <a href="index.php"
       class="nav-link<?= nav_active('home', $active_page) ?>"
       id="nav-home"
       onclick="setActiveNav('nav-home')">
      <i class="fas fa-house"></i> Home
    </a>

    <a href="index.php?view=dashboard"
       class="nav-link<?= nav_active('dashboard', $active_page) ?>"
       id="nav-dashboard"
       onclick="setActiveNav('nav-dashboard')">
      <i class="fas fa-gauge-high"></i> Dashboard
    </a>

    <a href="index.php?view=dashboard#gsm-section"
       class="nav-link"
       id="nav-gsm"
       onclick="gsmNavClick(event)">
      <i class="fas fa-mobile-screen-button"></i> GSM SMS
      <?php
      $gsm_count = 0;
      $today_result = $conn->query("SELECT COUNT(*) as cnt FROM gsm_messages WHERE DATE(sent_at) = CURDATE()");
      if ($today_result && $row = $today_result->fetch_assoc()) {
          $gsm_count = (int) $row['cnt'];
      }
      ?>
      <span class="nav-badge" id="sidebar-gsm-badge" style="background:var(--green-mid,#3cb96a);color:#fff;"><?= $gsm_count ?></span>
    </a>

    <div class="nav-section-label">Controls</div>

    <a href="monitor.php"
       class="nav-link<?= nav_active('monitoring', $active_page) ?>"
       id="nav-monitoring"
       onclick="setActiveNav('nav-monitoring')">
      <i class="fas fa-wifi"></i> Monitoring
      <span class="nav-badge">12</span>
    </a>

    <a href="schedule.php"
       class="nav-link<?= nav_active('scheduling', $active_page) ?>"
       id="nav-scheduling"
       onclick="setActiveNav('nav-scheduling')">
      <i class="fas fa-calendar-days"></i> Scheduling
    </a>

    <div class="nav-section-label">Admin</div>

    <a href="mis.php"
       class="nav-link<?= nav_active('users', $active_page) ?>"
       id="nav-users"
       onclick="setActiveNav('nav-users')">
      <i class="fas fa-users"></i> Manage Users
    </a>

  </div>

  <!-- FOOTER -->
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(substr($username ?? 'U', 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= $username ?? 'User' ?></div>
        <div class="user-role"><?= $role ?? 'Farmer' ?></div>
      </div>
    </div>
    <form method="post" action="logout.php">
      <button type="submit" class="btn-logout">
        <i class="fas fa-right-from-bracket"></i> Log Out
      </button>
    </form>
  </div>

</nav>

<script>
function setActiveNav(id) {
  document.querySelectorAll('.sidebar .nav-link').forEach(el => el.classList.remove('active'));
  const target = document.getElementById(id);
  if (target) target.classList.add('active');
}

function gsmNavClick(e) {
  e.preventDefault();

  setActiveNav('nav-gsm');

  const sidebar = document.getElementById('sidebar');
  if (sidebar) sidebar.classList.remove('open');

  const dashView   = document.getElementById('view-dashboard');
  const homeView   = document.getElementById('view-home');
  const gsmSection = document.getElementById('gsm-section');

  if (dashView && homeView) {
    homeView.classList.remove('active');
    dashView.classList.add('active');

    history.replaceState(null, '', 'index.php?view=dashboard#gsm-section');

    setTimeout(() => {
      if (gsmSection) gsmSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 80);
  } else {
    window.location.href = 'index.php?view=dashboard#gsm-section';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.hash === '#gsm-section') {
    setActiveNav('nav-gsm');
    setTimeout(() => {
      const el = document.getElementById('gsm-section');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 200);
  }

  const gsmSection = document.getElementById('gsm-section');
  if (gsmSection) {
    const observer = new IntersectionObserver(
      ([entry]) => {
        const onDashboard = !!document.getElementById('view-dashboard');
        if (!onDashboard) return;

        if (entry.isIntersecting) {
          setActiveNav('nav-gsm');
          history.replaceState(null, '', 'index.php?view=dashboard#gsm-section');
        } else {
          const dashView = document.getElementById('view-dashboard');
          if (dashView && dashView.classList.contains('active')) {
            const current = document.querySelector('.sidebar .nav-link.active');
            if (current && current.id === 'nav-gsm') {
              setActiveNav('nav-dashboard');
              history.replaceState(null, '', 'index.php?view=dashboard');
            }
          }
        }
      },
      { threshold: 0.15 } 
    );
    observer.observe(gsmSection);
  }
});
</script>