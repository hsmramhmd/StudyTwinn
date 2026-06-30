<?php
/** @var string $page_title */
/** @var string $page_subtitle */
/** @var string $current_page */
$flash = admin_flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — StudyTwin Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@600;700;800&display=swap" rel="stylesheet">
<?php include_once __DIR__ . '/../../includes/theme.php'; inject_theme_styles_and_script(); ?>
<style>
/* Admin tokens — inherit --bg, --text, --muted, --line, --card from theme.php */
:root {
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5; --teal-pale:#eaf6f8;
    --orange:#f0672b; --orange-light:#ffb26b; --orange-pale:#fff3ee;
    --surface-alt:#f8fbfc; --surface-muted:#eef3f6;
    --shadow-sm:0 4px 14px rgba(17,105,121,0.06);
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --radius:18px; --radius-sm:12px;
    --success:#16a34a; --success-bg:#dcfce7;
    --danger:#dc2626; --danger-bg:#fef2f2;
    --warn:#d97706; --warn-bg:#fffbeb;
    --badge-tutor-bg:#ede9fe; --badge-tutor-text:#6d28d9;
    --badge-inactive-bg:#f1f5f9;
}
:root[data-theme="dark"] {
    --teal-pale:#1e3a5f; --orange-pale:#3f2a1f;
    --surface-alt:#1e293b; --surface-muted:#334155;
    --shadow-sm:0 4px 14px rgba(0,0,0,0.25);
    --shadow:0 12px 30px rgba(0,0,0,0.3);
    --success:#86efac; --success-bg:#14532d;
    --danger:#fca5a5; --danger-bg:#450a0a;
    --warn:#fed7aa; --warn-bg:#451a03;
    --badge-tutor-bg:#312e81; --badge-tutor-text:#c4b5fd;
    --badge-inactive-bg:#334155;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5;}
.sidebar{width:250px;height:100vh;position:fixed;top:0;left:0;z-index:100;background:var(--card);border-right:1px solid var(--line);padding:22px;display:flex;flex-direction:column;overflow-y:auto;transition:width .25s ease;}
.sidebar .logo{margin-bottom:8px;}
.sidebar .logo h2{font-family:'Lexend',sans-serif;color:var(--teal);font-size:1.3rem;font-weight:800;}
.sidebar .logo span{font-size:.72rem;color:var(--muted);font-weight:600;letter-spacing:.04em;text-transform:uppercase;}
.sidebar .menu{list-style:none;padding:0;flex:1;margin-top:16px;}
.sidebar .menu .nav-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);padding:12px 14px 6px;}
.sidebar .menu li a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;text-decoration:none;color:var(--text);font-size:.88rem;font-weight:500;transition:background .2s;margin-bottom:2px;}
.sidebar .menu li a:hover,.sidebar .menu li a.active{background:rgba(17,105,121,.1);color:var(--teal);}
.sidebar .logout{padding-top:16px;margin-top:auto;}
.sidebar .logout a{display:block;padding:10px 14px;border-radius:10px;background:var(--orange-pale);color:var(--orange);text-decoration:none;font-weight:600;font-size:.88rem;}
.main{margin-left:250px;width:calc(100% - 250px);padding:26px 32px 60px;transition:margin-left .25s ease;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
.topbar-left{display:flex;align-items:center;gap:10px;}
.topbar-left h1{font-family:'Lexend',sans-serif;font-size:1.5rem;font-weight:700;}
.topbar-left p{color:var(--muted);font-size:.88rem;margin-top:2px;}
.topbar-right{display:flex;align-items:center;gap:10px;}
.admin-pill{background:var(--teal-pale);color:var(--teal);font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:20px;}
.admin-avatar{width:40px;height:40px;background:linear-gradient(135deg,var(--teal),var(--teal-light));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700;}
.sidebar-toggle{width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid var(--line);border-radius:8px;cursor:pointer;font-size:1.1rem;color:var(--text);}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-sm);padding:18px 20px;box-shadow:var(--shadow-sm);}
.stat-card.teal{background:linear-gradient(135deg,var(--teal),var(--teal-light));color:#fff;border:none;}
.stat-card.orange{background:linear-gradient(135deg,var(--orange),var(--orange-light));color:#fff;border:none;}
.stat-card .label{font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.85;margin-bottom:6px;}
.stat-card .value{font-family:'Lexend',sans-serif;font-size:1.75rem;font-weight:800;line-height:1;}
.stat-card .hint{font-size:.78rem;margin-top:4px;opacity:.8;}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-sm);margin-bottom:20px;overflow:hidden;box-shadow:var(--shadow-sm);}
.card-header{padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);flex-wrap:wrap;gap:10px;}
.card-header h3{font-family:'Lexend',sans-serif;font-size:.95rem;font-weight:700;}
.card-body{padding:20px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
table{width:100%;border-collapse:collapse;}
th{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:10px 14px;text-align:left;background:var(--surface-alt);border-bottom:1px solid var(--line);}
th.sortable{padding:0;}
th.sortable a{display:block;padding:10px 14px;color:var(--muted);text-decoration:none;white-space:nowrap;transition:color .15s,background .15s;}
th.sortable a:hover{color:var(--teal);background:rgba(17,105,121,.06);}
th.sort-active a{color:var(--teal);background:var(--teal-pale);}
.sort-hint{font-size:.78rem;color:var(--muted);margin-left:auto;}
td{padding:11px 14px;border-bottom:1px solid var(--line);font-size:.86rem;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--surface-alt);}
.badge{display:inline-flex;align-items:center;font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:20px;text-transform:capitalize;}
.badge-admin{background:var(--teal-pale);color:var(--teal);}
.badge-tutor{background:var(--badge-tutor-bg);color:var(--badge-tutor-text);}
.badge-student{background:var(--warn-bg);color:var(--warn);}
.badge-active,.badge-completed,.badge-confirmed,.badge-paid{background:var(--success-bg);color:var(--success);}
.badge-pending{background:var(--warn-bg);color:var(--warn);}
.badge-cancelled,.badge-failed,.badge-inactive{background:var(--badge-inactive-bg);color:var(--muted);}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;border:1px solid var(--line);background:var(--card);color:var(--text);text-decoration:none;transition:all .15s;}
.btn:hover{background:var(--surface-alt);}
.btn-teal{background:var(--teal);color:#fff;border-color:var(--teal);}
.btn-teal:hover{opacity:.9;}
.btn-orange{background:var(--orange);color:#fff;border-color:var(--orange);}
.btn-danger{background:var(--danger-bg);color:var(--danger);border-color:var(--danger);}
.btn-sm{padding:5px 10px;font-size:.75rem;}
.user-cell{display:flex;align-items:center;gap:10px;}
.user-av{width:30px;height:30px;border-radius:50%;background:var(--teal-pale);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;flex-shrink:0;}
.flash{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:.86rem;font-weight:500;}
.flash-success{background:var(--success-bg);color:var(--success);border:1px solid var(--success);}
.flash-error{background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger);}
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:center;}
.filter-bar select,.filter-bar input{padding:8px 12px;border:1px solid var(--line);border-radius:8px;font-size:.84rem;background:var(--card);color:var(--text);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.form-group label{display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:5px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:8px;font-size:.86rem;background:var(--input-bg,var(--card));color:var(--text);}
.chart-bars{display:flex;align-items:flex-end;gap:8px;height:100px;padding-top:10px;}
.chart-bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;}
.chart-bar{width:100%;background:var(--teal);border-radius:4px 4px 0 0;opacity:.8;min-height:4px;}
.chart-bar span{font-size:.65rem;color:var(--muted);}
.bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.bar-row .name{font-size:.8rem;width:110px;flex-shrink:0;color:var(--muted);}
.bar-track{flex:1;height:6px;background:var(--surface-muted);border-radius:3px;overflow:hidden;}
.bar-fill{height:100%;background:var(--teal);border-radius:3px;}
.bar-count{font-size:.78rem;color:var(--muted);width:30px;text-align:right;}
.empty-state{padding:40px;text-align:center;color:var(--muted);font-size:.88rem;}
.action-group{display:flex;gap:6px;flex-wrap:wrap;}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr);}.grid-2{grid-template-columns:1fr;}}
@media(max-width:768px){.sidebar{width:0;overflow:hidden;}.main{margin-left:0;width:100%;padding:18px;}.stats-grid{grid-template-columns:1fr;}}
.card-header h3,.topbar-left h1{color:var(--text);}
.sidebar .menu li a{color:var(--text);}
.sidebar-toggle{background:var(--card);border-color:var(--line);color:var(--text);}
.sidebar-toggle:hover{background:var(--surface-alt);}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <h2>StudyTwin</h2>
        <span>Admin Console</span>
    </div>
    <ul class="menu">
        <li class="nav-label">Overview</li>
        <li><a href="dashboard.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a></li>
        <li class="nav-label">Manage</li>
        <li><a href="users.php" class="<?= $current_page === 'users' ? 'active' : '' ?>">👥 Users</a></li>
        <li><a href="tutors.php" class="<?= $current_page === 'tutors' ? 'active' : '' ?>">👨‍🏫 Tutors</a></li>
        <li><a href="bookings.php" class="<?= $current_page === 'bookings' ? 'active' : '' ?>">📅 Bookings</a></li>
        <li><a href="rooms.php" class="<?= $current_page === 'rooms' ? 'active' : '' ?>">🏠 Study Rooms</a></li>
        <li><a href="payments.php" class="<?= $current_page === 'payments' ? 'active' : '' ?>">💳 Payments</a></li>
        <li><a href="rankings.php" class="<?= $current_page === 'rankings' ? 'active' : '' ?>">🏆 Top Rankings</a></li>
        <li class="nav-label">System</li>
        <li><a href="notifications.php" class="<?= $current_page === 'notifications' ? 'active' : '' ?>">🔔 Broadcast</a></li>
        <li><a href="reports.php" class="<?= $current_page === 'reports' ? 'active' : '' ?>">📥 Reports</a></li>
    </ul>
    <div class="logout">
        <a href="../logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" title="Toggle sidebar" type="button">☰</button>
            <div>
                <h1><?= htmlspecialchars($page_title) ?></h1>
                <?php if (!empty($page_subtitle)): ?>
                <p><?= htmlspecialchars($page_subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="topbar-right">
            <?php render_theme_toggle(); ?>
            <span class="admin-pill">Administrator</span>
            <div class="admin-avatar" title="<?= htmlspecialchars($admin_name) ?>"><?= admin_initials($admin_name) ?></div>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>