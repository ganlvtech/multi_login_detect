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

// 搜索 UID
showtableheader(Helpers::lang('search_uid'));
showformheader(Request::formHeaderAction());
showsetting(Helpers::lang('search_uid'), 'search_uid', '', 'text');
showsubmit('submit', Helpers::lang('search_uid'));
showformfooter();
showtablefooter();

// 重复登录日志
$perpage = Request::perPage();
$page = Request::page();

showtableheader(Helpers::lang('multi_login_log'));

$fields = [];
foreach (Log::fields() as $field) {
    if (in_array($field, ['saltkey1', 'saltkey2'])) {
        continue;
    }
    $fields[$field] = Helpers::lang('table_log_field_' . $field);
}
showsubtitle($fields);

if ($search_uid = Request::searchUid()) {
    $rows = Log::fetchAllOfUidByPage($search_uid, $page, $perpage);
    $count = Log::countOfUid($search_uid);
} else {
    $rows = Log::fetchAllByPage($page, $perpage);
    $count = Log::count();
}

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

$mpurl = ADMINSCRIPT . '?' . Request::queryWithoutPage();
$multipage = multi($count, $perpage, $page, $mpurl);
echo $multipage;

