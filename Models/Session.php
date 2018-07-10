<?php

namespace Ganlv\MultiLoginDetect\Models;

use DB;

class Session
{
    public static function fields()
    {
        return [
            'id',
            'uid',
            'username',
            'ip',
            'auth',
            'saltkey',
            'ua',
            'login_time',
            'last_online_time',
        ];
    }

    /**
     * 删除session表的一条记录
     *
     * @param string $auth Auth（不用经过addslashes处理）
     *
     * @return string
     */
    public static function deleteByAuth($auth)
    {
        $auth = substr($auth, 0, 255);
        $auth = daddslashes($auth);
        return DB::delete('multi_login_session', "`auth` = '$auth'", 1);
    }

    /**
     * 获取session表的一条记录
     *
     * @param string $auth Auth（不用经过addslashes处理）
     *
     * @return array
     */
    public static function fetchByAuth($auth)
    {
        $table = DB::table('multi_login_session');
        $auth = substr($auth, 0, 255);
        $auth = daddslashes($auth);
        $session = DB::fetch_first("SELECT * FROM `$table` WHERE `auth` = '$auth' LIMIT 1");
        if ($session) {
            $session['ip'] = long2ip($session['ip']);
        }
        return $session;
    }

    /**
     * 获取session表的一条记录
     *
     * @param int $uid
     *
     * @return array
     */
    public static function fetchLatestByUid($uid)
    {
        $table = DB::table('multi_login_session');
        $uid = daddslashes($uid);
        $session = DB::fetch_first("SELECT * FROM `$table` WHERE `uid` = '$uid' ORDER BY `id` DESC LIMIT 1");
        if ($session) {
            $session['ip'] = long2ip($session['ip']);
        }
        return $session;
    }

    /**
     * 插入session
     *
     * @param array $data [
     *     'uid' => 0,
     *     'username' =>
     *     'admin',
     *     'ip' => '127.0.0.1',
     *     'auth' => 'abc123+-...',
     *     'saltkey' => 'abcd1234',
     *     'ua' => 'Mozilla/5.0 ...'，
     * ]
     *
     * @return mixed
     */
    public static function insert($data)
    {
        return DB::insert('multi_login_session', [
            'uid' => $data['uid'],
            'username' => mb_substr($data['username'], 0, 15),
            'ip' => ip2long($data['ip']),
            'auth' => substr($data['auth'], 0, 255),
            'saltkey' => substr($data['saltkey'], 0, 8),
            'ua' => substr($data['ua'], 0, 1024),
            'login_time' => TIMESTAMP,
            'last_online_time' => TIMESTAMP,
        ]);
    }

    /**
     * 更新session最后在线时间
     *
     * @param int $id
     *
     * @return bool
     */
    public static function touchById($id)
    {
        $id = daddslashes($id);
        return DB::update('multi_login_session', [
            'last_online_time' => TIMESTAMP,
        ], "`id` = '$id'");
    }

    public static function deleteBefore($timestamp)
    {
        $timestamp = daddslashes($timestamp);
        return DB::delete('multi_login_session', "`last_online_time` < '$timestamp'");
    }

    public static function count()
    {
        $table = DB::table('multi_login_session');
        return DB::result_first("SELECT COUNT(*) FROM `$table`");
    }

    public static function fetchAllByPage($page, $perpage = 20)
    {
        $table = DB::table('multi_login_session');
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT * FROM `$table` ORDER BY `id` DESC LIMIT $start, $perpage");
        foreach ($sessions as &$session) {
            $session['ip'] = long2ip($session['ip']);
        }
        return $sessions;
    }
}