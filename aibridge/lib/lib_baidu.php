<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class lib_baidu {

	// 获取百度 access_token
	private static function get_access_token($api_key) {
		// api_key 格式: client_id|client_secret
		$parts = explode('|', $api_key);
		if (count($parts) !== 2) return null;
		$client_id = $parts[0];
		$client_secret = $parts[1];

		$cache_key = 'aibridge_baidu_token';
		$token = memory('get', $cache_key);
		if ($token) return $token;

		$url = "https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($response, true);
		if (isset($result['access_token'])) {
			memory('set', $cache_key, $result['access_token'], 86400);
			return $result['access_token'];
		}
		return null;
	}

	public static function chat($api_url, $api_key, $model, $messages, $extra = [], $stream = false) {
		$access_token = self::get_access_token($api_key);
		if (!$access_token) {
			return ['error' => '百度 API Key 格式错误，请使用 client_id|client_secret 格式'];
		}

		$url = $api_url . '?access_token=' . $access_token;

		// 转换消息格式为百度格式
		$baidu_messages = [];
		foreach ($messages as $msg) {
			$baidu_messages[] = [
				'role' => $msg['role'],
				'content' => $msg['content'],
			];
		}

		$body = [
			'messages' => $baidu_messages,
		];
		if (!empty($extra['temperature'])) $body['temperature'] = floatval($extra['temperature']);

		$headers = ['Content-Type: application/json'];

		$result = lib_aibridge::http_request($url, $headers, $body);
		if (isset($result['error'])) return $result;

		$data = $result['data'];
		if (isset($data['error_code'])) {
			return ['error' => $data['error_msg'] ?? '百度 API 错误'];
		}

		return [
			'content' => $data['result'] ?? '',
			'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
			'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
		];
	}
}