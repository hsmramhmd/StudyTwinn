<?php
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'dashboard.php';

function safe_redirect(string $url): void {
    if (!preg_match('/^[a-z_]+\.php(\?.*)?$/', $url)) $url = 'dashboard.php';
    header('Location: ' . $url);
    exit();
}

switch ($action) {

    case 'update_user_role':
        $user_id = (int)($_POST['user_id'] ?? 0);
        $role    = $_POST['role'] ?? '';
        if ($user_id && $user_id !== $admin_id && in_array($role, ['student', 'tutor', 'admin'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $role, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($role === 'tutor') {
                $chk = mysqli_query($conn, "SELECT id FROM tutors WHERE user_id = $user_id LIMIT 1");
                if ($chk && mysqli_num_rows($chk) === 0) {
                    mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, rating, price_per_hour)
                        VALUES ($user_id, 'General Tutoring', 'Various', 'Ready to help students.', 4.5, 25.00)");
                }
            }
            admin_flash_set('success', 'User role updated successfully.');
        } else {
            admin_flash_set('error', 'Unable to update user role.');
        }
        break;

    case 'delete_user':
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id && $user_id !== $admin_id) {
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role != 'admin'");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $ok = mysqli_stmt_affected_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
            admin_flash_set($ok ? 'success' : 'error', $ok ? 'User removed from the system.' : 'Cannot delete this user.');
        }
        break;

    case 'update_booking_status':
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        if ($booking_id && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $booking_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            admin_flash_set('success', 'Booking status updated.');
        }
        break;

    case 'update_tutor':
        $tutor_id = (int)($_POST['tutor_id'] ?? 0);
        $rating   = (float)($_POST['rating'] ?? 0);
        $price    = (float)($_POST['price_per_hour'] ?? 0);
        $subject  = trim($_POST['subject'] ?? '');
        if ($tutor_id && $rating >= 0 && $rating <= 5 && $price >= 0 && $subject !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE tutors SET rating = ?, price_per_hour = ?, subject = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ddsi', $rating, $price, $subject, $tutor_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            admin_flash_set('success', 'Tutor profile updated.');
        }
        break;

    case 'update_room_status':
        $room_id = (int)($_POST['room_id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        if ($room_id && in_array($status, ['open', 'closed', 'cancelled'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE rooms SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $room_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            admin_flash_set('success', 'Room status updated.');
        }
        break;

    case 'set_ranking':
        $tutor_id = (int)($_POST['tutor_id'] ?? 0);
        $position = (int)($_POST['rank_position'] ?? 0);
        $rating   = (float)($_POST['rating'] ?? 4.5);
        if ($tutor_id && $position >= 1 && $position <= 10) {
            mysqli_query($conn, "DELETE FROM tutor_top_rank WHERE rank_position = $position");
            $stmt = mysqli_prepare($conn, "INSERT INTO tutor_top_rank (tutor_id, rank_position, rating) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE tutor_id = VALUES(tutor_id), rating = VALUES(rating)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'iid', $tutor_id, $position, $rating);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                mysqli_query($conn, "REPLACE INTO tutor_top_rank (tutor_id, rank_position, rating) VALUES ($tutor_id, $position, $rating)");
            }
            admin_flash_set('success', "Rank #$position assigned.");
        }
        break;

    case 'remove_ranking':
        $position = (int)($_POST['rank_position'] ?? 0);
        if ($position >= 1) {
            mysqli_query($conn, "DELETE FROM tutor_top_rank WHERE rank_position = $position");
            admin_flash_set('success', 'Ranking slot cleared.');
        }
        break;

    case 'broadcast_notification':
        $title   = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $target  = $_POST['target_role'] ?? 'all';
        if ($title !== '' && $message !== '') {
            $where = '';
            if (in_array($target, ['student', 'tutor', 'admin'], true)) {
                $where = " WHERE role = '" . mysqli_real_escape_string($conn, $target) . "'";
            }
            $users_res = mysqli_query($conn, "SELECT id FROM users" . $where);
            $count = 0;
            $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            while ($users_res && ($u = mysqli_fetch_assoc($users_res))) {
                $uid = (int)$u['id'];
                mysqli_stmt_bind_param($stmt, 'iss', $uid, $title, $message);
                mysqli_stmt_execute($stmt);
                $count++;
            }
            mysqli_stmt_close($stmt);
            admin_flash_set('success', "Notification sent to $count user(s).");
        } else {
            admin_flash_set('error', 'Title and message are required.');
        }
        $redirect = 'notifications.php';
        break;

    default:
        admin_flash_set('error', 'Unknown action.');
}

safe_redirect($redirect);