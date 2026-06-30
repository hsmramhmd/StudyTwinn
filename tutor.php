<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include("db_connection.php");

$student_id = $_SESSION['user_id'];
$full_name  = $_SESSION['name'];
$role       = $_SESSION['role'];
$user_email = $_SESSION['email'];

/* =========================
   AVATAR COLOR
   ========================= */
$avatar_res    = mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id='$student_id'");
$avatar_row    = mysqli_fetch_assoc($avatar_res);
$avatar_color  = $avatar_row['avatar_color']  ?? 'orange';
$avatar_animal = $avatar_row['avatar_animal'] ?? 'fox';
$avatar_outfit = $avatar_row['avatar_outfit'] ?? 'none';

$animals_map = [
    'fox' => '🦊', 'cat' => '🐱', 'bear' => '🐻',
    'rabbit' => '🐰', 'owl' => '🦉', 'penguin' => '🐧',
];
$outfits_map = [
    'none' => '', 'graduation' => '🎓', 'chef' => '👨‍🍳',
    'ninja' => '🥷', 'wizard' => '🧙', 'astronaut' => '👨‍🚀',
    'knight' => '🧝', 'crown' => '👑',
];
$avatar_emoji        = $animals_map[$avatar_animal] ?? '🦊';
$avatar_outfit_emoji = $outfits_map[$avatar_outfit]  ?? '';

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

/* =========================
   BOOK SESSION
   ========================= */
$success = '';
$error   = '';

if (isset($_POST['book_session'])) {
    $tutor_id     = mysqli_real_escape_string($conn, $_POST['tutor_id']);
    $subject      = mysqli_real_escape_string($conn, $_POST['subject']);
    $session_date = mysqli_real_escape_string($conn, $_POST['session_date']);

    /* Prevent booking in the past */
    if ($session_date < date('Y-m-d')) {
        $error = "Please choose a future date.";
    } else {
        // Optional availability check: does this day of week have a slot for the tutor?
        $day_of_week = date('l', strtotime($session_date)); // Monday etc.
        $has_slot = mysqli_query($conn, "
            SELECT id FROM availability 
            WHERE tutor_id = '$tutor_id' AND day = '$day_of_week' 
            LIMIT 1
        ");
        if (!$has_slot || mysqli_num_rows($has_slot) === 0) {
            // Still allow but inform
            $warning = "Note: The chosen day has no listed availability for this tutor. Booking anyway.";
        }

        $sql = "INSERT INTO bookings (student_id, tutor_id, subject, session_date)
                VALUES ('$student_id','$tutor_id','$subject','$session_date')";

        if (mysqli_query($conn, $sql)) {
            $success = "Booking request sent! 🎉 The tutor will review and confirm.";
            if (!empty($warning)) $success .= " " . $warning;
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

/* =========================
   SEARCH / FILTER
   ========================= */
$search    = isset($_GET['search'])    ? mysqli_real_escape_string($conn, trim($_GET['search']))    : '';
$filter_ex = isset($_GET['expertise']) ? mysqli_real_escape_string($conn, trim($_GET['expertise'])) : '';

$where = "WHERE 1=1";
if ($search    !== '') $where .= " AND (users.full_name LIKE '%$search%' OR tutors.expertise LIKE '%$search%')";
if ($filter_ex !== '') $where .= " AND tutors.expertise = '$filter_ex'";

/* GET ALL TUTORS */
$tutors = mysqli_query($conn, "
    SELECT tutors.*, users.full_name
    FROM tutors
    JOIN users ON tutors.user_id = users.id
    $where
    ORDER BY tutors.rating DESC
");

/* EXPERTISE LIST for filter dropdown */
$expertise_res  = mysqli_query($conn, "SELECT DISTINCT expertise FROM tutors ORDER BY expertise ASC");
$expertise_list = [];
if ($expertise_res) {
    while ($ex = mysqli_fetch_assoc($expertise_res)) $expertise_list[] = $ex['expertise'];
}

/* LOAD AVAILABILITY SLOTS for all shown tutors (to display to students) */
$tutor_ids = [];
if ($tutors) {
    mysqli_data_seek($tutors, 0);
    while ($t = mysqli_fetch_assoc($tutors)) { $tutor_ids[] = (int)$t['id']; }
}
$availability_by_tutor = [];
if (!empty($tutor_ids)) {
    $ids_str = implode(',', $tutor_ids);
    $av_res = mysqli_query($conn, "
        SELECT tutor_id, day, start_time, end_time 
        FROM availability 
        WHERE tutor_id IN ($ids_str)
        ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
    ");
    if ($av_res) {
        while ($a = mysqli_fetch_assoc($av_res)) {
            $tid = $a['tutor_id'];
            if (!isset($availability_by_tutor[$tid])) $availability_by_tutor[$tid] = [];
            $availability_by_tutor[$tid][] = $a;
        }
    }
    // reset pointer for later loop
    mysqli_data_seek($tutors, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find Tutor — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --teal:#116979;
    --teal-dark:#0b4e5a;
    --teal-light:#1b90a5;
    --teal-pale:#eaf6f8;
    --orange:#f0672b;
    --orange-light:#ffb26b;
    --orange-pale:#fff3ee;
    --bg:#f4f8fb;
    --text:#1e2a35;
    --muted:#6b7b8c;
    --line:#eef3f6;
    --shadow-sm:0 4px 14px rgba(17,105,121,0.06);
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --shadow-lg:0 18px 44px rgba(17,105,121,0.12);
    --radius:18px;
    --radius-sm:12px;
    --ease:cubic-bezier(.4,0,.2,1);
    --avatar-grad: <?php echo $active_grad; ?>;
}

*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
html{ scroll-behavior:smooth; }
body{ background:var(--bg); display:flex; color:var(--text); }
::selection{ background:var(--teal); color:white; }
@keyframes fadeSlideIn{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:translateY(0); } }
@keyframes growBar{ from{ width:0%; } to{ width:var(--w,100%); } }
@media(prefers-reduced-motion:reduce){ *{ animation-duration:0.001ms!important; transition-duration:0.001ms!important; } }

/* ── SIDEBAR ── */
.sidebar{
    width:260px;
    position:fixed;
    top:0;
    bottom:0;

    display:flex;
    flex-direction:column;

    padding:25px;
    background:#fff;
    border-right:1px solid var(--line);
    overflow:hidden;
}
.logo{ display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.logo h2{ color:var(--teal); font-weight:800; font-family:'Lexend',sans-serif; }
.menu{
    flex:1;
    list-style:none;
}
.menu li{ margin-bottom:5px; }
.menu a{
    font-size:.9rem;
    text-decoration:none; color:var(--text); display:block;
    padding:9px 13px; border-radius:12px; transition:.3s;
}
:root[data-theme="dark"] .menu a {
    color: #cbd5e1 !important;
}
.menu a:hover, .menu a.active{ background:rgba(17,105,121,0.1); color:var(--teal); font-weight:600; }
.logout{
    flex-shrink:0;
    margin-top:auto;
    padding-top:16px;
    border-top:1px solid var(--line);
    background:var(--sidebar-bg,#fff);
}
.logout a{
    display:block; padding:12px 15px; border-radius:12px;
    text-decoration:none; background:#fff3ee; color:var(--orange); font-weight:600;
}
:root[data-theme="dark"] .logout { background: var(--sidebar-bg, #0f172a) !important; }
:root[data-theme="dark"] .logout a { background: #3f2a1f !important; color: #fb923c !important; }

/* ── MAIN ── */
.main{ margin-left:260px; width:calc(100% - 260px); padding:32px 36px 50px; }

/* ── TOPBAR ── */
.topbar{
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:28px; animation:fadeSlideIn .5s var(--ease) both;
}
.topbar-left h1{
    font-family:'Lexend',sans-serif; font-size:1.7rem;
    font-weight:700; color:var(--text);
}
.topbar-left p{ color:var(--muted); font-size:.92rem; margin-top:4px; }

.topbar-avatar{
    width:52px; height:52px;
    background: var(--avatar-grad);
    border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.7rem;
    box-shadow:0 6px 18px rgba(0,0,0,0.14);
    text-decoration:none; flex-shrink:0;
    transition:transform .25s var(--ease), box-shadow .25s var(--ease);
    position:relative;
}
.topbar-avatar:hover{ transform:scale(1.07); box-shadow:0 10px 24px rgba(0,0,0,0.2); }
.topbar-avatar .avatar-tooltip{
    display:none; position:absolute; top:calc(100% + 8px); right:0;
    background:#1e2a35; color:white; font-size:.7rem; font-weight:600;
    padding:5px 10px; border-radius:8px; white-space:nowrap; pointer-events:none;
}
.topbar-avatar:hover .avatar-tooltip{ display:block; }

/* ── ALERTS ── */
.alert{
    padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px;
    font-size:.9rem; font-weight:500;
}
.alert.success{ background:#e7f7ed; color:#188038; border-left:4px solid #22c55e; }
.alert.error  { background:#fff0f0; color:#c0392b; border-left:4px solid #ef4444; }

/* ── SEARCH BAR ── */
.search-bar{
    display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;
    animation:fadeSlideIn .5s var(--ease) .05s both;
}
.search-bar input,
.search-bar select{
    padding:11px 16px; border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-size:.9rem; color:var(--text); background:white;
    outline:none; transition:border-color .2s var(--ease);
}
.search-bar input{ flex:1; min-width:200px; }
.search-bar input:focus,
.search-bar select:focus{ border-color:var(--teal-light); }
.search-bar button{
    padding:11px 22px; border:none; border-radius:var(--radius-sm);
    background:var(--teal); color:white; font-weight:600;
    cursor:pointer; transition:background .2s var(--ease); width:auto;
}
.search-bar button:hover{ background:var(--teal-dark); }
.search-bar .reset-btn{
    padding:11px 18px; border:1.5px solid var(--line); border-radius:var(--radius-sm);
    background:white; color:var(--muted); font-weight:600;
    cursor:pointer; text-decoration:none; display:flex; align-items:center;
    transition:border-color .2s var(--ease), color .2s var(--ease);
}
.search-bar .reset-btn:hover{ border-color:var(--teal-light); color:var(--teal); }

/* ── RESULTS COUNT ── */
.results-count{
    font-size:.85rem; color:var(--muted); margin-bottom:18px;
}
.results-count strong{ color:var(--teal); }

/* ── TUTOR GRID ── */
.tutor-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(320px,1fr));
    gap:22px; animation:fadeSlideIn .5s var(--ease) .1s both;
}

.tutor-card{
    background:white; border-radius:var(--radius); padding:26px;
    box-shadow:var(--shadow); border:1px solid var(--line);
    display:flex; flex-direction:column; gap:0;
    transition:transform .3s var(--ease), box-shadow .3s var(--ease);
}
.tutor-card:hover{ transform:translateY(-4px); box-shadow:var(--shadow-lg); }

.tutor-header{ display:flex; align-items:center; gap:14px; margin-bottom:14px; }
.tutor-avatar{
    width:52px; height:52px; border-radius:14px;
    background:linear-gradient(135deg,var(--teal),var(--teal-light));
    color:white; display:flex; align-items:center; justify-content:center;
    font-family:'Lexend',sans-serif; font-size:1.3rem; font-weight:700;
    flex-shrink:0;
}
.tutor-name{ font-family:'Lexend',sans-serif; font-weight:700; font-size:1rem; color:var(--text); }
.tutor-expertise{
    display:inline-block; background:var(--orange-pale); color:var(--orange);
    padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700;
    margin-top:3px;
}

.tutor-stats{ display:flex; gap:16px; margin-bottom:14px; }
.tutor-stat{
    display:flex; align-items:center; gap:5px;
    font-size:.83rem; color:var(--muted);
}
.tutor-stat strong{ color:var(--text); }

.tutor-bio{
    font-size:.84rem; color:#556; line-height:1.65;
    margin-bottom:18px; flex:1;
}

/* ── BOOKING FORM ── */
.booking-form{ display:flex; flex-direction:column; gap:10px; }
.booking-form input[type=date]{
    width:100%; padding:11px 14px;
    border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-size:.88rem; color:var(--text); outline:none;
    transition:border-color .2s var(--ease);
}
.booking-form input[type=date]:focus{ border-color:var(--teal-light); }
.booking-form button{
    width:100%; padding:12px;
    border:none; border-radius:var(--radius-sm);
    background:linear-gradient(135deg,var(--orange),var(--orange-light));
    color:white; font-weight:700; font-size:.9rem;
    cursor:pointer; transition:opacity .2s var(--ease), transform .2s var(--ease);
}
.booking-form button:hover{ opacity:.9; transform:translateY(-1px); }

/* ── NO RESULTS ── */
.no-results{
    grid-column:1/-1; text-align:center;
    padding:60px 20px; color:var(--muted);
}
.no-results .no-results-icon{ font-size:3rem; margin-bottom:12px; }
.no-results p{ font-size:.95rem; }
.no-results a{ color:var(--teal); font-weight:600; text-decoration:none; }

/* ── RESPONSIVE ── */
@media(max-width:900px){
    .sidebar{ width:80px;  overflow-y:hidden; }
    .logo h2{ display:none; }
    .main{ margin-left:80px; width:calc(100% - 80px); }
    .tutor-grid{ grid-template-columns:1fr; }
}
@media(max-width:600px){
    .main{ padding:20px 16px 40px; }
    .search-bar{ flex-direction:column; }
    .search-bar input,
    .search-bar select,
    .search-bar button{ width:100%; }
}
</style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
    <div class="logo">
        <h2>StudyTwin</h2>
    </div>
    <ul class="menu">
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a class="active" href="tutor.php">📚 Find Tutor</a></li>
        <li><a href="rooms.php">📅 Rooms / Sessions</a></li>
        <li><a href="tutortoprank.php">🏆 Tutor Top Rank</a></li>
        <li><a href="bookings.php">📅 My Bookings</a></li>
        <li><a href="leaderboard.php">🥇 Leaderboard</a></li>
        <li><a href="messages.php">💬 Messages</a></li>
        <li><a href="profile.php">👤 Profile</a></li>
        <li><a href="quest.php">🎮 My Quests</a></li>
        <li><a href="rewards.php">🎁 My Rewards</a></li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role']==='tutor'): ?>
        <li><a href="tutor/tutor_dashboard.php">👨‍🏫 My Tutor Dashboard</a></li>
        <?php endif; ?>
    </ul>
    <div class="logout">
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" title="Toggle sidebar">☰</button>
            <h1>Find Your Tutor 📚</h1>
            <p>Book a personalised learning session with experienced tutors.</p>
        </div>
        <div style="display:flex; align-items:center;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role']==='tutor'): ?>
                <a href="tutor/tutor_dashboard.php" style="margin-right:10px;font-size:.85rem;background:#eaf6f8;color:#0b4e5a;padding:6px 12px;border-radius:8px;text-decoration:none;font-weight:600;">👨‍🏫 Tutor Dashboard</a>
            <?php endif; ?>
            <a href="profile.php" class="topbar-avatar" title="Customise your avatar" style="position:relative;">
                <?php echo $avatar_emoji; ?>
                <?php if ($avatar_outfit !== 'none' && $avatar_outfit_emoji): ?>
                    <span style="position:absolute;bottom:-6px;right:-6px;font-size:.9rem;background:var(--card);border-radius:8px;padding:1px 3px;border:1px solid var(--line);"><?php echo $avatar_outfit_emoji; ?></span>
                <?php endif; ?>
            </a>
            <?php 
            include_once("includes/theme.php"); 
            render_theme_toggle(); 
            ?>
        </div>
    </div>
    <?php inject_theme_styles_and_script(); ?>

    <!-- ALERTS -->
    <?php if ($success): ?>
        <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- SEARCH & FILTER -->
    <form method="GET" class="search-bar">
        <input
            type="text"
            name="search"
            placeholder="🔍 Search by name or subject…"
            value="<?php echo htmlspecialchars($search); ?>"
        >
        <select name="expertise">
            <option value="">All Subjects</option>
            <?php foreach ($expertise_list as $ex): ?>
                <option value="<?php echo htmlspecialchars($ex); ?>"
                    <?php echo ($filter_ex === $ex) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ex); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
        <?php if ($search !== '' || $filter_ex !== ''): ?>
            <a href="tutor.php" class="reset-btn">✕ Clear</a>
        <?php endif; ?>
    </form>

    <!-- RESULTS COUNT -->
    <?php
    $result_count = $tutors ? mysqli_num_rows($tutors) : 0;
    ?>
    <div class="results-count">
        Showing <strong><?php echo $result_count; ?></strong> tutor<?php echo $result_count != 1 ? 's' : ''; ?>
        <?php if ($search !== '' || $filter_ex !== ''): ?>
            matching your search
        <?php endif; ?>
    </div>

    <!-- TUTOR CARDS -->
    <div class="tutor-grid">

        <?php if ($result_count === 0): ?>
            <div class="no-results">
                <div class="no-results-icon">🦊</div>
                <p>No tutors found. <a href="tutor.php">Clear your search</a> to see all tutors.</p>
            </div>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($tutors)): ?>

                <div class="tutor-card">

                    <div class="tutor-header">
                        <div class="tutor-avatar">
                            <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="tutor-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <span class="tutor-expertise"><?php echo htmlspecialchars($row['expertise']); ?></span>
                        </div>
                    </div>

                    <div class="tutor-stats">
                        <div class="tutor-stat">⭐ <strong><?php echo htmlspecialchars($row['rating']); ?></strong> rating</div>
                        <div class="tutor-stat">💰 <strong>RM<?php echo htmlspecialchars($row['price_per_hour']); ?></strong>/hr</div>
                    </div>

                    <div class="tutor-bio">
                        <?php echo htmlspecialchars($row['bio'] ?: 'Experienced tutor ready to help.'); ?>
                    </div>

                    <?php 
                    $tid = (int)$row['id'];
                    $slots = $availability_by_tutor[$tid] ?? [];
                    if (!empty($slots)): ?>
                    <div style="margin:10px 0 12px;font-size:.82rem;">
                        <strong style="color:var(--teal);">🕒 Available:</strong><br>
                        <?php 
                        $shown = 0;
                        foreach ($slots as $s) {
                            if ($shown++ > 2) { echo " + more"; break; }
                            echo htmlspecialchars($s['day'] . ' ' . substr($s['start_time'],0,5) . '-' . substr($s['end_time'],0,5)) . '<br>';
                        }
                        ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.78rem; color:var(--muted); margin-bottom:8px;">No weekly slots listed yet — you can still book a date.</div>
                    <?php endif; ?>

                    <form method="POST" class="booking-form">
                        <input type="hidden" name="tutor_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="subject"  value="<?php echo htmlspecialchars($row['expertise']); ?>">

                        <input
                            type="date"
                            name="session_date"
                            min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                            required
                        >

                        <button type="submit" name="book_session">
                            📅 Book Session
                        </button>
                    </form>

                </div>

            <?php endwhile; ?>
        <?php endif; ?>

    </div>

</div><!-- /.main -->

</body>
</html>