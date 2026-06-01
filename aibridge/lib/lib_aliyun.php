<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_aliyun {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		// 转换消息格式
		$aliyun_messages = [];
		foreach ($messages as $msg) {
			if ($msg['role'] === 'system') {
				$aliyun_messages[] = ['role' => 'system', 'content' => $msg['content']];
			} else {
				$aliyun_messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
			}
		}

		$body = [
			'model' => $model,
			'input' => [
				'messages' => $aliyun_messages,
			],
			'parameters' => new \stdClass(),
		];
		if (!empty($extra['temperature'])) {
			$body['parameters']->temperature = floatval($extra['temperature']);
		}

		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $api_key,
		];

		$result = lib_aibridge::http_request($api_url, $headers, $body);
		if (isset($result['error'])) return $result;

		$data = $result['data'];
		if (isset($data['code']) && $data['code'] !== '') {
			return ['error' => $data['message'] ?? '阿里云 API 错误'];
		}

		$output = $data['output'] ?? [];
		return [
			'content' => $output['text'] ?? '',
			'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
			'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
		];
	}
}