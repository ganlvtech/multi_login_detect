<?php

namespace Ganlv\MultiLoginDetect\Libraries;

use C;

class Helpers
{
    /**
     * 获取本地化翻译
     *
     * @param string $langvar
     *
     * @return mixed|null|string
     */
    public static function lang($langvar)
    {
        return lang('plugin/multi_login_detect', 'multi_login_detect_' . $langvar);
    }

    /**
     * 安全地退出登录
     * 本函数会通过showmessage调用dexit
     * 参考 member.php?mod=logging&action=logout
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
        // ucenter同步退出登录
        if ($setting['allowsynlogin']) {
            uc_user_synlogout();
        }

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
     * @param string $reason 禁止原因，会生成禁止记录，并以通知形式提示用户，留空则不会通知用户
     *
     * @return bool
     */
    public static function banUser($uid, $type = 'post', $time = 0, $reason = '')
    {
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
        /** @var \table_common_member $table_common_member */
        $table_common_member = C::t('common_member' . $tableext);
        $table_common_member->update($member['uid'], $setarr);
        /** @var \table_common_member_field_forum $table_common_member */
        $table_common_member_field_forum = C::t('common_member_field_forum' . $tableext);
        $table_common_member_field_forum->update($member['uid'], ['groupterms' => ($groupterms ? serialize($groupterms) : '')]);

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
        if ($reason) {
            // 用户自己收到新通知
            $notearr = [
                'user' => self::lang('notify_user'),
                'day' => (int)$time,
                'reason' => $reason,
                'from_id' => 0,
                'from_idtype' => $from_idtype,
            ];
            notification_add($member['uid'], 'system', $noticekey, $notearr, 1);
        }

        return true;
    }

    /**
     * IP掩码检查
     *
     * @link https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5/594134#594134
     *
     * @param string $ip IP
     * @param string $range IP掩码，例如 192.168.1.1/24 127.0.0.1/8
     *
     * @return bool
     */
    public static function cidr_match($ip, $range)
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        return ($ip & $mask) === ($subnet & $mask);
    }

    /**
     * 格式化时间戳为 'Y-m-d H:i:s'
     *
     * @param $timestamp
     *
     * @return false|string
     */
    public static function formatDate($timestamp) {
        return date('Y-m-d H:i:s', $timestamp);
    }
}