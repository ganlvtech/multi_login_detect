<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$table = DB::table('multi_login_session');
$page = (int)$_GET['page'];
if ($page <= 0) {
    $page = 1;
}
$perpage = 20;
$start = ($page - 1) * $perpage;
$count = DB::result_first("SELECT COUNT(*) FROM `$table`");
$mpurl = ADMINSCRIPT . '?' . http_build_query([
        'action' => $_GET['action'],
        'operation' => $_GET['operation'],
        'identifier' => $_GET['identifier'],
        'pmod' => $_GET['pmod'],
    ]);
$multipage = multi($count, $perpage, $page, $mpurl);

$query = DB::query("SELECT * FROM `$table` ORDER BY `id` DESC LIMIT $start, $perpage");

$fields = [
    'id' => 'ID',
    'uid' => 'UID',
    'username' => '用户名',
    'ip' => 'IP',
    'auth' => 'auth',
    // 'saltkey' => 'saltkey',
    'ua' => 'User Agent',
    'login_time' => '登录时间',
    'last_online_time' => '最后在线时间',
];

showtableheader('当前登录用户');

showsubtitle($fields);

while ($row = DB::fetch($query)) {
    showtablerow('', [
        '', '', '',
        'title="' . dhtmlspecialchars(convertip($row['ip'])) . '"',
        'title="' . dhtmlspecialchars($row['auth']) . '"',
        '', '', '',
    ], dhtmlspecialchars([
        $row['id'],
        $row['uid'],
        $row['username'],
        long2ip($row['ip']),
        substr($row['auth'], 0, 7) . '...',
        // $row['saltkey'],
        $row['ua'],
        date('Y-m-d H:i:s', $row['login_time']),
        date('Y-m-d H:i:s', $row['last_online_time']),
    ]));
}

showtablefooter();

echo $multipage;

