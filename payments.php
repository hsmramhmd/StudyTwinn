<?php
require_once __DIR__ . '/init.php';

$page_title    = 'Payments';
$page_subtitle = 'Track revenue and payment records';

$status_filter = $_GET['status'] ?? '';
$sort          = admin_sort_get('paid_at', 'desc');

$where = '1=1';
if (in_array($status_filter, ['paid', 'pending', 'failed'], true)) {
    $where = "p.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

$order_sql = admin_sort_sql([
    'id'       => 'p.id',
    'student'  => 'u.full_name',
    'subject'  => 'b.subject',
    'session'  => 'b.session_date',
    'amount'   => 'p.amount',
    'method'   => 'p.payment_method',
    'status'   => 'p.status',
    'paid_at'  => 'p.paid_at',
], $sort['sort'], $sort['dir'], 'paid_at');

$total_paid = 0;
$paid_res = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS c FROM payments WHERE status='paid'");
if ($paid_res) $total_paid = (float)mysqli_fetch_assoc($paid_res)['c'];

$pending_count = admin_count($conn, "SELECT COUNT(*) AS c FROM payments WHERE status='pending'");

$payments = mysqli_query($conn, "
    SELECT p.id, p.amount, p.payment_method, p.status, p.paid_at,
           u.full_name AS student_name,
           b.subject, b.session_date
    FROM payments p
    JOIN users u ON p.student_id = u.id
    JOIN bookings b ON p.booking_id = b.id
    WHERE $where
    ORDER BY $order_sql
");

$total = $payments ? mysqli_num_rows($payments) : 0;
$list_q = admin_list_query();

function payment_filter_link(string $status, string $sort, string $dir): string {
    $params = ['sort' => $sort, 'dir' => $dir];
    if ($status !== '') $params['status'] = $status;
    return 'payments.php?' . http_build_query($params);
}

include __DIR__ . '/includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card teal">
        <div class="label">Total Revenue</div>
        <div class="value">RM <?= number_format($total_paid, 0) ?></div>
        <div class="hint">All paid transactions</div>
    </div>
    <div class="stat-card">
        <div class="label">Pending Payments</div>
        <div class="value"><?= $pending_count ?></div>
        <div class="hint">Awaiting confirmation</div>
    </div>
    <div class="stat-card orange">
        <div class="label">Transactions</div>
        <div class="value"><?= admin_count($conn, "SELECT COUNT(*) AS c FROM payments") ?></div>
        <div class="hint">All payment records</div>
    </div>
</div>

<div class="filter-bar">
    <a href="<?= payment_filter_link('', $sort['sort'], $sort['dir']) ?>" class="btn <?= $status_filter === '' ? 'btn-teal' : '' ?>">All</a>
    <a href="<?= payment_filter_link('paid', $sort['sort'], $sort['dir']) ?>" class="btn <?= $status_filter === 'paid' ? 'btn-teal' : '' ?>">Paid</a>
    <a href="<?= payment_filter_link('pending', $sort['sort'], $sort['dir']) ?>" class="btn <?= $status_filter === 'pending' ? 'btn-teal' : '' ?>">Pending</a>
    <a href="<?= payment_filter_link('failed', $sort['sort'], $sort['dir']) ?>" class="btn <?= $status_filter === 'failed' ? 'btn-teal' : '' ?>">Failed</a>
    <form method="GET" style="display:inline-flex;gap:8px;margin-left:auto;align-items:center;">
        <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>"><?php endif; ?>
        <select name="sort">
            <option value="paid_at" <?= $sort['sort'] === 'paid_at' ? 'selected' : '' ?>>Paid date</option>
            <option value="amount" <?= $sort['sort'] === 'amount' ? 'selected' : '' ?>>Amount</option>
            <option value="student" <?= $sort['sort'] === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="subject" <?= $sort['sort'] === 'subject' ? 'selected' : '' ?>>Subject</option>
            <option value="session" <?= $sort['sort'] === 'session' ? 'selected' : '' ?>>Session date</option>
            <option value="method" <?= $sort['sort'] === 'method' ? 'selected' : '' ?>>Method</option>
            <option value="status" <?= $sort['sort'] === 'status' ? 'selected' : '' ?>>Status</option>
        </select>
        <select name="dir">
            <option value="asc" <?= $sort['dir'] === 'asc' ? 'selected' : '' ?>>Asc</option>
            <option value="desc" <?= $sort['dir'] === 'desc' ? 'selected' : '' ?>>Desc</option>
        </select>
        <button type="submit" class="btn btn-sm">Sort</button>
    </form>
</div>
<p class="sort-hint" style="margin:-12px 0 16px;"><?= $total ?> payment(s) · click column headers to sort</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <?= admin_sort_th('ID', 'id', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Student', 'student', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Subject', 'subject', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Session', 'session', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Amount', 'amount', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Method', 'method', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Status', 'status', 'payments.php', $sort['sort'], $sort['dir']) ?>
                <?= admin_sort_th('Paid At', 'paid_at', 'payments.php', $sort['sort'], $sort['dir']) ?>
            </tr>
        </thead>
        <tbody>
        <?php if ($payments && mysqli_num_rows($payments) > 0):
            while ($p = mysqli_fetch_assoc($payments)): ?>
        <tr>
            <td>#<?= (int)$p['id'] ?></td>
            <td><?= htmlspecialchars($p['student_name']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($p['subject']) ?></td>
            <td style="color:var(--muted);"><?= date('M j, Y', strtotime($p['session_date'])) ?></td>
            <td><strong>RM <?= number_format((float)$p['amount'], 2) ?></strong></td>
            <td style="color:var(--muted);"><?= str_replace('_', ' ', htmlspecialchars($p['payment_method'])) ?></td>
            <td><?= admin_status_badge($p['status']) ?></td>
            <td style="color:var(--muted);"><?= date('M j, Y g:ia', strtotime($p['paid_at'])) ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="8" class="empty-state">No payment records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>