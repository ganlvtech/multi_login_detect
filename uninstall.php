<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$sql = <<<EOF
DROP TABLE IF EXISTS `pre_multi_login_session`;
DROP TABLE IF EXISTS `pre_multi_login_log`;
EOF;

runquery($sql);

$finish = true;
