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

// Time range
$options = [
    [
        'last_online_time1' => 0,
        'lang' => 'all_before',
    ],
    [
        'last_online_time1' => TIMESTAMP - 7 * 24 * 60 * 60,
        'lang' => 'last_7_days',
    ],
    [
        'last_online_time1' => TIMESTAMP - 3 * 24 * 60 * 60,
        'lang' => 'last_3_days',
    ],
    [
        'last_online_time1' => TIMESTAMP - 24 * 60 * 60,
        'lang' => 'last_1_days',
    ],
];
showtableheader(Helpers::lang('time_range'));
foreach ($options as $option) {
    showformheader(Request::formHeaderAction());
    showhiddenfields([
        'last_online_time1' => $option['last_online_time1'],
    ]);
    showsubmit('submit', Helpers::lang($option['lang']), '', '', '', false);
    showformfooter();
}
showtablefooter();

$perpage = Request::perPage();
$page = Request::page();
$count = Log::count();
$last_online_time1 = Request::lastOnlineTime1();

showtableheader(Helpers::lang('multi_login_log_top'));

showsubtitle([
    Helpers::lang('table_log_field_uid'),
    Helpers::lang('table_log_field_username'),
    Helpers::lang('admin_log_top_table_field_count'),
    Helpers::lang('admin_log_top_table_field_view_log'),
    Helpers::lang('admin_log_top_table_field_search_user'),
]);

global $_G;
$_G['setting_JS'] .= <<<'EOD'
;
function submit_search_user_form(submit_button_id) {
    document.getElementById(submit_button_id).click();
}
EOD;

if ($last_online_time1) {
    $rows = Log::fetchCountGroupByUserByPageAfter($last_online_time1, $page, $perpage);
} else {
    $rows = Log::fetchCountGroupByUserByPage($page, $perpage);
}
$lang_admin_log_top_table_field_view_log = Helpers::lang('admin_log_top_table_field_view_log');
$lang_admin_log_top_table_field_search_user = Helpers::lang('admin_log_top_table_field_search_user');
foreach ($rows as $row) {
    // 查看记录
    $view_log_href = ADMINSCRIPT . '?' . http_build_query([
            'action' => $_GET['action'],
            'operation' => $_GET['operation'],
            'do' => $_GET['do'],
            'identifier' => $_GET['identifier'],
            'pmod' => 'admin_log',
            'search_uid' => $row['uid'],
        ]);
    $view_log = "<a href=\"{$view_log_href}\">{$lang_admin_log_top_table_field_view_log}</a>";

    // 查找用户
    $search_user_form_submit_id = 'cpform-search-user-submit-' . $row['uid'];
    ob_start();
    showformheader('members&operation=search', 'style="display: none;"');
    showhiddenfields([
        'uid' => $row['uid'],
    ]);
    echo '<input type="submit" name="submit" value="1" id="' . $search_user_form_submit_id . '">';
    showformfooter();
    echo "<a href=\"javascript:\" onclick=\"submit_search_user_form('{$search_user_form_submit_id}')\">{$lang_admin_log_top_table_field_search_user}</a>";
    $search_user = ob_get_clean();

    showtablerow('', [], [
        dhtmlspecialchars($row['uid']),
        dhtmlspecialchars($row['username']),
        dhtmlspecialchars($row['count']),
        $view_log,
        $search_user,
    ]);
}
showtablefooter();

$mpurl = ADMINSCRIPT . '?' . Request::queryWithoutPage();
$multipage = multi($count, $perpage, $page, $mpurl);
echo $multipage;
