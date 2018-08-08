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

// 搜索 UID
showtableheader(Helpers::lang('search_uid'));
showformheader(Request::formHeaderAction());
showsetting(Helpers::lang('search_uid'), 'search_uid', '', 'text');
showsubmit('submit', Helpers::lang('search_uid'));
showformfooter();
showtablefooter();

// 当前登录用户
$perpage = Request::perPage();
$page = Request::page();

showtableheader(Helpers::lang('current_session'));

$fields = [];
foreach (Session::fields() as $field) {
    if (in_array($field, ['saltkey'])) {
        continue;
    }
    $fields[$field] = Helpers::lang('table_session_field_' . $field);
}
showsubtitle($fields);

if ($search_uid = Request::searchUid()) {
    $rows = Session::fetchAllOfUidByPage($search_uid, $page, $perpage);
    $count = Session::countOfUid($search_uid);
} else {
    $rows = Session::fetchAllByPage($page, $perpage);
    $count = Session::count();
}

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

$mpurl = ADMINSCRIPT . '?' . Request::queryWithoutPage();
$multipage = multi($count, $perpage, $page, $mpurl);
echo $multipage;
