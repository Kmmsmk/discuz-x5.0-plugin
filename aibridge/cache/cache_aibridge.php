<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function build_cache_plugin_aibridge() {
	// 刷新平台配置缓存
	memory('rm', 'aibridge_platforms');
	memory('rm', 'aibridge_enabled');
	memory('rm', 'aibridge_baidu_token');

	// 重新加载
	$rows = DB::fetch_all('SELECT * FROM %t ORDER BY id ASC', ['aibridge_config']);
	$platforms = [];
	foreach ($rows as $row) {
		$row['api_key'] = $row['api_key'] ? authcode($row['api_key'], 'DECODE') : '';
		$platforms[$row['platform']] = $row;
	}
	memory('set', 'aibridge_platforms', $platforms);

	$enabled = [];
	foreach ($rows as $row) {
		if ($row['enabled']) {
			$row['api_key'] = $row['api_key'] ? authcode($row['api_key'], 'DECODE') : '';
			$enabled[$row['platform']] = $row;
		}
	}
	memory('set', 'aibridge_enabled', $enabled);
}