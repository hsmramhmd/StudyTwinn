<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
include("../db_connection.php");

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
if ($role !== 'tutor') { header("Location: ../dashboard.php"); exit(); }

$tq = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id='$user_id' LIMIT 1");
if (!$tq || mysqli_num_rows($tq) == 0) {
    // Auto-create tutor row so the page works even on first visit
    mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, rating, price_per_hour)
                         VALUES ('$user_id', 'General', 'Tutoring', 'Available for sessions.', 4.5, 30)");
    $tq = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id='$user_id' LIMIT 1");
}
$tutor_id = ($tq && $r = mysqli_fetch_assoc($tq)) ? (int)$r['id'] : 0;

/* Handle tutor updating booking status */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['booking_id'], $_POST['new_status'])) {
    $bid = (int)$_POST['booking_id'];
    $new = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed = ['pending','confirmed','completed','cancelled'];
    if (in_array($new, $allowed)) {
        mysqli_query($conn, "UPDATE bookings SET status='$new' WHERE id=$bid AND tutor_id=$tutor_id");
    }
    header("Location: bookings.php?updated=1");
    exit();
}

$bookings = mysqli_query($conn, "
    SELECT b.*, u.full_name AS student_name, u.email, t.price_per_hour
    FROM bookings b
    JOIN users u ON b.student_id = u.id
    JOIN tutors t ON b.tutor_id = t.id
    WHERE b.tutor_id = '$tutor_id'
    ORDER BY b.session_date DESC, b.id DESC
");

/* Avatar */
$full_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Tutor';
$av = mysqli_fetch_assoc(mysqli_query($conn, "SELECT avatar_animal, avatar_outfit FROM users WHERE id='$user_id'"));
$animals = ['fox'=>'🦊','cat'=>'🐱','bear'=>'🐻','rabbit'=>'🐰','owl'=>'🦉','penguin'=>'🐧'];
$outfits = ['none'=>'','graduation'=>'🎓','chef'=>'👨‍🍳','ninja'=>'🥷','wizard'=>'🧙','astronaut'=>'👨‍🚀','knight'=>'🧝','crown'=>'👑'];
$avatar_emoji = $animals[$av['avatar_animal'] ?? 'fox'] ?? '🦊';
$avatar_outfit_emoji = $outfits[$av['avatar_outfit'] ?? 'none'] ?? '';
$avatar_outfit = $av['avatar_outfit'] ?? 'none';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Student Bookings — StudyTwin</title>
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

:root{ --teal:#116979; --orange:#f0672b; --bg:#f4f8f9; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6; --card:#ffffff; --sidebar-bg:#ffffff; --radius:16px; }
*{box-sizing:border-box; font-family:'Poppins',sans-serif; margin:0; padding:0;}
body{background:var(--bg); display:flex; color:var(--text);}
.card{background:var(--card); border-radius:var(--radius); padding:20px; box-shadow:0 8px 24px rgba(0,0,0,0.06); border:1px solid var(--line); margin-bottom:18px;}
table{width:100%; border-collapse:collapse;}
th,td{padding:11px 12px; text-align:left; border-bottom:1px solid var(--line); font-size:.9rem;}
th{font-weight:600; color:var(--muted); font-size:.8rem; text-transform:uppercase;}
.badge{padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:700;}
.badge.pending{background:#fff7ed; color:#c2410c;}
.badge.confirmed{background:#eff6ff; color:#1d4ed8;}
.badge.completed{background:#f0fdf4; color:#15803d;}
.badge.cancelled{background:#fef2f2; color:#b91c1c;}
.alert{padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:.88rem;}
.alert.success{background:#ecfdf5; color:#166534;}
</style>
<?php include_once("../includes/theme.php"); inject_theme_styles_and_script(); ?>
</head>
<body>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
    <div class="logo"><h2>StudyTwin</h2></div>
    <ul class="menu">
        <li><a href="tutor_dashboard.php">🏠 Dashboard</a></li>
        <li><a href="availability.php">🕒 My Availability (Rooms)</a></li>
        <li><a href="rooms.php">📅 My Rooms / Sessions</a></li>
        <li><a class="active" href="bookings.php">📥 Booking Requests</a></li>
        <li><a href="rewards.php">🎁 Tutor Rewards</a></li>
        <li><a href="profile.php">👤 Tutor Profile</a></li>
        <li style="margin-top:16px; border-top:1px solid var(--line); padding-top:12px;">
            <a href="../dashboard.php" class="switch-to-student" style="font-size:.85rem; padding:5px 10px; border-radius:6px; text-decoration:none;">👨‍🎓 Switch to Student View</a>
        </li>
    </ul>
    <div class="logout">
        <a href="../logout.php">🚪 Logout</a>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" title="Toggle sidebar">☰</button>
            <div>
                <h1>Booking Requests & Sessions</h1>
                <p>Review and manage student booking requests and update session statuses.</p>
            </div>
        </div>
        <div class="topbar-right">
            <a href="profile.php" class="topbar-avatar" title="Edit profile & avatar">
                <?php echo $avatar_emoji; ?>
                <?php if ($avatar_outfit !== 'none' && $avatar_outfit_emoji): ?>
                    <span style="position:absolute;bottom:-4px;right:-4px;font-size:.75rem;background:var(--card);border-radius:6px;padding:0 2px;border:1px solid var(--line);"><?php echo $avatar_outfit_emoji; ?></span>
                <?php endif; ?>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert success">✅ Booking status updated.</div>
    <?php endif; ?>

    <div class="card">
        <?php if ($bookings && mysqli_num_rows($bookings) > 0): ?>
        <table>
            <thead>
                <tr><th>Student</th><th>Subject</th><th>Date</th><th>Status</th><th>Booked On</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($b['student_name']); ?></strong><br><span style="font-size:.75rem;color:var(--muted);"><?php echo htmlspecialchars($b['email']); ?></span></td>
                    <td><?php echo htmlspecialchars($b['subject']); ?><br><span style="font-size:.7rem;color:var(--muted);">RM<?php echo number_format((float)($b['price_per_hour'] ?? 0), 2); ?>/hr</span></td>
                    <td><?php echo htmlspecialchars($b['session_date']); ?></td>
                    <td><span class="badge <?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($b['created_at']); ?></td>
                    <td>
                        <form method="POST" style="display:inline; font-size:0.8rem;">
                            <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                            <select name="new_status" style="padding:3px 6px; font-size:0.75rem; border:1px solid var(--line); border-radius:6px; background:var(--input-bg,#f7fafb); color:var(--text);">
                                <?php foreach (['pending','confirmed','completed','cancelled'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $b['status']==$st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" style="padding:3px 8px; font-size:0.75rem; background:var(--teal); color:white; border:none; border-radius:6px; cursor:pointer; margin-left:4px;">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="padding:40px;text-align:center;color:var(--muted);">
                <div style="font-size:2rem;margin-bottom:12px;">📥</div>
                <p>No students have booked you yet.<br>Set your availability and students will find you on the Find Tutor page.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>