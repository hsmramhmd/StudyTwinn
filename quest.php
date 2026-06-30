<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include("db_connection.php");

$user_id    = $_SESSION['user_id'];
$full_name  = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
$role       = $_SESSION['role'] ?? 'student';
$user_email = $_SESSION['email'] ?? '';

/* =========================
   AVATAR COLOR
   ========================= */
$avatar_res   = mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id='$user_id'");
$avatar_row   = mysqli_fetch_assoc($avatar_res);
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
   REAL PROGRESS FROM DB
   ========================= */

// Total completed sessions
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE student_id='$user_id' AND status='completed'");
$completed_sessions = $res ? (int)mysqli_fetch_assoc($res)['total'] : 0;

// Sessions completed this week
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE student_id='$user_id' AND status='completed' AND YEARWEEK(session_date, 1) = YEARWEEK(CURDATE(), 1)");
$sessions_this_week = $res ? (int)mysqli_fetch_assoc($res)['total'] : 0;

// Sessions completed this month
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE student_id='$user_id' AND status='completed' AND MONTH(session_date)=MONTH(CURDATE()) AND YEAR(session_date)=YEAR(CURDATE())");
$sessions_this_month = $res ? (int)mysqli_fetch_assoc($res)['total'] : 0;

// Distinct subjects studied
$res = mysqli_query($conn, "SELECT COUNT(DISTINCT subject) AS total FROM bookings WHERE student_id='$user_id' AND status='completed'");
$distinct_subjects = $res ? (int)mysqli_fetch_assoc($res)['total'] : 0;

// Subjects breakdown (for subject-based quests)
$subject_rows = [];
$res = mysqli_query($conn, "SELECT subject, COUNT(*) AS cnt FROM bookings WHERE student_id='$user_id' AND status='completed' GROUP BY subject");
if ($res) { while ($row = mysqli_fetch_assoc($res)) $subject_rows[$row['subject']] = (int)$row['cnt']; }

// Total messages sent
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE sender_id='$user_id'");
$messages_sent = $res ? (int)mysqli_fetch_assoc($res)['total'] : 0;

// Total reviews left
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reviews WHERE student_id='$user_id'");
$reviews_left = $res ? (int)mysqli_fetch_assoc($res)['total'] : 0;

// Study streak (weeks)
$streak_res = mysqli_query($conn, "SELECT DISTINCT session_date FROM bookings WHERE student_id='$user_id' AND status='completed' ORDER BY session_date DESC");
$current_streak = 0;
if ($streak_res && mysqli_num_rows($streak_res) > 0) {
    $week_buckets = [];
    while ($row = mysqli_fetch_assoc($streak_res)) {
        $week_buckets[date('o-W', strtotime($row['session_date']))] = true;
    }
    $week_keys = array_keys($week_buckets);
    rsort($week_keys);
    $checking_week = (int)date('W');
    $checking_year = (int)date('o');
    foreach ($week_keys as $wk) {
        $expected = sprintf('%04d-%02d', $checking_year, $checking_week);
        if ($wk === $expected) {
            $current_streak++;
            $checking_week--;
            if ($checking_week < 1) { $checking_week = 52; $checking_year--; }
        } else break;
    }
}

// XP from dashboard formula: 50 per session + 10 per review
$xp = ($completed_sessions * 50) + ($reviews_left * 10);

// Study points from rewards formula: 10 per session
$study_points = $completed_sessions * 10;

/* =========================
   QUEST DEFINITIONS
   (No DB table required — all derived from existing data)
   ========================= */

/*
   ----------------------------------------------------------
   OPTIONAL: To persist quest_completions in the DB, run:

   CREATE TABLE IF NOT EXISTS quest_completions (
       id          INT AUTO_INCREMENT PRIMARY KEY,
       user_id     INT NOT NULL,
       quest_id    VARCHAR(60) NOT NULL,
       claimed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
       xp_awarded  INT DEFAULT 0,
       pts_awarded INT DEFAULT 0,
       UNIQUE KEY unique_user_quest (user_id, quest_id),
       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   );

   Then use this page's "Claim" button logic to INSERT into quest_completions
   and UPDATE users SET xp = xp + ?, study_points = study_points + ?
   ----------------------------------------------------------
*/

// ── WEEKLY CHALLENGES ──
// Refresh every Monday. Progress resets automatically as it reads live DB data.
$weekly = [
    [
        'id'       => 'weekly_3_sessions',
        'icon'     => '📅',
        'title'    => 'Session Sprint',
        'desc'     => 'Complete 3 sessions this week',
        'progress' => min($sessions_this_week, 3),
        'goal'     => 3,
        'xp'       => 150,
        'pts'      => 30,
        'color'    => 'teal',
    ],
    [
        'id'       => 'weekly_2_subjects',
        'icon'     => '📚',
        'title'    => 'Subject Hopper',
        'desc'     => 'Study 2 different subjects this week',
        'progress' => min((function() use ($conn, $user_id) {
            $r = mysqli_query($conn, "SELECT COUNT(DISTINCT subject) AS c FROM bookings WHERE student_id='$user_id' AND status='completed' AND YEARWEEK(session_date,1)=YEARWEEK(CURDATE(),1)");
            return $r ? (int)mysqli_fetch_assoc($r)['c'] : 0;
        })(), 2),
        'goal'     => 2,
        'xp'       => 100,
        'pts'      => 20,
        'color'    => 'purple',
    ],
    [
        'id'       => 'weekly_send_message',
        'icon'     => '💬',
        'title'    => 'Stay Connected',
        'desc'     => 'Send 3 messages to tutors this week',
        'progress' => min((function() use ($conn, $user_id) {
            $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM messages WHERE sender_id='$user_id' AND YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)");
            return $r ? (int)mysqli_fetch_assoc($r)['c'] : 0;
        })(), 3),
        'goal'     => 3,
        'xp'       => 80,
        'pts'      => 15,
        'color'    => 'orange',
    ],
    [
        'id'       => 'weekly_leave_review',
        'icon'     => '⭐',
        'title'    => 'Share Your Voice',
        'desc'     => 'Leave a review for a tutor this week',
        'progress' => min((function() use ($conn, $user_id) {
            $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM reviews WHERE student_id='$user_id' AND YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)");
            return $r ? (int)mysqli_fetch_assoc($r)['c'] : 0;
        })(), 1),
        'goal'     => 1,
        'xp'       => 60,
        'pts'      => 10,
        'color'    => 'gold',
    ],
];

// ── ACHIEVEMENT QUESTS ──
$achievements = [
    [
        'id'       => 'ach_first_session',
        'icon'     => '🎯',
        'title'    => 'First Step',
        'desc'     => 'Complete your very first session',
        'progress' => min($completed_sessions, 1),
        'goal'     => 1,
        'xp'       => 50,
        'pts'      => 10,
        'tier'     => 'bronze',
    ],
    [
        'id'       => 'ach_5_sessions',
        'icon'     => '🏃',
        'title'    => 'Getting Momentum',
        'desc'     => 'Complete 5 sessions in total',
        'progress' => min($completed_sessions, 5),
        'goal'     => 5,
        'xp'       => 200,
        'pts'      => 40,
        'tier'     => 'silver',
    ],
    [
        'id'       => 'ach_10_sessions',
        'icon'     => '🎓',
        'title'    => 'Dedicated Scholar',
        'desc'     => 'Complete 10 sessions in total',
        'progress' => min($completed_sessions, 10),
        'goal'     => 10,
        'xp'       => 400,
        'pts'      => 80,
        'tier'     => 'gold',
    ],
    [
        'id'       => 'ach_streak_3',
        'icon'     => '🔥',
        'title'    => 'On Fire',
        'desc'     => 'Maintain a 3-week study streak',
        'progress' => min($current_streak, 3),
        'goal'     => 3,
        'xp'       => 250,
        'pts'      => 50,
        'tier'     => 'silver',
    ],
    [
        'id'       => 'ach_streak_5',
        'icon'     => '⚡',
        'title'    => 'Unstoppable',
        'desc'     => 'Maintain a 5-week study streak',
        'progress' => min($current_streak, 5),
        'goal'     => 5,
        'xp'       => 500,
        'pts'      => 100,
        'tier'     => 'gold',
    ],
    [
        'id'       => 'ach_3_subjects',
        'icon'     => '🌐',
        'title'    => 'Well-Rounded',
        'desc'     => 'Study 3 or more different subjects',
        'progress' => min($distinct_subjects, 3),
        'goal'     => 3,
        'xp'       => 180,
        'pts'      => 35,
        'tier'     => 'silver',
    ],
    [
        'id'       => 'ach_5_reviews',
        'icon'     => '✍️',
        'title'    => 'Feedback Champion',
        'desc'     => 'Leave 5 reviews for tutors',
        'progress' => min($reviews_left, 5),
        'goal'     => 5,
        'xp'       => 150,
        'pts'      => 25,
        'tier'     => 'bronze',
    ],
    [
        'id'       => 'ach_250xp',
        'icon'     => '🌟',
        'title'    => 'Scholar Star',
        'desc'     => 'Reach 250 total XP',
        'progress' => min($xp, 250),
        'goal'     => 250,
        'xp'       => 0,
        'pts'      => 50,
        'tier'     => 'gold',
    ],
];

// ── SUBJECT-BASED QUESTS ──
// Subjects match exactly what is stored in bookings.subject (from tutors.subject)
$subject_quests = [
    [
        'id'      => 'subj_webdev_3',
        'icon'    => '🌐',
        'subject' => 'Web Development',
        'title'   => 'Web Wizard',
        'desc'    => 'Complete 3 Web Development sessions',
        'progress'=> min($subject_rows['Web Development'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_db_3',
        'icon'    => '🗄️',
        'subject' => 'Database Management',
        'title'   => 'Data Architect',
        'desc'    => 'Complete 3 Database Management sessions',
        'progress'=> min(
            ($subject_rows['Database Management'] ?? 0) + ($subject_rows['Database Design'] ?? 0),
            3
        ),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_prog_3',
        'icon'    => '💻',
        'subject' => 'Programming Fundamentals',
        'title'   => 'Code Starter',
        'desc'    => 'Complete 3 Programming Fundamentals sessions',
        'progress'=> min($subject_rows['Programming Fundamentals'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_ai_3',
        'icon'    => '🤖',
        'subject' => 'Artificial Intelligence',
        'title'   => 'AI Pioneer',
        'desc'    => 'Complete 3 Artificial Intelligence sessions',
        'progress'=> min($subject_rows['Artificial Intelligence'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_cyber_3',
        'icon'    => '🔐',
        'subject' => 'Cybersecurity Basics',
        'title'   => 'Security Guard',
        'desc'    => 'Complete 3 Cybersecurity Basics sessions',
        'progress'=> min($subject_rows['Cybersecurity Basics'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_digi_3',
        'icon'    => '📣',
        'subject' => 'Digital Marketing',
        'title'   => 'Marketing Guru',
        'desc'    => 'Complete 3 Digital Marketing sessions',
        'progress'=> min($subject_rows['Digital Marketing'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_data_3',
        'icon'    => '📊',
        'subject' => 'Data Analytics',
        'title'   => 'Data Analyst',
        'desc'    => 'Complete 3 Data Analytics sessions',
        'progress'=> min($subject_rows['Data Analytics'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_mobile_3',
        'icon'    => '📱',
        'subject' => 'Mobile App Development',
        'title'   => 'App Builder',
        'desc'    => 'Complete 3 Mobile App Development sessions',
        'progress'=> min($subject_rows['Mobile App Development'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_net_3',
        'icon'    => '🔌',
        'subject' => 'Networking',
        'title'   => 'Network Hero',
        'desc'    => 'Complete 3 Networking sessions',
        'progress'=> min($subject_rows['Networking'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_sys_3',
        'icon'    => '🗂️',
        'subject' => 'System Analysis and Design',
        'title'   => 'Systems Thinker',
        'desc'    => 'Complete 3 System Analysis and Design sessions',
        'progress'=> min($subject_rows['System Analysis and Design'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_info_3',
        'icon'    => '📋',
        'subject' => 'Information Management',
        'title'   => 'Info Manager',
        'desc'    => 'Complete 3 Information Management sessions',
        'progress'=> min($subject_rows['Information Management'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_game_3',
        'icon'    => '🎮',
        'subject' => 'Gamification for Content Management',
        'title'   => 'Gamification Pro',
        'desc'    => 'Complete 3 Gamification for Content Management sessions',
        'progress'=> min($subject_rows['Gamification for Content Management'] ?? 0, 3),
        'goal'    => 3,
        'xp'      => 200,
        'pts'     => 40,
    ],
    [
        'id'      => 'subj_any_5',
        'icon'    => '🏆',
        'subject' => 'Any',
        'title'   => 'Subject Expert',
        'desc'    => 'Complete 5 sessions in any single subject',
        'progress'=> min(empty($subject_rows) ? 0 : max(array_values($subject_rows)), 5),
        'goal'    => 5,
        'xp'      => 300,
        'pts'     => 60,
    ],
];

/* =========================
   SUMMARY COUNTS
   ========================= */
$total_quests = count($weekly) + count($achievements) + count($subject_quests);
$completed_quests = 0;
foreach (array_merge($weekly, $achievements, $subject_quests) as $q) {
    if ($q['progress'] >= $q['goal']) $completed_quests++;
}
$total_quest_xp  = array_sum(array_column($weekly, 'xp')) + array_sum(array_column($achievements, 'xp')) + array_sum(array_column($subject_quests, 'xp'));
$total_quest_pts = array_sum(array_column($weekly, 'pts')) + array_sum(array_column($achievements, 'pts')) + array_sum(array_column($subject_quests, 'pts'));

// Days until Monday (week reset)
$days_until_monday = (8 - (int)date('N')) % 7;
if ($days_until_monday === 0) $days_until_monday = 7;

/* =========================
   UNREAD MESSAGES (topbar badge)
   ========================= */
$unread_res   = mysqli_query($conn, "SELECT COUNT(*) AS c FROM messages WHERE receiver_id='$user_id' AND is_read=0");
$total_unread = $unread_res ? (int)mysqli_fetch_assoc($unread_res)['c'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Quests — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5; --teal-pale:#eaf6f8;
    --orange:#f0672b; --orange-light:#ffb26b; --orange-pale:#fff3ee;
    --purple:#7c3aed; --purple-pale:#f5f3ff;
    --gold:#b45309; --gold-pale:#fffbeb;
    --bg:#f4f8fb; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6;
    --shadow-sm:0 4px 14px rgba(17,105,121,0.06);
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --shadow-lg:0 18px 44px rgba(17,105,121,0.14);
    --radius:18px; --radius-sm:12px;
    --ease:cubic-bezier(.4,0,.2,1);
    --avatar-grad: <?php echo $active_grad; ?>;
}

*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
html{ scroll-behavior:smooth; }
body{ background:var(--bg); display:flex; color:var(--text); }

@keyframes fadeSlideIn{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:translateY(0); } }
@keyframes growBar { from{ width:0%; } }
@keyframes pulse   { 0%,100%{ transform:scale(1); } 50%{ transform:scale(1.06); } }
@keyframes shimmer { 0%{ background-position:-200% 0; } 100%{ background-position:200% 0; } }
@keyframes popIn   { 0%{ transform:scale(.85); opacity:0; } 60%{ transform:scale(1.05); } 100%{ transform:scale(1); opacity:1; } }

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
    font-size:.9rem; text-decoration:none; color:var(--text); display:block; padding:9px 13px; border-radius:12px; transition:.3s; }
:root[data-theme="dark"] .menu a {
    color: #cbd5e1 !important;
}
.menu a:hover,.menu a.active{ background:rgba(17,105,121,0.1); color:var(--teal); font-weight:600; }
.logout{
    flex-shrink:0;
    margin-top:auto;
    padding-top:16px;
    border-top:1px solid var(--line);
    background:var(--sidebar-bg,#fff);
}
.logout a{ display:block; padding:12px 15px; border-radius:12px; text-decoration:none; background:var(--orange-pale); color:var(--orange); font-weight:600; }
:root[data-theme="dark"] .logout a {
    background: #3f2a1f !important;
    color: #fb923c !important;
}
:root[data-theme="dark"] .logout { background: var(--sidebar-bg, #0f172a) !important; }

/* ── MAIN ── */
.main{ margin-left:260px; width:calc(100% - 260px); padding:32px 36px 60px; }

/* ── TOPBAR ── */
.topbar{
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:28px; animation:fadeSlideIn .5s var(--ease) both;
}
.topbar-left h1{ font-family:'Lexend',sans-serif; font-size:1.7rem; font-weight:700; }
.topbar-left p{ color:var(--muted); font-size:.92rem; margin-top:4px; }
.topbar-avatar{
    width:52px; height:52px; background:var(--avatar-grad);
    border-radius:14px; display:flex; align-items:center; justify-content:center;
    font-size:1.7rem; box-shadow:0 6px 18px rgba(0,0,0,0.14);
    text-decoration:none; flex-shrink:0; position:relative;
    transition:transform .25s var(--ease), box-shadow .25s var(--ease);
}
.topbar-avatar:hover{ transform:scale(1.07); box-shadow:0 10px 24px rgba(0,0,0,0.2); }
.topbar-avatar .avatar-tooltip{
    display:none; position:absolute; top:calc(100% + 8px); right:0;
    background:#1e2a35; color:white; font-size:.7rem; font-weight:600;
    padding:5px 10px; border-radius:8px; white-space:nowrap; pointer-events:none;
}
.topbar-avatar:hover .avatar-tooltip{ display:block; }
.unread-badge-top{
    position:absolute; top:-5px; right:-5px;
    background:var(--orange); color:white; font-size:.6rem; font-weight:700;
    width:18px; height:18px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; border:2px solid white;
}

/* ── HERO ── */
.hero{
    background:linear-gradient(135deg,#0b4e5a,var(--teal-light));
    border-radius:var(--radius); padding:34px 40px; color:white;
    display:grid; grid-template-columns:1fr auto; gap:30px; align-items:center;
    margin-bottom:24px; position:relative; overflow:hidden;
    animation:fadeSlideIn .45s var(--ease) .05s both;
}
.hero::before{ content:''; position:absolute; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,0.05); top:-110px; right:-60px; }
.hero::after { content:''; position:absolute; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,0.04); bottom:-60px; left:140px; }
.hero-label{ font-size:.76rem; opacity:.75; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
.hero-title{ font-family:'Lexend',sans-serif; font-size:2rem; font-weight:800; line-height:1.15; }
.hero-sub{ font-size:.86rem; opacity:.8; margin-top:6px; }
.hero-stats{ display:flex; gap:24px; margin-top:18px; position:relative; z-index:1; }
.hero-stat{ text-align:center; }
.hero-stat-val{ font-family:'Lexend',sans-serif; font-size:1.5rem; font-weight:800; line-height:1; }
.hero-stat-label{ font-size:.68rem; opacity:.75; margin-top:3px; text-transform:uppercase; letter-spacing:.04em; }
.hero-stat-divider{ width:1px; background:rgba(255,255,255,0.2); align-self:stretch; }
.hero-right{ position:relative; z-index:1; text-align:center; }
.hero-icon{ font-size:72px; animation:pulse 3s ease-in-out infinite; filter:drop-shadow(0 8px 20px rgba(0,0,0,0.25)); }
.hero-reset-pill{
    display:inline-flex; align-items:center; gap:6px; margin-top:14px;
    background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25);
    border-radius:20px; padding:6px 14px; font-size:.74rem; font-weight:600;
    position:relative; z-index:1;
}

/* ── STAT MINI ROW ── */
.stats-row{
    display:grid; grid-template-columns:repeat(4,1fr); gap:16px;
    margin-bottom:26px; animation:fadeSlideIn .45s var(--ease) .1s both;
}
.stat-mini{ background:var(--card); border-radius:var(--radius-sm); padding:20px; box-shadow:var(--shadow); border:1px solid var(--line); }
.sm-icon{ font-size:1.4rem; margin-bottom:8px; }
.sm-val{ font-family:'Lexend',sans-serif; font-size:1.55rem; font-weight:800; color:var(--text); }
.sm-label{ font-size:.74rem; color:var(--muted); margin-top:2px; }

/* ── LAYOUT ── */
.quest-layout{ display:grid; grid-template-columns:1fr 340px; gap:22px; }

/* ── CARD ── */
.card{
    background:var(--card); border-radius:var(--radius); padding:26px;
    box-shadow:var(--shadow); border:1px solid var(--line); margin-bottom:22px;
    animation:fadeSlideIn .45s var(--ease) both;
}
.card:last-child{ margin-bottom:0; }
.card-header{
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:18px;
}
.card-title{ font-family:'Lexend',sans-serif; font-size:1rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.card-badge{ font-size:.7rem; font-weight:700; padding:4px 10px; border-radius:20px; background:var(--teal-pale); color:var(--teal); }

/* ── SECTION TABS ── */
.tab-pills{
    display:flex; gap:8px; margin-bottom:22px;
    animation:fadeSlideIn .45s var(--ease) .15s both;
}
.tab-pill{
    padding:9px 18px; border-radius:20px; font-size:.82rem; font-weight:700;
    border:1.5px solid var(--line); background:var(--input-bg); color:var(--muted);
    cursor:pointer; transition:all .2s var(--ease); text-decoration:none;
}
.tab-pill.active, .tab-pill:hover{ background:var(--teal); color:white; border-color:var(--teal); }

/* ── QUEST CARD ── */
.quest-item{
    display:flex; align-items:flex-start; gap:16px;
    padding:18px 20px; border-radius:var(--radius-sm);
    border:1.5px solid var(--line); background:#fafcfd;
    margin-bottom:12px; transition:transform .2s var(--ease), border-color .2s var(--ease), box-shadow .2s var(--ease);
    position:relative; overflow:hidden;
}
.quest-item:last-child{ margin-bottom:0; }
.quest-item:hover{ transform:translateY(-2px); box-shadow:var(--shadow-sm); border-color:#d5e8ec; }
.quest-item.completed{ background:linear-gradient(135deg,#f0fdf4,#dcfce7); border-color:rgba(34,197,94,0.3); }
.quest-item.completed:hover{ border-color:rgba(34,197,94,0.5); }

/* shimmer on completed */
.quest-item.completed::before{
    content:''; position:absolute; inset:0;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);
    background-size:200% 100%;
    animation:shimmer 2.5s linear infinite;
}

.quest-icon-wrap{
    width:50px; height:50px; border-radius:14px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:1.5rem; background:var(--teal-pale);
    border:1.5px solid rgba(17,105,121,0.12);
    position:relative; z-index:1;
}
.quest-item.completed .quest-icon-wrap{ background:#dcfce7; border-color:rgba(34,197,94,0.3); }

/* Icon background colour variants */
.quest-icon-wrap.orange{ background:var(--orange-pale); border-color:rgba(240,103,43,0.12); }
.quest-icon-wrap.purple{ background:var(--purple-pale); border-color:rgba(124,58,237,0.12); }
.quest-icon-wrap.gold  { background:var(--gold-pale);   border-color:rgba(180,83,9,0.12); }

.quest-body{ flex:1; min-width:0; position:relative; z-index:1; }
.quest-title{ font-size:.9rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.quest-desc{ font-size:.78rem; color:var(--muted); margin-top:3px; line-height:1.5; }
.quest-rewards{ display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
.reward-pill{
    font-size:.68rem; font-weight:700; padding:3px 10px; border-radius:20px;
    display:flex; align-items:center; gap:4px;
}
.reward-pill.xp { background:#eef0fd; color:#4338ca; }
.reward-pill.pts{ background:var(--gold-pale); color:var(--gold); }
.reward-pill.xp0{ display:none; }

.quest-progress-wrap{ margin-top:10px; }
.quest-progress-label{ display:flex; justify-content:space-between; font-size:.72rem; color:var(--muted); margin-bottom:5px; }
.quest-progress-label .pct{ font-weight:700; color:var(--teal); }
.quest-bar-track{ width:100%; height:7px; border-radius:6px; background:var(--line); overflow:hidden; }
.quest-bar-fill{
    height:100%; border-radius:6px;
    background:linear-gradient(90deg,var(--teal),var(--teal-light));
    animation:growBar 1s var(--ease) .2s both;
    transition:width .6s var(--ease);
}
.quest-item.completed .quest-bar-fill{ background:linear-gradient(90deg,#22c55e,#4ade80); }

.quest-status{
    flex-shrink:0; position:relative; z-index:1;
    display:flex; flex-direction:column; align-items:flex-end; gap:8px;
}
.done-badge{
    width:32px; height:32px; border-radius:50%; background:#22c55e;
    color:white; font-size:1rem; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 4px 12px rgba(34,197,94,0.3);
    animation:popIn .4s var(--ease) both;
}
.in-progress-badge{
    font-size:.68rem; font-weight:700; padding:4px 10px; border-radius:20px;
    background:var(--teal-pale); color:var(--teal); white-space:nowrap;
}
.locked-badge{
    font-size:.68rem; font-weight:700; padding:4px 10px; border-radius:20px;
    background:#f1f5f9; color:#94a3b8; white-space:nowrap;
}

/* Tier badges */
.tier-pill{
    font-size:.62rem; font-weight:700; padding:2px 8px; border-radius:10px;
    text-transform:uppercase; letter-spacing:.04em;
}
.tier-bronze{ background:#fef3c7; color:#92400e; }
.tier-silver{ background:#f1f5f9; color:#475569; }
.tier-gold  { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#78350f; }

/* ── SUBJECT QUEST ── */
.subject-tag{
    font-size:.65rem; font-weight:700; padding:2px 9px; border-radius:10px;
    background:var(--teal-pale); color:var(--teal);
}

/* ── RIGHT SIDEBAR ── */
.progress-overview{ background:var(--orange); color:white; border:none; }
:root[data-theme="dark"] .progress-overview {
    background: #9a3412;
}
.po-title{ font-family:'Lexend',sans-serif; font-size:1rem; font-weight:700; color:rgba(255,255,255,0.9); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.po-stat{ display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:rgba(255,255,255,0.15); border-radius:10px; margin-bottom:8px; font-size:.82rem; font-weight:600; }
.po-stat:last-child{ margin-bottom:0; }
.po-stat-val{ font-family:'Lexend',sans-serif; font-size:1rem; font-weight:800; }

.week-reset-card{ border:1.5px dashed rgba(240,103,43,0.35); background:var(--orange-pale); }
.wr-row{ display:flex; align-items:center; gap:10px; font-size:.83rem; color:var(--text); font-weight:600; }
.wr-icon{ font-size:1.2rem; flex-shrink:0; }
.wr-sub{ font-size:.72rem; color:var(--muted); margin-top:3px; }

.tip-card{ background:linear-gradient(135deg,var(--purple-pale),#ede9fe); border:1.5px solid rgba(124,58,237,0.15); }
.tip-header{ font-family:'Lexend',sans-serif; font-size:.88rem; font-weight:700; color:var(--purple); margin-bottom:12px; display:flex; align-items:center; gap:7px; }
.tip-item{ display:flex; align-items:flex-start; gap:10px; padding:9px 0; border-bottom:1px solid rgba(124,58,237,0.1); }
.tip-item:last-child{ border-bottom:none; padding-bottom:0; }
.tip-item-icon{ font-size:1rem; flex-shrink:0; margin-top:1px; }
.tip-item-text{ font-size:.76rem; color:#4c1d95; line-height:1.5; }

.leaderboard-teaser,
:root[data-theme="dark"] .leaderboard-teaser{
    background:linear-gradient(135deg,#0b4e5a,var(--teal)) !important;
    background-color:#0b4e5a !important;
    color:#ffffff !important; border:none !important; text-align:center; padding:22px 20px;
    display:block !important;
}
.lt-icon,:root[data-theme="dark"] .lt-icon{ font-size:2rem; margin-bottom:8px; line-height:1; display:block !important; color:#ffffff !important; }
.lt-title,:root[data-theme="dark"] .lt-title{ font-family:'Lexend',sans-serif; font-size:.95rem; font-weight:700; margin-bottom:4px; color:#ffffff !important; display:block !important; }
.lt-sub,:root[data-theme="dark"] .lt-sub{ font-size:.75rem; opacity:.85; margin-bottom:14px; color:#ffffff !important; display:block !important; }
.lt-btn,:root[data-theme="dark"] .lt-btn{
    display:inline-block !important; padding:10px 22px; border-radius:12px;
    background:rgba(255,255,255,0.2) !important; border:1.5px solid rgba(255,255,255,0.3) !important;
    color:#ffffff !important; font-size:.8rem; font-weight:700; text-decoration:none;
    transition:background .2s var(--ease);
}
.lt-btn:hover{ background:rgba(255,255,255,0.3) !important; }

/* ── EMPTY STATE ── */
.empty-quests{ text-align:center; padding:40px 20px; color:var(--muted); }
.empty-quests .eq-icon{ font-size:2.5rem; margin-bottom:10px; }
.empty-quests h4{ font-family:'Lexend',sans-serif; font-size:.95rem; color:var(--text); margin-bottom:6px; }
.empty-quests p{ font-size:.82rem; }
.empty-quests a{ color:var(--teal); font-weight:700; text-decoration:none; }

/* ── RESPONSIVE ── */
@media(max-width:1100px){
    .stats-row{ grid-template-columns:repeat(2,1fr); }
    .quest-layout{ grid-template-columns:1fr; }
}
@media(max-width:900px){
    .sidebar{ width:80px;  overflow-y:hidden; }
    .logo h2{ display:none; }
    .main{ margin-left:80px; width:calc(100% - 80px); padding:20px 16px 40px; }
    .hero{ grid-template-columns:1fr; }
    .hero-right{ display:none; }
}
@media(max-width:600px){
    .stats-row{ grid-template-columns:1fr 1fr; }
    .hero-stats{ flex-direction:column; gap:12px; }
    .hero-stat-divider{ display:none; }
}
</style>
<?php include_once("includes/theme.php"); inject_theme_styles_and_script(); ?>
</head>
<body>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
    <div class="logo">
        <h2>StudyTwin</h2>
    </div>
    <ul class="menu">
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="tutor.php">📚 Find Tutor</a></li>
        <li><a href="rooms.php">📅 Rooms / Sessions</a></li>
        <li><a href="tutortoprank.php">🏆 Tutor Top Rank</a></li>
        <li><a href="bookings.php">📅 My Bookings</a></li>
        <li><a href="leaderboard.php">🥇 Leaderboard</a></li>
        <li><a href="messages.php">💬 Messages</a></li>
        <li><a href="profile.php">👤 Profile</a></li>
        <li><a class="active" href="quest.php">🎮 My Quests</a></li>
        <li><a href="rewards.php">🎁 My Rewards</a></li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'tutor'): ?>
        <li style="margin-top:8px;"><a href="tutor/tutor_dashboard.php">👨‍🏫 Tutor Dashboard</a></li>
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
            <h1>🎮 My Quests</h1>
            <p>Complete quests to earn XP, Study Points, and unlock rewards.</p>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'tutor'): ?>
                <a href="tutor/tutor_dashboard.php" class="switch-to-tutor" style="font-size:0.82rem; padding:5px 11px; border-radius:8px; text-decoration:none; font-weight:600;">Switch to Tutor</a>
            <?php endif; ?>
            <a href="profile.php" class="topbar-avatar" title="Customise your avatar" style="position:relative;">
                <?php echo $avatar_emoji; ?>
                <?php if ($avatar_outfit !== 'none' && $avatar_outfit_emoji): ?>
                    <span style="position:absolute;bottom:-6px;right:-6px;font-size:.9rem;background:var(--card);border-radius:8px;padding:1px 3px;border:1px solid var(--line);"><?php echo $avatar_outfit_emoji; ?></span>
                <?php endif; ?>
                <?php if ($total_unread > 0): ?>
                    <span class="unread-badge-top"><?php echo $total_unread > 9 ? '9+' : $total_unread; ?></span>
                <?php endif; ?>
                <span class="avatar-tooltip">🎨 Customise avatar</span>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <!-- HERO -->
    <div class="hero">
        <div>
            <div class="hero-label">Quest Progress</div>
            <div class="hero-title">
                <?php
                $pct_done = $total_quests > 0 ? round(($completed_quests / $total_quests) * 100) : 0;
                if ($pct_done === 100) echo "All Quests Complete! 🏆";
                elseif ($pct_done >= 50) echo "Great progress, keep going! 🔥";
                elseif ($pct_done > 0) echo "You're on your way! ⚡";
                else echo "Start your first quest! 🚀";
                ?>
            </div>
            <div class="hero-sub">Every completed quest earns you XP and Study Points redeemable in My Rewards.</div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-val"><?php echo $completed_quests; ?>/<?php echo $total_quests; ?></div>
                    <div class="hero-stat-label">Quests Done</div>
                </div>
                <div class="hero-stat-divider"></div>
                <div class="hero-stat">
                    <div class="hero-stat-val"><?php echo number_format($xp); ?></div>
                    <div class="hero-stat-label">Total XP</div>
                </div>
                <div class="hero-stat-divider"></div>
                <div class="hero-stat">
                    <div class="hero-stat-val"><?php echo number_format($study_points); ?></div>
                    <div class="hero-stat-label">Study Points</div>
                </div>
                <div class="hero-stat-divider"></div>
                <div class="hero-stat">
                    <div class="hero-stat-val"><?php echo $current_streak; ?>🔥</div>
                    <div class="hero-stat-label">Week Streak</div>
                </div>
            </div>
            <div class="hero-reset-pill">
                🔄 Weekly quests reset in <?php echo $days_until_monday; ?> day<?php echo $days_until_monday != 1 ? 's' : ''; ?>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-icon">🎮</div>
        </div>
    </div>

    <!-- STAT MINI ROW -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="sm-icon">✅</div>
            <div class="sm-val"><?php echo $completed_sessions; ?></div>
            <div class="sm-label">Sessions Completed</div>
        </div>
        <div class="stat-mini">
            <div class="sm-icon">📅</div>
            <div class="sm-val"><?php echo $sessions_this_week; ?></div>
            <div class="sm-label">Sessions This Week</div>
        </div>
        <div class="stat-mini">
            <div class="sm-icon">🌐</div>
            <div class="sm-val"><?php echo $distinct_subjects; ?></div>
            <div class="sm-label">Subjects Studied</div>
        </div>
        <div class="stat-mini">
            <div class="sm-icon">🎮</div>
            <div class="sm-val"><?php echo $pct_done; ?>%</div>
            <div class="sm-label">Quests Complete</div>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="quest-layout">

        <!-- ═══ LEFT: QUEST SECTIONS ═══ -->
        <div>

            <!-- ── WEEKLY CHALLENGES ── -->
            <div class="card" id="weekly">
                <div class="card-header">
                    <div class="card-title">📅 Weekly Challenges
                        <span class="card-badge">Resets <?php echo $days_until_monday; ?>d</span>
                    </div>
                </div>
                <?php
                $wc_done = 0;
                foreach ($weekly as $q) { if ($q['progress'] >= $q['goal']) $wc_done++; }
                ?>
                <p style="font-size:.78rem;color:var(--muted);margin-top:-10px;margin-bottom:16px;">
                    <?php echo $wc_done; ?>/<?php echo count($weekly); ?> completed this week — progress resets every Monday.
                </p>

                <?php foreach ($weekly as $q):
                    $done    = $q['progress'] >= $q['goal'];
                    $pct     = $q['goal'] > 0 ? min(100, round(($q['progress'] / $q['goal']) * 100)) : 0;
                    $col_map = ['teal'=>'', 'orange'=>'orange', 'purple'=>'purple', 'gold'=>'gold'];
                    $col_cls = $col_map[$q['color']] ?? '';
                ?>
                <div class="quest-item <?php echo $done ? 'completed' : ''; ?>">
                    <div class="quest-icon-wrap <?php echo $col_cls; ?>"><?php echo $q['icon']; ?></div>
                    <div class="quest-body">
                        <div class="quest-title">
                            <?php echo htmlspecialchars($q['title']); ?>
                        </div>
                        <div class="quest-desc"><?php echo htmlspecialchars($q['desc']); ?></div>
                        <div class="quest-rewards">
                            <?php if ($q['xp'] > 0): ?>
                                <span class="reward-pill xp">⚡ <?php echo $q['xp']; ?> XP</span>
                            <?php endif; ?>
                            <span class="reward-pill pts">🪙 <?php echo $q['pts']; ?> pts</span>
                        </div>
                        <div class="quest-progress-wrap">
                            <div class="quest-progress-label">
                                <span><?php echo $q['progress']; ?>/<?php echo $q['goal']; ?></span>
                                <span class="pct"><?php echo $pct; ?>%</span>
                            </div>
                            <div class="quest-bar-track">
                                <div class="quest-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="quest-status">
                        <?php if ($done): ?>
                            <div class="done-badge">✓</div>
                        <?php elseif ($q['progress'] > 0): ?>
                            <div class="in-progress-badge">In Progress</div>
                        <?php else: ?>
                            <div class="locked-badge">Not Started</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── ACHIEVEMENT QUESTS ── -->
            <div class="card" id="achievements">
                <div class="card-header">
                    <div class="card-title">🏆 Achievement Quests
                        <?php
                        $ach_done = 0;
                        foreach ($achievements as $q) { if ($q['progress'] >= $q['goal']) $ach_done++; }
                        ?>
                        <span class="card-badge"><?php echo $ach_done; ?>/<?php echo count($achievements); ?></span>
                    </div>
                </div>
                <p style="font-size:.78rem;color:var(--muted);margin-top:-10px;margin-bottom:16px;">
                    Permanent milestones — progress never resets.
                </p>

                <?php foreach ($achievements as $q):
                    $done = $q['progress'] >= $q['goal'];
                    $pct  = $q['goal'] > 0 ? min(100, round(($q['progress'] / $q['goal']) * 100)) : 0;
                ?>
                <div class="quest-item <?php echo $done ? 'completed' : ''; ?>">
                    <div class="quest-icon-wrap"><?php echo $q['icon']; ?></div>
                    <div class="quest-body">
                        <div class="quest-title">
                            <?php echo htmlspecialchars($q['title']); ?>
                            <span class="tier-pill tier-<?php echo $q['tier']; ?>"><?php echo ucfirst($q['tier']); ?></span>
                        </div>
                        <div class="quest-desc"><?php echo htmlspecialchars($q['desc']); ?></div>
                        <div class="quest-rewards">
                            <?php if ($q['xp'] > 0): ?>
                                <span class="reward-pill xp">⚡ <?php echo $q['xp']; ?> XP</span>
                            <?php endif; ?>
                            <span class="reward-pill pts">🪙 <?php echo $q['pts']; ?> pts</span>
                        </div>
                        <div class="quest-progress-wrap">
                            <div class="quest-progress-label">
                                <span><?php echo number_format($q['progress']); ?>/<?php echo number_format($q['goal']); ?></span>
                                <span class="pct"><?php echo $pct; ?>%</span>
                            </div>
                            <div class="quest-bar-track">
                                <div class="quest-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="quest-status">
                        <?php if ($done): ?>
                            <div class="done-badge">✓</div>
                        <?php elseif ($q['progress'] > 0): ?>
                            <div class="in-progress-badge">In Progress</div>
                        <?php else: ?>
                            <div class="locked-badge">Not Started</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── SUBJECT-BASED QUESTS ── -->
            <div class="card" id="subjects">
                <div class="card-header">
                    <div class="card-title">📚 Subject Quests
                        <?php
                        $sq_done = 0;
                        foreach ($subject_quests as $q) { if ($q['progress'] >= $q['goal']) $sq_done++; }
                        ?>
                        <span class="card-badge"><?php echo $sq_done; ?>/<?php echo count($subject_quests); ?></span>
                    </div>
                </div>
                <p style="font-size:.78rem;color:var(--muted);margin-top:-10px;margin-bottom:16px;">
                    Build expertise by completing sessions in each of StudyTwin's subjects.
                </p>

                <?php foreach ($subject_quests as $q):
                    $done = $q['progress'] >= $q['goal'];
                    $pct  = $q['goal'] > 0 ? min(100, round(($q['progress'] / $q['goal']) * 100)) : 0;
                ?>
                <div class="quest-item <?php echo $done ? 'completed' : ''; ?>">
                    <div class="quest-icon-wrap orange"><?php echo $q['icon']; ?></div>
                    <div class="quest-body">
                        <div class="quest-title">
                            <?php echo htmlspecialchars($q['title']); ?>
                            <?php if ($q['subject'] !== 'Any'): ?>
                                <span class="subject-tag"><?php echo htmlspecialchars($q['subject']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="quest-desc"><?php echo htmlspecialchars($q['desc']); ?></div>
                        <div class="quest-rewards">
                            <?php if ($q['xp'] > 0): ?>
                                <span class="reward-pill xp">⚡ <?php echo $q['xp']; ?> XP</span>
                            <?php endif; ?>
                            <span class="reward-pill pts">🪙 <?php echo $q['pts']; ?> pts</span>
                        </div>
                        <div class="quest-progress-wrap">
                            <div class="quest-progress-label">
                                <span><?php echo $q['progress']; ?>/<?php echo $q['goal']; ?> sessions</span>
                                <span class="pct"><?php echo $pct; ?>%</span>
                            </div>
                            <div class="quest-bar-track">
                                <div class="quest-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="quest-status">
                        <?php if ($done): ?>
                            <div class="done-badge">✓</div>
                        <?php elseif ($q['progress'] > 0): ?>
                            <div class="in-progress-badge">In Progress</div>
                        <?php else: ?>
                            <div class="locked-badge">Not Started</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div><!-- /left column -->

        <!-- ═══ RIGHT SIDEBAR ═══ -->
        <div>

            <!-- PROGRESS OVERVIEW -->
            <div class="card progress-overview">
                <div class="po-title">📊 Your Summary</div>
                <?php
                $total_earnable_xp  = array_sum(array_column($weekly,'xp'))  + array_sum(array_column($achievements,'xp'))  + array_sum(array_column($subject_quests,'xp'));
                $total_earnable_pts = array_sum(array_column($weekly,'pts')) + array_sum(array_column($achievements,'pts')) + array_sum(array_column($subject_quests,'pts'));
                $earned_xp  = 0; $earned_pts = 0;
                foreach (array_merge($weekly, $achievements, $subject_quests) as $q) {
                    if ($q['progress'] >= $q['goal']) { $earned_xp += $q['xp']; $earned_pts += $q['pts']; }
                }
                ?>
                <div class="po-stat">
                    <span>🎮 Quests Completed</span>
                    <span class="po-stat-val"><?php echo $completed_quests; ?>/<?php echo $total_quests; ?></span>
                </div>
                <div class="po-stat">
                    <span>⚡ XP from Quests</span>
                    <span class="po-stat-val"><?php echo number_format($earned_xp); ?></span>
                </div>
                <div class="po-stat">
                    <span>🪙 Points from Quests</span>
                    <span class="po-stat-val"><?php echo number_format($earned_pts); ?></span>
                </div>
                <div class="po-stat">
                    <span>🎯 Potential Remaining</span>
                    <span class="po-stat-val"><?php echo number_format($total_earnable_pts - $earned_pts); ?> pts</span>
                </div>
            </div>

            <!-- WEEKLY RESET COUNTDOWN -->
            <div class="card week-reset-card">
                <div class="card-title" style="margin-bottom:12px;">🔄 Weekly Reset</div>
                <div class="wr-row">
                    <div class="wr-icon">⏰</div>
                    <div>
                        <div>Resets in <strong><?php echo $days_until_monday; ?> day<?php echo $days_until_monday != 1 ? 's' : ''; ?></strong> (Monday)</div>
                        <div class="wr-sub">Weekly challenges refresh every week. Make the most of them!</div>
                    </div>
                </div>
                <div style="margin-top:14px;">
                    <?php foreach ($weekly as $q):
                        $done = $q['progress'] >= $q['goal'];
                        $pct  = min(100, round(($q['progress'] / $q['goal']) * 100));
                    ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:.74rem;margin-bottom:4px;">
                            <span style="font-weight:600;color:var(--text);"><?php echo $q['icon'].' '.htmlspecialchars($q['title']); ?></span>
                            <span style="color:var(--muted);"><?php echo $q['progress']; ?>/<?php echo $q['goal']; ?></span>
                        </div>
                        <div style="height:5px;border-radius:4px;background:rgba(240,103,43,0.15);overflow:hidden;">
                            <div style="height:100%;width:<?php echo $pct; ?>%;border-radius:4px;background:<?php echo $done ? '#22c55e' : 'var(--orange)'; ?>;animation:growBar 1s var(--ease) both;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TIPS -->
            <div class="card tip-card">
                <div class="tip-header">💡 Quest Tips</div>
                <div class="tip-item">
                    <div class="tip-item-icon">📅</div>
                    <div class="tip-item-text">Book <strong>3 sessions a week</strong> to complete the Session Sprint challenge and earn maximum weekly XP.</div>
                </div>
                <div class="tip-item">
                    <div class="tip-item-icon">🔥</div>
                    <div class="tip-item-text">Study every week to grow your <strong>streak</strong> — longer streaks unlock harder achievement quests.</div>
                </div>
                <div class="tip-item">
                    <div class="tip-item-icon">📚</div>
                    <div class="tip-item-text">Try different subjects to unlock <strong>Subject Quests</strong> and the "Well-Rounded" achievement.</div>
                </div>
                <div class="tip-item">
                    <div class="tip-item-icon">⭐</div>
                    <div class="tip-item-text">Always <strong>leave a review</strong> after a session — it earns you XP <em>and</em> completes the Share Your Voice quest.</div>
                </div>
            </div>

            <!-- LEADERBOARD TEASER -->
            <div class="card leaderboard-teaser">
                <div class="lt-icon">🥇</div>
                <div class="lt-title">See How You Rank</div>
                <div class="lt-sub">Your quest XP and Study Points count towards the leaderboard. Climb higher every week!</div>
                <a href="leaderboard.php" class="lt-btn">View Leaderboard →</a>
            </div>

        </div><!-- /right sidebar -->
    </div><!-- /quest-layout -->

</div><!-- /.main -->

</body>
</html>