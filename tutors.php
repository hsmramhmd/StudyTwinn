<?php
require_once __DIR__ . '/init.php';

$page_title    = 'Tutors';
$page_subtitle = 'Manage tutor profiles, ratings, and pricing';

$search = trim($_GET['q'] ?? '');
$sort   = admin_sort_get('rating', 'desc');

$where = '1=1';
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where = "(u.full_name LIKE '%$s%' OR t.subject LIKE '%$s%' OR t.expertise LIKE '%$s%')";
}

$order_sql = admin_sort_sql([
    'name'     => 'u.full_name',
    'subject'  => 't.subject',
    'rating'   => 't.rating',
    'price'    => 't.price_per_hour',
    'bookings' => 'total_bookings',
    'completed'=> 'completed',
    'slots'    => 'slots',
], $sort['sort'], $sort['dir'], 'rating');

$tutors = mysqli_query($conn, "
    SELECT t.id, t.subject, t.expertise, t.bio, t.rating, t.price_per_hour,
           u.id AS user_id, u.full_name, u.email,
           (SELECT COUNT(*) FROM bookings b WHERE b.tutor_id = t.id) AS total_bookings,
           (SELECT COUNT(*) FROM bookings b WHERE b.tutor_id = t.id AND b.status='completed') AS completed,
           (SELECT COUNT(*) FROM availability a WHERE a.tutor_id = t.id) AS slots
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE $where
    ORDER BY $order_sql
");

$total = $tutors ? mysqli_num_rows($tutors) : 0;
$list_q = admin_list_query();

include __DIR__ . '/includes/header.php';
?>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="text" name="q" placeholder="Search tutor or subject…" value="<?= htmlspecialchars($search) ?>">
        <select name="sort">
            <option value="rating" <?= $sort['sort'] === 'rating' ? 'selected' : '' ?>>Rating</option>
            <option value="name" <?= $sort['sort'] === 'name' ? 'selected' : '' ?>>Name</option>
            <option value="subject" <?= $sort['sort'] === 'subject' ? 'selected' : '' ?>>Subject</option>
            <option value="price" <?= $sort['sort'] === 'price' ? 'selected' : '' ?>>Price</option>
            <option value="bookings" <?= $sort['sort'] === 'bookings' ? 'selected' : '' ?>>Total bookings</option>
            <option value="completed" <?= $sort['sort'] === 'completed' ? 'selected' : '' ?>>Completed sessions</option>
            <option value="slots" <?= $sort['sort'] === 'slots' ? 'selected' : '' ?>>Availability slots</option>
        </select>
        <select name="dir">
            <option value="asc" <?= $sort['dir'] === 'asc' ? 'selected' : '' ?>>Ascending</option>
            <option value="desc" <?= $sort['dir'] === 'desc' ? 'selected' : '' ?>>Descending</option>
        </select>
        <button type="submit" class="btn btn-teal">Apply</button>
        <?php if ($search): ?><a href="tutors.php" class="btn">Clear</a><?php endif; ?>
    </form>
    <span class="sort-hint"><?= $total ?> tutor(s) · click column headers to sort</span>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <?= admin_sort_th('Tutor', 'name', 'tutors.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Subject', 'subject', 'tutors.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Rating', 'rating', 'tutors.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Price/hr', 'price', 'tutors.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Bookings', 'bookings', 'tutors.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Slots', 'slots', 'tutors.php', $sort['sort'], $sort['dir']) ?>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($tutors && mysqli_num_rows($tutors) > 0):
            while ($t = mysqli_fetch_assoc($tutors)): ?>
        <tr>
            <td>
                <div class="user-cell">
                    <div class="user-av"><?= admin_initials($t['full_name']) ?></div>
                    <div>
                        <div><?= htmlspecialchars($t['full_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--muted);"><?= htmlspecialchars($t['email']) ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div><?= htmlspecialchars($t['subject']) ?></div>
                <?php if ($t['expertise']): ?>
                <div style="font-size:.72rem;color:var(--muted);"><?= htmlspecialchars($t['expertise']) ?></div>
                <?php endif; ?>
            </td>
            <td>⭐ <?= number_format((float)$t['rating'], 1) ?></td>
            <td>RM <?= number_format((float)$t['price_per_hour'], 2) ?></td>
            <td style="color:var(--muted);"><?= (int)$t['completed'] ?>/<?= (int)$t['total_bookings'] ?> done</td>
            <td style="color:var(--muted);"><?= (int)$t['slots'] ?></td>
            <td>
                <form method="POST" action="actions.php" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <input type="hidden" name="action" value="update_tutor">
                    <input type="hidden" name="tutor_id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="redirect" value="tutors.php<?= $list_q ?>">
                    <input type="text" name="subject" value="<?= htmlspecialchars($t['subject']) ?>" style="width:120px;padding:5px 8px;font-size:.75rem;border-radius:6px;border:1px solid var(--line);">
                    <input type="number" name="rating" value="<?= (float)$t['rating'] ?>" min="0" max="5" step="0.1" style="width:60px;padding:5px 8px;font-size:.75rem;border-radius:6px;border:1px solid var(--line);">
                    <input type="number" name="price_per_hour" value="<?= (float)$t['price_per_hour'] ?>" min="0" step="1" style="width:70px;padding:5px 8px;font-size:.75rem;border-radius:6px;border:1px solid var(--line);">
                    <button type="submit" class="btn btn-sm btn-teal">Save</button>
                </form>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" class="empty-state">No tutors found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>