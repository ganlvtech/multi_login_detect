<?php

namespace Ganlv\MultiLoginDetect\Libraries;

class Request
{
    public static function groupId()
    {
        global $_G;
        return (int)$_G['group']['groupid'];
    }

    public static function uid()
    {
        global $_G;
        return (int)$_G['uid'];
    }

    public static function username()
    {
        global $_G;
        return $_G['member']['username'];
    }

    public static function ip()
    {
        global $_G;
        return $_G['clientip'];
    }

    public static function auth()
    {
        global $_G;
        return $_G['cookie']['auth'];
    }

    public static function saltKey()
    {
        global $_G;
        return $_G['cookie']['saltkey'];
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
}