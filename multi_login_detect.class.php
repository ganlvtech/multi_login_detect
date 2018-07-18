<?php

use Ganlv\MultiLoginDetect\Libraries\MultiLoginDetect;

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once __DIR__ . '/Libraries/Helpers.php';
require_once __DIR__ . '/Libraries/Request.php';
require_once __DIR__ . '/Libraries/MultiLoginDetect.php';
require_once __DIR__ . '/Models/Session.php';
require_once __DIR__ . '/Models/Log.php';

class plugin_multi_login_detect
{
    /**
     * 全部所有页面钩子，从 1_diy_forum_discuz.tpl.php 等前台页面的第一行 hookscriptoutput 的调用。
     * global_footer 在页面渲染前触发，比 global_header 触发的更早，参考 upload/source/admincp/discuzhook.dat 中的顺序。
     *
     * @return string
     */
    public static function global_footer()
    {
        global $_G;
        $multiLoginDetect = new MultiLoginDetect($_G['cache']['plugin']['multi_login_detect']);
        $multiLoginDetect->tryHandleMultiLogin();
        return '';
    }
}

class plugin_multi_login_detect_member
{
    public static function logging_method()
    {
        MultiLoginDetect::tryHandleLogoutRequest();
        return plugin_multi_login_detect::global_footer();
    }
}

class mobileplugin_multi_login_detect
{
    public static function global_header_mobile()
    {
        return plugin_multi_login_detect::global_footer();
    }
}