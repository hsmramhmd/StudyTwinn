<?php
/** CLI login + page load smoke test */
$base = 'http://localhost/studytwin/';
$cookie = tempnam(sys_get_temp_dir(), 'stcookie');

function http_req($url, $cookie, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return [$code, $redirect, $body];
}

$accounts = [
    'admin'   => ['email' => 'admin@test.com', 'expect' => 'admin/dashboard.php'],
    'student' => ['email' => 'student@test.com', 'expect' => 'dashboard.php'],
];

$fail = 0;
foreach ($accounts as $role => $acc) {
    [$code, $redir] = http_req($base . 'index.php', $cookie, [
        'email' => $acc['email'],
        'password' => '123456',
    ]);
    if ($code !== 302 || strpos($redir, $acc['expect']) === false) {
        echo "FAIL $role login: code=$code redirect=$redir\n";
        $fail++;
        continue;
    }
    echo "OK $role login -> $redir\n";

    [$pcode,, $body] = http_req($base . $acc['expect'], $cookie);
    if ($pcode !== 200) {
        echo "FAIL $role dashboard: HTTP $pcode\n";
        $fail++;
        continue;
    }
    if (strpos($body, 'data-theme') === false && strpos($body, 'theme-toggle') === false) {
        echo "FAIL $role dashboard: missing theme toggle\n";
        $fail++;
        continue;
    }
    if (strpos($body, 'studyTwin') === false && strpos($body, 'StudyTwin') === false && strpos($body, 'studytwin') === false) {
        echo "WARN $role dashboard: branding not found\n";
    }
    echo "OK $role dashboard loads with theme (HTTP $pcode)\n";
}

unlink($cookie);
exit($fail ? 1 : 0);