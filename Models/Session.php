<?php

namespace Ganlv\MultiLoginDetect\Models;

use DB;

class Session
{
    const TABLE = 'multi_login_session';

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

    public static function decode($data)
    {
        $data['ip'] = long2ip($data['ip']);
        return $data;
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
        $auth = DB::quote($auth);
        return DB::delete(self::TABLE, "`auth` = $auth", 1);
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
        $table = DB::table(self::TABLE);
        $auth = substr($auth, 0, 255);
        $auth = DB::quote($auth);
        $session = DB::fetch_first("SELECT * FROM `$table` WHERE `auth` = $auth LIMIT 1");
        if ($session) {
            $session = self::decode($session);
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
        $table = DB::table(self::TABLE);
        $session = DB::fetch_first("SELECT * FROM `$table` WHERE `uid` = '$uid' ORDER BY `id` DESC LIMIT 1");
        if ($session) {
            $session = self::decode($session);
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
        return DB::insert(self::TABLE, [
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
        return DB::update(self::TABLE, [
            'last_online_time' => TIMESTAMP,
        ], "`id` = '$id'");
    }

    /**
     * @param int $timestamp
     *
     * @return bool
     */
    public static function deleteBefore($timestamp)
    {
        return DB::delete(self::TABLE, "`last_online_time` < '$timestamp'");
    }

    /**
     * @param int $timestamp
     *
     * @return bool
     */
    public static function deleteByUidBefore($uid, $timestamp)
    {
        return DB::delete(self::TABLE, "`uid` = '$uid' AND `last_online_time` < '$timestamp'");
    }

    public static function count()
    {
        $table = DB::table(self::TABLE);
        return DB::result_first("SELECT COUNT(*) FROM `$table`");
    }

    /**
     * @param int $page
     * @param int $perpage
     *
     * @return array
     */
    public static function fetchAllByPage($page, $perpage = 20)
    {
        $table = DB::table(self::TABLE);
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT * FROM `$table` ORDER BY `id` DESC LIMIT $start, $perpage");
        foreach ($sessions as &$session) {
            $session = self::decode($session);
        }
        return $sessions;
    }

    public static function countOfUid($uid)
    {
        $table = DB::table(self::TABLE);
        $uid = daddslashes($uid);
        return DB::result_first("SELECT COUNT(*) FROM `$table` WHERE `uid` = '$uid'");
    }

    /**
     * @param int $uid
     * @param int $page
     * @param int $perpage
     *
     * @return array
     */
    public static function fetchAllOfUidByPage($uid, $page, $perpage = 20)
    {
        $table = DB::table(self::TABLE);
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT * FROM `$table` WHERE `uid` = '$uid' ORDER BY `id` DESC LIMIT $start, $perpage");
        foreach ($sessions as &$session) {
            $session = self::decode($session);
        }
        return $sessions;
    }
}