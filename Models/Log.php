<?php

namespace Ganlv\MultiLoginDetect\Models;

use DB;

class Log
{
    const TABLE = 'multi_login_log';

    public static function fields()
    {
        return [
            'id',
            'uid',
            'username',
            // 最新的登录信息
            'ip1',
            'auth1',
            'saltkey1',
            'ua1',
            'login_time1',
            'last_online_time1',
            // 当前登录信息
            'ip2',
            'auth2',
            'saltkey2',
            'ua2',
            'login_time2',
            'last_online_time2',
        ];
    }

    public static function decode($data) {
        $data['ip1'] = long2ip($data['ip1']);
        $data['ip2'] = long2ip($data['ip2']);
        return $data;
    }

    /**
     * 插入log
     *
     * @param array $data [
     *     'uid' => 0,
     *     'username' => 'admin',
     *     'ip1' => '127.0.0.1',
     *     'auth1' => 'abc123+-...',
     *     'saltkey1' => 'abcd1234',
     *     'ua1' => 'Mozilla/5.0 ...',
     *     'login_time1' => 1530000000,
     *     'last_online_time1' => 1530000000,
     *     'ip2' => '127.0.0.1',
     *     'auth2' => 'abc123+-...',
     *     'saltkey2' => 'abcd1234',
     *     'ua2' => 'Mozilla/5.0 ...',
     *     'login_time2' => 1530000000,
     * ]
     *
     * @return mixed
     */
    public static function insert($data)
    {
        return DB::insert(self::TABLE, [
            'uid' => $data['uid'],
            'username' => mb_substr($data['username'], 0, 15),
            // 最新的登录信息
            'ip1' => ip2long($data['ip1']),
            'auth1' => substr($data['auth1'], 0, 255),
            'saltkey1' => substr($data['saltkey1'], 0, 8),
            'ua1' => substr($data['ua1'], 0, 1024),
            'login_time1' => $data['login_time1'],
            'last_online_time1' => $data['last_online_time1'],
            // 被挤掉的登录信息
            'ip2' => ip2long($data['ip2']),
            'auth2' => substr($data['auth2'], 0, 255),
            'saltkey2' => substr($data['saltkey2'], 0, 8),
            'ua2' => substr($data['ua2'], 0, 1024),
            'login_time2' => $data['login_time2'],
            'last_online_time2' => TIMESTAMP,
        ]);
    }

    public static function count()
    {
        $table = DB::table(self::TABLE);
        return DB::result_first("SELECT COUNT(*) FROM `$table`");
    }

    public static function countOfUid($uid)
    {
        $table = DB::table(self::TABLE);
        $uid = daddslashes($uid);
        return DB::result_first("SELECT COUNT(*) FROM `$table` WHERE `uid` = '$uid'");
    }

    /**
     * @param int $timestamp
     *
     * @return bool
     */
    public static function deleteBefore($timestamp)
    {
        return DB::delete(self::TABLE, "`last_online_time2` < '$timestamp'");
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

    public static function userCount()
    {
        $table = DB::table(self::TABLE);
        return DB::result_first("SELECT COUNT(DISTINCT(`uid`)) FROM `$table`");
    }

    /**
     * @param int $page
     * @param int $perpage
     *
     * @return array
     */
    public static function fetchCountGroupByUserByPage($page, $perpage = 20)
    {
        $table = DB::table(self::TABLE);
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT `uid`, `username`, COUNT(*) AS `count` FROM `$table` GROUP BY `uid` ORDER BY `count` DESC LIMIT $start, $perpage");
        return $sessions;
    }

    /**
     * @param int $page
     * @param int $perpage
     *
     * @return array
     */
    public static function fetchCountGroupByUserByPageAfter($last_online_time1, $page, $perpage = 20)
    {
        $table = DB::table(self::TABLE);
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT `uid`, `username`, COUNT(*) AS `count` FROM `$table` WHERE `last_online_time1` > '$last_online_time1' GROUP BY `uid` ORDER BY `count` DESC LIMIT $start, $perpage");
        return $sessions;
    }

    /**
     * @param $page
     * @param int $perpage
     * @todo not used
     *
     * @return array
     */
    public static function fetchOrderByIpCountByPage($page, $perpage = 20)
    {
        $table = DB::table(self::TABLE);
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT `$table`.*, `ip_count`.`count` FROM `$table` LEFT JOIN (SELECT `ip2`, COUNT(*) AS `count` FROM `$table` GROUP BY `ip2`) AS `ip_count` ON `$table`.`ip2` = `ip_count`.`ip2` ORDER BY `ip_count`.`count` DESC LIMIT $start, $perpage");
        foreach ($sessions as &$session) {
            $session = self::decode($session);
        }
        return $sessions;
    }
}