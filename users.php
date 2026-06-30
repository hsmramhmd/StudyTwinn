<?php
require_once __DIR__ . '/init.php';

$page_title    = 'Users';
$page_subtitle = 'Manage all StudyTwin accounts';

$role_filter = $_GET['role'] ?? '';
$search      = trim($_GET['q'] ?? '');
$sort        = admin_sort_get('joined', 'desc');

$where = ['1=1'];
if (in_array($role_filter, ['student', 'tutor', 'admin'], true)) {
    $where[] = "u.role = '" . mysqli_real_escape_string($conn, $role_filter) . "'";
}
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(u.full_name LIKE '%$s%' OR u.email LIKE '%$s%')";
}
$where_sql = implode(' AND ', $where);

$order_sql = admin_sort_sql([
    'name'     => 'u.full_name',
    'email'    => 'u.email',
    'role'     => 'u.role',
    'bookings' => 'booking_count',
    'joined'   => 'u.created_at',
], $sort['sort'], $sort['dir'], 'joined');

$users_res = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.role, u.created_at,
           (SELECT COUNT(*) FROM bookings b WHERE b.student_id = u.id) AS booking_count
    FROM users u
    WHERE $where_sql
    ORDER BY $order_sql
");

$total = $users_res ? mysqli_num_rows($users_res) : 0;
$list_q = admin_list_query();

include __DIR__ . '/includes/header.php';
?>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="text" name="q" placeholder="Search name or email…" value="<?= htmlspecialchars($search) ?>">
        <select name="role">
            <option value="">All roles</option>
            <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
            <option value="tutor" <?= $role_filter === 'tutor' ? 'selected' : '' ?>>Tutors</option>
            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
        </select>
        <select name="sort" title="Sort by">
            <option value="joined" <?= $sort['sort'] === 'joined' ? 'selected' : '' ?>>Joined date</option>
            <option value="name" <?= $sort['sort'] === 'name' ? 'selected' : '' ?>>Name</option>
            <option value="email" <?= $sort['sort'] === 'email' ? 'selected' : '' ?>>Email</option>
            <option value="role" <?= $sort['sort'] === 'role' ? 'selected' : '' ?>>Role</option>
            <option value="bookings" <?= $sort['sort'] === 'bookings' ? 'selected' : '' ?>>Bookings</option>
        </select>
        <select name="dir">
            <option value="asc" <?= $sort['dir'] === 'asc' ? 'selected' : '' ?>>Ascending</option>
            <option value="desc" <?= $sort['dir'] === 'desc' ? 'selected' : '' ?>>Descending</option>
        </select>
        <button type="submit" class="btn btn-teal">Apply</button>
        <?php if ($search || $role_filter): ?>
        <a href="users.php" class="btn">Clear</a>
        <?php endif; ?>
    </form>
    <span class="sort-hint"><?= $total ?> user(s) · click column headers to sort</span>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <?= admin_sort_th('User', 'name', 'users.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Email', 'email', 'users.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Role', 'role', 'users.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Bookings', 'bookings', 'users.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Joined', 'joined', 'users.php', $sort['sort'], $sort['dir']) ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($users_res && mysqli_num_rows($users_res) > 0):
            while ($u = mysqli_fetch_assoc($users_res)): ?>
        <tr>
            <td>
                <div class="user-cell">
                    <div class="user-av"><?= admin_initials($u['full_name']) ?></div>
                    <?= htmlspecialchars($u['full_name']) ?>
                </div>
            </td>
            <td style="color:var(--muted);"><?= htmlspecialchars($u['email']) ?></td>
            <td><?= admin_role_badge($u['role']) ?></td>
            <td style="color:var(--muted);"><?= (int)$u['booking_count'] ?></td>
            <td style="color:var(--muted);"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ((int)$u['id'] !== $admin_id): ?>
                <div class="action-group">
                    <form method="POST" action="actions.php" style="display:inline-flex;gap:4px;align-items:center;">
                        <input type="hidden" name="action" value="update_user_role">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="redirect" value="users.php<?= $list_q ?>">
                        <select name="role" style="padding:5px 8px;font-size:.75rem;border-radius:6px;border:1px solid var(--line);">
                            <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>student</option>
                            <option value="tutor" <?= $u['role'] === 'tutor' ? 'selected' : '' ?>>tutor</option>
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-teal">Save</button>
                    </form>
                    <form method="POST" action="actions.php" onsubmit="return confirm('Delete this user permanently?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="redirect" value="users.php<?= $list_q ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
                <?php else: ?>
                <span style="color:var(--muted);font-size:.8rem;">You</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6" class="empty-state">No users match your filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>