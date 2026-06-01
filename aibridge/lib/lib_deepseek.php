<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_deepseek {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		// DeepSeek 兼容 OpenAI 格式
		return lib_openai::chat($api_url, $api_key, $model, $messages, $extra, $stream);
	}
}