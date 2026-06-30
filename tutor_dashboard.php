<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include("../db_connection.php");

$user_id    = (int)$_SESSION['user_id'];
$full_name  = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Tutor';
$role       = $_SESSION['role'] ?? '';
$user_email = $_SESSION['email'] ?? '';

if ($role !== 'tutor') {
    header("Location: ../dashboard.php");
    exit();
}

// Get or ensure tutor profile exists
$tutor_res = mysqli_query($conn, "SELECT t.*, u.full_name FROM tutors t JOIN users u ON t.user_id = u.id WHERE t.user_id = '$user_id' LIMIT 1");
$tutor = $tutor_res ? mysqli_fetch_assoc($tutor_res) : null;

if (!$tutor) {
    // Auto-create a minimal tutor profile for this user (demo convenience)
    $subject = "General Tutoring";
    mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, rating, price_per_hour) 
        VALUES ('$user_id', '$subject', 'Various', 'Ready to help students succeed.', 4.5, 25.00)");
    $tutor_res = mysqli_query($conn, "SELECT t.*, u.full_name FROM tutors t JOIN users u ON t.user_id = u.id WHERE t.user_id = '$user_id' LIMIT 1");
    $tutor = $tutor_res ? mysqli_fetch_assoc($tutor_res) : null;
}

$tutor_id = $tutor ? $tutor['id'] : 0;

/* Quick actions for Booking Requests */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_booking_id'])) {
    $bid = (int)$_POST['action_booking_id'];
    $action = $_POST['action'] ?? '';
    $new_status = '';
    if ($action === 'confirm') $new_status = 'confirmed';
    if ($action === 'decline') $new_status = 'cancelled';
    if ($new_status) {
        mysqli_query($conn, "UPDATE bookings SET status='$new_status' WHERE id=$bid AND tutor_id=$tutor_id AND status='pending'");
    }
    header("Location: tutor_dashboard.php?req_updated=1");
    exit();
}

// Stats
$upcoming_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE tutor_id='$tutor_id' AND status IN ('pending','confirmed') AND session_date >= CURDATE()");
if ($res) $upcoming_count = (int)mysqli_fetch_assoc($res)['c'];

$completed_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE tutor_id='$tutor_id' AND status='completed'");
if ($res) $completed_count = (int)mysqli_fetch_assoc($res)['c'];

$total_students = 0;
$res = mysqli_query($conn, "SELECT COUNT(DISTINCT student_id) AS c FROM bookings WHERE tutor_id='$tutor_id'");
if ($res) $total_students = (int)mysqli_fetch_assoc($res)['c'];

// Availability summary
$avail_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM availability WHERE tutor_id='$tutor_id'");
if ($res) $avail_count = (int)mysqli_fetch_assoc($res)['c'];

// Rooms created (for gamification)
$rooms_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM rooms WHERE tutor_id='$tutor_id'");
if ($res) $rooms_count = (int)mysqli_fetch_assoc($res)['c'];

// Recent / upcoming bookings for this tutor
$my_bookings = mysqli_query($conn, "
    SELECT b.id, b.subject, b.session_date, b.status,
           u.full_name AS student_name, u.email AS student_email
    FROM bookings b
    JOIN users u ON b.student_id = u.id
    WHERE b.tutor_id = '$tutor_id'
    ORDER BY b.session_date ASC, b.id DESC
    LIMIT 8
");

// Availability slots
$my_slots = mysqli_query($conn, "
    SELECT id, day, start_time, end_time
    FROM availability
    WHERE tutor_id = '$tutor_id'
    ORDER BY FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
");

/* ========== TUTOR GAMIFICATION ========== */
$tutor_xp = ($completed_count * 35) + ($total_students * 20) + (round(($tutor['rating'] ?? 4.5) * 30)) + ($avail_count * 5) + ($rooms_count * 15);

$tutor_levels = [
    ['name'=>'Novice Tutor', 'icon'=>'🌱', 'min'=>0],
    ['name'=>'Apprentice', 'icon'=>'📖', 'min'=>200],
    ['name'=>'Mentor', 'icon'=>'🎓', 'min'=>500],
    ['name'=>'Expert Educator', 'icon'=>'⭐', 'min'=>1000],
    ['name'=>'Master Tutor', 'icon'=>'🏆', 'min'=>1800],
];

$tutor_current_level = $tutor_levels[0];
$tutor_next_level = $tutor_levels[1];
foreach ($tutor_levels as $i => $lvl) {
    if ($tutor_xp >= $lvl['min']) {
        $tutor_current_level = $lvl;
        $tutor_next_level = isset($tutor_levels[$i+1]) ? $tutor_levels[$i+1] : $lvl;
    }
}
$tutor_xp_in_level = ($tutor_current_level['name'] !== 'Master Tutor') ? ($tutor_xp - $tutor_current_level['min']) : 0;
$tutor_xp_needed   = ($tutor_next_level['min'] > $tutor_current_level['min']) ? ($tutor_next_level['min'] - $tutor_current_level['min']) : 1;
$tutor_xp_pct = ($tutor_xp_needed > 0) ? min(100, round(($tutor_xp_in_level / $tutor_xp_needed) * 100)) : 100;

// Tutor Badges
$tutor_badges = [
    ['icon'=>'📚', 'name'=>'Session Starter', 'desc'=>'Complete your first session', 'unlocked' => $completed_count >= 1],
    ['icon'=>'👥', 'name'=>'Student Magnet', 'desc'=>'Teach 3+ unique students', 'unlocked' => $total_students >= 3],
    ['icon'=>'⭐', 'name'=>'Highly Rated', 'desc'=>'Reach 4.5+ average rating', 'unlocked' => ($tutor['rating'] ?? 0) >= 4.5],
    ['icon'=>'🕒', 'name'=>'Availability Pro', 'desc'=>'Set 10+ availability slots', 'unlocked' => $avail_count >= 10],
    ['icon'=>'🏠', 'name'=>'Room Creator', 'desc'=>'Create 3+ custom rooms/sessions', 'unlocked' => $rooms_count >= 3],
];
$tutor_unlocked = 0;
foreach ($tutor_badges as $b) if ($b['unlocked']) $tutor_unlocked++;


// Pending Booking Requests (key feature)
$pending_requests = mysqli_query($conn, "
    SELECT b.id, b.subject, b.session_date, u.full_name AS student_name, t.price_per_hour
    FROM bookings b
    JOIN users u ON b.student_id = u.id
    JOIN tutors t ON b.tutor_id = t.id
    WHERE b.tutor_id = '$tutor_id' AND b.status = 'pending'
    ORDER BY b.session_date ASC
    LIMIT 5
");

// Avatar (reuse student logic style)
$avatar_res = mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id='$user_id'");
$avatar_row = $avatar_res ? mysqli_fetch_assoc($avatar_res) : null;
$avatar_color  = $avatar_row['avatar_color'] ?? 'orange';
$avatar_animal = $avatar_row['avatar_animal'] ?? 'fox';
$avatar_outfit = $avatar_row['avatar_outfit'] ?? 'none';

$animals = ['fox'=>'🦊','cat'=>'🐱','bear'=>'🐻','rabbit'=>'🐰','owl'=>'🦉','penguin'=>'🐧'];
$outfits = ['none'=>'','graduation'=>'🎓','chef'=>'👨‍🍳','ninja'=>'🥷','wizard'=>'🧙','astronaut'=>'👨‍🚀','knight'=>'🧝','crown'=>'👑'];
$avatar_emoji = $animals[$avatar_animal] ?? '🦊';
$avatar_outfit_emoji = $outfits[$avatar_outfit] ?? '';

$color_themes = [
    'orange'   => ['grad'=>'linear-gradient(135deg,#f0672b,#ffb26b)'],
    'teal'     => ['grad'=>'linear-gradient(135deg,#116979,#1b90a5)'],
    'purple'   => ['grad'=>'linear-gradient(135deg,#7c3aed,#a78bfa)'],
    'rose'     => ['grad'=>'linear-gradient(135deg,#e11d48,#fb7185)'],
    'green'    => ['grad'=>'linear-gradient(135deg,#16a34a,#4ade80)'],
    'midnight' => ['grad'=>'linear-gradient(135deg,#1e293b,#475569)'],
    'gold'     => ['grad'=>'linear-gradient(135deg,#b45309,#fbbf24)'],
    'sky'      => ['grad'=>'linear-gradient(135deg,#0284c7,#38bdf8)'],
];
$active_grad = $color_themes[$avatar_color]['grad'] ?? $color_themes['orange']['grad'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor Dashboard — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@600;700;800&display=swap" rel="stylesheet">
<style>
/* ═══════════ SHARED SIDEBAR ═══════════ */
.sidebar{width:250px;height:100vh;position:fixed;top:0;left:0;bottom:0;z-index:100;background:var(--sidebar-bg,#ffffff);border-right:1px solid var(--line,#eef3f6);padding:22px;display:flex;flex-direction:column;overflow-y:auto;transition:width 0.25s ease;}
.sidebar .logo{margin-bottom:24px;}
.sidebar .logo h2{font-family:'Lexend',sans-serif;color:var(--teal,#116979);font-size:1.3rem;font-weight:800;margin:0;}
.sidebar .menu{list-style:none;padding:0;flex:1;}
.sidebar .menu li a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;text-decoration:none;color:var(--text,#1e2a35);font-size:0.9rem;font-weight:500;transition:background 0.2s;margin-bottom:2px;}
.sidebar .menu li a:hover,.sidebar .menu li a.active{background:rgba(17,105,121,0.1);color:var(--teal,#116979);}
.sidebar .menu .sep{margin-top:14px;padding-top:12px;border-top:1px solid var(--line,#eef3f6);}
.sidebar .logout{padding-top:16px;margin-top:auto;}
.sidebar .logout a{display:block;padding:10px 14px;border-radius:10px;background:#fff3ee;color:var(--orange,#f0672b);text-decoration:none;font-weight:600;font-size:0.9rem;}
/* ═══════════ SHARED TOPBAR ═══════════ */
.main{margin-left:250px;width:calc(100% - 250px);padding:26px 32px 60px;transition:margin-left 0.25s ease;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
.topbar-left{display:flex;align-items:center;gap:10px;}
.topbar-left h1{font-family:'Lexend',sans-serif;font-size:1.6rem;font-weight:700;white-space:nowrap;}
.topbar-left p{color:var(--muted,#6b7b8c);font-size:.9rem;margin-top:2px;white-space:nowrap;}
.topbar-right{display:flex;align-items:center;gap:6px;}
.topbar-avatar{width:44px;height:44px;background:linear-gradient(135deg,#116979,#1b90a5);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;text-decoration:none;box-shadow:0 4px 14px rgba(0,0,0,0.12);position:relative;flex-shrink:0;}
.sidebar-toggle{width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid var(--line,#eef3f6);border-radius:8px;cursor:pointer;font-size:1.1rem;color:var(--text,#1e2a35);flex-shrink:0;transition:background 0.2s;}
.sidebar-toggle:hover{background:rgba(17,105,121,0.08);color:var(--teal,#116979);}
/* ═══════════════════════════════════════ */

:root{
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5; --teal-pale:#eaf6f8;
    --orange:#f0672b; --orange-light:#ffb26b; --orange-pale:#fff3ee;
    --bg:#f4f8f9; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6;
    --shadow-sm:0 4px 14px rgba(17,105,121,0.06);
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --shadow-lg:0 18px 44px rgba(17,105,121,0.12);
    --radius:18px; --radius-sm:12px;
    --ease:cubic-bezier(.4,0,.2,1);
    --avatar-grad: <?php echo $active_grad; ?>;
}
*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
html{ scroll-behavior:smooth; }
body{ background:var(--bg); display:flex; color:var(--text); }
::selection{ background:var(--teal); color:white; }

    width:260px; height:100vh; position:fixed;
    background:var(--sidebar-bg); border-right:1px solid var(--line);
    padding:25px; display:flex; flex-direction:column; z-index:10;
}
    text-decoration:none; color:#667; display:block;
    padding:11px 14px; border-radius:12px; transition:.25s; font-weight:500; font-size:.95rem;
}
    display:block; padding:11px 14px; border-radius:12px;
    text-decoration:none; background:#fff3ee; color:var(--orange); font-weight:600; font-size:.95rem;
}


    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:26px;
}
    font-family:'Lexend',sans-serif; font-size:1.65rem; font-weight:700;
}


    width:48px; height:48px;
    background: var(--avatar-grad);
    border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.5rem;
    box-shadow:0 6px 18px rgba(0,0,0,0.14);
    text-decoration:none;
    position:relative;
}

.stats-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap:16px;
    margin-bottom:26px;
}
.stat-card{
    background:var(--card); border-radius:var(--radius); padding:18px 20px;
    box-shadow:var(--shadow-sm); border:1px solid var(--line);
}
.stat-card .label{ font-size:.82rem; color:var(--muted); font-weight:600; }
.stat-card .value{ font-size:1.85rem; font-weight:800; color:var(--text); line-height:1.1; margin:4px 0; }
.stat-card .hint{ font-size:.78rem; color:var(--muted); }

.card{
    background:var(--card); border-radius:var(--radius); padding:22px;
    box-shadow:var(--shadow-sm); border:1px solid var(--line);
    margin-bottom:22px;
}
.card-header{
    display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;
}
.card-header h3{ font-family:'Lexend',sans-serif; font-size:1.1rem; }

.booking-list{ display:flex; flex-direction:column; gap:10px; }
.booking-row{
    display:flex; justify-content:space-between; align-items:center;
    padding:12px 16px; border-radius:12px; background:var(--card); border:1px solid var(--line);
}
:root[data-theme="dark"] .booking-row:hover{ background:#334155; }
.booking-row .info{ display:flex; gap:14px; align-items:center; }
.badge{
    font-size:.72rem; padding:2px 9px; border-radius:999px; font-weight:700;
}
.badge.pending{ background:#fff7ed; color:#c2410c; }
.badge.confirmed{ background:#ecfdf5; color:#166534; }
.badge.completed{ background:#f0fdf4; color:#166534; }
.badge.cancelled{ background:#fef2f2; color:#991b1b; }

.slot-list{ display:flex; flex-wrap:wrap; gap:8px; }
.slot-pill{
    background:var(--teal-pale); color:var(--teal); padding:6px 14px; border-radius:999px;
    font-size:.85rem; font-weight:600; display:inline-flex; align-items:center; gap:6px;
}
:root[data-theme="dark"] .slot-pill {
    background: #1e3a5f;
    color: #bae6fd;
}
.slot-pill .del{ cursor:pointer; font-size:0.8rem; opacity:0.6; }
.slot-pill .del:hover{ opacity:1; color:#c2410c; }

a.btn, button.btn{
    display:inline-block; padding:10px 18px; border-radius:10px;
    font-weight:700; font-size:.9rem; text-decoration:none; cursor:pointer;
    border:none; transition:all .2s;
}
.btn-teal{ background:var(--teal); color:white; }
.btn-teal:hover{ background:var(--teal-dark); }
.btn-orange{ background:var(--orange); color:white; }
.btn-outline{ background:white; border:1.5px solid var(--line); color:var(--text); }

.empty{ padding:30px; text-align:center; color:var(--muted); }

.collapsible-header { cursor:pointer; }
</style>
<?php include_once("../includes/theme.php"); inject_theme_styles_and_script(); ?>
</head>
<body>

<div class="sidebar">
    <div class="logo"><h2>StudyTwin</h2></div>
    <ul class="menu">
        <li><a class="active" href="tutor_dashboard.php">🏠 Dashboard</a></li>
        <li><a href="availability.php">🕒 My Availability (Rooms)</a></li>
        <li><a href="rooms.php">📅 My Rooms / Sessions</a></li>
        <li><a href="bookings.php">📥 Booking Requests</a></li>
        <li><a href="rewards.php">🎁 Tutor Rewards</a></li>
        <li><a href="profile.php">👤 Tutor Profile</a></li>
        <li class="sep"><a href="../dashboard.php">👨‍🎓 Switch to Student View</a></li>
    </ul>
    <div class="logout">
        <a href="../logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" title="Toggle sidebar">☰</button>
            <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?> 👋</h1>
            <p>Review booking requests, set your availability, and manage your tutoring sessions.</p>
        </div>
        <div class="topbar-right">
            <a href="profile.php" class="topbar-avatar" title="Edit profile & avatar">
                <?php echo $avatar_emoji; ?>
                <?php if ($avatar_outfit_emoji): ?>
                    <span style="position:absolute;bottom:-4px;right:-4px;font-size:.75rem;background:var(--card);border-radius:6px;padding:0 2px;border:1px solid var(--line);"><?php echo $avatar_outfit_emoji; ?></span>
                <?php endif; ?>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">📅 UPCOMING SESSIONS</div>
            <div class="value"><?php echo $upcoming_count; ?></div>
            <div class="hint">Confirmed or pending</div>
        </div>
        <div class="stat-card">
            <div class="label">✅ COMPLETED</div>
            <div class="value"><?php echo $completed_count; ?></div>
            <div class="hint">Sessions finished</div>
        </div>
        <div class="stat-card">
            <div class="label">👥 UNIQUE STUDENTS</div>
            <div class="value"><?php echo $total_students; ?></div>
            <div class="hint">Taught so far</div>
        </div>
        <div class="stat-card">
            <div class="label">🕒 AVAILABILITY SLOTS</div>
            <div class="value"><?php echo $avail_count; ?></div>
            <div class="hint"><a href="availability.php" style="color:var(--teal);text-decoration:none;">Manage rooms →</a></div>
        </div>
    </div>

    <!-- TUTOR GAMIFICATION -->
    <div class="card">
        <div class="card-header">
            <h3>⚡ Tutor Progress & Achievements</h3>
            <a href="rewards.php" class="btn btn-outline" style="font-size:.8rem;padding:6px 12px;">View all rewards →</a>
        </div>
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="font-size:1.8rem;"><?php echo $tutor_current_level['icon']; ?></div>
                    <div>
                        <div style="font-weight:700; font-size:1.05rem;"><?php echo $tutor_current_level['name']; ?></div>
                        <div style="font-size:.8rem; color:var(--muted);"><?php echo number_format($tutor_xp); ?> XP total</div>
                    </div>
                </div>
            </div>
            <?php if ($tutor_current_level['name'] !== 'Master Tutor'): ?>
            <div style="flex:1; min-width:180px;">
                <div style="font-size:.75rem; color:var(--muted); display:flex; justify-content:space-between;">
                    <span><?php echo $tutor_current_level['name']; ?></span>
                    <span><?php echo $tutor_next_level['name']; ?> <?php echo $tutor_next_level['icon']; ?></span>
                </div>
                <div style="background:#e5e7eb; height:8px; border-radius:999px; margin:4px 0;">
                    <div style="background:linear-gradient(to right, var(--teal), var(--teal-light)); height:8px; border-radius:999px; width:<?php echo $tutor_xp_pct; ?>%;"></div>
                </div>
                <div style="font-size:.72rem; color:var(--muted);"><?php echo number_format($tutor_xp_in_level); ?> / <?php echo number_format($tutor_xp_needed); ?> XP to next</div>
            </div>
            <?php else: ?>
            <div style="color:#f59e0b; font-weight:600;">🏆 Maximum level reached!</div>
            <?php endif; ?>
        </div>

        <div style="margin-top:16px;">
            <div style="font-size:.8rem; font-weight:600; margin-bottom:6px;">Achievements (<?php echo $tutor_unlocked; ?>/<?php echo count($tutor_badges); ?>)</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <?php foreach ($tutor_badges as $badge): ?>
                    <div style="background:<?php echo $badge['unlocked'] ? '#ecfdf5' : '#f3f4f6'; ?>; color:<?php echo $badge['unlocked'] ? '#166534' : '#6b7280'; ?>; padding:4px 10px; border-radius:999px; font-size:.72rem; display:flex; align-items:center; gap:4px; border:1px solid <?php echo $badge['unlocked'] ? '#86efac' : '#e5e7eb'; ?>;">
                        <span><?php echo $badge['icon']; ?></span>
                        <span><?php echo $badge['name']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- BOOKING REQUESTS -->
    <?php if (isset($_GET['req_updated'])): ?>
        <div style="background:#ecfdf5;color:#166534;padding:8px 12px;border-radius:6px;margin-bottom:12px;font-size:.85rem;">✅ Request updated.</div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header collapsible-header" data-target="#pending-requests">
            <h3>📥 Pending Booking Requests (<?php echo $pending_requests ? mysqli_num_rows($pending_requests) : 0; ?>)</h3>
        </div>
        <div id="pending-requests" class="collapsible-content">
            <?php if ($pending_requests && mysqli_num_rows($pending_requests) > 0): ?>
                <div class="booking-list">
                <?php while ($req = mysqli_fetch_assoc($pending_requests)): ?>
                    <div class="booking-row">
                        <div class="info">
                            <div>
                                <strong><?php echo htmlspecialchars($req['student_name']); ?></strong>
                                <span style="color:var(--muted); font-size:.8rem;">(<?php echo htmlspecialchars($req['subject']); ?>)</span>
                                <span style="font-size:.7rem; color:var(--muted);"> RM<?php echo number_format((float)($req['price_per_hour'] ?? 0), 2); ?>/hr</span>
                            </div>
                            <div style="font-size:.85rem; color:var(--muted);"><?php echo htmlspecialchars($req['session_date']); ?></div>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action_booking_id" value="<?php echo (int)$req['id']; ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn btn-teal" style="padding:6px 10px;font-size:.8rem;">Confirm</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action_booking_id" value="<?php echo (int)$req['id']; ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="btn" style="padding:6px 10px;font-size:.8rem;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;" onclick="return confirm('Decline this request?');">Decline</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty" style="padding:10px 0;">No pending requests. Students will request when they book your open slots.</div>
            <?php endif; ?>
            <div style="margin-top:8px;">
                <a href="bookings.php" class="btn btn-outline" style="font-size:.85rem;">Manage All Requests →</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📌 Your Tutor Profile</h3>
            <a href="profile.php" class="btn btn-outline">Edit Profile</a>
        </div>
        <div style="display:flex; gap:30px; flex-wrap:wrap;">
            <div>
                <strong style="font-size:1.1rem;"><?php echo htmlspecialchars($tutor['full_name'] ?? $full_name); ?></strong><br>
                <span style="color:var(--orange); font-weight:700;"><?php echo htmlspecialchars($tutor['subject'] ?? ''); ?></span><br>
                <span style="color:var(--muted);"><?php echo htmlspecialchars($tutor['expertise'] ?? ''); ?></span>
            </div>
            <div>
                <span style="font-size:1.6rem; font-weight:800;">⭐ <?php echo htmlspecialchars($tutor['rating'] ?? '4.5'); ?></span><br>
                <span style="color:var(--muted);">RM <?php echo number_format((float)($tutor['price_per_hour'] ?? 0), 2); ?>/hour</span>
            </div>
            <div style="max-width:420px; color:var(--muted);">
                <?php echo htmlspecialchars($tutor['bio'] ?? 'No bio yet.'); ?>
            </div>
        </div>
    </div>

    <!-- UPCOMING BOOKINGS -->
    <div class="card">
        <div class="card-header collapsible-header" data-target="#upcoming-bookings">
            <h3>📅 Confirmed & Recent Sessions</h3>
        </div>
        <div id="upcoming-bookings" class="collapsible-content">
            <?php if ($my_bookings && mysqli_num_rows($my_bookings) > 0): ?>
                <div class="booking-list">
                <?php while ($b = mysqli_fetch_assoc($my_bookings)): ?>
                    <div class="booking-row">
                        <div class="info">
                            <div>
                                <strong><?php echo htmlspecialchars($b['student_name']); ?></strong>
                                <span style="color:var(--muted); font-size:.8rem;">(<?php echo htmlspecialchars($b['subject']); ?>)</span>
                            </div>
                            <div style="font-size:.85rem; color:var(--muted);"><?php echo htmlspecialchars($b['session_date']); ?></div>
                        </div>
                        <div>
                            <span class="badge <?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty">No sessions yet. Review pending requests above or students will book your open slots.</div>
            <?php endif; ?>
            <div style="margin-top:14px;">
                <a href="bookings.php" class="btn btn-teal">Manage All Booking Requests →</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header collapsible-header" data-target="#availability-summary">
            <h3>🕒 Your Current Availability (<?php echo $avail_count; ?> slots)</h3>
        </div>
        <div id="availability-summary" class="collapsible-content">
            <?php if ($my_slots && mysqli_num_rows($my_slots) > 0): ?>
                <div class="slot-list">
                    <?php while ($s = mysqli_fetch_assoc($my_slots)): ?>
                        <span class="slot-pill">
                            <?php echo htmlspecialchars($s['day']); ?> 
                            <?php echo substr($s['start_time'],0,5); ?>–<?php echo substr($s['end_time'],0,5); ?>
                        </span>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty" style="padding:14px 0;">
                    You have not set any availability yet.
                </div>
            <?php endif; ?>
            <a href="availability.php" class="btn btn-orange" style="margin-top:14px; display:inline-block;">Set up / Edit Recurring →</a>
            <a href="rooms.php" class="btn btn-teal" style="margin-top:8px; display:inline-block;">Manage Specific Rooms / Sessions →</a>
        </div>
    </div>

</div>

</body>
</html>