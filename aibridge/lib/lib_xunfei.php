<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_xunfei {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		// 讯飞使用 Bearer token
		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $api_key,
		];

		$body = [
			'model' => $model,
			'messages' => $messages,
		];
		if (!empty($extra['temperature'])) $body['temperature'] = floatval($extra['temperature']);

		$result = lib_aibridge::http_request($api_url, $headers, $body);
		if (isset($result['error'])) return $result;

		$data = $result['data'];
		if (isset($data['header']['code']) && $data['header']['code'] !== 0) {
			return ['error' => $data['header']['message'] ?? '讯飞 API 错误'];
		}

		$choices = $data['choices'] ?? [];
		$content = '';
		if (!empty($choices)) {
			$content = $choices[0]['content'] ?? '';
		}

		return [
			'content' => $content,
			'prompt_tokens' => 0,
			'completion_tokens' => 0,
		];
	}
}