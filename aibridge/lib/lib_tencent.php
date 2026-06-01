<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_tencent {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		// 腾讯混元使用 Bearer token
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
		if (isset($data['Error'])) {
			return ['error' => $data['Error']['Message'] ?? '腾讯混元 API 错误'];
		}

		$choices = $data['Choices'] ?? [];
		$content = '';
		if (!empty($choices)) {
			$msg = $choices[0]['Message'] ?? [];
			$content = $msg['Content'] ?? '';
		}

		return [
			'content' => $content,
			'prompt_tokens' => 0,
			'completion_tokens' => 0,
		];
	}
}