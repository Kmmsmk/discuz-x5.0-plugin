<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

/**
 * AIBridge RESTful API 类
 *
 * Discuz X5 路由规则：
 *   GET/POST /api/aibridge/chat      -> chat_before()
 *   GET/POST /api/aibridge/platforms -> platforms_before()
 *   POST     /api/aibridge/clear     -> clear_before()
 *   POST     /api/aibridge/stream    -> stream_before()
 */
class restful_aibridge {

    /**
     * 加载插件依赖文件
     */
    private static function load_libs() {
        static $loaded = false;
        if ($loaded) return;

        $plugin_dir = DISCUZ_ROOT . 'source/plugin/aibridge/';
        $files = [
            'table/table_aibridge_config.php',
            'table/table_aibridge_log.php',
            'table/table_aibridge_conversation.php',
            'lib/lib_aibridge.php',
            'lib/lib_openai.php',
            'lib/lib_baidu.php',
            'lib/lib_aliyun.php',
            'lib/lib_xunfei.php',
            'lib/lib_tencent.php',
            'lib/lib_deepseek.php',
            'lib/lib_claude.php',
            'lib/lib_custom.php',
        ];
        foreach ($files as $f) {
            require_once $plugin_dir . $f;
        }
        $loaded = true;
    }

    /**
     * AI 对话接口
     * 路由: POST /api/aibridge/chat
     *
     * POST 参数:
     *   message     string  必填，用户输入内容
     *   platform    string  可选，AI平台（openai/baidu/aliyun/deepseek/...）
     *   model       string  可选，模型名称
     *   prompt_id   int     可选，系统 Prompt 模板 ID
     *   action_type string  可选，chat/polish/continue/translate/suggest，默认 chat
     */
    public static function chat_before(&$data, $param) {
        global $_G;
        self::load_libs();

        // 验证登录
        if (empty($_G['uid'])) {
            $data = ['ret' => -1, 'msg' => '请先登录后再使用 AI 功能'];
            return;
        }

        // 验证权限
        if (!\aibridge\lib_aibridge::check_permission($_G['uid'])) {
            $data = ['ret' => -2, 'msg' => '您当前的用户组没有使用 AI 功能的权限'];
            return;
        }

        // 验证频率限制
        if (!\aibridge\lib_aibridge::check_rate_limit($_G['uid'])) {
            $remaining = 0;
            $daily_limit = intval($_G['cache']['plugin']['aibridge']['daily_limit'] ?? 0);
            $data = ['ret' => -3, 'msg' => '今日 AI 使用次数已达上限（' . $daily_limit . '次），请明天再试'];
            return;
        }

        // 获取并校验参数
        $message     = trim($_POST['message'] ?? '');
        $platform    = trim($_POST['platform'] ?? '');
        $model       = trim($_POST['model'] ?? '');
        $prompt_id   = intval($_POST['prompt_id'] ?? 0);
        $action_type = trim($_POST['action_type'] ?? 'chat');

        if (empty($message)) {
            $data = ['ret' => -4, 'msg' => '消息内容不能为空'];
            return;
        }
        if (mb_strlen($message) > 8000) {
            $data = ['ret' => -4, 'msg' => '消息内容过长，最多支持 8000 个字符'];
            return;
        }

        // 确定平台
        if (empty($platform)) {
            $platform = $_G['cache']['plugin']['aibridge']['default_platform'] ?: 'openai';
        }
        // 验证平台合法性
        if (!array_key_exists($platform, \aibridge\lib_aibridge::$platform_names)) {
            $platform = 'openai';
        }

        // 确定模型
        if (empty($model)) {
            $model = $_G['cache']['plugin']['aibridge']['default_model'] ?: '';
        }

        // 构建消息列表
        $messages = [];

        // 根据 action_type 或 prompt_id 添加系统提示
        if ($prompt_id > 0) {
            $prompt = DB::fetch_first('SELECT system_prompt FROM %t WHERE id=%d', ['aibridge_prompt', $prompt_id]);
            if ($prompt && !empty($prompt['system_prompt'])) {
                $messages[] = ['role' => 'system', 'content' => $prompt['system_prompt']];
            }
        } elseif ($action_type === 'polish') {
            $messages[] = ['role' => 'system', 'content' => '你是一个专业的文字编辑，请对用户提供的文本进行润色和优化，使其更加流畅、专业。保持原文核心意思不变，直接返回润色后的文本，不要添加解释说明。'];
        } elseif ($action_type === 'continue') {
            $messages[] = ['role' => 'system', 'content' => '你是一个创意写作助手，请根据用户提供的文本开头，自然地续写后续内容，保持与原文风格和语气一致。直接返回续写内容，不要重复原文，不要添加额外说明。'];
        } elseif ($action_type === 'translate') {
            $messages[] = ['role' => 'system', 'content' => '你是一个专业的翻译助手。如果用户提供的是中文，翻译成英文；如果是英文或其他语言，翻译成中文。直接返回翻译结果，不要添加解释。'];
        } elseif ($action_type === 'suggest') {
            $messages[] = ['role' => 'system', 'content' => '你是一个论坛回复助手。请根据帖子内容，生成一条简短、有价值、友好的回复建议。回复应当切题、有建设性，长度控制在50-200字之间，直接输出回复正文内容。'];
        } elseif ($action_type === 'chat') {
            // 仅 chat 模式下使用会话历史
        }

        // 加载对话历史（仅 chat 模式）
        if ($action_type === 'chat') {
            $conv = \aibridge\table_aibridge_conversation::t()->fetch_by_uid($_G['uid']);
            if ($conv) {
                $history = json_decode($conv['messages_json'], true) ?: [];
                // 保留最近 20 条（10轮对话）
                if (count($history) > 20) {
                    $history = array_slice($history, -20);
                }
                // 追加历史（跳过 system 消息）
                foreach ($history as $h) {
                    if ($h['role'] !== 'system') {
                        $messages[] = $h;
                    }
                }
            }
        }

        // 添加用户消息
        $messages[] = ['role' => 'user', 'content' => $message];

        // 是否启用流式输出
        $stream = !empty($_G['cache']['plugin']['aibridge']['enable_stream']);

        // 调用 AI
        $ai_result = \aibridge\lib_aibridge::call_ai($platform, $messages, $model, $stream);

        if (isset($ai_result['error'])) {
            // 记录失败日志
            \aibridge\table_aibridge_log::t()->insert_log([
                'uid'               => $_G['uid'],
                'platform'          => $platform,
                'model'             => $model,
                'prompt_tokens'     => 0,
                'completion_tokens' => 0,
                'created_at'        => time(),
                'status'            => 0,
                'error_msg'         => $ai_result['error'],
            ]);
            $data = ['ret' => -5, 'msg' => $ai_result['error']];
            return;
        }

        // 记录成功日志
        \aibridge\table_aibridge_log::t()->insert_log([
            'uid'               => $_G['uid'],
            'platform'          => $platform,
            'model'             => $model ?: ($ai_result['model'] ?? ''),
            'prompt_tokens'     => intval($ai_result['prompt_tokens'] ?? 0),
            'completion_tokens' => intval($ai_result['completion_tokens'] ?? 0),
            'created_at'        => time(),
            'status'            => 1,
            'error_msg'         => '',
        ]);

        $content = $ai_result['content'] ?? '';

        // 保存/更新对话历史（仅 chat 模式）
        if ($action_type === 'chat') {
            $messages[] = ['role' => 'assistant', 'content' => $content];
            \aibridge\table_aibridge_conversation::t()->save_conversation($_G['uid'], $platform, $messages);
        }

        // 计算剩余次数
        $remaining = \aibridge\lib_aibridge::get_remaining_limit($_G['uid']);

        $data = [
            'ret'       => 0,
            'msg'       => 'success',
            'content'   => $content,
            'platform'  => $platform,
            'model'     => $model ?: ($ai_result['model'] ?? ''),
            'remaining' => $remaining,
        ];
    }

    /**
     * 获取可用平台列表
     * 路由: GET /api/aibridge/platforms
     */
    public static function platforms_before(&$data, $param) {
        global $_G;
        self::load_libs();

        // 已启用的平台
        $enabled_platforms = \aibridge\lib_aibridge::get_enabled_platforms();
        $list = [];
        foreach ($enabled_platforms as $pid => $p) {
            $name   = \aibridge\lib_aibridge::$platform_names[$pid] ?? $pid;
            $models = \aibridge\lib_aibridge::$platform_models[$pid] ?? [];
            $list[] = [
                'platform' => $pid,
                'name'     => $name,
                'model'    => $p['model'],
                'models'   => $models,
            ];
        }

        // 已登录用户：附加 Prompt 模板列表
        $prompts = [];
        if (!empty($_G['uid'])) {
            $rows = DB::fetch_all('SELECT id, name, is_default FROM %t ORDER BY is_default DESC, id ASC', ['aibridge_prompt']);
            foreach ($rows as $r) {
                $prompts[] = [
                    'id'         => intval($r['id']),
                    'name'       => $r['name'],
                    'is_default' => intval($r['is_default']),
                ];
            }
        }

        $data = [
            'ret'       => 0,
            'platforms' => $list,
            'prompts'   => $prompts,
            'default_platform' => $_G['cache']['plugin']['aibridge']['default_platform'] ?? '',
        ];
    }

    /**
     * 清空当前用户的对话历史
     * 路由: POST /api/aibridge/clear
     */
    public static function clear_before(&$data, $param) {
        global $_G;
        self::load_libs();

        if (empty($_G['uid'])) {
            $data = ['ret' => -1, 'msg' => '请先登录'];
            return;
        }

        \aibridge\table_aibridge_conversation::t()->clear_conversation($_G['uid']);
        $data = ['ret' => 0, 'msg' => '对话历史已清空'];
    }

    /**
     * 获取用户今日剩余调用次数
     * 路由: GET /api/aibridge/quota
     */
    public static function quota_before(&$data, $param) {
        global $_G;
        self::load_libs();

        if (empty($_G['uid'])) {
            $data = ['ret' => -1, 'msg' => '请先登录'];
            return;
        }

        $daily_limit = intval($_G['cache']['plugin']['aibridge']['daily_limit'] ?? 0);
        $used = \aibridge\table_aibridge_log::t()->count_by_uid_today($_G['uid']);
        $remaining = $daily_limit > 0 ? max(0, $daily_limit - $used) : -1;

        $data = [
            'ret'         => 0,
            'daily_limit' => $daily_limit,
            'used'        => $used,
            'remaining'   => $remaining,
        ];
    }
}