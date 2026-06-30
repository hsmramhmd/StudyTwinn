<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
include(__DIR__ . "/db_connection.php");

$user_id    = $_SESSION['user_id'];
$full_name  = $_SESSION['name'];
$role       = $_SESSION['role'];
$user_email = $_SESSION['email'];

/* AVATAR THEMES */
$_avatar_color_themes = [
    'orange'   => ['label' => 'Ember Fox',  'grad' => 'linear-gradient(135deg,#f0672b,#ffb26b)', 'ring' => '#f0672b', 'pale' => '#fff3ee', 'hex' => '#f0672b'],
    'teal'     => ['label' => 'Ocean Fox',  'grad' => 'linear-gradient(135deg,#116979,#1b90a5)', 'ring' => '#116979', 'pale' => '#eaf6f8', 'hex' => '#116979'],
    'purple'   => ['label' => 'Mystic Fox', 'grad' => 'linear-gradient(135deg,#7c3aed,#a78bfa)', 'ring' => '#7c3aed', 'pale' => '#f5f3ff', 'hex' => '#7c3aed'],
    'rose'     => ['label' => 'Cherry Fox', 'grad' => 'linear-gradient(135deg,#e11d48,#fb7185)', 'ring' => '#e11d48', 'pale' => '#fff1f2', 'hex' => '#e11d48'],
    'green'    => ['label' => 'Forest Fox', 'grad' => 'linear-gradient(135deg,#16a34a,#4ade80)', 'ring' => '#16a34a', 'pale' => '#f0fdf4', 'hex' => '#16a34a'],
    'midnight' => ['label' => 'Night Fox',  'grad' => 'linear-gradient(135deg,#1e293b,#475569)', 'ring' => '#1e293b', 'pale' => '#f1f5f9', 'hex' => '#1e293b'],
    'gold'     => ['label' => 'Golden Fox', 'grad' => 'linear-gradient(135deg,#b45309,#fbbf24)', 'ring' => '#b45309', 'pale' => '#fffbeb', 'hex' => '#b45309'],
    'sky'      => ['label' => 'Sky Fox',    'grad' => 'linear-gradient(135deg,#0284c7,#38bdf8)', 'ring' => '#0284c7', 'pale' => '#f0f9ff', 'hex' => '#0284c7'],
];
if (!isset($_SESSION['avatar_color']) && isset($conn) && isset($user_id)) {
    $ac_res = mysqli_query($conn, "SELECT avatar_color FROM users WHERE id='$user_id' LIMIT 1");
    if ($ac_res) {
        $ac_row = mysqli_fetch_assoc($ac_res);
        $_SESSION['avatar_color'] = $ac_row['avatar_color'] ?? 'orange';
    }
}
$avatar_color = $_SESSION['avatar_color'] ?? 'orange';
if (!array_key_exists($avatar_color, $_avatar_color_themes)) $avatar_color = 'orange';
$avatar_theme = $_avatar_color_themes[$avatar_color];

/* rest of your dashboard code continues below... */