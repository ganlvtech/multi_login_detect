<?php

use Ganlv\MultiLoginDetect\Libraries\Helpers;
use Ganlv\MultiLoginDetect\Libraries\Request;
use Ganlv\MultiLoginDetect\Models\Log;

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

require_once __DIR__ . '/Libraries/Helpers.php';
require_once __DIR__ . '/Libraries/Request.php';
require_once __DIR__ . '/Models/Log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Log::deleteBefore(TIMESTAMP - 7 * 86400);
    cpmsg(Helpers::lang('clear_log_ok'));
    return;
}

// 清空较早的日志
showtableheader(Helpers::lang('clear_log'));
showformheader($_GET['action'] . '&' . http_build_query([
        'operation' => $_GET['operation'],
        'do' => $_GET['do'],
        'identifier' => $_GET['identifier'],
        'pmod' => $_GET['pmod'],
    ]));
showsubmit('submit', Helpers::lang('clear_log'));
showformfooter();
showtablefooter();

// 重复登录日志
$perpage = 20;
$count = Log::count();
$page = Request::page();

showtableheader(Helpers::lang('multi_login_log'));

$fields = [];
foreach (Log::fields() as $field) {
    $fields[$field] = Helpers::lang('table_log_field_' . $field);
}
unset($fields['saltkey1']);
unset($fields['saltkey2']);
showsubtitle($fields);

$rows = Log::fetchAllByPage($page, $perpage);
foreach ($rows as $row) {
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
        $row['ip1'],
        substr($row['auth1'], 0, 7) . '...',
        // $row['saltkey1'],
        $row['ua1'],
        Helpers::formatDate($row['login_time1']),
        Helpers::formatDate($row['last_online_time1']),
        $row['ip2'],
        substr($row['auth2'], 0, 7) . '...',
        // $row['saltkey2'],
        ($row['ua1'] === $row['ua2']) ? Helpers::lang('same_user_agent') : $row['ua2'],
        Helpers::formatDate($row['login_time2']),
        Helpers::formatDate($row['last_online_time2']),
    ]));
}
showtablefooter();

$mpurl = ADMINSCRIPT . '?' . http_build_query([
        'action' => $_GET['action'],
        'operation' => $_GET['operation'],
        'do' => $_GET['do'],
        'identifier' => $_GET['identifier'],
        'pmod' => $_GET['pmod'],
    ]);
$multipage = multi($count, $perpage, $page, $mpurl);
echo $multipage;

