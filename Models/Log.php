<?php

namespace Ganlv\MultiLoginDetect\Models;

use DB;

class Log
{
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
        return DB::insert('multi_login_log', [
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
        $table = DB::table('multi_login_log');
        return DB::result_first("SELECT COUNT(*) FROM `$table`");
    }

    public static function deleteBefore($timestamp)
    {
        $timestamp = daddslashes($timestamp);
        return DB::delete('multi_login_log', "`last_online_time2` < '$timestamp'");
    }

    public static function fetchAllByPage($page, $perpage = 20)
    {
        $table = DB::table('multi_login_log');
        $start = ($page - 1) * $perpage;
        $sessions = DB::fetch_all("SELECT * FROM `$table` ORDER BY `id` DESC LIMIT $start, $perpage");
        foreach ($sessions as &$session) {
            $session['ip1'] = long2ip($session['ip1']);
            $session['ip2'] = long2ip($session['ip2']);
        }
        return $sessions;
    }
}