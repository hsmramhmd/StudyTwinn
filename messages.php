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
   SELECT CHAT USER
   ========================= */
$receiver_id = isset($_GET['user']) ? intval($_GET['user']) : 0;

/* =========================
   SEND MESSAGE
   ========================= */
if (isset($_POST['send_message']) && $receiver_id > 0) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    if (!empty($message)) {
        mysqli_query($conn, "
            INSERT INTO messages (sender_id, receiver_id, message)
            VALUES ('$user_id','$receiver_id','$message')
        ");
        header("Location: messages.php?user=" . $receiver_id);
        exit();
    }
}

/* =========================
   MARK MESSAGES AS READ
   ========================= */
if ($receiver_id > 0) {
    mysqli_query($conn, "
        UPDATE messages SET is_read = 1
        WHERE sender_id = '$receiver_id'
          AND receiver_id = '$user_id'
          AND is_read = 0
    ");
}

/* =========================
   CONTACT LIST with unread counts
   ========================= */
$users = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.role,
           COUNT(m.id) AS unread_count
    FROM users u
    LEFT JOIN messages m
        ON m.sender_id = u.id
       AND m.receiver_id = '$user_id'
       AND m.is_read = 0
    WHERE u.id != '$user_id'
    GROUP BY u.id, u.full_name, u.role
    ORDER BY unread_count DESC, u.full_name ASC
");

/* =========================
   RECEIVER INFO
   ========================= */
$receiver_name = '';
$receiver_role = '';
if ($receiver_id > 0) {
    $rr = mysqli_query($conn, "SELECT full_name, role FROM users WHERE id='$receiver_id'");
    if ($rr && $rv = mysqli_fetch_assoc($rr)) {
        $receiver_name = $rv['full_name'];
        $receiver_role = $rv['role'];
    }
}

/* =========================
   CHAT HISTORY
   ========================= */
$chat = null;
if ($receiver_id > 0) {
    $chat = mysqli_query($conn, "
        SELECT m.*, u.full_name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id='$user_id' AND m.receiver_id='$receiver_id')
           OR (m.sender_id='$receiver_id' AND m.receiver_id='$user_id')
        ORDER BY m.created_at ASC
    ");
}

/* =========================
   TOTAL UNREAD (for topbar badge)
   ========================= */
$unread_res   = mysqli_query($conn, "SELECT COUNT(*) AS c FROM messages WHERE receiver_id='$user_id' AND is_read=0");
$total_unread = $unread_res ? (int)mysqli_fetch_assoc($unread_res)['c'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — StudyTwin</title>
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
    text-decoration:none; background:var(--orange-pale); color:var(--orange); font-weight:600;
}
:root[data-theme="dark"] .logout a {
    background: #3f2a1f !important;
    color: #fb923c !important;
}
:root[data-theme="dark"] .logout { background: var(--sidebar-bg, #0f172a) !important; }

/* ── MAIN ── */
.main{ margin-left:260px; width:calc(100% - 260px); padding:32px 36px 50px; display:flex; flex-direction:column; height:100vh; }

/* ── TOPBAR ── */
.topbar{
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:24px; flex-shrink:0; animation:fadeSlideIn .5s var(--ease) both;
}
.topbar-left h1{ font-family:'Lexend',sans-serif; font-size:1.7rem; font-weight:700; }
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
.unread-badge-top{
    position:absolute; top:-5px; right:-5px;
    background:var(--orange); color:white;
    font-size:.6rem; font-weight:700;
    width:18px; height:18px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    border:2px solid white;
}

/* ── CHAT LAYOUT ── */
.chat-layout{
    display:grid;
    grid-template-columns:300px 1fr;
    gap:20px;
    flex:1;
    min-height:0;
    animation:fadeSlideIn .5s var(--ease) .05s both;
}

/* ── CONTACTS PANEL ── */
.users-card{
    background:white; border-radius:var(--radius);
    box-shadow:var(--shadow); border:1px solid var(--line);
    display:flex; flex-direction:column; overflow:hidden;
}
.users-card-header{
    padding:18px 20px 12px;
    border-bottom:1px solid var(--line);
    font-family:'Lexend',sans-serif; font-size:1rem; font-weight:700;
    flex-shrink:0;
}
.contacts-list{
    overflow-y:auto; flex:1; padding:10px;
}
.contact{
    display:flex; align-items:center; gap:12px;
    padding:11px 12px; border-radius:var(--radius-sm);
    margin-bottom:4px; text-decoration:none; color:var(--text);
    transition:background .2s var(--ease);
    position:relative;
}
.contact:hover{ background:var(--teal-pale); }
.contact.active-contact{ background:var(--teal-pale); border-left:3px solid var(--teal); }
.contact-avatar{
    width:40px; height:40px; border-radius:12px;
    background:linear-gradient(135deg,var(--teal),var(--teal-light));
    color:white; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.95rem; flex-shrink:0;
}
.contact-info{ flex:1; min-width:0; }
.contact-name{ font-size:.88rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.contact-role{ font-size:.74rem; color:var(--muted); margin-top:1px; }
.unread-badge{
    background:var(--orange); color:white;
    font-size:.65rem; font-weight:700;
    min-width:20px; height:20px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    padding:0 5px; flex-shrink:0;
}

/* ── CHAT PANEL ── */
.chat-card{
    background:white; border-radius:var(--radius);
    box-shadow:var(--shadow); border:1px solid var(--line);
    display:flex; flex-direction:column; overflow:hidden;
}

/* Chat header */
.chat-header{
    padding:16px 22px;
    border-bottom:1px solid var(--line);
    display:flex; align-items:center; gap:14px;
    flex-shrink:0;
}
.chat-header-avatar{
    width:42px; height:42px; border-radius:12px;
    background:linear-gradient(135deg,var(--teal),var(--teal-light));
    color:white; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:1rem; flex-shrink:0;
}
.chat-header-name{ font-family:'Lexend',sans-serif; font-weight:700; font-size:.98rem; }
.chat-header-role{ font-size:.75rem; color:var(--muted); margin-top:2px; }

/* Messages */
.chat-box{
    flex:1; overflow-y:auto; padding:20px;
    display:flex; flex-direction:column; gap:10px;
    min-height:0;
}

/* Date separator */
.date-sep{
    text-align:center; font-size:.72rem; color:var(--muted);
    margin:6px 0; position:relative;
}
.date-sep::before{
    content:''; position:absolute; left:0; top:50%;
    width:calc(50% - 60px); height:1px; background:var(--line);
}
.date-sep::after{
    content:''; position:absolute; right:0; top:50%;
    width:calc(50% - 60px); height:1px; background:var(--line);
}

/* Bubbles */
.bubble-wrap{
    display:flex; flex-direction:column;
    max-width:68%;
}
.bubble-wrap.mine{ align-self:flex-end; align-items:flex-end; }
.bubble-wrap.theirs{ align-self:flex-start; align-items:flex-start; }

.bubble-sender{ font-size:.71rem; color:var(--muted); font-weight:600; margin-bottom:3px; padding:0 4px; }

.bubble{
    padding:11px 15px; border-radius:16px;
    font-size:.88rem; line-height:1.55; word-break:break-word;
}
.bubble-wrap.mine .bubble{
    background:linear-gradient(135deg,var(--orange),var(--orange-light));
    color:white;
    border-bottom-right-radius:4px;
}
.bubble-wrap.theirs .bubble{
    background:var(--teal-pale);
    color:var(--text);
    border-bottom-left-radius:4px;
}
.bubble-time{
    font-size:.68rem; color:var(--muted);
    margin-top:3px; padding:0 4px;
}

/* Message input */
.message-form{
    display:flex; gap:10px; padding:16px 20px;
    border-top:1px solid var(--line); flex-shrink:0;
    align-items:center;
}
.message-form input{
    flex:1; padding:12px 16px;
    border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-size:.9rem; color:var(--text); outline:none;
    transition:border-color .2s var(--ease);
}
.message-form input:focus{ border-color:var(--teal-light); }
.message-form button{
    padding:12px 22px; border:none;
    background:linear-gradient(135deg,var(--orange),var(--orange-light));
    color:white; font-weight:700; border-radius:var(--radius-sm);
    cursor:pointer; transition:opacity .2s var(--ease), transform .2s var(--ease);
    white-space:nowrap;
}
.message-form button:hover{ opacity:.9; transform:translateY(-1px); }

/* Empty states */
.empty-chat{
    flex:1; display:flex; justify-content:center; align-items:center;
    flex-direction:column; gap:12px; color:var(--muted); text-align:center; padding:40px;
}
.empty-chat .empty-icon{ font-size:3rem; }
.empty-chat h3{ font-family:'Lexend',sans-serif; font-size:1.1rem; color:var(--text); }
.empty-chat p{ font-size:.88rem; }

.no-messages{
    flex:1; display:flex; justify-content:center; align-items:center;
    flex-direction:column; gap:8px; color:var(--muted);
}
.no-messages .nm-icon{ font-size:2.2rem; }
.no-messages p{ font-size:.85rem; }

/* ── RESPONSIVE ── */
@media(max-width:900px){
    .sidebar{ width:80px;  overflow-y:hidden; }
    .logo h2{ display:none; }
    .main{ margin-left:80px; width:calc(100% - 80px); padding:20px 16px 30px; }
    .chat-layout{ grid-template-columns:1fr; }
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
        <li><a class="active" href="messages.php">💬 Messages</a></li>
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
            <h1>Messages 💬</h1>
            <p>Connect and chat with tutors and students.</p>
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
    <!-- CHAT LAYOUT -->
    <div class="chat-layout">

        <!-- CONTACTS PANEL -->
        <div class="users-card">
            <div class="users-card-header">👥 Contacts</div>
            <div class="contacts-list">
                <?php if (mysqli_num_rows($users) === 0): ?>
                    <p style="padding:16px;font-size:.85rem;color:var(--muted);">No contacts yet.</p>
                <?php else: ?>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <a
                            class="contact <?php echo ($u['id'] == $receiver_id) ? 'active-contact' : ''; ?>"
                            href="messages.php?user=<?php echo $u['id']; ?>"
                        >
                            <div class="contact-avatar">
                                <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                            </div>
                            <div class="contact-info">
                                <div class="contact-name"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                <div class="contact-role"><?php echo ucfirst($u['role']); ?></div>
                            </div>
                            <?php if ((int)$u['unread_count'] > 0): ?>
                                <span class="unread-badge">
                                    <?php echo (int)$u['unread_count'] > 9 ? '9+' : (int)$u['unread_count']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHAT PANEL -->
        <div class="chat-card">

            <?php if ($receiver_id > 0): ?>

                <!-- Chat header -->
                <div class="chat-header">
                    <div class="chat-header-avatar">
                        <?php echo strtoupper(substr($receiver_name, 0, 1)); ?>
                    </div>
                    <div>
                        <div class="chat-header-name"><?php echo htmlspecialchars($receiver_name); ?></div>
                        <div class="chat-header-role"><?php echo ucfirst($receiver_role); ?></div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-box" id="chatBox">
                    <?php
                    $prev_date = '';
                    $msg_count = 0;
                    if ($chat) {
                        while ($msg = mysqli_fetch_assoc($chat)):
                            $msg_count++;
                            $is_mine  = ($msg['sender_id'] == $user_id);
                            $msg_date = date("d M Y", strtotime($msg['created_at']));
                            $msg_time = date("g:i A", strtotime($msg['created_at']));
                    ?>
                        <?php if ($msg_date !== $prev_date): ?>
                            <div class="date-sep"><?php echo $msg_date; ?></div>
                            <?php $prev_date = $msg_date; ?>
                        <?php endif; ?>

                        <div class="bubble-wrap <?php echo $is_mine ? 'mine' : 'theirs'; ?>">
                            <?php if (!$is_mine): ?>
                                <div class="bubble-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                            <?php endif; ?>
                            <div class="bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            <div class="bubble-time"><?php echo $msg_time; ?></div>
                        </div>

                    <?php endwhile; } ?>

                    <?php if ($msg_count === 0): ?>
                        <div class="no-messages">
                            <div class="nm-icon">💬</div>
                            <p>No messages yet. Say hello!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Input -->
                <form method="POST" class="message-form" id="msgForm">
                    <input
                        type="text"
                        name="message"
                        id="msgInput"
                        placeholder="Type a message…"
                        autocomplete="off"
                        required
                    >
                    <button type="submit" name="send_message">Send ➤</button>
                </form>

            <?php else: ?>

                <div class="empty-chat">
                    <div class="empty-icon">💬</div>
                    <h3>No conversation selected</h3>
                    <p>Pick a contact on the left to start chatting.</p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>
/* Auto-scroll to bottom of chat */
const chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

/* Submit on Enter key (Shift+Enter = newline if textarea — here it's input so just Enter) */
const msgInput = document.getElementById('msgInput');
if (msgInput) {
    msgInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('msgForm').submit();
        }
    });
}
</script>

</body>
</html>