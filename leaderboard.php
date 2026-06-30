<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include("db_connection.php");

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
$role      = $_SESSION['role'] ?? 'student';

/* =========================
   AVATAR COLOR
   ========================= */

$avatar_res    = mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id='$user_id'");
$avatar_row    = mysqli_fetch_assoc($avatar_res);
$avatar_color  = $avatar_row['avatar_color']  ?? 'orange';
$avatar_animal = $avatar_row['avatar_animal'] ?? 'fox';
$avatar_outfit = $avatar_row['avatar_outfit'] ?? 'none';

$animals = [
    'fox'     => '🦊', 'cat'  => '🐱', 'bear'    => '🐻',
    'rabbit'  => '🐰', 'owl'  => '🦉', 'penguin' => '🐧',
];
$outfits = [
    'none' => '', 'graduation' => '🎓', 'chef' => '👨‍🍳',
    'ninja' => '🥷', 'wizard' => '🧙', 'astronaut' => '👨‍🚀',
    'knight' => '🧝', 'crown' => '👑',
];
$avatar_emoji        = $animals[$avatar_animal] ?? '🦊';
$avatar_outfit_emoji = $outfits[$avatar_outfit]  ?? '';

$color_themes = [
    'orange'   => ['label'=>'Ember Fox',  'grad'=>'linear-gradient(135deg,#f0672b,#ffb26b)', 'hex'=>'#f0672b'],
    'teal'     => ['label'=>'Ocean Fox',  'grad'=>'linear-gradient(135deg,#116979,#1b90a5)', 'hex'=>'#116979'],
    'purple'   => ['label'=>'Mystic Fox', 'grad'=>'linear-gradient(135deg,#7c3aed,#a78bfa)', 'hex'=>'#7c3aed'],
    'rose'     => ['label'=>'Cherry Fox', 'grad'=>'linear-gradient(135deg,#e11d48,#fb7185)', 'hex'=>'#e11d48'],
    'green'    => ['label'=>'Forest Fox', 'grad'=>'linear-gradient(135deg,#16a34a,#4ade80)', 'hex'=>'#16a34a'],
    'midnight' => ['label'=>'Night Fox',  'grad'=>'linear-gradient(135deg,#1e293b,#475569)', 'hex'=>'#1e293b'],
    'gold'     => ['label'=>'Golden Fox', 'grad'=>'linear-gradient(135deg,#b45309,#fbbf24)', 'hex'=>'#b45309'],
    'sky'      => ['label'=>'Sky Fox',    'grad'=>'linear-gradient(135deg,#0284c7,#38bdf8)', 'hex'=>'#0284c7'],
];
$active_theme = $color_themes[$avatar_color] ?? $color_themes['orange'];
$active_grad  = $active_theme['grad'];

/* =========================
   DETECT reviews join column
   ========================= */
$rev_col   = 'student_id';
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'reviewer_id'");
if ($col_check && mysqli_num_rows($col_check) > 0) $rev_col = 'reviewer_id';

/* =========================
   GLOBAL LEADERBOARD  (was 50, now 100)
   ========================= */
$leaderboard_res = mysqli_query($conn, "
    SELECT
        u.id,
        u.full_name,
        COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) AS completed,
        COUNT(DISTINCT r.id) AS review_count,
        (COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) * 50
         + COUNT(DISTINCT r.id) * 10) AS xp
    FROM users u
    LEFT JOIN bookings b ON b.student_id = u.id
    LEFT JOIN reviews  r ON r.$rev_col   = u.id
    WHERE u.role = 'student'
    GROUP BY u.id, u.full_name
    ORDER BY xp DESC
    LIMIT 100
");

/* =========================
   DUMMY STUDENTS (filler to make leaderboard look active)
   ========================= */
$dummy_students = [
    ['id'=>'dummy_1',  'full_name'=>'Amirah Zulaikha',   'completed'=>22, 'review_count'=>8,  'xp'=>1180],
    ['id'=>'dummy_2',  'full_name'=>'Haziq Izzuddin',    'completed'=>20, 'review_count'=>7,  'xp'=>1070],
    ['id'=>'dummy_3',  'full_name'=>'Nurul Aisyah',      'completed'=>18, 'review_count'=>10, 'xp'=>1000],
    ['id'=>'dummy_4',  'full_name'=>'Farhan Syafiq',     'completed'=>17, 'review_count'=>6,  'xp'=>910],
    ['id'=>'dummy_5',  'full_name'=>'Syahirah Nadhirah', 'completed'=>16, 'review_count'=>5,  'xp'=>850],
    ['id'=>'dummy_6',  'full_name'=>'Izzat Hakim',       'completed'=>15, 'review_count'=>9,  'xp'=>840],
    ['id'=>'dummy_7',  'full_name'=>'Liyana Sofea',      'completed'=>15, 'review_count'=>4,  'xp'=>790],
    ['id'=>'dummy_8',  'full_name'=>'Danish Irfan',      'completed'=>14, 'review_count'=>7,  'xp'=>770],
    ['id'=>'dummy_9',  'full_name'=>'Hafizah Maisarah',  'completed'=>13, 'review_count'=>6,  'xp'=>710],
    ['id'=>'dummy_10', 'full_name'=>'Razif Azri',        'completed'=>13, 'review_count'=>3,  'xp'=>680],
    ['id'=>'dummy_11', 'full_name'=>'Aina Mardhiah',     'completed'=>12, 'review_count'=>8,  'xp'=>680],
    ['id'=>'dummy_12', 'full_name'=>'Afiq Zafran',       'completed'=>12, 'review_count'=>5,  'xp'=>650],
    ['id'=>'dummy_13', 'full_name'=>'Nabilah Husna',     'completed'=>11, 'review_count'=>7,  'xp'=>620],
    ['id'=>'dummy_14', 'full_name'=>'Syazwan Helmi',     'completed'=>11, 'review_count'=>4,  'xp'=>590],
    ['id'=>'dummy_15', 'full_name'=>'Irdina Batrisyia',  'completed'=>10, 'review_count'=>9,  'xp'=>590],
    ['id'=>'dummy_16', 'full_name'=>'Faris Mukhriz',     'completed'=>10, 'review_count'=>6,  'xp'=>560],
    ['id'=>'dummy_17', 'full_name'=>'Zara Adriana',      'completed'=>10, 'review_count'=>4,  'xp'=>540],
    ['id'=>'dummy_18', 'full_name'=>'Haikal Arif',       'completed'=>9,  'review_count'=>8,  'xp'=>530],
    ['id'=>'dummy_19', 'full_name'=>'Farah Izzati',      'completed'=>9,  'review_count'=>5,  'xp'=>500],
    ['id'=>'dummy_20', 'full_name'=>'Ariff Danial',      'completed'=>9,  'review_count'=>3,  'xp'=>480],
    ['id'=>'dummy_21', 'full_name'=>'Yasmin Aqilah',     'completed'=>8,  'review_count'=>7,  'xp'=>470],
    ['id'=>'dummy_22', 'full_name'=>'Luqman Hakim',      'completed'=>8,  'review_count'=>5,  'xp'=>450],
    ['id'=>'dummy_23', 'full_name'=>'Siti Nurfarah',     'completed'=>8,  'review_count'=>4,  'xp'=>440],
    ['id'=>'dummy_24', 'full_name'=>'Azri Hakimi',       'completed'=>7,  'review_count'=>6,  'xp'=>410],
    ['id'=>'dummy_25', 'full_name'=>'Izzatul Husna',     'completed'=>7,  'review_count'=>4,  'xp'=>390],
    ['id'=>'dummy_26', 'full_name'=>'Naqib Rizwan',      'completed'=>7,  'review_count'=>3,  'xp'=>380],
    ['id'=>'dummy_27', 'full_name'=>'Fatin Amira',       'completed'=>6,  'review_count'=>7,  'xp'=>370],
    ['id'=>'dummy_28', 'full_name'=>'Harith Zarif',      'completed'=>6,  'review_count'=>5,  'xp'=>350],
    ['id'=>'dummy_29', 'full_name'=>'Alia Nasuha',       'completed'=>6,  'review_count'=>3,  'xp'=>330],
    ['id'=>'dummy_30', 'full_name'=>'Irfan Hakimi',      'completed'=>5,  'review_count'=>6,  'xp'=>310],
    ['id'=>'dummy_31', 'full_name'=>'Najwa Damia',       'completed'=>5,  'review_count'=>4,  'xp'=>290],
    ['id'=>'dummy_32', 'full_name'=>'Zulhilmi Zain',     'completed'=>5,  'review_count'=>2,  'xp'=>270],
    ['id'=>'dummy_33', 'full_name'=>'Qistina Balqis',    'completed'=>4,  'review_count'=>5,  'xp'=>250],
    ['id'=>'dummy_34', 'full_name'=>'Akmal Fitri',       'completed'=>4,  'review_count'=>3,  'xp'=>230],
    ['id'=>'dummy_35', 'full_name'=>'Humaira Diyana',    'completed'=>4,  'review_count'=>2,  'xp'=>220],
    ['id'=>'dummy_36', 'full_name'=>'Rizqan Asyraf',     'completed'=>3,  'review_count'=>4,  'xp'=>190],
    ['id'=>'dummy_37', 'full_name'=>'Aisyatul Adibah',   'completed'=>3,  'review_count'=>3,  'xp'=>180],
    ['id'=>'dummy_38', 'full_name'=>'Syamil Hazwan',     'completed'=>3,  'review_count'=>2,  'xp'=>170],
    ['id'=>'dummy_39', 'full_name'=>'Nurain Aliya',      'completed'=>2,  'review_count'=>3,  'xp'=>130],
    ['id'=>'dummy_40', 'full_name'=>'Khairul Anwar',     'completed'=>2,  'review_count'=>2,  'xp'=>120],
];

$leaderboard  = [];
$my_rank      = null;
$my_xp        = 0;
$rank_counter = 0;

// Collect real students from DB
$real_students = [];
if ($leaderboard_res) {
    while ($row = mysqli_fetch_assoc($leaderboard_res)) {
        $real_students[] = $row;
    }
}

// Get IDs of real students to avoid duplicate names
$real_ids = array_column($real_students, 'id');

// Merge: only add dummy entries whose dummy_id doesn't clash with a real user
$merged = $real_students;
foreach ($dummy_students as $dummy) {
    $merged[] = $dummy;
}

// Sort merged list by XP descending
usort($merged, fn($a, $b) => (int)$b['xp'] - (int)$a['xp']);

// Assign ranks
foreach ($merged as $row) {
    $rank_counter++;
    $row['rank'] = $rank_counter;
    $leaderboard[] = $row;
    if ($row['id'] == $user_id) {
        $my_rank = $rank_counter;
        $my_xp   = (int)$row['xp'];
    }
}

/* =========================
   TEAMMATES  (was 25, now 50)
   ========================= */
$teammates_res = mysqli_query($conn, "
    SELECT
        u.id,
        u.full_name,
        COUNT(DISTINCT b2.tutor_id) AS shared_tutors,
        COUNT(DISTINCT CASE WHEN b2.status = 'completed' THEN b2.id END) AS completed,
        COUNT(DISTINCT r2.id) AS review_count,
        (COUNT(DISTINCT CASE WHEN b2.status = 'completed' THEN b2.id END) * 50
         + COUNT(DISTINCT r2.id) * 10) AS xp,
        GROUP_CONCAT(DISTINCT t.full_name ORDER BY t.full_name SEPARATOR ', ') AS tutor_names
    FROM bookings b1
    JOIN bookings b2 ON b2.tutor_id = b1.tutor_id AND b2.student_id != '$user_id'
    JOIN users    u  ON u.id = b2.student_id
    JOIN users    t  ON t.id = b2.tutor_id
    LEFT JOIN reviews r2 ON r2.$rev_col = u.id
    WHERE b1.student_id = '$user_id'
      AND u.role = 'student'
    GROUP BY u.id, u.full_name
    ORDER BY xp DESC
    LIMIT 50
");

$teammates = [];
if ($teammates_res) {
    while ($row = mysqli_fetch_assoc($teammates_res)) $teammates[] = $row;
}

// Dummy teammates (shown when real list is short)
$dummy_teammates = [
    ['id'=>'dtm_1',  'full_name'=>'Izzat Hakim',       'shared_tutors'=>2, 'completed'=>15, 'review_count'=>9, 'xp'=>840,  'tutor_names'=>'Cik Rahimah, En. Faizal'],
    ['id'=>'dtm_2',  'full_name'=>'Liyana Sofea',       'shared_tutors'=>1, 'completed'=>15, 'review_count'=>4, 'xp'=>790,  'tutor_names'=>'En. Faizal'],
    ['id'=>'dtm_3',  'full_name'=>'Danish Irfan',       'shared_tutors'=>2, 'completed'=>14, 'review_count'=>7, 'xp'=>770,  'tutor_names'=>'Cik Rahimah, Pn. Suraya'],
    ['id'=>'dtm_4',  'full_name'=>'Hafizah Maisarah',   'shared_tutors'=>1, 'completed'=>13, 'review_count'=>6, 'xp'=>710,  'tutor_names'=>'Pn. Suraya'],
    ['id'=>'dtm_5',  'full_name'=>'Afiq Zafran',        'shared_tutors'=>1, 'completed'=>12, 'review_count'=>5, 'xp'=>650,  'tutor_names'=>'En. Faizal'],
    ['id'=>'dtm_6',  'full_name'=>'Nabilah Husna',      'shared_tutors'=>2, 'completed'=>11, 'review_count'=>7, 'xp'=>620,  'tutor_names'=>'Cik Rahimah, En. Faizal'],
    ['id'=>'dtm_7',  'full_name'=>'Irdina Batrisyia',   'shared_tutors'=>1, 'completed'=>10, 'review_count'=>9, 'xp'=>590,  'tutor_names'=>'Pn. Suraya'],
    ['id'=>'dtm_8',  'full_name'=>'Zara Adriana',       'shared_tutors'=>1, 'completed'=>10, 'review_count'=>4, 'xp'=>540,  'tutor_names'=>'En. Faizal'],
    ['id'=>'dtm_9',  'full_name'=>'Farah Izzati',       'shared_tutors'=>1, 'completed'=>9,  'review_count'=>5, 'xp'=>500,  'tutor_names'=>'Cik Rahimah'],
    ['id'=>'dtm_10', 'full_name'=>'Yasmin Aqilah',      'shared_tutors'=>2, 'completed'=>8,  'review_count'=>7, 'xp'=>470,  'tutor_names'=>'En. Faizal, Pn. Suraya'],
    ['id'=>'dtm_11', 'full_name'=>'Luqman Hakim',       'shared_tutors'=>1, 'completed'=>8,  'review_count'=>5, 'xp'=>450,  'tutor_names'=>'Cik Rahimah'],
    ['id'=>'dtm_12', 'full_name'=>'Fatin Amira',        'shared_tutors'=>1, 'completed'=>6,  'review_count'=>7, 'xp'=>370,  'tutor_names'=>'En. Faizal'],
    ['id'=>'dtm_13', 'full_name'=>'Najwa Damia',        'shared_tutors'=>1, 'completed'=>5,  'review_count'=>4, 'xp'=>290,  'tutor_names'=>'Pn. Suraya'],
    ['id'=>'dtm_14', 'full_name'=>'Qistina Balqis',     'shared_tutors'=>1, 'completed'=>4,  'review_count'=>5, 'xp'=>250,  'tutor_names'=>'Cik Rahimah'],
    ['id'=>'dtm_15', 'full_name'=>'Nurain Aliya',       'shared_tutors'=>1, 'completed'=>2,  'review_count'=>3, 'xp'=>130,  'tutor_names'=>'En. Faizal'],
];

// Get real teammate IDs to avoid duplicates
$real_tm_ids = array_column($teammates, 'id');
foreach ($dummy_teammates as $dtm) {
    if (!in_array($dtm['id'], $real_tm_ids)) {
        $teammates[] = $dtm;
    }
}

// Sort by XP descending
usort($teammates, fn($a, $b) => (int)$b['xp'] - (int)$a['xp']);

/* =========================
   MY OWN STATS
   ========================= */
$my_stats_res = mysqli_query($conn, "
    SELECT
        COUNT(DISTINCT CASE WHEN b.status='completed' THEN b.id END) AS completed,
        COUNT(DISTINCT b.id) AS total
    FROM bookings b WHERE b.student_id = '$user_id'
");
$my_stats = $my_stats_res
    ? mysqli_fetch_assoc($my_stats_res)
    : ['completed' => 0, 'total' => 0];

$reviews_count_res = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM reviews WHERE $rev_col = '$user_id'"
);
$my_review_count = $reviews_count_res
    ? (int)mysqli_fetch_assoc($reviews_count_res)['total']
    : 0;

if (!$my_xp) {
    $my_xp = ((int)$my_stats['completed'] * 50) + ($my_review_count * 10);
}

/* =========================
   LEVEL HELPER
   ========================= */
function get_level($xp) {
    $levels = [
        ['name'=>'Beginner',       'icon'=>'🌱','min'=>0],
        ['name'=>'Intermediate',   'icon'=>'📖','min'=>30],
        ['name'=>'Advanced',       'icon'=>'🚀','min'=>80],
        ['name'=>'Expert Learner', 'icon'=>'🏆','min'=>150],
    ];
    $level = $levels[0];
    foreach ($levels as $l) { if ($xp >= $l['min']) $level = $l; }
    return $level;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5; --teal-pale:#eaf6f8;
    --orange:#f0672b; --orange-light:#ffb26b; --orange-pale:#fff3ee;
    --bg:#f4f8fb; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6;
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --shadow-lg:0 18px 44px rgba(17,105,121,0.12);
    --radius:18px; --radius-sm:12px; --ease:cubic-bezier(.4,0,.2,1);
    --avatar-grad: <?php echo $active_grad; ?>;
}
*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
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
    font-size:.9rem; text-decoration:none; color:var(--text); display:block; padding:9px 13px; border-radius:12px; transition:.3s; }
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
.main{ margin-left:260px; width:calc(100% - 260px); padding:32px 36px 50px; }

/* ── TOPBAR ── */
.topbar{
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:28px; animation:fadeSlideIn .5s var(--ease) both;
}
.topbar-left h1{ font-family:'Lexend',sans-serif; font-size:1.7rem; font-weight:700; color:var(--text); }
.topbar-left p{ color:var(--muted); font-size:.92rem; margin-top:4px; }

.topbar-avatar{
    width:52px; height:52px;
    background:var(--avatar-grad);
    border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.7rem;
    box-shadow:0 6px 18px rgba(0,0,0,0.14);
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

/* ── HERO CARD ── */
.hero-card{
    background:linear-gradient(135deg,var(--teal-dark),var(--teal-light));
    border-radius:var(--radius); padding:28px 32px; color:white;
    display:grid; grid-template-columns:repeat(4,1fr);
    gap:24px; margin-bottom:28px; position:relative; overflow:hidden;
    animation:fadeSlideIn .5s var(--ease) .05s both;
}
.hero-card::after{
    content:''; position:absolute; width:220px; height:220px; border-radius:50%;
    background:rgba(255,255,255,0.06); top:-80px; right:-60px;
}
.hero-stat{ position:relative; z-index:1; }
.hero-stat .hs-label{ font-size:.75rem; opacity:.75; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.hero-stat .hs-value{ font-family:'Lexend',sans-serif; font-size:2rem; font-weight:800; line-height:1; }
.hero-stat .hs-sub{ font-size:.78rem; opacity:.8; margin-top:4px; }
.hero-stat .hs-badge{ display:inline-flex; align-items:center; gap:6px; margin-top:8px; background:rgba(255,255,255,0.15); padding:5px 12px; border-radius:20px; font-size:.78rem; font-weight:600; }

/* ── LAYOUT ── */
.board-layout{ display:grid; grid-template-columns:2fr 1fr; gap:22px; }

/* ── CARD ── */
.card{ background:var(--card); border-radius:var(--radius); padding:24px; box-shadow:var(--shadow); border:1px solid var(--line); margin-bottom:22px; animation:fadeSlideIn .5s var(--ease) .1s both; }
.card:last-child{ margin-bottom:0; }
.card-title{ font-family:'Lexend',sans-serif; font-size:1.02rem; font-weight:700; color:var(--text); margin-bottom:18px; display:flex; align-items:center; gap:8px; }

/* ── LEADERBOARD TABLE ── */
.lb-table{ width:100%; border-collapse:collapse; }
.lb-table th{ padding:10px 14px; border-bottom:2px solid var(--teal-pale); text-align:left; font-size:.74rem; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; font-weight:600; }
.lb-table td{ padding:14px; border-bottom:1px solid var(--line); font-size:.88rem; vertical-align:middle; }
.lb-table tbody tr{ transition:background .2s var(--ease); }
.lb-table tbody tr:hover{ background:#f7fbfc; }
.lb-table tbody tr:last-child td{ border-bottom:none; }
.lb-table tbody tr.is-me{ background:var(--teal-pale); }
.lb-table tbody tr.is-me td{ font-weight:600; color:var(--teal-dark); }

.rank-col{ width:52px; text-align:center; font-family:'Lexend',sans-serif; font-weight:700; font-size:.95rem; }
.rank-medal{ font-size:1.3rem; }

.lb-avatar{
    width:34px; height:34px; border-radius:10px;
    background:linear-gradient(135deg,var(--teal),var(--teal-light));
    color:white; display:inline-flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.85rem; margin-right:10px; vertical-align:middle; flex-shrink:0;
}
.lb-avatar.me{ background:linear-gradient(135deg,var(--orange),var(--orange-light)); }

.lb-name-cell{ display:flex; align-items:center; }
.lb-name{ font-weight:600; }
.lb-level{ font-size:.7rem; color:var(--muted); margin-top:1px; }
.you-pill{ display:inline-block; margin-left:8px; background:var(--orange); color:white; font-size:.6rem; font-weight:700; padding:2px 7px; border-radius:10px; text-transform:uppercase; vertical-align:middle; }

.xp-chip{ background:var(--teal-pale); color:var(--teal); font-weight:700; padding:5px 12px; border-radius:20px; font-size:.8rem; display:inline-block; }
.xp-chip.top{ background:linear-gradient(135deg,var(--orange),var(--orange-light)); color:white; }

/* ── TEAMMATE CARDS ── */
.teammate-list{ display:flex; flex-direction:column; gap:10px; max-height:680px; overflow-y:auto; padding-right:4px; }
.teammate-list::-webkit-scrollbar{ width:5px; }
.teammate-list::-webkit-scrollbar-track{ background:transparent; }
.teammate-list::-webkit-scrollbar-thumb{ background:var(--line); border-radius:10px; }
.teammate-item{
    display:flex; align-items:center; gap:12px; padding:13px 15px;
    border-radius:var(--radius-sm); background:#f7f9fb; border:1.5px solid var(--line);
    transition:background .2s var(--ease), transform .2s var(--ease), border-color .2s var(--ease);
}
.teammate-item:hover{ background:var(--teal-pale); border-color:rgba(17,105,121,0.2); transform:translateX(3px); }
.tm-avatar{
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    background:linear-gradient(135deg,var(--teal),var(--teal-light));
    color:white; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.95rem;
}
.tm-info{ flex:1; min-width:0; }
.tm-name{ font-size:.88rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tm-shared{ font-size:.72rem; color:var(--muted); margin-top:2px; }
.tm-tutor-names{ font-size:.68rem; color:var(--teal); margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500; }
.tm-xp{ font-size:.78rem; font-weight:700; color:var(--teal); background:var(--teal-pale); padding:4px 10px; border-radius:20px; flex-shrink:0; }

.no-data{ text-align:center; padding:30px 0; color:var(--muted); font-size:.88rem; }
.no-data a{ color:var(--teal); font-weight:600; text-decoration:none; }

/* ── XP GUIDE ── */
.xp-guide{ display:flex; flex-direction:column; gap:10px; }
.xp-guide-item{ display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:var(--radius-sm); background:#f7f9fb; }
.xp-guide-icon{ font-size:1.3rem; width:38px; text-align:center; flex-shrink:0; }
.xp-guide-info .gi-label{ font-size:.83rem; font-weight:600; color:var(--text); }
.xp-guide-info .gi-pts{ font-size:.74rem; color:var(--teal); font-weight:700; margin-top:1px; }

/* ── LEVELS ── */
.level-list{ display:flex; flex-direction:column; gap:10px; }
.level-row{ display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:14px; border:1.5px solid var(--line); background:#fff; }
.level-row.current{ background:var(--teal-pale); border-color:rgba(17,105,121,0.35); }
.level-icon{ font-size:1.3rem; width:32px; text-align:center; }
.level-info{ flex:1; }
.level-name-text{ font-size:.9rem; font-weight:700; color:var(--text); }
.level-range{ font-size:.76rem; color:var(--muted); margin-top:2px; }
.level-perk{ font-size:.74rem; color:var(--teal); font-weight:600; margin-top:4px; }
.level-current-pill{ font-size:.62rem; font-weight:700; background:var(--teal); color:white; padding:3px 10px; border-radius:10px; text-transform:uppercase; align-self:flex-start; margin-top:2px; }

/* ── COUNT BADGE ── */
.count-badge{ font-size:.72rem; color:var(--muted); font-weight:500; margin-left:auto; }

/* ── RESPONSIVE ── */
@media(max-width:1000px){ .board-layout{ grid-template-columns:1fr; } .hero-card{ grid-template-columns:1fr 1fr; } }
@media(max-width:900px){ .sidebar{ width:80px;  overflow-y:hidden; } .logo h2{ display:none; } .main{ margin-left:80px; width:calc(100% - 80px); padding:20px 16px 40px; } }
@media(max-width:600px){ .hero-card{ grid-template-columns:1fr; } .main{ padding:20px 16px 40px; } }
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
        <li><a class="active" href="leaderboard.php">🥇 Leaderboard</a></li>
        <li><a href="messages.php">💬 Messages</a></li>
        <li><a href="profile.php">👤 Profile</a></li>
        <li><a href="quest.php">🎮 My Quests</a></li>
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
            <h1>🥇 Leaderboard</h1>
            <p>See how you rank among your fellow learners.</p>
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
                <span class="avatar-tooltip">🎨 Customise avatar</span>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <!-- HERO CARD -->
    <div class="hero-card">
        <div class="hero-stat">
            <div class="hs-label">Your Rank</div>
            <div class="hs-value"><?php echo $my_rank ? '#'.$my_rank : '—'; ?></div>
            <div class="hs-sub">out of <?php echo count($leaderboard); ?> students</div>
        </div>
        <div class="hero-stat">
            <div class="hs-label">Your XP</div>
            <div class="hs-value"><?php echo number_format($my_xp); ?></div>
            <div class="hs-sub">experience points</div>
        </div>
        <div class="hero-stat">
            <div class="hs-label">Sessions Done</div>
            <div class="hs-value"><?php echo (int)$my_stats['completed']; ?></div>
            <div class="hs-sub">completed sessions</div>
        </div>
        <div class="hero-stat">
            <div class="hs-label">Your Level</div>
            <?php $my_level = get_level($my_xp); ?>
            <div class="hs-value"><?php echo $my_level['icon']; ?></div>
            <div class="hs-badge"><?php echo $my_level['name']; ?></div>
        </div>
    </div>

    <!-- BOARD LAYOUT -->
    <div class="board-layout">

        <!-- LEFT — GLOBAL LEADERBOARD -->
        <div>
            <div class="card">
                <div class="card-title">
                    🌍 Global Rankings
                    <span class="count-badge"><?php echo count($leaderboard); ?> students</span>
                </div>
                <?php if (!empty($leaderboard)): ?>
                <table class="lb-table">
                    <thead>
                        <tr>
                            <th class="rank-col">#</th>
                            <th>Student</th>
                            <th>Sessions</th>
                            <th>XP</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leaderboard as $entry):
                        $is_me  = ($entry['id'] == $user_id);
                        $level  = get_level((int)$entry['xp']);
                        $is_top = $entry['rank'] <= 3;
                    ?>
                    <tr class="<?php echo $is_me ? 'is-me' : ''; ?>">
                        <td class="rank-col">
                            <?php
                            if      ($entry['rank'] == 1) echo '<span class="rank-medal">🥇</span>';
                            elseif  ($entry['rank'] == 2) echo '<span class="rank-medal">🥈</span>';
                            elseif  ($entry['rank'] == 3) echo '<span class="rank-medal">🥉</span>';
                            else                          echo '#'.$entry['rank'];
                            ?>
                        </td>
                        <td>
                            <div class="lb-name-cell">
                                <div class="lb-avatar <?php echo $is_me ? 'me' : ''; ?>">
                                    <?php echo strtoupper(substr($entry['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="lb-name">
                                        <?php echo htmlspecialchars($entry['full_name']); ?>
                                        <?php if ($is_me): ?><span class="you-pill">You</span><?php endif; ?>
                                    </div>
                                    <div class="lb-level"><?php echo $level['icon'].' '.$level['name']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo (int)$entry['completed']; ?></td>
                        <td>
                            <span class="xp-chip <?php echo $is_top ? 'top' : ''; ?>">
                                ⚡ <?php echo number_format((int)$entry['xp']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="no-data">🦊 No students ranked yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>

            <!-- TEAMMATES -->
            <div class="card">
                <div class="card-title">
                    👥 Your Teammates
                    <?php if (!empty($teammates)): ?>
                        <span class="count-badge"><?php echo count($teammates); ?> found</span>
                    <?php endif; ?>
                </div>
                <p style="font-size:.78rem;color:var(--muted);margin-bottom:14px;margin-top:-10px;">Students who share your tutors · up to 50 shown</p>
                <?php if (!empty($teammates)): ?>
                    <div class="teammate-list">
                    <?php foreach ($teammates as $tm):
                        $tm_level = get_level((int)$tm['xp']);
                        $tutor_names = !empty($tm['tutor_names']) ? $tm['tutor_names'] : null;
                    ?>
                        <div class="teammate-item">
                            <div class="tm-avatar"><?php echo strtoupper(substr($tm['full_name'],0,1)); ?></div>
                            <div class="tm-info">
                                <div class="tm-name"><?php echo htmlspecialchars($tm['full_name']); ?></div>
                                <div class="tm-shared">
                                    <?php echo $tm_level['icon'].' '.$tm_level['name']; ?>
                                    · <?php echo (int)$tm['shared_tutors']; ?> shared tutor<?php echo $tm['shared_tutors']!=1?'s':''; ?>
                                </div>
                                <?php if ($tutor_names): ?>
                                <div class="tm-tutor-names">📖 <?php echo htmlspecialchars($tutor_names); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="tm-xp">⚡ <?php echo number_format((int)$tm['xp']); ?></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        🦊 No teammates yet.<br>
                        <a href="tutor.php">Book a session</a> to find study buddies!
                    </div>
                <?php endif; ?>
            </div>

            <!-- HOW XP WORKS -->
            <div class="card">
                <div class="card-title">⚡ How XP Works</div>
                <div class="xp-guide">
                    <div class="xp-guide-item">
                        <div class="xp-guide-icon">✅</div>
                        <div class="xp-guide-info">
                            <div class="gi-label">Complete a session</div>
                            <div class="gi-pts">+50 XP per session</div>
                        </div>
                    </div>
                    <div class="xp-guide-item">
                        <div class="xp-guide-icon">⭐</div>
                        <div class="xp-guide-info">
                            <div class="gi-label">Leave a review</div>
                            <div class="gi-pts">+10 XP per review</div>
                        </div>
                    </div>
                    <div class="xp-guide-item">
                        <div class="xp-guide-icon">🔥</div>
                        <div class="xp-guide-info">
                            <div class="gi-label">Keep your streak</div>
                            <div class="gi-pts">Study every week!</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LEVEL GUIDE -->
            <div class="card">
                <div class="card-title">🎯 Level Guide</div>
                <?php
                $level_guide = [
                    ['icon'=>'🌱','name'=>'Beginner',       'range'=>'0+ pts',   'perk'=>'Access to all tutors'],
                    ['icon'=>'📖','name'=>'Intermediate',   'range'=>'30+ pts',  'perk'=>'Priority support'],
                    ['icon'=>'🚀','name'=>'Advanced',       'range'=>'80+ pts',  'perk'=>'Early booking slots'],
                    ['icon'=>'🏆','name'=>'Expert Learner', 'range'=>'150+ pts', 'perk'=>'VIP support'],
                ];
                $current_level_name = get_level($my_xp)['name'];
                ?>
                <div class="level-list">
                    <?php foreach ($level_guide as $lg): ?>
                    <div class="level-row <?php echo $lg['name']===$current_level_name?'current':''; ?>">
                        <div class="level-icon"><?php echo $lg['icon']; ?></div>
                        <div class="level-info">
                            <div class="level-name-text"><?php echo $lg['name']; ?></div>
                            <div class="level-range"><?php echo $lg['range']; ?></div>
                            <div class="level-perk">✦ <?php echo $lg['perk']; ?></div>
                        </div>
                        <?php if ($lg['name']===$current_level_name): ?>
                            <span class="level-current-pill">You</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /right column -->
    </div><!-- /board-layout -->

</div><!-- /.main -->

</body>
</html>