<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db_connection.php';

$admin_id    = (int)$_SESSION['user_id'];
$admin_name  = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Administrator';
$admin_email = $_SESSION['email'] ?? '';
$current_page = basename($_SERVER['PHP_SELF'], '.php');

function admin_flash_set(string $type, string $message): void {
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_flash_get(): ?array {
    if (empty($_SESSION['admin_flash'])) return null;
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    return $flash;
}

function admin_count(mysqli $conn, string $sql): int {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return (int)($row['c'] ?? $row['total'] ?? 0);
}

function admin_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') $initials .= strtoupper($p[0]);
    }
    return $initials ?: 'AD';
}

function admin_status_badge(string $status): string {
    $map = [
        'pending'   => 'badge-pending',
        'confirmed' => 'badge-confirmed',
        'completed' => 'badge-completed',
        'cancelled' => 'badge-cancelled',
        'paid'      => 'badge-completed',
        'failed'    => 'badge-cancelled',
        'open'      => 'badge-confirmed',
        'closed'    => 'badge-inactive',
    ];
    $cls = $map[$status] ?? 'badge-inactive';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($status) . '</span>';
}

function admin_role_badge(string $role): string {
    $map = ['admin' => 'badge-admin', 'tutor' => 'badge-tutor', 'student' => 'badge-student'];
    $cls = $map[$role] ?? 'badge-inactive';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($role) . '</span>';
}

/** Read sort column and direction from query string. */
function admin_sort_get(string $default_col, string $default_dir = 'desc'): array {
    $dir = strtolower($_GET['dir'] ?? $default_dir);
    if (!in_array($dir, ['asc', 'desc'], true)) $dir = $default_dir;
    $sort = $_GET['sort'] ?? $default_col;
    return ['sort' => $sort, 'dir' => $dir];
}

/** Build ORDER BY clause from a whitelist of column => SQL expression. */
function admin_sort_sql(array $allowed, string $sort, string $dir, string $default_col): string {
    if (!isset($allowed[$sort])) $sort = $default_col;
    $dir = ($dir === 'asc') ? 'ASC' : 'DESC';
    return $allowed[$sort] . ' ' . $dir;
}

/** Collect active list filters/sort params for URLs and form redirects. */
function admin_list_params(array $extra_keys = []): array {
    $params = [];
    foreach (array_merge(['q', 'role', 'status', 'sort', 'dir'], $extra_keys) as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $params[$key] = $_GET[$key];
        }
    }
    return $params;
}

function admin_list_query(array $extra_keys = []): string {
    $q = admin_list_params($extra_keys);
    return $q ? '?' . http_build_query($q) : '';
}

/** URL for toggling sort on a column (click again flips asc/desc). */
function admin_sort_url(string $page, string $column, string $current_sort, string $current_dir): string {
    $params = admin_list_params();
    $params['sort'] = $column;
    $params['dir'] = ($current_sort === $column && $current_dir === 'asc') ? 'desc' : 'asc';
    return $page . '?' . http_build_query($params);
}

/** Render a clickable sortable table header. */
function admin_sort_th(string $label, string $column, string $page, string $current_sort, string $current_dir): string {
    $active = ($current_sort === $column);
    $arrow  = $active ? ($current_dir === 'asc' ? ' ▲' : ' ▼') : '';
    $url    = admin_sort_url($page, $column, $current_sort, $current_dir);
    $cls    = 'sortable' . ($active ? ' sort-active' : '');
    return '<th class="' . $cls . '"><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . $arrow . '</a></th>';
}