<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
include("../db_connection.php");

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
if ($role !== 'tutor') { header("Location: ../profile.php"); exit(); }

$full_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Tutor';
$msg = '';

// Handle tutor profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tutor'])) {
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $expertise = mysqli_real_escape_string($conn, trim($_POST['expertise']));
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $price = (float)($_POST['price_per_hour'] ?? 25);

    $check = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id='$user_id'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE tutors SET subject='$subject', expertise='$expertise', bio='$bio', price_per_hour='$price' WHERE user_id='$user_id'");
    } else {
        mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, price_per_hour, rating) VALUES ('$user_id','$subject','$expertise','$bio','$price', 4.5)");
    }
    $msg = "Profile updated successfully!";
}

// Load tutor data
$tutor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tutors WHERE user_id='$user_id'")) ?: ['subject'=>'','expertise'=>'','bio'=>'','price_per_hour'=>25,'rating'=>4.5];

// Avatar
$av = mysqli_fetch_assoc(mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id='$user_id'"));
$avatar_color = $av['avatar_color'] ?? 'teal';
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
<title>Tutor Profile — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lexend:wght@700&display=swap" rel="stylesheet">
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

:root{--teal:#116979;--orange:#f0672b;--bg:#f4f8f9;--text:#1e2a35;--muted:#6b7b8c;--line:#eef3f6;--card:#ffffff;--sidebar-bg:#ffffff;--input-bg:#f7fafb;--radius:16px;}
*{box-sizing:border-box;font-family:'Poppins',sans-serif;margin:0;padding:0;}
body{background:var(--bg);display:flex;color:var(--text);}
.card{max-width:720px;background:var(--card);border-radius:18px;padding:28px;box-shadow:0 10px 30px rgba(17,105,121,.08);border:1px solid var(--line);}
input,textarea,select{width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;margin-top:4px;background:var(--input-bg);color:var(--text);font-family:'Poppins',sans-serif;font-size:.9rem;}
label{font-size:.85rem;font-weight:600;color:var(--muted);margin-top:14px;display:block;}
.btn{background:var(--teal);color:white;padding:12px 26px;border:none;border-radius:11px;font-weight:700;cursor:pointer;font-size:.95rem;margin-top:20px;}
.btn:hover{background:#0b4e5a;}
.alert{margin:12px 0;padding:10px 14px;border-radius:8px;background:#ecfdf5;color:#166534;font-size:.9rem;}
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
        <li><a href="bookings.php">📥 Booking Requests</a></li>
        <li><a href="rewards.php">🎁 Tutor Rewards</a></li>
        <li><a class="active" href="profile.php">👤 Tutor Profile</a></li>
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
                <h1>👤 Tutor Profile</h1>
                <p>Update the information students will see.</p>
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

    <?php if ($msg): ?><div class="alert">✅ <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <label>Subject / Main Topic</label>
            <input name="subject" value="<?php echo htmlspecialchars($tutor['subject']); ?>" required>

            <label>Expertise / Tags</label>
            <input name="expertise" value="<?php echo htmlspecialchars($tutor['expertise']); ?>" placeholder="PHP, MySQL, React...">

            <label>Hourly Rate (RM)</label>
            <input type="number" step="0.5" name="price_per_hour" value="<?php echo $tutor['price_per_hour']; ?>">

            <label>Bio (shown to students)</label>
            <textarea name="bio" rows="4"><?php echo htmlspecialchars($tutor['bio']); ?></textarea>

            <label>Current Rating (read-only for demo)</label>
            <input type="text" value="<?php echo $tutor['rating']; ?>" disabled>

            <button type="submit" name="update_tutor" class="btn">Save Tutor Profile</button>
        </form>
    </div>

    <div style="margin-top:20px; display:flex; gap:16px; align-items:center;">
        <a href="tutor_dashboard.php" style="color:var(--teal);text-decoration:none;font-weight:500;">← Back to Tutor Dashboard</a>
        <span style="color:var(--line);">|</span>
        <a href="../profile.php" style="color:var(--teal);text-decoration:none;font-weight:500;">View full avatar profile</a>
    </div>
</div>
</body>
</html>