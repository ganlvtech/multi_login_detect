<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class plugin_multi_login_detect
{
    /**
     * 全部所有页面钩子，从 1_diy_forum_discuz.tpl.php 等前台页面的第一行 hookscriptoutput 的调用。在页面渲染前触发。
     *
     * @return string
     */
    public static function global_header()
    {
        global $_G;
        $config = [
            'group_id_white_list' => [],
            'message' => '您的帐户由于异地登录，您已被迫下线，同时已被封号，如果是误判需解封请联系管理！',
            'need_ban' => false,
            'ban_type' => 'post',
            'ban_time' => 86400,
            'ban_reason' => '您的帐户由于异地登录，您已被迫下线，同时已被封号，如果是误判需解封请联系管理！',
        ];
        $config = array_merge($config, $_G['cache']['plugin']['multi_login_detect']);

        // 判断用户是否登录（未登录用户的 uid 为 0），只有已登录用户才做检查。
        $uid = $_G['uid'];
        if ($uid <= 0) {
            return '';
        }

        // 用户在不受控用户组中，直接返回，不继续检查。
        if (!is_array($config['group_id_white_list'])) {
            $config['group_id_white_list'] = unserialize($config['group_id_white_list']);
        }
        if (in_array($_G['group']['groupid'], $config['group_id_white_list'])) {
            return '';
        }

        // 如果当前登录会话 id 在数据库中不存在（刚登陆的新会话），则记录这个会话 id，不继续检查。
        $table_multi_login_session = DB::table('multi_login_session');
        $auth = daddslashes($_G['cookie']['auth']);
        $ua = substr($_SERVER['HTTP_USER_AGENT'], 0, 1024);
        $original_session = DB::fetch_first("SELECT `id` FROM `$table_multi_login_session` WHERE `uid` = '$uid' AND `auth` = '$auth' LIMIT 1");
        if (!$original_session) {
            DB::insert('multi_login_session', [
                'uid' => $uid,
                'username' => $_G['member']['username'],
                'ip' => ip2long($_G['clientip']),
                'auth' => $_G['cookie']['auth'],
                'saltkey' => $_G['cookie']['saltkey'],
                'ua' => $ua,
                'login_time' => TIMESTAMP,
                'last_online_time' => TIMESTAMP,
            ]);
            return '';
        }

        // 如果已存在，则更新最后在线时间
        DB::update('multi_login_session', [
            'last_online_time' => TIMESTAMP,
        ], "`id` = '{$original_session['id']}'");

        // 如果当前账户就是最新的会话，则为正常登录状态，直接返回。
        $latest_session = DB::fetch_first("SELECT * FROM `$table_multi_login_session` WHERE `uid` = '$uid' ORDER BY `id` DESC LIMIT 1");
        if ($original_session['id'] >= $latest_session['id']) {
            return '';
        }

        // 记录异常登录状态
        DB::insert('multi_login_log', [
            'uid' => $uid,
            'username' => $_G['member']['username'],
            // 最新的登录信息
            'ip1' => $latest_session['ip'],
            'auth1' => $latest_session['auth'],
            'saltkey1' => $latest_session['saltkey'],
            'ua1' => $latest_session['ua'],
            'login_time1' => $latest_session['login_time'],
            'last_online_time1' => $latest_session['last_online_time'],
            // 当前登录信息
            'ip2' => ip2long($_G['clientip']),
            'auth2' => $_G['cookie']['auth'],
            'saltkey2' => $_G['cookie']['saltkey'],
            'ua2' => $ua,
            'login_time2' => TIMESTAMP,
            'last_online_time2' => TIMESTAMP,
        ]);

        // 清除之前的多余的登录信息
        DB::delete('multi_login_session', "`id` < '{$latest_session['id']}' and `uid` = '$uid'");

        // 如果异常登录封号
        if ($config['need_ban']) {
            self::banUser($uid, $config['ban_type'], $config['ban_time'], $config['ban_reason']);
        }

        // 退出登录
        self::logout($config['message']);

        // cannot reach
        return '';
    }

    /**
     * 安全地退出登录
     *
     * 参考 member.php?mod=loggint&action=logout
     *
     * @param string $message 退出登录提示信息
     */
    public static function logout($message = '')
    {
        global $_G;

        // member.php
        require_once libfile('function/member');
        require_once libfile('class/member');

        // member_logging.php
        $setting = $_G['setting'];

        // \logging_ctl::logging_ctl();
        require_once libfile('function/misc');
        loaducenter();

        // \logging_ctl::on_logout();
        // ucenter同步退出登录，如果formhash不相等则表示已退出登录
        $ucsynlogout = $setting['allowsynlogin'] ? uc_user_synlogout() : '';
        // if ($_GET['formhash'] != $_G['formhash']) {
        //     // TODO
        //     showmessage('logout_succeed', dreferer(), ['formhash' => FORMHASH, 'ucsynlogout' => $ucsynlogout, 'referer' => rawurlencode(dreferer())]);
        // }

        // 主站清除Cookie，用户组改为游客，清空登录信息
        clearcookies();
        $_G['groupid'] = $_G['member']['groupid'] = 7;
        $_G['uid'] = $_G['member']['uid'] = 0;
        $_G['username'] = $_G['member']['username'] = $_G['member']['password'] = '';
        $_G['setting']['styleid'] = $setting['styleid'];

        if ($message) {
            showmessage($message);
        }
    }

    /**
     * 禁止用户
     *
     * 参考 admin.php?action=members&operation=ban
     *
     * @param int $uid UID
     * @param string $type 禁止类型：禁止发言 'post'、禁止访问 'visit'、锁定用户 'status'（锁定用户后该用户将无法访问及进行任何操作，包括其它用户也无法访问该用户的相关信息）
     * @param int|float $time 禁止时长（天），小于等于 0 表示永久禁止（支持小数）（锁定用户只能是永久锁定，必须手动解锁）
     * @param string $reason 禁止原因，会以通知形式提示用户
     *
     * @return bool
     */
    public static function banUser($uid, $type = 'post', $time = 0, $reason = 'Multiple logging.')
    {
        global $_G;

        if (!in_array($type, ['post', 'visit', 'status'])) {
            return false;
        }

        // upload/source/admincp/admincp_members.php:43
        // 获取用户信息
        /** @var array $member Hello */
        $member = getuserbyuid($uid);
        if (!$member) {
            return false;
        }
        $tableext = isset($member['_inarchive']) ? '_archive' : '';

        // upload/source/admincp/admincp_members.php:1497
        /**
         * @var array $groupterms common_member_field_forum 表中的字段，使用 serialize 存储在库中
         * 当设置临时用户组时，用于记录原来用户组的信息，包括 adminid, groupid, 过期时间等
         * $groupterms 数组格式为
         * [
         *     'main' => [
         *         'time' => 恢复时间,
         *         'adminid' => original_adminid（原来的管理组 id）,
         *         'groupid' => original_groupid（原来的用户组 id）
         *     ],
         *     'ext' => [new_groupid => 过期时间],
         * ]
         */
        $groupterms = [];
        /** @var array $setarr UPDATE `common_member` SET 语句中更新的新数据 */
        $setarr = [];
        if ($type === 'status') {
            // 锁定用户
            $setarr['status'] = -1;
        } else {
            // 禁止发言、禁止访问采用更改用户组的方式
            /** 禁止发言 groupid 是 4，禁止访问 groupid 是 5 */
            $groupidnew = ($type === 'post') ? 4 : 5;
            // 如果已经禁止了，就不重新禁止了，避免把 groupterms 覆盖掉
            if ($groupidnew !== $member['groupid']) {
                // 过期时间
                if ($time > 0) {
                    // 过期时间大于 0 表示会定时恢复原来用户组
                    $expiry = TIMESTAMP + (int)(86400 * $time);
                    $groupterms = [
                        'main' => [
                            'time' => $expiry,
                            'adminid' => $member['adminid'],
                            'groupid' => $member['groupid'],
                        ],
                        'ext' => [
                            $groupidnew => $expiry,
                        ],
                    ];
                    // groupexpiry 是 common_member 表中的字段，用户组过期时间，配合 common_member_field_forum 表的 groupterms 字段完成临时用户组的功能
                    require_once libfile('function/forum');
                    $setarr['groupexpiry'] = groupexpiry($groupterms);
                } else {
                    // 过期时间小于等于 0 表示永久禁止
                    $setarr['groupexpiry'] = 0;
                }
                /** 被禁用户新的管理组是 -1 */
                $setarr['adminid'] = -1;
                $setarr['groupid'] = $groupidnew;
            }
            // 删除被禁用户的所有回帖关联点评
            // 启用点评关联回帖(post comment related reply post)功能：打开 全局 > 站点功能 > 帖子点评 > 楼层回复，打开 用户 > 用户组 > 帖子相关 > 帖子直接点评 > 点评回复
            // 如果有需要则取消注释
            // $postcomment_cache_pid = [];
            // foreach (C::t('forum_postcomment')->fetch_all_by_authorid($member['uid']) as $postcomment) {
            //     $postcomment_cache_pid[$postcomment['pid']] = $postcomment['pid'];
            // }
            // C::t('forum_postcomment')->delete_by_authorid($member['uid'], false, true);
            // if ($postcomment_cache_pid) {
            //     C::t('forum_postcache')->delete($postcomment_cache_pid);
            // }
        }
        // 进行数据库操作
        C::t('common_member' . $tableext)->update($member['uid'], $setarr);
        C::t('common_member_field_forum' . $tableext)->update($member['uid'], ['groupterms' => ($groupterms ? serialize($groupterms) : '')]);

        // 用户违规记录和用户新通知
        if ($type === 'post') {
            $crimeaction = 'crime_banspeak';
            $noticekey = 'member_ban_speak';
            $from_idtype = 'banspeak';
        } elseif ($type === 'visit') {
            $crimeaction = 'crime_banvisit';
            $noticekey = 'member_ban_visit';
            $from_idtype = 'banvisit';
        } else {
            // $bannew === 'status'
            $crimeaction = 'crime_banstatus';
            $noticekey = 'member_ban_status';
            $from_idtype = 'banstatus';
        }
        $reason = trim($reason);
        // 后台管理：工具 > 运行记录 > 违规记录
        require_once libfile('function/member');
        crime('recordaction', $member['uid'], $crimeaction, lang('forum/misc', 'crime_reason', ['reason' => $reason]));
        // 用户自己收到新通知
        $notearr = [
            'user' => '重复登录检测插件',
            'day' => (int)$time,
            'reason' => $reason,
            'from_id' => 0,
            'from_idtype' => $from_idtype,
        ];
        notification_add($member['uid'], 'system', $noticekey, $notearr, 1);

        return true;
    }
}

class plugin_multi_login_detect_member extends plugin_multi_login_detect
{
}

class mobileplugin_multi_login_detect extends plugin_multi_login_detect
{
    public static function global_header_mobile()
    {
        self::global_header();
    }
}