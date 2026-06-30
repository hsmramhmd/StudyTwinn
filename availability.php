<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include("../db_connection.php");

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? '';

if ($role !== 'tutor') {
    header("Location: ../dashboard.php");
    exit();
}

// Get tutor id
$tq = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id = '$user_id' LIMIT 1");
if (!$tq || mysqli_num_rows($tq) == 0) {
    // Create tutor row on the fly
    mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, rating, price_per_hour) 
                         VALUES ('$user_id', 'General', 'Tutoring', 'Available for sessions.', 4.5, 30)");
    $tq = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id = '$user_id' LIMIT 1");
}
$tutor_data = mysqli_fetch_assoc($tq);
$tutor_id = (int)$tutor_data['id'];

$msg = '';
$msg_type = '';

// Handle add slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $day   = mysqli_real_escape_string($conn, $_POST['day']);
    $start = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end   = mysqli_real_escape_string($conn, $_POST['end_time']);

    if ($start >= $end) {
        $msg = "End time must be after start time.";
        $msg_type = 'error';
    } else {
        // overlap check
        $check = mysqli_query($conn, "
            SELECT id FROM availability 
            WHERE tutor_id = $tutor_id AND day = '$day' AND (
                ('$start' >= start_time AND '$start' < end_time) OR
                ('$end' > start_time AND '$end' <= end_time) OR
                ('$start' <= start_time AND '$end' >= end_time)
            )
        ");
        if ($check && mysqli_num_rows($check) > 0) {
            $msg = "This slot overlaps with an existing availability.";
            $msg_type = 'error';
        } else {
            $ins = mysqli_query($conn, "
                INSERT INTO availability (tutor_id, day, start_time, end_time)
                VALUES ($tutor_id, '$day', '$start', '$end')
            ");
            if ($ins) {
                $msg = "Availability slot added successfully!";
                $msg_type = 'success';
            } else {
                $msg = "Failed to add slot.";
                $msg_type = 'error';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM availability WHERE id = $del_id AND tutor_id = $tutor_id");
    header("Location: availability.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) {
    $msg = "Slot removed.";
    $msg_type = 'success';
}

// Load current slots
$slots = mysqli_query($conn, "
    SELECT * FROM availability 
    WHERE tutor_id = $tutor_id 
    ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
");

// Avatar data for header
$av = mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id='$user_id'");
$av_row = $av ? mysqli_fetch_assoc($av) : [];
$avatar_color = $av_row['avatar_color'] ?? 'orange';
$avatar_animal = $av_row['avatar_animal'] ?? 'fox';
$avatar_outfit = $av_row['avatar_outfit'] ?? 'none';

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
$active_grad = $color_themes[$avatar_color]['grad'] ?? 'linear-gradient(135deg,#116979,#1b90a5)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Availability — StudyTwin</title>
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
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5;
    --orange:#f0672b; --orange-light:#ffb26b;
    --bg:#f4f8f9; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6;
    --card:#ffffff; --sidebar-bg:#ffffff; --input-bg:#f7fafb;
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --radius:18px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif;}
body{background:var(--bg);display:flex;color:var(--text);}
.card{background:var(--card);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow,0 4px 14px rgba(0,0,0,.06));border:1px solid var(--line);margin-bottom:22px;}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;align-items:end;}
.form-group label{display:block;font-size:.82rem;font-weight:600;color:var(--muted);margin-bottom:5px;}
.form-group input, .form-group select{padding:11px 14px;border:1.5px solid var(--line);border-radius:11px;font-size:.95rem;width:100%;background:var(--input-bg,#f7fafb);color:var(--text);}
.btn{padding:12px 20px;border:none;border-radius:11px;font-weight:700;cursor:pointer;font-size:.95rem;}
.btn-teal{background:var(--teal);color:#fff;}
.btn-teal:hover{background:#0b4e5a;}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:12px 14px;text-align:left;border-bottom:1px solid #eef3f6;font-size:.92rem;}
.table th{color:#4d6366;font-weight:600;}
.badge-day{background:#eaf6f8;color:#0b4e5a;padding:2px 10px;border-radius:999px;font-size:.8rem;font-weight:700;}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600;}
.alert.success{background:#ecfdf5;color:#166534;border-left:4px solid #10b981;}
.alert.error{background:#fef2f2;color:#b91c1c;border-left:4px solid #ef4444;}
.slot{display:inline-flex;align-items:center;gap:8px;background:#f0f7f8;padding:8px 14px;border-radius:999px;margin:4px 4px 4px 0;font-weight:600;}
</style>
<?php include_once("../includes/theme.php"); inject_theme_styles_and_script(); ?>
</head>
<body>

<div class="sidebar">
    <div class="logo"><h2>StudyTwin</h2></div>
    <ul class="menu">
        <li><a href="tutor_dashboard.php">🏠 Dashboard</a></li>
        <li><a class="active" href="availability.php">🕒 My Availability (Rooms)</a></li>
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
            <div>
                <h1 style="font-family:'Lexend',sans-serif;font-size:1.6rem;">My Availability 🕒</h1>
                <p style="color:var(--muted);">Set weekly time slots so students can discover and book your open sessions.</p>
            </div>
        </div>
        <div class="topbar-right" style="display:flex;align-items:center;gap:6px;">
            <a href="profile.php" class="topbar-avatar" title="Edit profile & avatar">
                <?php echo $avatar_emoji ?? '👨‍🏫'; ?>
                <?php if (!empty($avatar_outfit) && $avatar_outfit !== 'none' && !empty($avatar_outfit_emoji)): ?>
                    <span style="position:absolute;bottom:-4px;right:-4px;font-size:.75rem;background:var(--card);border-radius:6px;padding:0 2px;border:1px solid var(--line);"><?php echo $avatar_outfit_emoji; ?></span>
                <?php endif; ?>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- ADD FORM -->
    <div class="card">
        <h3 style="margin-bottom:14px;font-family:'Lexend',sans-serif;">Add New Time Slot</h3>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label>Day of Week</label>
                <select name="day" required>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>
            </div>
            <div class="form-group">
                <label>Start Time</label>
                <input type="time" name="start_time" value="09:00" required>
            </div>
            <div class="form-group">
                <label>End Time</label>
                <input type="time" name="end_time" value="10:00" required>
            </div>
            <div class="form-group">
                <button type="submit" name="add_slot" class="btn btn-teal" style="width:100%;">+ Add Availability Slot</button>
            </div>
        </form>
        <p style="margin-top:10px;font-size:.8rem;color:#6b7b8c;">These open slots allow students to find and send booking requests to you.</p>
    </div>

    <!-- CURRENT SLOTS -->
    <div class="card">
        <h3 style="margin-bottom:12px;">Your Current Weekly Slots (<?php echo $slots ? mysqli_num_rows($slots) : 0; ?>)</h3>

        <?php if ($slots && mysqli_num_rows($slots) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time Range</th>
                        <th>Duration</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($s = mysqli_fetch_assoc($slots)): 
                    $start = substr($s['start_time'], 0, 5);
                    $end   = substr($s['end_time'], 0, 5);
                ?>
                    <tr>
                        <td><span class="badge-day"><?php echo htmlspecialchars($s['day']); ?></span></td>
                        <td><strong><?php echo $start; ?></strong> — <?php echo $end; ?></td>
                        <td style="color:#6b7b8c;"><?php 
                            $diff = (strtotime($s['end_time']) - strtotime($s['start_time'])) / 60;
                            echo round($diff) . " min";
                        ?></td>
                        <td>
                            <a href="?delete=<?php echo (int)$s['id']; ?>" 
                               onclick="return confirm('Delete this availability slot?');" 
                               style="color:#c2410c;font-weight:700;text-decoration:none;">✕ Remove</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding:18px 0;color:#6b7b8c;">
                No availability set yet. Add slots above so students can discover your open times.
            </div>
        <?php endif; ?>
    </div>

    <div style="font-size:.85rem;color:#6b7b8c;">
        Tip: These slots are recurring every week. Students browsing tutors will see your available days.
    </div>
</div>

</body>
</html>