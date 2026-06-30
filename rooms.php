<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include("../db_connection.php");

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
if ($role !== 'tutor') {
    header("Location: ../dashboard.php");
    exit();
}

$tq = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id='$user_id' LIMIT 1");
if (!$tq || mysqli_num_rows($tq) == 0) {
    mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, rating, price_per_hour)
                         VALUES ('$user_id', 'General', 'Tutoring', 'Available for sessions.', 4.5, 30)");
    $tq = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id='$user_id' LIMIT 1");
}
$tutor_id = ($tq && $r=mysqli_fetch_assoc($tq)) ? (int)$r['id'] : 0;

$msg = '';
$msg_type = '';

// Handle create room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?: 'Study Session'));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $date = mysqli_real_escape_string($conn, $_POST['session_date']);
    $start = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end = mysqli_real_escape_string($conn, $_POST['end_time']);
    $max = max(1, (int)$_POST['max_students']);
    $desc = mysqli_real_escape_string($conn, trim($_POST['description']));

    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $msg = "Invalid date.";
        $msg_type = 'error';
    } elseif ($date < date('Y-m-d')) {
        $msg = "Date must be in the future.";
        $msg_type = 'error';
    } elseif ($start >= $end) {
        $msg = "End time must be after start.";
        $msg_type = 'error';
    } else {
        $ins = mysqli_query($conn, "
            INSERT INTO rooms (tutor_id, title, subject, session_date, start_time, end_time, max_students, description, status)
            VALUES ($tutor_id, '$title', '$subject', '$date', '$start', '$end', $max, '$desc', 'open')
        ");
        if ($ins) {
            $msg = "Room created successfully!";
            $msg_type = 'success';
        } else {
            $msg = "Error creating room.";
            $msg_type = 'error';
        }
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_room'])) {
    $rid = (int)$_POST['room_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $date = mysqli_real_escape_string($conn, $_POST['session_date']);
    $start = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end = mysqli_real_escape_string($conn, $_POST['end_time']);
    $max = max(1, (int)$_POST['max_students']);
    $desc = mysqli_real_escape_string($conn, trim($_POST['description']));
    $status = in_array($_POST['status'], ['open','closed','cancelled']) ? $_POST['status'] : 'open';

    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $msg = "Invalid date."; $msg_type = 'error';
    } elseif ($start >= $end) {
        $msg = "End time must be after start time."; $msg_type = 'error';
    } else {
        $upd = mysqli_query($conn, "
            UPDATE rooms SET 
                title='$title', subject='$subject', session_date='$date', 
                start_time='$start', end_time='$end', max_students=$max, 
                description='$desc', status='$status'
            WHERE id=$rid AND tutor_id=$tutor_id
        ");
        if ($upd) {
            $msg = "Room updated.";
            $msg_type = 'success';
        } else {
            $msg = "Failed to update room.";
            $msg_type = 'error';
        }
    }
}

// Get my rooms + joined count
$my_rooms_res = @mysqli_query($conn, "
    SELECT r.*, (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id) AS joined
    FROM rooms r
    WHERE r.tutor_id = $tutor_id
    ORDER BY r.session_date DESC, r.created_at DESC
");
$my_rooms = [];
if ($my_rooms_res) {
    while($row = mysqli_fetch_assoc($my_rooms_res)) $my_rooms[] = $row;
} else {
    $my_rooms = [];
}

// For editing: if ?edit=ID
$edit_room = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er = mysqli_query($conn, "SELECT * FROM rooms WHERE id=$eid AND tutor_id=$tutor_id");
    $edit_room = $er ? mysqli_fetch_assoc($er) : null;
}

// Avatar setup (abbreviated for brevity)
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
<title>My Rooms — StudyTwin</title>
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

:root{ --teal:#116979; --orange:#f0672b; --bg:#f4f8f9; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6; --card:#ffffff; --sidebar-bg:#ffffff; --input-bg:#f7fafb; --radius:16px; }
*{box-sizing:border-box; font-family:'Poppins',sans-serif; margin:0; padding:0;}
body{background:var(--bg); display:flex; color:var(--text);}
.card{background:var(--card); border-radius:var(--radius); padding:20px; box-shadow:0 8px 24px rgba(0,0,0,0.06); border:1px solid var(--line); margin-bottom:18px;}
.form-grid{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
.form-group label{display:block; font-size:.8rem; font-weight:600; margin-bottom:3px;}
input, select, textarea, button{padding:9px; border:1px solid var(--line); border-radius:6px; font-size:.9rem; width:100%; background:var(--input-bg); color:var(--text);}
button.btn{background:var(--teal); color:white; border:none; cursor:pointer; font-weight:600;}
table{width:100%; border-collapse:collapse; font-size:.9rem;}
th,td{padding:8px 10px; border-bottom:1px solid var(--line); text-align:left;}
.badge{padding:2px 8px; border-radius:999px; font-size:.7rem; font-weight:700;}
.alert{padding:8px 12px; border-radius:6px; margin-bottom:12px; font-size:.85rem;}
.alert.success{background:#e6ffed; color:#0a5;}
.alert.error{background:#ffe6e6; color:#a00;}
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
        <li><a class="active" href="rooms.php">📅 My Rooms / Sessions</a></li>
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
                <h1>📅 My Rooms / Sessions</h1>
                <p>Create and manage your tutoring sessions.</p>
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

    <?php if ($msg): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Create Room -->
    <div class="card">
        <h3>Create New Room</h3>
        <form method="POST" class="form-grid">
            <div class="form-group"><label>Title</label><input name="title" value="Study Session" required></div>
            <div class="form-group"><label>Subject</label><input name="subject" placeholder="e.g. Math" required></div>
            <div class="form-group"><label>Date</label><input type="date" name="session_date" min="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="form-group"><label>Max Students</label><input type="number" name="max_students" value="5" min="1" max="50" required></div>
            <div class="form-group"><label>Start Time</label><input type="time" name="start_time" value="14:00" required></div>
            <div class="form-group"><label>End Time</label><input type="time" name="end_time" value="15:00" required></div>
            <div class="form-group" style="grid-column:1/-1;"><label>Description (optional)</label><textarea name="description" rows="2" placeholder="What will be covered?"></textarea></div>
            <div style="grid-column:1/-1;"><button type="submit" name="create_room" class="btn">Create Room</button></div>
        </form>
    </div>

    <!-- My Rooms List + Edit -->
    <div class="card">
        <h3>Your Rooms (<?php echo count($my_rooms); ?>)</h3>
        <?php if (empty($my_rooms)): ?>
            <p style="color:#6b7b8c;">No rooms yet. Create one above.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Title</th><th>Subject</th><th>When</th><th>Limit</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($my_rooms as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['title']); ?></td>
                        <td><?php echo htmlspecialchars($r['subject']); ?></td>
                        <td><?php echo $r['session_date'] . ' ' . substr($r['start_time'],0,5); ?></td>
                        <td><?php echo $r['max_students']; ?></td>
                        <td><?php echo $r['joined']; ?></td>
                        <td><span class="badge" style="background:#f0f7f8;"><?php echo $r['status']; ?></span></td>
                        <td>
                            <a href="?edit=<?php echo $r['id']; ?>" style="font-size:.8rem;">Edit</a>
                            <?php if ($r['joined'] > 0): ?>
                                <a href="#" style="font-size:.75rem; color:#6b7b8c;" onclick="alert('Joined students: view in full bookings or extend later.'); return false;">View Students</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($edit_room && $edit_room['id'] == $r['id']): ?>
                    <tr><td colspan="7">
                        <form method="POST" style="background:#f8f9fa; padding:10px; border-radius:6px;">
                            <input type="hidden" name="room_id" value="<?php echo $edit_room['id']; ?>">
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:8px; margin-bottom:8px;">
                                <input name="title" value="<?php echo htmlspecialchars($edit_room['title']); ?>">
                                <input name="subject" value="<?php echo htmlspecialchars($edit_room['subject']); ?>">
                                <input type="date" name="session_date" value="<?php echo $edit_room['session_date']; ?>">
                                <input type="number" name="max_students" value="<?php echo $edit_room['max_students']; ?>" min="1">
                                <input type="time" name="start_time" value="<?php echo $edit_room['start_time']; ?>">
                                <input type="time" name="end_time" value="<?php echo $edit_room['end_time']; ?>">
                            </div>
                            <textarea name="description" style="width:100%; margin-bottom:8px;"><?php echo htmlspecialchars($edit_room['description']); ?></textarea>
                            <select name="status">
                                <?php foreach (['open','closed','cancelled'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $edit_room['status']==$st?'selected':''; ?>><?php echo ucfirst($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="edit_room" class="btn" style="padding:4px 10px; margin-left:8px;">Save Changes</button>
                            <a href="rooms.php" style="margin-left:8px; font-size:.8rem;">Cancel</a>
                        </form>
                    </td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>