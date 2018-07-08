<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$table = DB::table('multi_login_log');
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
    'ip1' => '最新的IP',
    'auth1' => '最新的auth',
    // 'saltkey1' => '最新的saltkey',
    'ua1' => '最新的UA',
    'login_time1' => '最后登录时间',
    'last_online_time1' => '最后在线时间',
    'ip2' => '被挤下线的IP',
    'auth2' => '被挤下线的auth',
    // 'saltkey2' => '被挤下线的saltkey',
    'ua2' => '被挤下线的UA',
    'login_time2' => '被挤下线的登录时间',
    'last_online_time2' => '被挤下线的时间',
];

showtableheader('重复登录日志');

showsubtitle($fields);

while ($row = DB::fetch($query)) {
    showtablerow('', [
        '', '', '',
        'title="' . dhtmlspecialchars(convertip($row['ip1'])) . '"',
        'title="' . dhtmlspecialchars($row['auth1']) . '"',
        '', '', '',
        'title="' . dhtmlspecialchars(convertip($row['ip2'])) . '"',
        'title="' . dhtmlspecialchars($row['auth2']) . '"',
        'title="' . dhtmlspecialchars($row['ua2']) . '"',
        '', '',
    ], dhtmlspecialchars([
        $row['id'],
        $row['uid'],
        $row['username'],
        long2ip($row['ip1']),
        substr($row['auth1'], 0, 7) . '...',
        // $row['saltkey1'],
        $row['ua1'],
        date('Y-m-d H:i:s', $row['login_time1']),
        date('Y-m-d H:i:s', $row['last_online_time1']),
        long2ip($row['ip2']),
        substr($row['auth2'], 0, 7) . '...',
        // $row['saltkey2'],
        ($row['ua1'] === $row['ua2']) ? 'UA相同' : $row['ua2'],
        date('Y-m-d H:i:s', $row['login_time2']),
        date('Y-m-d H:i:s', $row['last_online_time2']),
    ]));
}

showtablefooter();

echo $multipage;

