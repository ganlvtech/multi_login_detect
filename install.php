<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS `pre_multi_login_session` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `auth` CHAR(255) NOT NULL COMMENT 'Auth code',
  `saltkey` CHAR(8) NOT NULL COMMENT 'Salt key',
  `ip` INT(11) UNSIGNED NOT NULL NOT NULL COMMENT 'IP',
  `uid` MEDIUMINT UNSIGNED NOT NULL COMMENT 'User ID', 
  `username` CHAR(15) NOT NULL,
  `ua` VARCHAR(1024) NOT NULL COMMENT 'User Agent',
  `login_time` INT(11) UNSIGNED NOT NULL COMMENT 'Last activity time',
  `last_online_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Last online time time',
  PRIMARY KEY (`id`),
  INDEX (`auth`),
  INDEX (`uid`)
) ENGINE = MEMORY;

CREATE TABLE IF NOT EXISTS `pre_multi_login_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` INT(11) NOT NULL COMMENT 'User ID',
  `username` CHAR(15) NOT NULL,
  `ip1` INT(11) NOT NULL COMMENT 'Latest IP',
  `auth1` CHAR(255) NOT NULL COMMENT 'Latest auth code',
  `saltkey1` CHAR(8) NOT NULL COMMENT 'Latest salt key',
  `ua1` VARCHAR(1024) NOT NULL COMMENT 'Latest user agent',
  `login_time1` INT(11) UNSIGNED NOT NULL COMMENT 'Latest session last activity time',
  `last_online_time1` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Latest session last online time',
  `ip2` INT(11) NOT NULL COMMENT 'Current IP',
  `auth2` CHAR(255) NOT NULL COMMENT 'Current auth code',
  `saltkey2` CHAR(8) NOT NULL COMMENT 'Current salt key',
  `ua2` VARCHAR(1024) NOT NULL COMMENT 'Latest user agent',
  `login_time2` INT(11) UNSIGNED NOT NULL COMMENT 'Current session last activity time',
  `last_online_time2` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Current session last online time',
  PRIMARY KEY (`id`),
  INDEX (`uid`)
) ENGINE = MyISAM;
EOF;

runquery($sql);

$finish = true;
