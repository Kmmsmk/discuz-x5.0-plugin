<?php

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$createSql = <<<EOF
DROP TABLE IF EXISTS cdb_aibridge_config;
DROP TABLE IF EXISTS cdb_aibridge_log;
DROP TABLE IF EXISTS cdb_aibridge_conversation;
DROP TABLE IF EXISTS cdb_aibridge_prompt;
EOF;

runquery($createSql);

if(method_exists('menu', 'platform_del')) {
	menu::platform_del('aibridge');
}

echo $installlang['uninstall'];

$finish = TRUE;