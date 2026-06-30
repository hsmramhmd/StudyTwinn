<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include("db_connection.php");

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['name'];
$role      = $_SESSION['role'];

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
   FETCH RANKINGS
   ========================= */
$rankings_query = mysqli_query($conn, "
    SELECT tr.rank_position, u.full_name, tr.rating
    FROM tutor_top_rank tr
    JOIN tutors t ON tr.tutor_id = t.id
    JOIN users  u ON t.user_id   = u.id
    ORDER BY tr.rank_position ASC
");

$ranked_tutors = [];
if ($rankings_query && mysqli_num_rows($rankings_query) > 0) {
    while ($row = mysqli_fetch_assoc($rankings_query)) {
        $pos = (int)$row['rank_position'];
        if      ($pos === 1) $rank_display = "🥇";
        elseif  ($pos === 2) $rank_display = "🥈";
        elseif  ($pos === 3) $rank_display = "🥉";
        else                 $rank_display = "#" . $pos;

        $ranked_tutors[] = [
            'rank'   => $rank_display,
            'pos'    => $pos,
            'name'   => $row['full_name'],
            'rating' => number_format($row['rating'], 1),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor Top Rank — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5; --teal-pale:#eaf6f8;
    --orange:#f0672b; --orange-light:#ffb26b; --orange-pale:#fff3ee;
    --bg:#f4f8fb; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6;
    --shadow-sm:0 4px 14px rgba(17,105,121,0.06);
    --shadow:0 12px 30px rgba(17,105,121,0.08);
    --shadow-lg:0 18px 44px rgba(17,105,121,0.12);
    --radius:18px; --radius-sm:12px;
    --ease:cubic-bezier(.4,0,.2,1);
    --avatar-grad: <?php echo $active_grad; ?>;
}

*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
html{ scroll-behavior:smooth; }
body{ background:var(--bg); display:flex; overflow-x:hidden; }

/* ── FLOATING FOX BACKGROUND ── */
.floating-bg{
    position:fixed; top:0; left:260px;
    width:calc(100% - 260px); height:100vh;
    z-index:0; pointer-events:none;
}
.floating-fox{
    position:absolute; font-size:24px;
    opacity:0.18; animation:packFloat 9s ease-in-out infinite;
}
.fox-1{ top:10%; left:8%;  animation-delay:0s;   animation-duration:8s; }
.fox-2{ top:25%; left:45%; animation-delay:1.5s; animation-duration:10s; }
.fox-3{ top:15%; left:82%; animation-delay:3s;   animation-duration:7.5s; }
.fox-4{ top:50%; left:15%; animation-delay:4.5s; animation-duration:9.5s; }
.fox-5{ top:45%; left:75%; animation-delay:0.8s; animation-duration:8.5s; }
.fox-6{ top:78%; left:12%; animation-delay:2.2s; animation-duration:11s; }
.fox-7{ top:80%; left:48%; animation-delay:5s;   animation-duration:9s; }
.fox-8{ top:72%; left:88%; animation-delay:1.2s; animation-duration:8s; }

::selection{ background:var(--teal); color:white; }
@keyframes fadeSlideIn{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:translateY(0); } }
@keyframes growBar{ from{ width:0%; } }
@keyframes pulse{ 0%,100%{ transform:scale(1); } 50%{ transform:scale(1.06); } }

/* ── SIDEBAR ── */
.sidebar{
    width:260px; position:fixed;
    top:0; bottom:0;
    background:var(--sidebar-bg); border-right:1px solid var(--line);
    padding:25px; display:flex; flex-direction:column; z-index:10;
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
    text-decoration:none; background:var(--orange-pale); color:var(--orange); font-weight:600;
}
:root[data-theme="dark"] .logout a {
    background: #3f2a1f !important;
    color: #fb923c !important;
}
:root[data-theme="dark"] .logout { background: var(--sidebar-bg, #0f172a) !important; }

/* ── MAIN ── */
.main{
    margin-left:260px; width:calc(100% - 260px);
    padding:32px 36px 50px;
    display:flex; flex-direction:column; align-items:center;
    position:relative; z-index:5;
}

/* ── TOPBAR ── */
.topbar{
    display:flex; justify-content:space-between; align-items:center;
    width:100%; margin-bottom:28px; animation:fadeSlideIn .5s var(--ease) both;
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

/* ── RANKING CARD ── */
@keyframes slowFloat{
    0%,100%{ transform:translateY(0); }
    50%     { transform:translateY(-8px); }
}

.ranking-card{
    width:100%; max-width:780px;
    background:white; border-radius:24px;
    padding:40px; box-shadow:var(--shadow-lg);
    border:1px solid var(--line);
    animation:fadeSlideIn .5s var(--ease) .05s both, slowFloat 6s ease-in-out .6s infinite;
}

.ranking-title{
    color:var(--teal); margin-bottom:32px;
    font-family:'Lexend',sans-serif; font-size:1.8rem; font-weight:800;
    text-align:center; letter-spacing:-.5px;
}

/* ── RANK ITEMS ── */
.rank-item{
    display:flex; justify-content:space-between; align-items:center;
    padding:16px 22px; margin-bottom:12px;
    border-radius:var(--radius-sm); border:1.5px solid var(--line);
    background:#fafcfd;
    transition:transform .3s var(--ease), box-shadow .3s var(--ease), border-color .3s var(--ease);
}
.rank-item:last-child{ margin-bottom:0; }
.rank-item:hover{
    transform:translateY(-3px) scale(1.005);
    box-shadow:var(--shadow);
    border-color:#c8e8ec;
}

/* Gold/Silver/Bronze highlights */
.rank-item.gold  { background:linear-gradient(135deg,#fffbeb,#fef9c3); border-color:#fde68a; }
.rank-item.silver{ background:linear-gradient(135deg,#f8fafc,#f1f5f9); border-color:#cbd5e1; }
.rank-item.bronze{ background:linear-gradient(135deg,#fff7f3,#fef0e7); border-color:#fdba74; }

.rank-left{ display:flex; align-items:center; gap:20px; }

.medal{
    font-size:2rem; width:52px; height:52px;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.medal.text-rank{
    font-size:.9rem; font-weight:700; color:var(--muted);
    background:#f0f4f6; border-radius:50%;
}

.tutor-info .tutor-name{
    font-family:'Lexend',sans-serif; font-size:1rem; font-weight:700; color:var(--text);
}
.tutor-info .tutor-pos{
    font-size:.75rem; color:var(--muted); margin-top:2px;
}

.rating{
    background:var(--orange-pale); color:var(--orange);
    padding:8px 16px; border-radius:30px;
    font-weight:700; font-size:.88rem;
    display:flex; align-items:center; gap:5px;
    flex-shrink:0;
}

/* ── EMPTY STATE ── */
.empty-rank{
    text-align:center; padding:50px 20px; color:var(--muted);
}
.empty-rank .empty-icon{ font-size:3rem; margin-bottom:12px; }
.empty-rank p{ font-size:.95rem; }

/* ── RESPONSIVE ── */
@media(max-width:900px){
    .sidebar{ width:80px;  overflow-y:hidden; }
    .logo h2{ display:none; }
    .main{ margin-left:80px; width:calc(100% - 80px); padding:20px 16px 40px; }
    .floating-bg{ left:80px; width:calc(100% - 80px); }
    .ranking-card{ padding:24px 18px; }
}
</style>
<?php include_once("includes/theme.php"); inject_theme_styles_and_script(); ?>
</head>
<body>

<!-- FLOATING FOX BACKGROUND -->
<div class="floating-bg">
    <div class="floating-fox fox-1">🦊</div>
    <div class="floating-fox fox-2">🦊</div>
    <div class="floating-fox fox-3">🦊</div>
    <div class="floating-fox fox-4">🦊</div>
    <div class="floating-fox fox-5">🦊</div>
    <div class="floating-fox fox-6">🦊</div>
    <div class="floating-fox fox-7">🦊</div>
    <div class="floating-fox fox-8">🦊</div>
</div>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
    <div class="logo">
        <h2>StudyTwin</h2>
    </div>
    <ul class="menu">
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="tutor.php">📚 Find Tutor</a></li>
        <li><a href="rooms.php">📅 Rooms / Sessions</a></li>
        <li><a class="active" href="tutortoprank.php">🏆 Tutor Top Rank</a></li>
        <li><a href="bookings.php">📅 My Bookings</a></li>
        <li><a href="leaderboard.php">🥇 Leaderboard</a></li>
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
            <h1>🏆 Tutor Top Rank</h1>
            <p>Our highest-rated tutors based on student reviews.</p>
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'tutor'): ?>
                <a href="tutor/tutor_dashboard.php" class="switch-to-tutor" style="font-size:0.82rem; padding:5px 11px; border-radius:8px; text-decoration:none; font-weight:600;">Switch to Tutor</a>
            <?php endif; ?>
            <a href="profile.php" class="topbar-avatar" title="Customise your avatar" style="position:relative;">
                <?php echo $avatar_emoji; ?>
                <?php if ($avatar_outfit !== 'none' && $avatar_outfit_emoji): ?>
                    <span style="position:absolute;bottom:-6px;right:-6px;font-size:.9rem;background:var(--card);border-radius:8px;padding:1px 3px;border:1px solid var(--line);"><?php echo $avatar_outfit_emoji; ?></span>
                <?php endif; ?>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <!-- RANKING CARD -->
    <div class="ranking-card">
        <h1 class="ranking-title">🏆 Tutor Top Rank</h1>

        <?php if (!empty($ranked_tutors)): ?>
            <?php foreach ($ranked_tutors as $tutor):
                $pos        = $tutor['pos'];
                $is_medal   = in_array($tutor['rank'], ['🥇','🥈','🥉']);
                $item_class = match($pos) { 1=>'gold', 2=>'silver', 3=>'bronze', default=>'' };
                $pos_label  = match($pos) {
                    1 => '1st Place', 2 => '2nd Place', 3 => '3rd Place',
                    default => 'Rank #' . $pos
                };
            ?>
                <div class="rank-item <?php echo $item_class; ?>">
                    <div class="rank-left">
                        <span class="medal <?php echo !$is_medal ? 'text-rank' : ''; ?>">
                            <?php echo htmlspecialchars($tutor['rank']); ?>
                        </span>
                        <div class="tutor-info">
                            <div class="tutor-name"><?php echo htmlspecialchars($tutor['name']); ?></div>
                            <div class="tutor-pos"><?php echo $pos_label; ?></div>
                        </div>
                    </div>
                    <span class="rating">⭐ <?php echo htmlspecialchars($tutor['rating']); ?></span>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty-rank">
                <div class="empty-icon">🦊</div>
                <p>No tutors ranked yet. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.main -->

</body>
</html>