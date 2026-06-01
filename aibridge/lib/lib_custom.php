<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_custom {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		// 自定义平台兼容 OpenAI Chat Completions 格式
		if (empty($api_url)) {
			return ['error' => '自定义平台必须填写 API 地址'];
		}
		return lib_openai::chat($api_url, $api_key, $model, $messages, $extra, $stream);
	}
}