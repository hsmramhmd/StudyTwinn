<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

include('db_connection.php');

$type   = $_GET['type']   ?? 'users';
$format = $_GET['format'] ?? 'csv';

$rows = [];

switch ($type) {
    case 'users':
        $rows[] = ['ID', 'Name', 'Email', 'Role', 'Joined'];
        $res = mysqli_query($conn, "SELECT id, full_name, email, role, created_at FROM users ORDER BY id");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $rows[] = [$r['id'], $r['full_name'], $r['email'], $r['role'], $r['created_at']];
        }
        break;

    case 'bookings':
        $rows[] = ['ID', 'Student', 'Tutor', 'Subject', 'Date', 'Status', 'Created'];
        $res = mysqli_query($conn, "
            SELECT b.id, su.full_name AS student, tu.full_name AS tutor,
                   b.subject, b.session_date, b.status, b.created_at
            FROM bookings b
            JOIN users su ON b.student_id = su.id
            JOIN tutors t ON b.tutor_id = t.id
            JOIN users tu ON t.user_id = tu.id
            ORDER BY b.session_date DESC
        ");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $rows[] = [$r['id'], $r['student'], $r['tutor'], $r['subject'], $r['session_date'], $r['status'], $r['created_at']];
        }
        break;

    case 'tutors':
        $rows[] = ['ID', 'Name', 'Email', 'Subject', 'Rating', 'Price/hr', 'Bookings'];
        $res = mysqli_query($conn, "
            SELECT t.id, u.full_name, u.email, t.subject, t.rating, t.price_per_hour,
                   (SELECT COUNT(*) FROM bookings b WHERE b.tutor_id = t.id) AS bookings
            FROM tutors t JOIN users u ON t.user_id = u.id ORDER BY t.rating DESC
        ");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $rows[] = [$r['id'], $r['full_name'], $r['email'], $r['subject'], $r['rating'], $r['price_per_hour'], $r['bookings']];
        }
        break;

    case 'payments':
        $rows[] = ['ID', 'Student', 'Subject', 'Amount', 'Method', 'Status', 'Paid At'];
        $res = mysqli_query($conn, "
            SELECT p.id, u.full_name, b.subject, p.amount, p.payment_method, p.status, p.paid_at
            FROM payments p
            JOIN users u ON p.student_id = u.id
            JOIN bookings b ON p.booking_id = b.id
            ORDER BY p.paid_at DESC
        ");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $rows[] = [$r['id'], $r['full_name'], $r['subject'], $r['amount'], $r['payment_method'], $r['status'], $r['paid_at']];
        }
        break;

    case 'teammates':
        $rows[] = ['Student A', 'Student B', 'Shared Tutors', 'Completed Sessions', 'XP'];
        $res = mysqli_query($conn, "SELECT * FROM teammate_map ORDER BY xp DESC LIMIT 100");
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $rows[] = [$r['main_student'], $r['teammate_id'], $r['shared_tutors'], $r['completed_sessions'], $r['xp']];
        }
        break;

    case 'monthly':
    default:
        $rows[] = ['Metric', 'Value'];
        $metrics = [
            'Report Date'       => date('Y-m-d H:i'),
            'Total Users'       => admin_metric($conn, "SELECT COUNT(*) AS c FROM users"),
            'Students'          => admin_metric($conn, "SELECT COUNT(*) AS c FROM users WHERE role='student'"),
            'Tutors'            => admin_metric($conn, "SELECT COUNT(*) AS c FROM users WHERE role='tutor'"),
            'Total Bookings'    => admin_metric($conn, "SELECT COUNT(*) AS c FROM bookings"),
            'Completed Sessions'=> admin_metric($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status='completed'"),
            'Pending Bookings'  => admin_metric($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status='pending'"),
            'Total Revenue'     => admin_metric($conn, "SELECT COALESCE(SUM(amount),0) AS c FROM payments WHERE status='paid'"),
            'Messages Sent'     => admin_metric($conn, "SELECT COUNT(*) AS c FROM messages"),
            'Reviews'           => admin_metric($conn, "SELECT COUNT(*) AS c FROM reviews"),
        ];
        foreach ($metrics as $k => $v) $rows[] = [$k, $v];
        break;
}

function admin_metric(mysqli $conn, string $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    return mysqli_fetch_assoc($res)['c'] ?? 0;
}

if ($format === 'csv') {
    $filename = "studytwin_{$type}_" . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    $out = fopen('php://output', 'w');
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

header('Location: admin/reports.php');
exit;