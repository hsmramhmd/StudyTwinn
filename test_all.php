<?php
$base = 'http://localhost/studytwin/';
$cookie = tempnam(sys_get_temp_dir(), 'stall');
$fail = 0;

function req($url, $cookie, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
    ]);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

// Login as admin
req($base . 'index.php', $cookie, ['email' => 'admin@test.com', 'password' => '123456']);

$admin_pages = [
    'admin/dashboard.php', 'admin/users.php', 'admin/tutors.php', 'admin/bookings.php',
    'admin/payments.php', 'admin/rooms.php', 'admin/rankings.php', 'admin/notifications.php', 'admin/reports.php',
];

foreach ($admin_pages as $p) {
    [$code, $body] = req($base . $p, $cookie);
    if ($code !== 200) { echo "FAIL $p HTTP $code\n"; $fail++; continue; }
    if (strpos($body, 'theme-toggle') === false) { echo "FAIL $p no theme toggle\n"; $fail++; continue; }
    if (strpos($body, 'data-theme') === false && strpos($body, 'studytwin-theme') === false) {
        echo "FAIL $p no theme script\n"; $fail++; continue;
    }
    if (strpos($body, '[data-theme="dark"]') === false) {
        echo "FAIL $p missing dark mode CSS\n"; $fail++; continue;
    }
    echo "OK $p\n";
}

// Public pages
foreach (['index.php', 'register.php'] as $p) {
    [$code, $body] = req($base . $p, $cookie);
    if ($code !== 200 || strpos($body, 'theme-toggle') === false) {
        echo "FAIL public $p\n"; $fail++;
    } else {
        echo "OK public $p\n";
    }
}

// Tutor login + dashboard
$cookie2 = tempnam(sys_get_temp_dir(), 'sttut');
req($base . 'index.php', $cookie2, ['email' => 'aina@test.com', 'password' => '123456']);
foreach (['tutor/tutor_dashboard.php', 'tutor/rewards.php'] as $tp) {
    [$code, $body] = req($base . $tp, $cookie2);
    if ($code !== 200 || strpos($body, 'theme-toggle') === false) {
        echo "FAIL $tp\n"; $fail++;
    } elseif ($tp === 'tutor/rewards.php' && strpos($body, 'Achievements') === false) {
        echo "FAIL $tp missing achievements\n"; $fail++;
    } else {
        echo "OK $tp\n";
    }
}
unlink($cookie2);

// Student authenticated pages
$cookie3 = tempnam(sys_get_temp_dir(), 'ststu');
req($base . 'index.php', $cookie3, ['email' => 'student@test.com', 'password' => '123456']);
$student_pages = ['dashboard.php','bookings.php','tutor.php','profile.php','messages.php'];
foreach ($student_pages as $p) {
    [$code, $body] = req($base . $p, $cookie3);
    if ($code !== 200 || strpos($body, 'theme-toggle') === false) {
        echo "FAIL student $p HTTP $code\n"; $fail++;
    } else {
        echo "OK student $p\n";
    }
}
unlink($cookie3);

unlink($cookie);
echo $fail ? "\n$fail test(s) failed.\n" : "\nAll page tests passed.\n";
exit($fail ? 1 : 0);