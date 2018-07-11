<?php

namespace Ganlv\MultiLoginDetect\Libraries;

use Ganlv\MultiLoginDetect\Models\Log;
use Ganlv\MultiLoginDetect\Models\Session;

class MultiLoginDetect
{
    /** @var array 配置 */
    protected $config = [
        'group_id_white_list' => [],
        'message' => '',
        'allow_same_auth' => true,
        'allow_different_ua' => true,
        'allow_same_ip_range' => true,
        'ip_cidr' => 24,
        'need_ban' => false,
        'ban_type' => 'post',
        'ban_time' => 86400,
        'ban_reason' => '',
    ];
    /** @var array 当前 session */
    protected $currentSession = [];
    /** @var array 最新 session */
    protected $latestSession = [];

    // Main code

    /**
     * MultiLoginDetect constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $defaultConfig = [
            'group_id_white_list' => [],
            'message' => Helpers::lang('message'),
            'allow_same_auth' => true,
            'allow_different_ua' => true,
            'allow_same_ip_range' => true,
            'ip_cidr' => 24,
            'need_ban' => false,
            'ban_type' => 'post',
            'ban_time' => 86400,
            'ban_reason' => Helpers::lang('ban_reason'),
        ];
        $config = array_merge($defaultConfig, $config);
        if (!is_array($config['group_id_white_list'])) {
            $config['group_id_white_list'] = unserialize($config['group_id_white_list']);
            if (!is_array($config['group_id_white_list'])) {
                $config['group_id_white_list'] = [];
            }
        }
        $config['group_id_white_list'] = (array)$config['group_id_white_list'];
        $config['message'] = (string)$config['message'];
        $config['allow_same_auth'] = (bool)$config['allow_same_auth'];
        $config['allow_different_ua'] = (bool)$config['allow_different_ua'];
        $config['allow_same_ip_range'] = (bool)$config['allow_same_ip_range'];
        $config['ip_cidr'] = (int)$config['ip_cidr'];
        $config['need_ban'] = (bool)$config['need_ban'];
        $config['ban_time'] = (int)$config['ban_time'];
        $config['ban_reason'] = (string)$config['ban_reason'];
        $this->config = $config;
    }

    /**
     * 判断用户是否登录
     * 未登录用户的 uid 为 0
     *
     * @return bool
     */
    public static function isLogin()
    {
        return Request::uid() > 0;
    }

    /**
     * 判断当前请求是否是退出登录
     *
     * @return bool
     */
    public static function isLogoutRequest()
    {
        return CURSCRIPT === 'member' && CURMODULE === 'logging' && $_GET['action'] === 'logout';
    }

    /**
     * 判断退出登录请求，并删除退出登录的 session 记录
     */
    public static function tryHandleLogoutRequest()
    {
        if (self::isLogoutRequest()) {
            Session::deleteByAuth(Request::auth());
        }
    }

    /**
     * 用户组是否在白名单中
     *
     * @param int $group_id
     *
     * @return bool
     */
    public function isInWhiteList($group_id)
    {
        return in_array($group_id, $this->config['group_id_white_list']);
    }

    /**
     * 插入当前 session 信息
     *
     * @return mixed
     */
    public static function insertCurrentSession()
    {
        return Session::insert([
            'uid' => Request::uid(),
            'username' => Request::username(),
            'ip' => Request::ip(),
            'auth' => Request::auth(),
            'saltkey' => Request::saltKey(),
            'ua' => Request::userAgent(),
        ]);
    }

    /**
     * 用户是否仍在线
     *
     * @param int $last_online_time 最后在线时间
     *
     * @return bool
     */
    public static function isOnline($last_online_time)
    {
        return TIMESTAMP - $last_online_time < Request::onlineTimeSpan() * 60;
    }


    /**
     * 判断是否是重复登录
     *
     * @return bool|array 异常登录的 session1
     */
    public function detectMultiLogin()
    {
        // 判断用户是否登录，未登录用户不会多点登陆。
        if (!self::isLogin()) {
            return false;
        }

        // 用户在不受控用户组中，不检查。
        if ($this->isInWhiteList(Request::groupId())) {
            return false;
        }

        // 如果当前登录 session 的 auth 在数据库中不存在（即刚登陆的新会话），刚登陆不会多点登陆，只登记，不继续检查。
        $this->currentSession = Session::fetchByAuth(Request::auth());
        if (!$this->currentSession) {
            self::insertCurrentSession();
            return false;
        }

        // 如果 auth 相同，但是 IP 或 UserAgent 不相同，则可能是较为高级的多点登录，算作一个新的 session，
        if (!$this->config['allow_same_auth']) {
            if ($this->config['allow_same_ip_range']) {
                // IP 段不同，则是异地登录
                if (!Helpers::cidr_match(Request::ip(), $this->currentSession['ip'] . '/' . $this->config['ip_cidr'])) {
                    return $this->currentSession;
                }
            } else {
                if (Request::ip() !== $this->currentSession['ip']) {
                    return $this->currentSession;
                }
            }
            if (!$this->config['allow_different_ua']) {
                // UserAgent 不同，则是异地登录
                if ($this->currentSession['ua'] !== Request::userAgent()) {
                    return $this->currentSession;
                }
            }
        }

        // 如果已存在，则更新最后在线时间
        Session::touchById($this->currentSession['id']);

        // 如果当前账户就是最新的会话，则为正常登录状态。
        $this->latestSession = Session::fetchLatestByUid(Request::uid());
        if ($this->currentSession['auth'] === $this->latestSession['auth']) {
            return false;
        }

        // 如果在同一IP地址段，则为正常登录状态。
        if ($this->config['allow_same_ip_range']) {
            if (Helpers::cidr_match(Request::ip(), $this->latestSession['ip'] . '/' . $this->config['ip_cidr'])) {
                return false;
            }
        }

        // 是多点登录
        return $this->latestSession;
    }

    /**
     * 尝试处理多点登录
     * 直接调用此方法即可，没有其他要求。
     *
     * @return bool
     */
    public function tryHandleMultiLogin()
    {
        $session1 = $this->detectMultiLogin();
        if (!$session1) {
            return false;
        }

        // 记录异常登录状态
        Log::insert([
            'uid' => Request::uid(),
            'username' => Request::username(),
            // 最新的登录信息
            'ip1' => $session1['ip'],
            'auth1' => $session1['auth'],
            'saltkey1' => $session1['saltkey'],
            'ua1' => $session1['ua'],
            'login_time1' => $session1['login_time'],
            'last_online_time1' => $session1['last_online_time'],
            // 当前登录信息
            'ip2' => Request::ip(),
            'auth2' => Request::auth(),
            'saltkey2' => Request::saltKey(),
            'ua2' => Request::userAgent(),
            'login_time2' => $this->currentSession['login_time'],
            'last_online_time2' => TIMESTAMP,
        ]);

        // 清除多余的登录信息
        Session::deleteByAuth(Request::auth());

        // 如果异常登录封号
        if ($this->config['need_ban']) {
            Helpers::banUser(Request::uid(), $this->config['ban_type'], $this->config['ban_time'], $this->config['ban_reason']);
        }

        // 退出登录
        Helpers::logout($this->config['message']);

        // cannot reach
        return true;
    }
}
