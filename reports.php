<?php
require_once __DIR__ . '/init.php';

$page_title    = 'Reports';
$page_subtitle = 'Export system data for analysis';

$total_users    = admin_count($conn, "SELECT COUNT(*) AS c FROM users");
$total_bookings = admin_count($conn, "SELECT COUNT(*) AS c FROM bookings");
$total_revenue  = 0;
$rev = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS c FROM payments WHERE status='paid'");
if ($rev) $total_revenue = (float)mysqli_fetch_assoc($rev)['c'];
$avg_rating = 0;
$avg = mysqli_query($conn, "SELECT AVG(rating) AS c FROM reviews");
if ($avg) $avg_rating = round((float)mysqli_fetch_assoc($avg)['c'], 1);

include __DIR__ . '/includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
        <div class="label">Users</div>
        <div class="value"><?= number_format($total_users) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Bookings</div>
        <div class="value"><?= number_format($total_bookings) ?></div>
    </div>
    <div class="stat-card teal">
        <div class="label">Avg Review Rating</div>
        <div class="value"><?= $avg_rating ?: '—' ?></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>User Report</h3></div>
        <div class="card-body">
            <p style="color:var(--muted);font-size:.86rem;margin-bottom:14px;">Full list of registered users with roles and join dates.</p>
            <a href="../export.php?type=users&format=csv" class="btn btn-teal">📥 Export Users CSV</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Bookings Report</h3></div>
        <div class="card-body">
            <p style="color:var(--muted);font-size:.86rem;margin-bottom:14px;">All sessions with student, tutor, subject, date, and status.</p>
            <a href="../export.php?type=bookings&format=csv" class="btn btn-teal">📥 Export Bookings CSV</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Tutors Report</h3></div>
        <div class="card-body">
            <p style="color:var(--muted);font-size:.86rem;margin-bottom:14px;">Tutor profiles with ratings, pricing, and booking counts.</p>
            <a href="../export.php?type=tutors&format=csv" class="btn btn-teal">📥 Export Tutors CSV</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Payments Report</h3></div>
        <div class="card-body">
            <p style="color:var(--muted);font-size:.86rem;margin-bottom:14px;">Revenue records — total paid: <strong>RM <?= number_format($total_revenue, 2) ?></strong></p>
            <a href="../export.php?type=payments&format=csv" class="btn btn-teal">📥 Export Payments CSV</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Monthly Summary</h3></div>
        <div class="card-body">
            <p style="color:var(--muted);font-size:.86rem;margin-bottom:14px;">High-level platform metrics for the current month.</p>
            <a href="../export.php?type=monthly&format=csv" class="btn btn-teal">📥 Export Summary CSV</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Study Teammates</h3></div>
        <div class="card-body">
            <p style="color:var(--muted);font-size:.86rem;margin-bottom:14px;">Students who share tutors — useful for peer-matching insights.</p>
            <a href="../export.php?type=teammates&format=csv" class="btn btn-teal">📥 Export Teammates CSV</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>