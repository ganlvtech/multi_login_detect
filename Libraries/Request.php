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
}