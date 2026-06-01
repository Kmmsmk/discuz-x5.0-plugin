<?php

namespace aibridge;

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class lib_aibridge {

    // 平台名称映射
    public static $platform_names = [
        'openai'   => 'OpenAI',
        'baidu'    => '百度文心',
        'aliyun'   => '阿里通义',
        'xunfei'   => '讯飞星火',
        'tencent'  => '腾讯混元',
        'deepseek' => 'DeepSeek',
        'claude'   => 'Anthropic Claude',
        'custom'   => '自定义平台',
    ];

    // 各平台默认模型列表
    public static $platform_models = [
        'openai'   => ['gpt-4o', 'gpt-4o-mini', 'gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo'],
        'baidu'    => ['ernie-4.0-8k', 'ernie-3.5-8k', 'ernie-speed-8k', 'ernie-lite-8k'],
        'aliyun'   => ['qwen-turbo', 'qwen-plus', 'qwen-max', 'qwen-long'],
        'xunfei'   => ['generalv3.5', 'generalv3', 'generalv2'],
        'tencent'  => ['hunyuan-lite', 'hunyuan-standard', 'hunyuan-pro'],
        'deepseek' => ['deepseek-chat', 'deepseek-coder', 'deepseek-reasoner'],
        'claude'   => ['claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'],
        'custom'   => [],
    ];

    /**
     * 获取平台配置（从缓存或数据库）
     */
    public static function get_platform_config($platform) {
        $cache_key = 'aibridge_platforms';
        $platforms = memory('get', $cache_key);
        if ($platforms === null) {
            $platforms = [];
            $rows = table_aibridge_config::t()->fetch_all_platforms();
            foreach ($rows as $row) {
                $row['api_key'] = $row['api_key'] ? authcode($row['api_key'], 'DECODE') : '';
                $platforms[$row['platform']] = $row;
            }
            memory('set', $cache_key, $platforms);
        }
        return isset($platforms[$platform]) ? $platforms[$platform] : null;
    }

    /**
     * 获取所有已启用的平台
     */
    public static function get_enabled_platforms() {
        $cache_key = 'aibridge_enabled';
        $platforms = memory('get', $cache_key);
        if ($platforms === null) {
            $rows = table_aibridge_config::t()->fetch_enabled_platforms();
            $platforms = [];
            foreach ($rows as $row) {
                $row['api_key'] = $row['api_key'] ? authcode($row['api_key'], 'DECODE') : '';
                $platforms[$row['platform']] = $row;
            }
            memory('set', $cache_key, $platforms);
        }
        return $platforms;
    }

    /**
     * 调用 AI 平台
     */
    public static function call_ai($platform, $messages, $model = '', $stream = false) {
        $config = self::get_platform_config($platform);
        if (!$config || !$config['enabled']) {
            return ['error' => '平台未启用或配置不存在'];
        }
        if (empty($config['api_key'])) {
            return ['error' => 'API Key 未配置，请在后台完成平台配置'];
        }

        $model  = $model ?: $config['model'];
        $api_key = $config['api_key'];
        $api_url = $config['api_url'];
        $extra   = json_decode($config['extra_conf'], true) ?: [];

        switch ($platform) {
            case 'openai':
                return lib_openai::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'baidu':
                return lib_baidu::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'aliyun':
                return lib_aliyun::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'xunfei':
                return lib_xunfei::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'tencent':
                return lib_tencent::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'deepseek':
                return lib_deepseek::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'claude':
                return lib_claude::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            case 'custom':
                return lib_custom::chat($api_url, $api_key, $model, $messages, $extra, $stream);
            default:
                return ['error' => '不支持的平台：' . $platform];
        }
    }

    /**
     * 通用 HTTP POST 请求
     */
    public static function http_request($url, $headers, $body, $timeout = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => '请求失败: ' . $error, 'http_code' => $http_code];
        }
        if ($response === false || $response === '') {
            return ['error' => '空响应（HTTP ' . $http_code . '）', 'http_code' => $http_code];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => '响应解析失败（非 JSON）', 'raw' => substr($response, 0, 500), 'http_code' => $http_code];
        }

        return ['data' => $result, 'http_code' => $http_code];
    }

    /**
     * 流式 HTTP 请求（SSE）
     * $callback(string $chunk): void
     */
    public static function http_stream_request($url, $headers, $body, $callback, $timeout = 60) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use ($callback) {
            $callback($data);
            return strlen($data);
        });

        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => '流式请求失败: ' . $error];
        }
        return ['success' => true];
    }

    /**
     * 检查用户是否有权限使用 AI 功能
     */
    public static function check_permission($uid) {
        global $_G;
        $allowed_groups = isset($_G['cache']['plugin']['aibridge']['allowed_groups'])
            ? trim($_G['cache']['plugin']['aibridge']['allowed_groups'])
            : '';
        // 未设置限制，所有登录用户均可使用
        if (empty($allowed_groups)) return true;

        $groups = array_map('trim', explode(',', $allowed_groups));
        $groups = array_filter($groups);
        if (empty($groups)) return true;

        $user_group = strval($_G['member']['groupid'] ?? 0);
        return in_array($user_group, $groups);
    }

    /**
     * 检查用户今日调用频率限制
     */
    public static function check_rate_limit($uid) {
        global $_G;
        $daily_limit = intval($_G['cache']['plugin']['aibridge']['daily_limit'] ?? 0);
        if ($daily_limit <= 0) return true;

        $count = table_aibridge_log::t()->count_by_uid_today($uid);
        return $count < $daily_limit;
    }

    /**
     * 获取剩余调用次数
     */
    public static function get_remaining_limit($uid) {
        global $_G;
        $daily_limit = intval($_G['cache']['plugin']['aibridge']['daily_limit'] ?? 0);
        if ($daily_limit <= 0) return -1; // -1 表示不限制

        $count = table_aibridge_log::t()->count_by_uid_today($uid);
        return max(0, $daily_limit - $count);
    }
}