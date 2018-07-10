<?php

use Ganlv\MultiLoginDetect\Libraries\Helpers;
use Ganlv\MultiLoginDetect\Libraries\Request;
use Ganlv\MultiLoginDetect\Models\Session;

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

require_once __DIR__ . '/Libraries/Helpers.php';
require_once __DIR__ . '/Libraries/Request.php';
require_once __DIR__ . '/Models/Session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Session::deleteBefore(TIMESTAMP - 20 * 60);
    cpmsg(Helpers::lang('clear_session_ok'));
    return;
}

// 清空未活动的登录信息
showtableheader(Helpers::lang('clear_session'));
showformheader($_GET['action'] . '&' . http_build_query([
        'operation' => $_GET['operation'],
        'do' => $_GET['do'],
        'identifier' => $_GET['identifier'],
        'pmod' => $_GET['pmod'],
    ]));
showsubmit('submit', Helpers::lang('clear_session'));
showformfooter();
showtablefooter();

// 当前登录用户
$perpage = 20;
$count = Session::count();
$page = Request::page();

showtableheader(Helpers::lang('current_session'));

$fields = [];
foreach (Session::fields() as $field) {
    $fields[$field] = Helpers::lang('table_session_field_' . $field);
}
unset($fields['saltkey']);
showsubtitle($fields);

$rows = Session::fetchAllByPage($page, $perpage);
foreach ($rows as $row) {
    showtablerow('', [
        '', '', '',
        'title="' . dhtmlspecialchars(convertip($row['ip'])) . '"',
        'title="' . dhtmlspecialchars($row['auth']) . '"',
        '', '', '',
    ], dhtmlspecialchars([
        $row['id'],
        $row['uid'],
        $row['username'],
        $row['ip'],
        substr($row['auth'], 0, 7) . '...',
        // $row['saltkey'],
        $row['ua'],
        Helpers::formatDate($row['login_time']),
        Helpers::formatDate($row['last_online_time']),
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
