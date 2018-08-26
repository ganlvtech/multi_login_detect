<?php

namespace Ganlv\MultiLoginDetect\Libraries;

class Request
{
    public static function groupId()
    {
        global $_G;
        return (int)$_G['groupid'];
    }

    public static function uid()
    {
        global $_G;
        return (int)$_G['uid'];
    }

    public static function username()
    {
        global $_G;
        return (string)$_G['username'];
    }

    public static function ip()
    {
        global $_G;
        return (string)$_G['clientip'];
    }

    public static function auth()
    {
        global $_G;
        return (string)$_G['cookie']['auth'];
    }

    public static function saltKey()
    {
        global $_G;
        return (string)$_G['cookie']['saltkey'];
    }

    public static function userAgent()
    {
        return substr($_SERVER['HTTP_USER_AGENT'], 0, 1024);
    }

    public static function page()
    {
        $page = (int)$_GET['page'];
        if ($page <= 0) {
            $page = 1;
        }
        return $page;
    }

    public static function perPage()
    {
        $page = (int)$_GET['per_page'];
        if ($page <= 0) {
            $page = 20;
        }
        return $page;
    }

    public static function queryWithoutPage()
    {
        $vars = $_GET;
        unset($vars['page']);
        return http_build_query($vars);
    }

    public static function formHeaderAction()
    {
        $vars = $_GET;
        unset($vars['page']);
        unset($vars['action']);
        return $_GET['action'] . '&' . http_build_query($vars);
    }

    /**
     * 全局 > 站点功能 > 其他 > 用户在线时间更新时长(分钟)
     *
     * @return int 分钟（默认为 10）
     */
    public static function onlineTimeSpan()
    {
        global $_G;
        return isset($_G['setting']['oltimespan']) ? (int)$_G['setting']['oltimespan'] : 10;
    }

    public static function sessionExpiredBefore()
    {
        return TIMESTAMP - self::onlineTimeSpan() * 60;
    }

    public static function searchUid()
    {
        if (isset($_GET['search_uid'])) {
            return (int)$_GET['search_uid'];
        } elseif (isset($_POST['search_uid'])) {
            return (int)$_POST['search_uid'];
        } else {
            return false;
        }
    }

    public static function lastOnlineTime1()
    {
        if (isset($_GET['last_online_time1'])) {
            return (int)$_GET['last_online_time1'];
        } elseif (isset($_POST['last_online_time1'])) {
            return (int)$_POST['last_online_time1'];
        } else {
            return false;
        }
    }
}