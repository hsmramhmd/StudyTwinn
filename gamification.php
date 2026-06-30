<?php
/**
 * Tutor gamification stats — include after $conn, $user_id are set.
 * Populates: $tutor, $tutor_id, $tutor_xp, levels, badges, $teaching_points, etc.
 */
if (!isset($conn) || !isset($user_id)) return;

$tutor_res = mysqli_query($conn, "
    SELECT t.*, u.full_name, u.email
    FROM tutors t JOIN users u ON t.user_id = u.id
    WHERE t.user_id = '" . (int)$user_id . "' LIMIT 1
");
$tutor = $tutor_res ? mysqli_fetch_assoc($tutor_res) : null;

if (!$tutor) {
    mysqli_query($conn, "INSERT INTO tutors (user_id, subject, expertise, bio, rating, price_per_hour)
        VALUES ('" . (int)$user_id . "', 'General Tutoring', 'Various', 'Ready to help students succeed.', 4.5, 25.00)");
    $tutor_res = mysqli_query($conn, "
        SELECT t.*, u.full_name, u.email FROM tutors t JOIN users u ON t.user_id = u.id
        WHERE t.user_id = '" . (int)$user_id . "' LIMIT 1
    ");
    $tutor = $tutor_res ? mysqli_fetch_assoc($tutor_res) : null;
}

$tutor_id = $tutor ? (int)$tutor['id'] : 0;

$completed_count = $pending_count = $confirmed_count = $total_students = $avail_count = $rooms_count = 0;
$review_count = 0;
$avg_review = 0.0;
$is_top_ranked = false;
$top_rank_pos = 0;

if ($tutor_id) {
    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE tutor_id=$tutor_id AND status='completed'");
    if ($r) $completed_count = (int)mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE tutor_id=$tutor_id AND status='pending'");
    if ($r) $pending_count = (int)mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE tutor_id=$tutor_id AND status='confirmed'");
    if ($r) $confirmed_count = (int)mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT student_id) AS c FROM bookings WHERE tutor_id=$tutor_id");
    if ($r) $total_students = (int)mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM availability WHERE tutor_id=$tutor_id");
    if ($r) $avail_count = (int)mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM rooms WHERE tutor_id=$tutor_id");
    if ($r) $rooms_count = (int)mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS c, AVG(rating) AS avg_r FROM reviews WHERE tutor_id=$tutor_id");
    if ($r) {
        $row = mysqli_fetch_assoc($r);
        $review_count = (int)$row['c'];
        $avg_review   = $row['avg_r'] ? round((float)$row['avg_r'], 1) : 0.0;
    }

    $r = mysqli_query($conn, "SELECT rank_position FROM tutor_top_rank WHERE tutor_id=$tutor_id LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) {
        $is_top_ranked = true;
        $top_rank_pos  = (int)mysqli_fetch_assoc($r)['rank_position'];
    }
}

$tutor_rating = (float)($tutor['rating'] ?? 4.5);
$total_sessions_handled = $completed_count + $confirmed_count;

$tutor_xp = ($completed_count * 35) + ($total_students * 20) + (round($tutor_rating * 30))
          + ($avail_count * 5) + ($rooms_count * 15) + ($review_count * 10);

$teaching_points = ($completed_count * 15) + ($total_students * 8) + ($review_count * 5);

$tutor_levels = [
    ['name'=>'Novice Tutor',    'icon'=>'🌱', 'min'=>0,    'color'=>'#6b7b8c', 'perks'=>['Basic tutor profile','Accept booking requests']],
    ['name'=>'Apprentice',      'icon'=>'📖', 'min'=>200,  'color'=>'#116979', 'perks'=>['Profile highlight badge','Session reminders']],
    ['name'=>'Mentor',          'icon'=>'🎓', 'min'=>500,  'color'=>'#1b90a5', 'perks'=>['Featured in tutor search','Priority listing']],
    ['name'=>'Expert Educator', 'icon'=>'⭐', 'min'=>1000, 'color'=>'#f0672b', 'perks'=>['Top Rank eligibility','5% platform fee discount']],
    ['name'=>'Master Tutor',    'icon'=>'🏆', 'min'=>1800, 'color'=>'#b45309', 'perks'=>['Master badge on profile','Certificate of Excellence','VIP tutor status']],
];

$tutor_current_level = $tutor_levels[0];
$tutor_next_level    = $tutor_levels[1];
foreach ($tutor_levels as $i => $lvl) {
    if ($tutor_xp >= $lvl['min']) {
        $tutor_current_level = $lvl;
        $tutor_next_level    = $tutor_levels[$i + 1] ?? $lvl;
    }
}

$tutor_xp_in_level = ($tutor_current_level['name'] !== 'Master Tutor')
    ? ($tutor_xp - $tutor_current_level['min']) : 0;
$tutor_xp_needed = ($tutor_next_level['min'] > $tutor_current_level['min'])
    ? ($tutor_next_level['min'] - $tutor_current_level['min']) : 1;
$tutor_xp_pct = ($tutor_xp_needed > 0)
    ? min(100, round(($tutor_xp_in_level / $tutor_xp_needed) * 100)) : 100;

$tutor_badges = [
    ['icon'=>'📚', 'name'=>'Session Starter',  'desc'=>'Complete your first tutoring session',     'req'=>'1 session',   'unlocked'=>$completed_count >= 1],
    ['icon'=>'🔟', 'name'=>'Ten Sessions',     'desc'=>'Complete 10 tutoring sessions',            'req'=>'10 sessions', 'unlocked'=>$completed_count >= 10],
    ['icon'=>'💯', 'name'=>'Century Club',     'desc'=>'Complete 25 tutoring sessions',            'req'=>'25 sessions', 'unlocked'=>$completed_count >= 25],
    ['icon'=>'👥', 'name'=>'Student Magnet',   'desc'=>'Teach 3 or more unique students',          'req'=>'3 students',  'unlocked'=>$total_students >= 3],
    ['icon'=>'🌟', 'name'=>'Classroom Hero',   'desc'=>'Teach 10 or more unique students',         'req'=>'10 students', 'unlocked'=>$total_students >= 10],
    ['icon'=>'⭐', 'name'=>'Highly Rated',     'desc'=>'Maintain a 4.5+ tutor rating',             'req'=>'4.5 rating',  'unlocked'=>$tutor_rating >= 4.5],
    ['icon'=>'💎', 'name'=>'Five Star',        'desc'=>'Reach 4.9 average tutor rating',           'req'=>'4.9 rating',  'unlocked'=>$tutor_rating >= 4.9],
    ['icon'=>'🕒', 'name'=>'Availability Pro', 'desc'=>'Set 10 or more weekly time slots',         'req'=>'10 slots',    'unlocked'=>$avail_count >= 10],
    ['icon'=>'📅', 'name'=>'Always Open',      'desc'=>'Set 20 or more weekly time slots',         'req'=>'20 slots',    'unlocked'=>$avail_count >= 20],
    ['icon'=>'🏠', 'name'=>'Room Creator',     'desc'=>'Create 3 or more study rooms',             'req'=>'3 rooms',     'unlocked'=>$rooms_count >= 3],
    ['icon'=>'🏫', 'name'=>'Study Hub',        'desc'=>'Create 5 or more study rooms',             'req'=>'5 rooms',     'unlocked'=>$rooms_count >= 5],
    ['icon'=>'💬', 'name'=>'Review Champion',  'desc'=>'Receive 5+ student reviews',               'req'=>'5 reviews',   'unlocked'=>$review_count >= 5],
    ['icon'=>'🏅', 'name'=>'Top Ranked',       'desc'=>'Featured on Tutor Top Rank leaderboard',   'req'=>'Admin pick',  'unlocked'=>$is_top_ranked],
    ['icon'=>'🎯', 'name'=>'Quick Responder',  'desc'=>'Confirm 5 or more booking requests',       'req'=>'5 confirmed', 'unlocked'=>$confirmed_count >= 5],
    ['icon'=>'🏆', 'name'=>'Master Educator',  'desc'=>'Reach Master Tutor level',                 'req'=>'1800 XP',     'unlocked'=>$tutor_current_level['name'] === 'Master Tutor'],
];

$tutor_unlocked = 0;
foreach ($tutor_badges as $b) {
    if ($b['unlocked']) $tutor_unlocked++;
}

$tutor_certificates = [
    ['sessions'=>10, 'title'=>'Certificate of Teaching Excellence', 'desc'=>'Awarded for completing 10 tutoring sessions', 'unlocked'=>$completed_count >= 10],
    ['sessions'=>25, 'title'=>'Certificate of Master Mentorship',   'desc'=>'Awarded for completing 25 tutoring sessions', 'unlocked'=>$completed_count >= 25],
    ['sessions'=>50, 'title'=>'Certificate of Distinguished Service', 'desc'=>'Awarded for completing 50 tutoring sessions', 'unlocked'=>$completed_count >= 50],
];

$tutor_milestones = [
    ['xp'=>200,  'reward'=>'Profile Boost',       'icon'=>'📣', 'desc'=>'Your profile gets a visibility boost for 7 days'],
    ['xp'=>500,  'reward'=>'Mentor Badge',        'icon'=>'🎓', 'desc'=>'Mentor badge displayed on your tutor card'],
    ['xp'=>1000, 'reward'=>'Featured Listing',    'icon'=>'✨', 'desc'=>'Eligible for featured tutor placement'],
    ['xp'=>1800, 'reward'=>'Master Certificate',  'icon'=>'📜', 'desc'=>'Official Master Tutor e-certificate'],
];

$tutor_next_badges = array_values(array_filter($tutor_badges, fn($b) => !$b['unlocked']));