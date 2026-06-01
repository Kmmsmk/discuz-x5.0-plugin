<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_claude {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		// Claude 使用 x-api-key
		$headers = [
			'Content-Type: application/json',
			'x-api-key: ' . $api_key,
			'anthropic-version: 2023-06-01',
		];

		// 分离 system 消息
		$system_prompt = '';
		$claude_messages = [];
		foreach ($messages as $msg) {
			if ($msg['role'] === 'system') {
				$system_prompt = $msg['content'];
			} else {
				$claude_messages[] = $msg;
			}
		}

		$body = [
			'model' => $model,
			'messages' => $claude_messages,
			'max_tokens' => !empty($extra['max_tokens']) ? intval($extra['max_tokens']) : 4096,
		];
		if ($system_prompt) {
			$body['system'] = $system_prompt;
		}

		$result = lib_aibridge::http_request($api_url, $headers, $body);
		if (isset($result['error'])) return $result;

		$data = $result['data'];
		if (isset($data['error'])) {
			return ['error' => $data['error']['message'] ?? 'Claude API 错误'];
		}

		$content = '';
		if (isset($data['content'][0]['text'])) {
			$content = $data['content'][0]['text'];
		}

		return [
			'content' => $content,
			'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
			'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
		];
	}
}