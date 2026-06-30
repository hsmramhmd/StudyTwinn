<?php
require_once __DIR__ . '/init.php';

$page_title    = 'Broadcast';
$page_subtitle = 'Send system notifications to users';

$recent = mysqli_query($conn, "
    SELECT n.title, n.message, n.created_at, COUNT(*) AS sent_count,
           MIN(u.role) AS sample_role
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    GROUP BY n.title, n.message, n.created_at
    ORDER BY n.created_at DESC
    LIMIT 10
");

include __DIR__ . '/includes/header.php';
?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Send Notification</h3></div>
        <div class="card-body">
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="broadcast_notification">
                <input type="hidden" name="redirect" value="notifications.php">
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Target Audience</label>
                    <select name="target_role">
                        <option value="all">All users</option>
                        <option value="student">Students only</option>
                        <option value="tutor">Tutors only</option>
                        <option value="admin">Admins only</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="e.g. System Maintenance" required maxlength="255">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Message</label>
                    <textarea name="message" rows="4" placeholder="Write your announcement…" required style="resize:vertical;"></textarea>
                </div>
                <button type="submit" class="btn btn-teal" onclick="return confirm('Send this notification to the selected audience?');">Broadcast</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Suggested Announcements</h3></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="padding:12px;background:var(--teal-pale);border-radius:10px;font-size:.84rem;">
                    <strong>📅 New semester slots</strong><br>
                    <span style="color:var(--muted);">Inform students that new tutor availability has been added for the upcoming semester.</span>
                </div>
                <div style="padding:12px;background:var(--orange-pale);border-radius:10px;font-size:.84rem;">
                    <strong>🏆 Leaderboard update</strong><br>
                    <span style="color:var(--muted);">Announce top performers and encourage students to complete more sessions for XP.</span>
                </div>
                <div style="padding:12px;background:var(--surface-alt,#f1f5f9);border-radius:10px;font-size:.84rem;border:1px solid var(--line);">
                    <strong>🔧 Scheduled maintenance</strong><br>
                    <span style="color:var(--muted);">Notify all users about planned downtime or system updates.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Recent Broadcasts</h3></div>
    <table>
        <thead><tr><th>Title</th><th>Message</th><th>Recipients</th><th>Sent</th></tr></thead>
        <tbody>
        <?php if ($recent && mysqli_num_rows($recent) > 0):
            while ($n = mysqli_fetch_assoc($recent)): ?>
        <tr>
            <td><strong><?= htmlspecialchars($n['title']) ?></strong></td>
            <td style="color:var(--muted);max-width:300px;"><?= htmlspecialchars(mb_strimwidth($n['message'], 0, 80, '…')) ?></td>
            <td><?= (int)$n['sent_count'] ?> user(s)</td>
            <td style="color:var(--muted);"><?= date('M j, Y g:ia', strtotime($n['created_at'])) ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="4" class="empty-state">No broadcasts sent yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>