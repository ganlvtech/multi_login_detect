<?php

use Ganlv\MultiLoginDetect\Libraries\Helpers;
use Ganlv\MultiLoginDetect\Libraries\Request;
use Ganlv\MultiLoginDetect\Models\Log;
use Ganlv\MultiLoginDetect\Models\Session;

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

require_once __DIR__ . '/Libraries/Helpers.php';
require_once __DIR__ . '/Libraries/Request.php';
require_once __DIR__ . '/Models/Session.php';
require_once __DIR__ . '/Models/Log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['clear_log'])) {
        Log::deleteBefore(TIMESTAMP - 7 * 86400);
        cpmsg(Helpers::lang('clear_log_ok'));
        return;
    } elseif (isset($_GET['clear_session'])) {
        Session::deleteBefore(TIMESTAMP - 20 * 60);
        cpmsg(Helpers::lang('clear_session_ok'));
        return;
    }
}

// 手动清空未活动的登录信息
showtableheader(Helpers::lang('clear_session'));
showformheader(Request::formHeaderAction());
showhiddenfields([
    'clear_session' => 1,
]);
showsubmit('submit', Helpers::lang('clear_session'), '', '', '', false);
showformfooter();
showtablefooter();

// 清空较早的日志
showtableheader(Helpers::lang('clear_log'));
showformheader(Request::formHeaderAction());
showhiddenfields([
    'clear_log' => 1,
]);
showsubmit('submit', Helpers::lang('clear_log'), '', '', '', false);
showformfooter();
showtablefooter();

