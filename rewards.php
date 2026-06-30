<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'tutor') {
    header('Location: ../index.php');
    exit();
}

include('../db_connection.php');

$user_id   = (int)$_SESSION['user_id'];
$full_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Tutor';
include __DIR__ . '/includes/gamification.php';

$avatar_res = mysqli_query($conn, "SELECT avatar_color, avatar_animal, avatar_outfit FROM users WHERE id=$user_id");
$avatar_row = $avatar_res ? mysqli_fetch_assoc($avatar_res) : [];
$avatar_color  = $avatar_row['avatar_color']  ?? 'orange';
$avatar_animal = $avatar_row['avatar_animal'] ?? 'fox';
$avatar_outfit = $avatar_row['avatar_outfit'] ?? 'none';
$animals = ['fox'=>'🦊','cat'=>'🐱','bear'=>'🐻','rabbit'=>'🐰','owl'=>'🦉','penguin'=>'🐧'];
$outfits = ['none'=>'','graduation'=>'🎓','chef'=>'👨‍🍳','ninja'=>'🥷','wizard'=>'🧙','astronaut'=>'👨‍🚀','knight'=>'🧝','crown'=>'👑'];
$avatar_emoji = $animals[$avatar_animal] ?? '🦊';
$avatar_outfit_emoji = $outfits[$avatar_outfit] ?? '';
$color_themes = [
    'orange'=>['grad'=>'linear-gradient(135deg,#f0672b,#ffb26b)'],
    'teal'=>['grad'=>'linear-gradient(135deg,#116979,#1b90a5)'],
    'purple'=>['grad'=>'linear-gradient(135deg,#7c3aed,#a78bfa)'],
    'rose'=>['grad'=>'linear-gradient(135deg,#e11d48,#fb7185)'],
    'green'=>['grad'=>'linear-gradient(135deg,#16a34a,#4ade80)'],
    'midnight'=>['grad'=>'linear-gradient(135deg,#1e293b,#475569)'],
    'gold'=>['grad'=>'linear-gradient(135deg,#b45309,#fbbf24)'],
    'sky'=>['grad'=>'linear-gradient(135deg,#0284c7,#38bdf8)'],
];
$active_grad = $color_themes[$avatar_color]['grad'] ?? $color_themes['orange']['grad'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor Rewards — StudyTwin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Lexend:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --teal:#116979; --teal-dark:#0b4e5a; --teal-light:#1b90a5; --teal-pale:#eaf6f8;
    --orange:#f0672b; --orange-light:#ffb26b; --orange-pale:#fff3ee;
    --bg:#f4f8f9; --text:#1e2a35; --muted:#6b7b8c; --line:#eef3f6;
    --shadow:0 12px 30px rgba(17,105,121,0.08); --radius:18px; --radius-sm:12px;
    --avatar-grad: <?= $active_grad ?>;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);display:flex;color:var(--text);}
.sidebar{width:250px;height:100vh;position:fixed;top:0;left:0;z-index:100;background:var(--sidebar-bg,#fff);border-right:1px solid var(--line);padding:22px;display:flex;flex-direction:column;}
.sidebar .logo h2{font-family:'Lexend',sans-serif;color:var(--teal);font-size:1.3rem;font-weight:800;}
.sidebar .menu{list-style:none;flex:1;margin-top:16px;}
.sidebar .menu li a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;text-decoration:none;color:var(--text);font-size:.88rem;font-weight:500;margin-bottom:2px;}
.sidebar .menu li a:hover,.sidebar .menu li a.active{background:rgba(17,105,121,.1);color:var(--teal);}
.sidebar .menu .sep{margin-top:14px;padding-top:12px;border-top:1px solid var(--line);}
.sidebar .logout{margin-top:auto;padding-top:16px;}
.sidebar .logout a{display:block;padding:10px 14px;border-radius:10px;background:var(--orange-pale);color:var(--orange);text-decoration:none;font-weight:600;font-size:.88rem;}
.main{margin-left:250px;width:calc(100% - 250px);padding:28px 32px 60px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px;flex-wrap:wrap;gap:12px;}
.topbar-left{display:flex;align-items:center;gap:10px;}
.topbar-left h1{font-family:'Lexend',sans-serif;font-size:1.6rem;font-weight:700;}
.topbar-left p{color:var(--muted);font-size:.88rem;margin-top:2px;}
.topbar-avatar{width:48px;height:48px;background:var(--avatar-grad);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;text-decoration:none;box-shadow:0 4px 14px rgba(0,0,0,.12);position:relative;}
.sidebar-toggle{width:36px;height:36px;border:1px solid var(--line);border-radius:8px;background:transparent;cursor:pointer;font-size:1.1rem;color:var(--text);}
.hero{background:linear-gradient(135deg,var(--teal-dark),var(--teal-light));border-radius:var(--radius);padding:34px 38px;color:#fff;display:grid;grid-template-columns:1fr auto;gap:28px;margin-bottom:24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.06);top:-100px;right:-60px;}
.hero-points{font-family:'Lexend',sans-serif;font-size:3rem;font-weight:800;line-height:1;}
.hero-label{font-size:.76rem;opacity:.8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;}
.hero-sub{font-size:.86rem;opacity:.85;margin-top:6px;}
.hero-level-badge{background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.25);border-radius:12px;padding:8px 16px;font-size:.84rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;margin-top:14px;}
.hero-bar-track{width:100%;max-width:360px;height:8px;border-radius:6px;background:rgba(255,255,255,.2);margin-top:12px;overflow:hidden;}
.hero-bar-fill{height:100%;background:linear-gradient(90deg,var(--orange-light),var(--orange));border-radius:6px;}
.hero-bar-label{font-size:.72rem;opacity:.75;margin-top:5px;}
.hero-trophy{font-size:4.5rem;filter:drop-shadow(0 8px 16px rgba(0,0,0,.2));}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-mini{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-sm);padding:18px;box-shadow:var(--shadow);}
.stat-mini .sm-val{font-family:'Lexend',sans-serif;font-size:1.5rem;font-weight:800;}
.stat-mini .sm-label{font-size:.72rem;color:var(--muted);margin-top:2px;}
.layout{display:grid;grid-template-columns:2fr 1fr;gap:20px;}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;margin-bottom:20px;box-shadow:var(--shadow);}
.card-title{font-family:'Lexend',sans-serif;font-size:1rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.card-sub{font-size:.78rem;color:var(--muted);margin:-10px 0 14px;}
.badges-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.badge-item{display:flex;flex-direction:column;align-items:center;text-align:center;gap:5px;padding:14px 8px;border-radius:var(--radius-sm);border:1.5px solid var(--line);background:var(--card);position:relative;transition:transform .2s;}
.badge-item.unlocked{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:rgba(34,197,94,.35);}
.badge-item.unlocked:hover{transform:translateY(-3px);}
.badge-item.locked{opacity:.42;filter:grayscale(1);}
.badge-emoji{font-size:1.7rem;}
.badge-name{font-size:.65rem;font-weight:700;line-height:1.3;}
.badge-req{font-size:.58rem;color:var(--muted);}
.badge-check{position:absolute;top:6px;right:6px;width:16px;height:16px;border-radius:50%;background:#22c55e;color:#fff;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;}
.level-row{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-sm);border:1.5px solid var(--line);margin-bottom:8px;background:var(--card);}
.level-row.current{border-color:var(--teal);background:var(--teal-pale);}
.lv-you{font-size:.62rem;font-weight:700;background:var(--teal);color:#fff;padding:3px 9px;border-radius:10px;margin-left:auto;}
.cert-item{display:flex;align-items:center;gap:14px;padding:16px;border-radius:var(--radius-sm);border:1.5px solid var(--line);margin-bottom:10px;background:var(--card);}
.cert-item.earned{background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:rgba(245,158,11,.35);}
.cert-item.locked{opacity:.5;}
.milestone{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-sm);border:1px solid var(--line);margin-bottom:8px;}
.milestone.done{background:var(--teal-pale);border-color:var(--teal);}
.milestone.locked{opacity:.5;}
.next-list{display:flex;flex-direction:column;gap:8px;}
.next-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:var(--surface-alt,var(--teal-pale));font-size:.82rem;}
.perk-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;margin-bottom:6px;border-left:3px solid transparent;}
.perk-row.on{background:var(--teal-pale);border-left-color:var(--teal);}
.perk-row.off{opacity:.45;}
.rank-banner{display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:var(--radius-sm);background:linear-gradient(135deg,#fef3c7,#fde68a);border:1.5px solid #fbbf24;margin-bottom:18px;}
@media(max-width:1000px){.layout{grid-template-columns:1fr;}.stats-row{grid-template-columns:repeat(2,1fr);}.badges-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:700px){.main{margin-left:0;width:100%;padding:18px;}.sidebar{display:none;}.badges-grid{grid-template-columns:repeat(2,1fr);}.hero{grid-template-columns:1fr;}.hero-trophy-wrap{display:none;}}
</style>
<?php include_once('../includes/theme.php'); inject_theme_styles_and_script(); ?>
</head>
<body>

<div class="sidebar">
    <div class="logo"><h2>StudyTwin</h2></div>
    <ul class="menu">
        <li><a href="tutor_dashboard.php">🏠 Dashboard</a></li>
        <li><a href="availability.php">🕒 My Availability</a></li>
        <li><a href="rooms.php">📅 My Rooms / Sessions</a></li>
        <li><a href="bookings.php">📥 Booking Requests</a></li>
        <li><a class="active" href="rewards.php">🎁 Tutor Rewards</a></li>
        <li><a href="profile.php">👤 Tutor Profile</a></li>
        <li class="sep"><a href="../dashboard.php">👨‍🎓 Switch to Student View</a></li>
    </ul>
    <div class="logout"><a href="../logout.php">🚪 Logout</a></div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" title="Toggle sidebar" type="button">☰</button>
            <div>
                <h1>🎁 Tutor Rewards</h1>
                <p>Track your teaching progression, unlock achievements, and climb the tutor ranks.</p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <a href="profile.php" class="topbar-avatar" title="Edit profile">
                <?= $avatar_emoji ?>
                <?php if ($avatar_outfit_emoji): ?>
                <span style="position:absolute;bottom:-4px;right:-4px;font-size:.7rem;background:var(--card);border-radius:6px;padding:0 2px;border:1px solid var(--line);"><?= $avatar_outfit_emoji ?></span>
                <?php endif; ?>
            </a>
            <?php render_theme_toggle(); ?>
        </div>
    </div>

    <?php if ($is_top_ranked): ?>
    <div class="rank-banner">
        <span style="font-size:2rem;"><?= ['🥇','🥈','🥉'][$top_rank_pos - 1] ?? '🏅' ?></span>
        <div>
            <strong>You're on Tutor Top Rank #<?= $top_rank_pos ?>!</strong>
            <div style="font-size:.8rem;color:#92400e;margin-top:2px;">Students can see you on the featured leaderboard.</div>
        </div>
        <a href="../tutortoprank.php" style="margin-left:auto;font-size:.8rem;font-weight:700;color:var(--teal);text-decoration:none;">View leaderboard →</a>
    </div>
    <?php endif; ?>

    <div class="hero">
        <div>
            <div class="hero-label">Teaching XP</div>
            <div class="hero-points"><?= number_format($tutor_xp) ?> <span style="font-size:1.4rem;opacity:.85;">XP</span></div>
            <div class="hero-sub"><?= number_format($teaching_points) ?> Teaching Points · <?= $tutor_unlocked ?>/<?= count($tutor_badges) ?> achievements unlocked</div>
            <div class="hero-level-badge"><?= $tutor_current_level['icon'] ?> <?= htmlspecialchars($tutor_current_level['name']) ?></div>
            <?php if ($tutor_current_level['name'] !== 'Master Tutor'): ?>
            <div class="hero-bar-track"><div class="hero-bar-fill" style="width:<?= $tutor_xp_pct ?>%;"></div></div>
            <div class="hero-bar-label"><?= number_format($tutor_xp_in_level) ?> / <?= number_format($tutor_xp_needed) ?> XP to <?= $tutor_next_level['icon'] ?> <?= htmlspecialchars($tutor_next_level['name']) ?></div>
            <?php else: ?>
            <div class="hero-bar-label" style="margin-top:12px;">🏆 Maximum tutor level reached!</div>
            <?php endif; ?>
        </div>
        <div class="hero-trophy-wrap" style="text-align:center;">
            <div class="hero-trophy"><?= $tutor_current_level['icon'] ?></div>
            <div style="font-size:.78rem;opacity:.8;margin-top:4px;">Current Rank</div>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-mini"><div style="font-size:1.3rem;">✅</div><div class="sm-val"><?= $completed_count ?></div><div class="sm-label">Sessions completed</div></div>
        <div class="stat-mini"><div style="font-size:1.3rem;">👥</div><div class="sm-val"><?= $total_students ?></div><div class="sm-label">Students taught</div></div>
        <div class="stat-mini"><div style="font-size:1.3rem;">⭐</div><div class="sm-val"><?= number_format($tutor_rating, 1) ?></div><div class="sm-label">Tutor rating</div></div>
        <div class="stat-mini"><div style="font-size:1.3rem;">💬</div><div class="sm-val"><?= $review_count ?></div><div class="sm-label">Student reviews</div></div>
    </div>

    <div class="layout">
        <div>
            <div class="card">
                <div class="card-title">🏅 Achievements</div>
                <div class="card-sub">Unlock badges by teaching sessions, helping students, and building your profile.</div>
                <div class="badges-grid">
                    <?php foreach ($tutor_badges as $b): ?>
                    <div class="badge-item <?= $b['unlocked'] ? 'unlocked' : 'locked' ?>" title="<?= htmlspecialchars($b['desc']) ?>">
                        <?php if ($b['unlocked']): ?><span class="badge-check">✓</span><?php endif; ?>
                        <span class="badge-emoji"><?= $b['icon'] ?></span>
                        <span class="badge-name"><?= htmlspecialchars($b['name']) ?></span>
                        <span class="badge-req"><?= htmlspecialchars($b['req']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">📜 Teaching Certificates</div>
                <div class="card-sub">Earn official certificates as you hit session milestones.</div>
                <?php foreach ($tutor_certificates as $c): ?>
                <div class="cert-item <?= $c['unlocked'] ? 'earned' : 'locked' ?>">
                    <span style="font-size:2rem;"><?= $c['unlocked'] ? '📜' : '🔒' ?></span>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:.9rem;"><?= htmlspecialchars($c['title']) ?></div>
                        <div style="font-size:.74rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($c['desc']) ?></div>
                    </div>
                    <span style="font-size:.72rem;color:var(--muted);"><?= $c['sessions'] ?> sessions</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-title">📈 Tutor Levels</div>
                <?php foreach ($tutor_levels as $lvl): ?>
                <div class="level-row <?= $lvl['name'] === $tutor_current_level['name'] ? 'current' : '' ?>">
                    <span style="font-size:1.3rem;"><?= $lvl['icon'] ?></span>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:.84rem;"><?= htmlspecialchars($lvl['name']) ?></div>
                        <div style="font-size:.7rem;color:var(--muted);"><?= $lvl['min'] ?>+ XP</div>
                    </div>
                    <?php if ($lvl['name'] === $tutor_current_level['name']): ?><span class="lv-you">You</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-title">🎁 Level Perks</div>
                <div class="card-sub">Benefits for <?= htmlspecialchars($tutor_current_level['name']) ?></div>
                <?php foreach ($tutor_current_level['perks'] as $perk): ?>
                <div class="perk-row on"><span>✓</span><span style="font-size:.83rem;"><?= htmlspecialchars($perk) ?></span></div>
                <?php endforeach; ?>
                <?php if ($tutor_current_level['name'] !== 'Master Tutor'): ?>
                <div style="font-size:.72rem;color:var(--muted);margin:10px 0 6px;">Unlock at <?= $tutor_next_level['icon'] ?> <?= htmlspecialchars($tutor_next_level['name']) ?>:</div>
                <?php foreach ($tutor_next_level['perks'] as $perk): ?>
                <div class="perk-row off"><span>○</span><span style="font-size:.82rem;"><?= htmlspecialchars($perk) ?></span></div>
                <?php endforeach; endif; ?>
            </div>

            <div class="card">
                <div class="card-title">🎯 Next to Unlock</div>
                <?php if (empty($tutor_next_badges)): ?>
                <p style="font-size:.84rem;color:var(--muted);">🎉 All achievements unlocked — you're a Master Educator!</p>
                <?php else: ?>
                <div class="next-list">
                    <?php foreach (array_slice($tutor_next_badges, 0, 4) as $nb): ?>
                    <div class="next-item">
                        <span><?= $nb['icon'] ?></span>
                        <div>
                            <strong><?= htmlspecialchars($nb['name']) ?></strong>
                            <div style="font-size:.72rem;color:var(--muted);"><?= htmlspecialchars($nb['desc']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">🚀 XP Milestones</div>
                <?php foreach ($tutor_milestones as $m): ?>
                <div class="milestone <?= $tutor_xp >= $m['xp'] ? 'done' : 'locked' ?>">
                    <span style="font-size:1.4rem;"><?= $m['icon'] ?></span>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:.82rem;"><?= htmlspecialchars($m['reward']) ?></div>
                        <div style="font-size:.7rem;color:var(--muted);"><?= htmlspecialchars($m['desc']) ?></div>
                    </div>
                    <span style="font-size:.7rem;font-weight:700;color:var(--muted);"><?= $m['xp'] ?> XP</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-title">💡 How to earn XP</div>
                <div class="next-list">
                    <div class="next-item"><span>✅</span><div><strong>+35 XP</strong> per completed session</div></div>
                    <div class="next-item"><span>👥</span><div><strong>+20 XP</strong> per unique student taught</div></div>
                    <div class="next-item"><span>⭐</span><div><strong>+30 XP</strong> based on your rating</div></div>
                    <div class="next-item"><span>🕒</span><div><strong>+5 XP</strong> per availability slot</div></div>
                    <div class="next-item"><span>🏠</span><div><strong>+15 XP</strong> per study room created</div></div>
                    <div class="next-item"><span>💬</span><div><strong>+10 XP</strong> per student review</div></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>