<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_openai {

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		$body = [
			'model' => $model,
			'messages' => $messages,
			'stream' => $stream,
		];
		if (!empty($extra['temperature'])) $body['temperature'] = floatval($extra['temperature']);
		if (!empty($extra['max_tokens'])) $body['max_tokens'] = intval($extra['max_tokens']);

		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $api_key,
		];

		if ($stream) {
			$full_content = '';
			$result = lib_aibridge::http_stream_request($api_url, $headers, $body, function($chunk) use (&$full_content) {
				$lines = explode("\n", $chunk);
				foreach ($lines as $line) {
					$line = trim($line);
					if (strpos($line, 'data: ') === 0) {
						$json_str = substr($line, 6);
						if ($json_str === '[DONE]') continue;
						$json = json_decode($json_str, true);
						if (isset($json['choices'][0]['delta']['content'])) {
							$full_content .= $json['choices'][0]['delta']['content'];
						}
					}
				}
			});
			if (isset($result['error'])) return $result;
			return [
				'content' => $full_content,
				'prompt_tokens' => 0,
				'completion_tokens' => 0,
			];
		}

		$result = lib_aibridge::http_request($api_url, $headers, $body);
		if (isset($result['error'])) return $result;

		$data = $result['data'];
		if (isset($data['error'])) {
			return ['error' => $data['error']['message'] ?? 'OpenAI API 错误'];
		}

		return [
			'content' => $data['choices'][0]['message']['content'] ?? '',
			'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
			'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
		];
	}
}