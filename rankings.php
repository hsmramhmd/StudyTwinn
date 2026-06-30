<?php
require_once __DIR__ . '/init.php';

$page_title    = 'Top Rankings';
$page_subtitle = 'Curate the featured tutors on Tutor Top Rank page';

$rankings = [];
$rank_res = mysqli_query($conn, "
    SELECT tr.rank_position, tr.rating, t.id AS tutor_id, u.full_name, t.subject
    FROM tutor_top_rank tr
    JOIN tutors t ON tr.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    ORDER BY tr.rank_position ASC
");
if ($rank_res) while ($row = mysqli_fetch_assoc($rank_res)) $rankings[(int)$row['rank_position']] = $row;

$all_tutors = mysqli_query($conn, "
    SELECT t.id, u.full_name, t.subject, t.rating
    FROM tutors t JOIN users u ON t.user_id = u.id
    ORDER BY u.full_name ASC
");

$perf_sort = admin_sort_get('rating', 'desc');
$perf_order = admin_sort_sql([
    'name'     => 'u.full_name',
    'subject'  => 't.subject',
    'rating'   => 't.rating',
    'bookings' => 'bookings',
], $perf_sort['sort'], $perf_sort['dir'], 'rating');
$perf_list_q = admin_list_query();

include __DIR__ . '/includes/header.php';
?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Current Top 3</h3></div>
        <div class="card-body">
            <?php for ($pos = 1; $pos <= 3; $pos++):
                $medal = ['🥇','🥈','🥉'][$pos - 1];
                $slot  = $rankings[$pos] ?? null;
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:1.4rem;"><?= $medal ?></span>
                    <div>
                        <?php if ($slot): ?>
                        <strong><?= htmlspecialchars($slot['full_name']) ?></strong>
                        <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($slot['subject']) ?> · ⭐ <?= number_format((float)$slot['rating'], 1) ?></div>
                        <?php else: ?>
                        <span style="color:var(--muted);">Slot empty</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($slot): ?>
                <form method="POST" action="actions.php" onsubmit="return confirm('Remove rank #<?= $pos ?>?');">
                    <input type="hidden" name="action" value="remove_ranking">
                    <input type="hidden" name="rank_position" value="<?= $pos ?>">
                    <input type="hidden" name="redirect" value="rankings.php<?= $perf_list_q ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
            <p style="font-size:.78rem;color:var(--muted);margin-top:14px;">These appear on <a href="../tutortoprank.php" style="color:var(--teal);">tutortoprank.php</a> for all students.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Assign Ranking</h3></div>
        <div class="card-body">
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="set_ranking">
                <input type="hidden" name="redirect" value="rankings.php<?= $perf_list_q ?>">
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Rank Position</label>
                    <select name="rank_position">
                        <option value="1">🥇 #1 — Gold</option>
                        <option value="2">🥈 #2 — Silver</option>
                        <option value="3">🥉 #3 — Bronze</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Tutor</label>
                    <select name="tutor_id" required>
                        <option value="">— Select tutor —</option>
                        <?php if ($all_tutors): mysqli_data_seek($all_tutors, 0);
                            while ($t = mysqli_fetch_assoc($all_tutors)): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?> — <?= htmlspecialchars($t['subject']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Display Rating</label>
                    <input type="number" name="rating" value="4.8" min="0" max="5" step="0.1">
                </div>
                <button type="submit" class="btn btn-teal">Assign Rank</button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Tutors — By Performance</h3>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <select name="sort">
                <option value="rating" <?= $perf_sort['sort'] === 'rating' ? 'selected' : '' ?>>Rating</option>
                <option value="bookings" <?= $perf_sort['sort'] === 'bookings' ? 'selected' : '' ?>>Bookings</option>
                <option value="name" <?= $perf_sort['sort'] === 'name' ? 'selected' : '' ?>>Name</option>
                <option value="subject" <?= $perf_sort['sort'] === 'subject' ? 'selected' : '' ?>>Subject</option>
            </select>
            <select name="dir">
                <option value="desc" <?= $perf_sort['dir'] === 'desc' ? 'selected' : '' ?>>Desc</option>
                <option value="asc" <?= $perf_sort['dir'] === 'asc' ? 'selected' : '' ?>>Asc</option>
            </select>
            <button type="submit" class="btn btn-sm">Sort</button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <?= admin_sort_th('Tutor', 'name', 'rankings.php', $perf_sort['sort'], $perf_sort['dir']) ?>
                <?= admin_sort_th('Subject', 'subject', 'rankings.php', $perf_sort['sort'], $perf_sort['dir']) ?>
                <?= admin_sort_th('Rating', 'rating', 'rankings.php', $perf_sort['sort'], $perf_sort['dir']) ?>
                <?= admin_sort_th('Bookings', 'bookings', 'rankings.php', $perf_sort['sort'], $perf_sort['dir']) ?>
                <th>Quick Assign</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $perf = mysqli_query($conn, "
            SELECT t.id, u.full_name, t.subject, t.rating,
                   COUNT(b.id) AS bookings
            FROM tutors t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN bookings b ON b.tutor_id = t.id
            GROUP BY t.id, u.full_name, t.subject, t.rating
            ORDER BY $perf_order
            LIMIT 50
        ");
        if ($perf): while ($t = mysqli_fetch_assoc($perf)): ?>
        <tr>
            <td><?= htmlspecialchars($t['full_name']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($t['subject']) ?></td>
            <td>⭐ <?= number_format((float)$t['rating'], 1) ?></td>
            <td style="color:var(--muted);"><?= (int)$t['bookings'] ?></td>
            <td>
                <form method="POST" action="actions.php" style="display:inline-flex;gap:4px;">
                    <input type="hidden" name="action" value="set_ranking">
                    <input type="hidden" name="tutor_id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="rating" value="<?= (float)$t['rating'] ?>">
                    <input type="hidden" name="redirect" value="rankings.php<?= $perf_list_q ?>">
                    <select name="rank_position" style="padding:4px 8px;font-size:.75rem;border-radius:6px;">
                        <option value="1">#1</option>
                        <option value="2">#2</option>
                        <option value="3">#3</option>
                    </select>
                    <button type="submit" class="btn btn-sm">Set</button>
                </form>
            </td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>