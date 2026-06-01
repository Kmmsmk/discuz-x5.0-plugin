<?php

namespace aibridge\admin;

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

class admin_config {

    public static function run() {
        // 处理表单提交（POST 方式）
        if(isset($_POST['submit']) && submitcheck('submit')) {
            self::save_config();
            return;
        }

        self::show_form();
    }

    private static function show_form() {
        // 获取所有平台配置
        $platforms = \aibridge\table_aibridge_config::t()->fetch_all_platforms();

        // 构建平台数据索引
        $platform_data = [];
        foreach ($platforms as $p) {
            $platform_data[$p['platform']] = $p;
        }

        $base_action = 'plugins&operation=config&identifier=aibridge&pmod=config';

        echo '<style>
        .aibridge-admin-wrap { padding: 10px 0; }
        .aibridge-platform-block { margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; }
        .aibridge-platform-header { padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .aibridge-platform-header h4 { margin: 0; font-size: 14px; color: #0f172a; }
        .aibridge-enabled-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .aibridge-badge-on { background: #dcfce7; color: #16a34a; }
        .aibridge-badge-off { background: #f1f5f9; color: #64748b; }
        .aibridge-platform-body { padding: 16px; }
        .aibridge-form-row { display: flex; align-items: center; margin-bottom: 12px; }
        .aibridge-form-label { width: 120px; font-size: 13px; color: #334155; flex-shrink: 0; }
        .aibridge-form-control { flex: 1; }
        .aibridge-form-control input[type="text"], .aibridge-form-control select, .aibridge-form-control textarea { 
            width: 100%; max-width: 460px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 13px; color: #0f172a; box-sizing: border-box;
        }
        .aibridge-form-control textarea { height: 60px; resize: vertical; }
        .aibridge-form-desc { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        .aibridge-switch-wrap { display: flex; align-items: center; gap: 8px; }
        .aibridge-switch-wrap label { font-size: 13px; color: #334155; cursor: pointer; }
        .aibridge-tip { padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 3px; font-size: 12px; color: #166534; margin-bottom: 16px; }
        .aibridge-warn { padding: 10px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 3px; font-size: 12px; color: #92400e; margin-bottom: 16px; }
        </style>';

        echo '<div class="aibridge-admin-wrap">';
        echo '<form method="post" action="' . ADMINSCRIPT . '?' . $base_action . '">';
        echo formhash('submit');

        // 导航链接
        echo '<div style="margin-bottom:16px;">';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=config" style="margin-right:12px;font-size:13px;color:#0F766E;font-weight:600;">平台配置</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=general" style="margin-right:12px;font-size:13px;color:#334155;">通用设置</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=prompt" style="margin-right:12px;font-size:13px;color:#334155;">Prompt模板</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=stats" style="margin-right:12px;font-size:13px;color:#334155;">用量统计</a>';
        echo '</div>';

        showtableheader('AI 平台配置', 'fixpadding');

        echo '<tr><td colspan="2">';
        echo '<div class="aibridge-tip">配置各 AI 平台的 API Key 和参数。API Key 将被加密存储。启用平台后用户可以在前台使用对应的 AI 服务。</div>';
        echo '</td></tr>';

        $all_platforms = \aibridge\lib_aibridge::$platform_names;
        foreach ($all_platforms as $platform_id => $platform_name) {
            $p = isset($platform_data[$platform_id]) ? $platform_data[$platform_id] : [
                'platform' => $platform_id, 'api_key' => '', 'api_url' => '', 'model' => '', 'enabled' => 0, 'extra_conf' => '{}'
            ];
            $models = \aibridge\lib_aibridge::$platform_models[$platform_id] ?? [];
            $enabled = intval($p['enabled']);

            $badge_class = $enabled ? 'aibridge-badge-on' : 'aibridge-badge-off';
            $badge_text = $enabled ? '已启用' : '未启用';

            echo '<tr><td colspan="2">';
            echo '<div class="aibridge-platform-block">';
            echo '<div class="aibridge-platform-header">';
            echo '<h4>' . htmlspecialchars($platform_name) . '</h4>';
            echo '<span class="aibridge-enabled-badge ' . $badge_class . '">' . $badge_text . '</span>';
            echo '</div>';
            echo '<div class="aibridge-platform-body">';

            // 启用开关
            echo '<div class="aibridge-form-row">';
            echo '<div class="aibridge-form-label">启用此平台</div>';
            echo '<div class="aibridge-form-control"><div class="aibridge-switch-wrap">';
            echo '<input type="radio" name="config[' . $platform_id . '][enabled]" id="en_' . $platform_id . '_1" value="1"' . ($enabled ? ' checked' : '') . '>';
            echo '<label for="en_' . $platform_id . '_1">启用</label>';
            echo '<input type="radio" name="config[' . $platform_id . '][enabled]" id="en_' . $platform_id . '_0" value="0"' . (!$enabled ? ' checked' : '') . '>';
            echo '<label for="en_' . $platform_id . '_0">禁用</label>';
            echo '</div></div>';
            echo '</div>';

            // API Key
            echo '<div class="aibridge-form-row">';
            echo '<div class="aibridge-form-label">API Key</div>';
            echo '<div class="aibridge-form-control">';
            $api_key_val = $p['api_key'] ? '••••••••（已设置，提交新值可更新）' : '';
            echo '<input type="text" name="config[' . $platform_id . '][api_key]" value="" placeholder="' . htmlspecialchars($api_key_val ?: '请输入 API Key') . '">';
            if ($p['api_key']) {
                echo '<div class="aibridge-form-desc">✓ 已配置 API Key（留空则保留原有值）</div>';
            }
            if ($platform_id === 'baidu') {
                echo '<div class="aibridge-form-desc">百度文心格式：client_id|client_secret</div>';
            }
            echo '</div>';
            echo '</div>';

            // API 地址
            $default_urls = [
                'openai'   => 'https://api.openai.com/v1/chat/completions',
                'baidu'    => 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro',
                'aliyun'   => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
                'xunfei'   => 'https://spark-api.xf-yun.com/v3.5/chat',
                'tencent'  => 'https://hunyuan.tencentcloudapi.com',
                'deepseek' => 'https://api.deepseek.com/v1/chat/completions',
                'claude'   => 'https://api.anthropic.com/v1/messages',
                'custom'   => '',
            ];
            echo '<div class="aibridge-form-row">';
            echo '<div class="aibridge-form-label">API 地址</div>';
            echo '<div class="aibridge-form-control">';
            echo '<input type="text" name="config[' . $platform_id . '][api_url]" value="' . htmlspecialchars($p['api_url'] ?: ($default_urls[$platform_id] ?? '')) . '" placeholder="API Endpoint URL">';
            if ($platform_id === 'custom') {
                echo '<div class="aibridge-form-desc">自定义平台请填写兼容 OpenAI Chat Completions 格式的 API 地址</div>';
            }
            echo '</div>';
            echo '</div>';

            // 模型
            echo '<div class="aibridge-form-row">';
            echo '<div class="aibridge-form-label">模型</div>';
            echo '<div class="aibridge-form-control">';
            if (!empty($models)) {
                echo '<select name="config[' . $platform_id . '][model]">';
                foreach ($models as $m) {
                    $sel = ($p['model'] === $m) ? ' selected' : '';
                    echo '<option value="' . htmlspecialchars($m) . '"' . $sel . '>' . htmlspecialchars($m) . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="text" name="config[' . $platform_id . '][model]" value="' . htmlspecialchars($p['model']) . '" placeholder="模型名称">';
            }
            echo '</div>';
            echo '</div>';

            // 额外配置
            echo '<div class="aibridge-form-row">';
            echo '<div class="aibridge-form-label">额外配置</div>';
            echo '<div class="aibridge-form-control">';
            echo '<textarea name="config[' . $platform_id . '][extra_conf]" placeholder=\'{"temperature":0.7,"max_tokens":4096}\'>' . htmlspecialchars($p['extra_conf'] ?: '{}') . '</textarea>';
            echo '<div class="aibridge-form-desc">JSON 格式，可配置 temperature、max_tokens 等参数</div>';
            echo '</div>';
            echo '</div>';

            echo '</div></div>';
            echo '</td></tr>';
        }

        showsubmit('submit', '保存配置');
        showtablefooter();
        echo '</form>';
        echo '</div>';
    }

    private static function save_config() {
        $configs = isset($_POST['config']) ? $_POST['config'] : [];

        foreach ($configs as $platform => $data) {
            // 校验平台名合法
            if (!array_key_exists($platform, \aibridge\lib_aibridge::$platform_names)) continue;

            $update = [
                'enabled'    => intval($data['enabled']),
                'api_url'    => trim($data['api_url'] ?? ''),
                'model'      => trim($data['model'] ?? ''),
                'extra_conf' => trim($data['extra_conf'] ?? '{}'),
                'updated_at' => time(),
            ];

            // extra_conf JSON 校验
            json_decode($update['extra_conf']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $update['extra_conf'] = '{}';
            }

            // API Key：只有输入了新值才更新
            $api_key = trim($data['api_key'] ?? '');
            if ($api_key !== '' && strpos($api_key, '•') === false) {
                $update['api_key'] = authcode($api_key, 'ENCODE');
            }

            // 检查平台是否已存在
            $existing = \aibridge\table_aibridge_config::t()->fetch_by_platform($platform);
            if ($existing) {
                \aibridge\table_aibridge_config::t()->update_platform($platform, $update);
            } else {
                $update['platform'] = $platform;
                $update['created_at'] = time();
                DB::insert('aibridge_config', $update);
            }
        }

        // 清除平台缓存
        memory('rm', 'aibridge_platforms');
        memory('rm', 'aibridge_enabled');
        memory('rm', 'aibridge_baidu_token');

        cpmsg('AI 平台配置已保存', 'action=plugins&operation=config&identifier=aibridge&pmod=config', 'succeed');
    }
}