<?php
session_start();

// Redirect to the integrated admin dashboard when logged in as admin
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// ─── Standalone demo auth (legacy fallback) ───────────────────────────────
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'studytwin2024';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = 'Invalid credentials.';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$logged_in = !empty($_SESSION['admin_logged_in']);

// ─── Mock data (replace with PDO queries) ─────────────────────────────────
$stats = [
    'total_users'    => 1284,
    'active_today'   => 87,
    'sessions_week'  => 432,
    'matched_pairs'  => 311,
];

$users = [
    ['id'=>1,'name'=>'Aishah Nor','email'=>'aishah@example.com','role'=>'student','status'=>'active','joined'=>'2024-11-03','sessions'=>24,'partner'=>'Haziq Zain'],
    ['id'=>2,'name'=>'Haziq Zain','email'=>'haziq@example.com','role'=>'student','status'=>'active','joined'=>'2024-11-05','sessions'=>24,'partner'=>'Aishah Nor'],
    ['id'=>3,'name'=>'Nurul Huda','email'=>'nurul@example.com','role'=>'student','status'=>'inactive','joined'=>'2024-10-20','sessions'=>7,'partner'=>'—'],
    ['id'=>4,'name'=>'Fariz Amin','email'=>'fariz@example.com','role'=>'student','status'=>'active','joined'=>'2024-12-01','sessions'=>15,'partner'=>'Wani Kassim'],
    ['id'=>5,'name'=>'Wani Kassim','email'=>'wani@example.com','role'=>'student','status'=>'active','joined'=>'2024-12-02','sessions'=>15,'partner'=>'Fariz Amin'],
    ['id'=>6,'name'=>'Dr. Rashid','email'=>'rashid@example.com','role'=>'admin','status'=>'active','joined'=>'2024-09-01','sessions'=>0,'partner'=>'—'],
];

$pairs = [
    ['id'=>1,'twin_a'=>'Aishah Nor','twin_b'=>'Haziq Zain','subject'=>'Mathematics','score'=>94,'sessions'=>24,'last_active'=>'2025-06-28','status'=>'active'],
    ['id'=>2,'twin_a'=>'Fariz Amin','twin_b'=>'Wani Kassim','subject'=>'Biology','score'=>88,'sessions'=>15,'last_active'=>'2025-06-27','status'=>'active'],
    ['id'=>3,'twin_a'=>'Lina Razak','twin_b'=>'Omar Said','subject'=>'Chemistry','score'=>72,'sessions'=>9,'last_active'=>'2025-06-20','status'=>'active'],
    ['id'=>4,'twin_a'=>'Syafiq Bakar','twin_b'=>'Izzah Lokman','subject'=>'Physics','score'=>61,'sessions'=>4,'last_active'=>'2025-05-30','status'=>'inactive'],
];

$sessions = [
    ['id'=>1,'pair'=>'Aishah & Haziq','subject'=>'Mathematics','date'=>'2025-06-28','duration'=>'52 min','score'=>92,'notes'=>'Completed differentiation module'],
    ['id'=>2,'pair'=>'Fariz & Wani','subject'=>'Biology','date'=>'2025-06-27','duration'=>'45 min','score'=>85,'notes'=>'Cell division revision'],
    ['id'=>3,'pair'=>'Lina & Omar','subject'=>'Chemistry','date'=>'2025-06-20','duration'=>'38 min','score'=>70,'notes'=>'Stoichiometry practice'],
    ['id'=>4,'pair'=>'Aishah & Haziq','subject'=>'Mathematics','date'=>'2025-06-21','duration'=>'60 min','score'=>95,'notes'=>'Integration techniques'],
    ['id'=>5,'pair'=>'Fariz & Wani','subject'=>'Biology','date'=>'2025-06-18','duration'=>'41 min','score'=>80,'notes'=>'Photosynthesis quiz'],
];

$active_tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Study Twin — Admin</title>
<?php include_once("includes/theme.php"); inject_theme_styles_and_script(); ?>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:        #f5f4f0;
    --surface:   #ffffff;
    --surface-2: #f0efe9;
    --border:    #e2e0d8;
    --border-strong: #ccc9be;
    --text:      #1a1916;
    --text-2:    #5a5850;
    --text-3:    #8f8c82;
    --accent:    #4a5cf7;
    --accent-bg: #eef0fe;
    --accent-text: #2d3ab3;
    --success:   #1a7f4b;
    --success-bg:#e4f5ec;
    --danger:    #b91c1c;
    --danger-bg: #fef2f2;
    --warn:      #a16207;
    --warn-bg:   #fffbeb;
    --radius:    8px;
    --shadow:    0 1px 3px rgba(0,0,0,.08), 0 0 0 0.5px rgba(0,0,0,.06);
  }

  body { background: var(--bg); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; color: var(--text); line-height: 1.5; }

  /* ── Login ── */
  .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
  .login-card { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; padding: 2.5rem 2rem; width: 100%; max-width: 380px; box-shadow: var(--shadow); }
  .login-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
  .login-logo .mark { width: 36px; height: 36px; background: var(--accent); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; }
  .login-logo .mark svg { width: 20px; height: 20px; fill: #fff; }
  .login-logo h1 { font-size: 18px; font-weight: 600; letter-spacing: -.3px; }
  .login-logo span { font-size: 11px; color: var(--text-3); display: block; }
  .field { margin-bottom: 1rem; }
  .field label { display: block; font-size: 12px; font-weight: 500; color: var(--text-2); margin-bottom: 5px; }
  .field input { width: 100%; padding: 9px 12px; border: 0.5px solid var(--border-strong); border-radius: var(--radius); font-size: 14px; color: var(--text); background: var(--surface); outline: none; transition: border-color .15s; }
  .field input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(74,92,247,.12); }
  .btn-primary { width: 100%; padding: 10px; background: var(--accent); color: #fff; border: none; border-radius: var(--radius); font-size: 14px; font-weight: 500; cursor: pointer; transition: opacity .15s; }
  .btn-primary:hover { opacity: .88; }
  .error-msg { background: var(--danger-bg); color: var(--danger); font-size: 13px; padding: 9px 12px; border-radius: var(--radius); margin-bottom: 1rem; border: 0.5px solid #fca5a5; }

  /* ── Layout ── */
  .app { display: flex; min-height: 100vh; }
  .sidebar { width: 220px; background: var(--surface); border-right: 0.5px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 10; }
  .sidebar-logo { padding: 1.25rem 1rem 1rem; display: flex; align-items: center; gap: 9px; border-bottom: 0.5px solid var(--border); }
  .sidebar-logo .mark { width: 30px; height: 30px; background: var(--accent); border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .sidebar-logo .mark svg { width: 16px; height: 16px; fill: #fff; }
  .sidebar-logo strong { font-size: 14px; font-weight: 600; }
  .sidebar-logo span { font-size: 10px; color: var(--text-3); display: block; line-height: 1; }
  .nav { padding: .75rem .5rem; flex: 1; }
  .nav a { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: var(--radius); color: var(--text-2); text-decoration: none; font-size: 13px; font-weight: 500; transition: background .12s, color .12s; margin-bottom: 2px; }
  .nav a svg { width: 16px; height: 16px; flex-shrink: 0; stroke: currentColor; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
  .nav a:hover { background: var(--bg); color: var(--text); }
  .nav a.active { background: var(--accent-bg); color: var(--accent-text); }
  .nav-section { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-3); padding: 12px 10px 4px; }
  .sidebar-footer { padding: .75rem .5rem; border-top: 0.5px solid var(--border); }
  .sidebar-footer form button { display: flex; align-items: center; gap: 9px; width: 100%; padding: 8px 10px; border-radius: var(--radius); background: none; border: none; color: var(--text-2); font-size: 13px; font-weight: 500; cursor: pointer; }
  .sidebar-footer form button:hover { background: var(--danger-bg); color: var(--danger); }
  .sidebar-footer form button svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }

  .main { margin-left: 220px; flex: 1; }
  .topbar { background: var(--surface); border-bottom: 0.5px solid var(--border); padding: .75rem 1.75rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 5; }
  .topbar h2 { font-size: 15px; font-weight: 600; letter-spacing: -.2px; }
  .topbar-right { display: flex; align-items: center; gap: .75rem; }
  .badge-admin { background: var(--accent-bg); color: var(--accent-text); font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
  .avatar { width: 30px; height: 30px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 700; }
  .content { padding: 1.75rem; }

  /* ── Stats grid ── */
  .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 1.75rem; }
  .stat-card { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; padding: 1.1rem 1.25rem; }
  .stat-label { font-size: 11px; font-weight: 500; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
  .stat-value { font-size: 28px; font-weight: 600; letter-spacing: -1px; color: var(--text); line-height: 1; }
  .stat-sub { font-size: 12px; color: var(--text-3); margin-top: 4px; }
  .stat-up { color: var(--success); }
  .stat-down { color: var(--danger); }

  /* ── Cards ── */
  .card { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; margin-bottom: 1.25rem; overflow: hidden; }
  .card-header { padding: .9rem 1.25rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 0.5px solid var(--border); }
  .card-header h3 { font-size: 13px; font-weight: 600; }
  .card-body { padding: 1.25rem; }

  /* ── Table ── */
  table { width: 100%; border-collapse: collapse; }
  th { font-size: 11px; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; padding: 10px 12px; text-align: left; background: var(--surface-2); border-bottom: 0.5px solid var(--border); }
  td { padding: 10px 12px; border-bottom: 0.5px solid var(--border); font-size: 13px; color: var(--text); vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--surface-2); }
  .user-cell { display: flex; align-items: center; gap: 9px; }
  .user-av { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; background: var(--accent-bg); color: var(--accent-text); }

  /* ── Badges ── */
  .badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
  .badge-active { background: var(--success-bg); color: var(--success); }
  .badge-inactive { background: var(--surface-2); color: var(--text-3); }
  .badge-admin-role { background: var(--accent-bg); color: var(--accent-text); }
  .badge-student { background: var(--warn-bg); color: var(--warn); }

  /* ── Buttons ── */
  .btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: var(--radius); font-size: 12px; font-weight: 500; cursor: pointer; border: 0.5px solid var(--border-strong); background: var(--surface); color: var(--text-2); text-decoration: none; transition: background .12s; }
  .btn:hover { background: var(--surface-2); }
  .btn-accent { background: var(--accent); color: #fff; border-color: var(--accent); }
  .btn-accent:hover { opacity: .88; }
  .btn-danger { background: var(--danger-bg); color: var(--danger); border-color: #fca5a5; }
  .btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* ── Match score bar ── */
  .score-bar { display: flex; align-items: center; gap: 8px; }
  .bar-track { flex: 1; height: 5px; background: var(--surface-2); border-radius: 3px; overflow: hidden; max-width: 80px; }
  .bar-fill { height: 100%; border-radius: 3px; background: var(--accent); }
  .bar-fill.good { background: var(--success); }
  .bar-fill.warn { background: #f59e0b; }
  .bar-fill.low { background: var(--danger); }

  /* ── Charts placeholder ── */
  .mini-chart { height: 120px; background: var(--surface-2); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: var(--text-3); font-size: 12px; position: relative; overflow: hidden; }
  .chart-bars { display: flex; align-items: flex-end; gap: 5px; height: 80px; padding: 0 1rem; }
  .chart-bar { flex: 1; background: var(--accent); border-radius: 3px 3px 0 0; opacity: .7; transition: opacity .2s; }
  .chart-bar:hover { opacity: 1; }

  /* ── Reports ── */
  .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .report-card { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; padding: 1.1rem 1.25rem; }
  .report-card h4 { font-size: 13px; font-weight: 600; margin-bottom: .3rem; }
  .report-card p { font-size: 12px; color: var(--text-3); margin-bottom: .9rem; }
  .export-row { display: flex; gap: 8px; flex-wrap: wrap; }

  /* ── Empty state ── */
  .empty { padding: 2.5rem; text-align: center; color: var(--text-3); font-size: 13px; }

  /* ── Section title ── */
  .section-title { font-size: 13px; font-weight: 600; color: var(--text-2); margin-bottom: .75rem; }

  /* ── Tabs ── */
  .sub-tabs { display: flex; gap: 4px; margin-bottom: 1.25rem; }
  .sub-tab { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 500; color: var(--text-3); cursor: pointer; background: none; border: 0.5px solid transparent; text-decoration: none; }
  .sub-tab.active, .sub-tab:hover { background: var(--surface); border-color: var(--border); color: var(--text); }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .report-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<?php if (!$logged_in): ?>
<!-- ══════════ LOGIN ══════════ -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="mark">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      </div>
      <div>
        <h1>Study Twin</h1>
        <span>Admin portal</span>
      </div>
    </div>
    <?php if (!empty($login_error)): ?>
      <div class="error-msg"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="admin" autocomplete="username" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <button type="submit" name="login" class="btn-primary">Sign in</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ══════════ APP SHELL ══════════ -->
<div class="app">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
      </div>
      <div><strong>Study Twin</strong><span>Admin</span></div>
    </div>
    <nav class="nav">
      <div class="nav-section">Overview</div>
      <a href="?tab=dashboard" class="<?= $active_tab==='dashboard'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>
      <div class="nav-section">Manage</div>
      <a href="?tab=users" class="<?= $active_tab==='users'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Users
      </a>
      <a href="?tab=pairs" class="<?= $active_tab==='pairs'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><path d="M17 20h5v-1a3 3 0 0 0-5.356-1.857"/><path d="M7 20H2v-1a3 3 0 0 1 5.356-1.857"/><path d="M10 10a3 3 0 1 0 4 0"/><path d="M12 14a5 5 0 0 0-5 5v1h10v-1a5 5 0 0 0-5-5z"/></svg>
        Matched pairs
      </a>
      <a href="?tab=sessions" class="<?= $active_tab==='sessions'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Study sessions
      </a>
      <div class="nav-section">Reports</div>
      <a href="?tab=reports" class="<?= $active_tab==='reports'?'active':'' ?>">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Reports & exports
      </a>
    </nav>
    <div class="sidebar-footer">
      <form method="POST">
        <button type="submit" name="logout">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign out
        </button>
      </form>
    </div>
  </aside>

  <!-- Main content -->
  <main class="main">
    <div class="topbar">
      <h2>
        <?php
        $titles = ['dashboard'=>'Dashboard','users'=>'Users','pairs'=>'Matched pairs','sessions'=>'Study sessions','reports'=>'Reports & exports'];
        echo $titles[$active_tab] ?? 'Dashboard';
        ?>
      </h2>
      <div class="topbar-right">
        <span class="badge-admin">Admin</span>
        <div class="avatar">AD</div>
      </div>
    </div>

    <div class="content">

    <?php if ($active_tab === 'dashboard'): ?>
    <!-- ══ DASHBOARD ══ -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Total users</div>
        <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
        <div class="stat-sub stat-up">↑ 14 this week</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active today</div>
        <div class="stat-value"><?= $stats['active_today'] ?></div>
        <div class="stat-sub stat-up">↑ 6.8% vs yesterday</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Sessions this week</div>
        <div class="stat-value"><?= number_format($stats['sessions_week']) ?></div>
        <div class="stat-sub stat-up">↑ 23 sessions</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active pairs</div>
        <div class="stat-value"><?= $stats['matched_pairs'] ?></div>
        <div class="stat-sub stat-down">↓ 2 unmatched</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1.25rem;">
      <div class="card">
        <div class="card-header"><h3>Sessions per day (last 7 days)</h3></div>
        <div class="card-body">
          <div class="mini-chart" style="height:110px;background:none;">
            <div class="chart-bars" style="width:100%;height:100%;align-items:flex-end;">
              <?php
              $bars = [52,65,48,70,60,80,57];
              $max = max($bars);
              foreach ($bars as $i => $v):
                $h = round(($v/$max)*80);
                $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
              ?>
              <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                <div class="chart-bar" style="width:100%;height:<?=$h?>px;background:var(--accent);border-radius:3px 3px 0 0;"></div>
                <span style="font-size:10px;color:var(--text-3);"><?=$days[$i]?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>Top subjects</h3></div>
        <div class="card-body">
          <?php
          $subjects = [['Mathematics',87,'good'],['Biology',64,'good'],['Chemistry',51,'warn'],['Physics',38,'low'],['History',22,'low']];
          foreach ($subjects as [$sub,$cnt,$cls]):
          ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:9px;">
            <span style="font-size:12px;width:90px;color:var(--text-2);"><?=$sub?></span>
            <div class="bar-track" style="flex:1;max-width:none;">
              <div class="bar-fill <?=$cls?>" style="width:<?=round($cnt/max(array_column($subjects,1))*100)?>%;"></div>
            </div>
            <span style="font-size:12px;color:var(--text-3);width:28px;text-align:right;"><?=$cnt?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Recent sessions</h3></div>
      <table>
        <thead><tr><th>Pair</th><th>Subject</th><th>Date</th><th>Duration</th><th>Score</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($sessions,0,4) as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['pair']) ?></td>
          <td><?= htmlspecialchars($s['subject']) ?></td>
          <td style="color:var(--text-3);"><?= $s['date'] ?></td>
          <td style="color:var(--text-3);"><?= $s['duration'] ?></td>
          <td>
            <?php $cls = $s['score']>=85?'good':($s['score']>=70?'warn':'low'); ?>
            <div class="score-bar">
              <div class="bar-track"><div class="bar-fill <?=$cls?>" style="width:<?=$s['score']?>%;"></div></div>
              <span style="font-size:12px;color:var(--text-3);"><?=$s['score']?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($active_tab === 'users'): ?>
    <!-- ══ USERS ══ -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
      <div style="font-size:13px;color:var(--text-3);"><?= count($users) ?> users total</div>
      <a href="#" class="btn btn-accent">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add user
      </a>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Sessions</th><th>Study twin</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
          $initials = implode('', array_map(fn($w)=>$w[0], explode(' ', $u['name'])));
        ?>
        <tr>
          <td>
            <div class="user-cell">
              <div class="user-av"><?= htmlspecialchars($initials) ?></div>
              <?= htmlspecialchars($u['name']) ?>
            </div>
          </td>
          <td style="color:var(--text-3);"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= $u['role']==='admin'?'badge-admin-role':'badge-student' ?>"><?= $u['role'] ?></span></td>
          <td><span class="badge <?= $u['status']==='active'?'badge-active':'badge-inactive' ?>"><?= $u['status'] ?></span></td>
          <td style="color:var(--text-3);"><?= $u['sessions'] ?></td>
          <td style="color:var(--text-3);"><?= htmlspecialchars($u['partner']) ?></td>
          <td style="color:var(--text-3);"><?= $u['joined'] ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="#" class="btn" title="Edit">
                <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </a>
              <?php if ($u['role'] !== 'admin'): ?>
              <a href="#" class="btn btn-danger" title="Deactivate">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($active_tab === 'pairs'): ?>
    <!-- ══ MATCHED PAIRS ══ -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
      <div style="font-size:13px;color:var(--text-3);"><?= count($pairs) ?> pairs · <?= count(array_filter($pairs, fn($p)=>$p['status']==='active')) ?> active</div>
      <a href="#" class="btn btn-accent">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create pair
      </a>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>Twin A</th><th>Twin B</th><th>Subject</th><th>Compatibility</th><th>Sessions</th><th>Last active</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pairs as $p):
          $cls = $p['score']>=85?'good':($p['score']>=70?'warn':'low');
        ?>
        <tr>
          <td><?= htmlspecialchars($p['twin_a']) ?></td>
          <td><?= htmlspecialchars($p['twin_b']) ?></td>
          <td style="color:var(--text-3);"><?= htmlspecialchars($p['subject']) ?></td>
          <td>
            <div class="score-bar">
              <div class="bar-track"><div class="bar-fill <?=$cls?>" style="width:<?=$p['score']?>%;"></div></div>
              <span style="font-size:12px;color:var(--text-3);"><?=$p['score']?>%</span>
            </div>
          </td>
          <td style="color:var(--text-3);"><?= $p['sessions'] ?></td>
          <td style="color:var(--text-3);"><?= $p['last_active'] ?></td>
          <td><span class="badge <?= $p['status']==='active'?'badge-active':'badge-inactive' ?>"><?= $p['status'] ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="#" class="btn">
                <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </a>
              <a href="#" class="btn btn-danger">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($active_tab === 'sessions'): ?>
    <!-- ══ SESSIONS ══ -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
      <div style="font-size:13px;color:var(--text-3);"><?= count($sessions) ?> sessions recorded</div>
      <div style="display:flex;gap:8px;">
        <select style="padding:6px 10px;border:0.5px solid var(--border-strong);border-radius:var(--radius);font-size:12px;color:var(--text-2);background:var(--surface);cursor:pointer;">
          <option>All subjects</option>
          <option>Mathematics</option>
          <option>Biology</option>
          <option>Chemistry</option>
        </select>
      </div>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>Pair</th><th>Subject</th><th>Date</th><th>Duration</th><th>Score</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s):
          $cls = $s['score']>=85?'good':($s['score']>=70?'warn':'low');
        ?>
        <tr>
          <td><?= htmlspecialchars($s['pair']) ?></td>
          <td><?= htmlspecialchars($s['subject']) ?></td>
          <td style="color:var(--text-3);"><?= $s['date'] ?></td>
          <td style="color:var(--text-3);"><?= $s['duration'] ?></td>
          <td>
            <div class="score-bar">
              <div class="bar-track"><div class="bar-fill <?=$cls?>" style="width:<?=$s['score']?>%;"></div></div>
              <span style="font-size:12px;color:var(--text-3);"><?=$s['score']?>%</span>
            </div>
          </td>
          <td style="color:var(--text-3);max-width:200px;"><?= htmlspecialchars($s['notes']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($active_tab === 'reports'): ?>
    <!-- ══ REPORTS ══ -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
      <div class="stat-card">
        <div class="stat-label">Avg session score</div>
        <div class="stat-value">84%</div>
        <div class="stat-sub stat-up">↑ 3.2% vs last month</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Avg sessions / pair</div>
        <div class="stat-value">13</div>
        <div class="stat-sub" style="color:var(--text-3);">Target: 20 / month</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pair retention</div>
        <div class="stat-value">78%</div>
        <div class="stat-sub stat-down">↓ 2.1% vs last month</div>
      </div>
    </div>

    <div class="report-grid">
      <div class="report-card">
        <h4>User report</h4>
        <p>Full list of registered users with roles, status, and activity.</p>
        <div class="export-row">
          <a href="export.php?type=users&format=csv" class="btn">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </a>
          <a href="export.php?type=users&format=pdf" class="btn">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Export PDF
          </a>
        </div>
      </div>
      <div class="report-card">
        <h4>Session analytics</h4>
        <p>Session counts, scores, and duration breakdowns by subject.</p>
        <div class="export-row">
          <a href="export.php?type=sessions&format=csv" class="btn">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </a>
          <a href="export.php?type=sessions&format=xlsx" class="btn">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            Export Excel
          </a>
        </div>
      </div>
      <div class="report-card">
        <h4>Pair compatibility report</h4>
        <p>Match scores, subject alignment, and pair activity status.</p>
        <div class="export-row">
          <a href="export.php?type=pairs&format=csv" class="btn">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
          </a>
        </div>
      </div>
      <div class="report-card">
        <h4>Monthly summary</h4>
        <p>High-level summary of platform usage for the current month.</p>
        <div class="export-row">
          <a href="export.php?type=monthly&format=pdf" class="btn">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Export PDF
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    </div><!-- /content -->
  </main>
</div><!-- /app -->
<?php endif; ?>
</body>
</html>
