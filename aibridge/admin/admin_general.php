<?php

namespace aibridge\admin;

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

class admin_general {

	public static function run() {
		if(isset($_POST['submit']) && submitcheck('submit')) {
			self::save_settings();
			return;
		}
		self::show_form();
	}

	private static function show_form() {
		global $_G;

		// 读取插件变量
		$cache = $_G['cache']['plugin']['aibridge'] ?? [];

		$allowed_groups = $cache['allowed_groups'] ?? '';
		$daily_limit    = isset($cache['daily_limit']) ? intval($cache['daily_limit']) : 50;
		$max_tokens     = isset($cache['max_tokens']) ? intval($cache['max_tokens']) : 4096;
		$default_platform = $cache['default_platform'] ?? 'openai';
		$default_model  = $cache['default_model'] ?? '';
		$enable_stream  = isset($cache['enable_stream']) ? intval($cache['enable_stream']) : 1;

		$base_action = 'plugins&operation=config&identifier=aibridge&pmod=general';
		$platforms = \aibridge\lib_aibridge::$platform_names;

		echo '<style>
		.aibridge-gen-wrap { padding: 8px 0; }
		.aibridge-gen-row { display:flex; align-items:flex-start; margin-bottom:18px; padding-bottom:18px; border-bottom:1px solid #f1f5f9; }
		.aibridge-gen-row:last-child { border-bottom:none; }
		.aibridge-gen-label { width:150px; font-size:13px; color:#0f172a; font-weight:500; flex-shrink:0; padding-top:6px; }
		.aibridge-gen-control { flex:1; }
		.aibridge-gen-control input[type="text"], .aibridge-gen-control select { 
			padding:7px 10px; border:1px solid #e2e8f0; border-radius:3px; font-size:13px; color:#0f172a; min-width:200px;
		}
		.aibridge-gen-control input[type="text"]:focus, .aibridge-gen-control select:focus { border-color:#0F766E; outline:none; }
		.aibridge-gen-desc { font-size:12px; color:#64748b; margin-top:5px; line-height:1.5; }
		.aibridge-radio-row { display:flex; gap:16px; align-items:center; padding-top:6px; }
		.aibridge-radio-row label { font-size:13px; color:#334155; cursor:pointer; display:flex; align-items:center; gap:4px; }
		.aibridge-tip { padding:10px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:3px; font-size:12px; color:#166534; margin-bottom:16px; }
		</style>';

		// 导航
		echo '<div style="margin-bottom:16px;">';
		echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=config" style="margin-right:12px;font-size:13px;color:#334155;">平台配置</a>';
		echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=general" style="margin-right:12px;font-size:13px;color:#0F766E;font-weight:600;">通用设置</a>';
		echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=prompt" style="margin-right:12px;font-size:13px;color:#334155;">Prompt模板</a>';
		echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=stats" style="margin-right:12px;font-size:13px;color:#334155;">用量统计</a>';
		echo '</div>';

		echo '<form method="post" action="' . ADMINSCRIPT . '?' . $base_action . '">';
		echo formhash('submit');

		showtableheader('AI 通用设置', 'fixpadding');
		echo '<tr><td colspan="2"><div class="aibridge-gen-wrap">';

		echo '<div class="aibridge-tip">通用设置影响全体用户的 AI 功能行为。修改后立即生效。</div>';

		// 默认 AI 平台
		echo '<div class="aibridge-gen-row">';
		echo '<div class="aibridge-gen-label">默认 AI 平台</div>';
		echo '<div class="aibridge-gen-control">';
		echo '<select name="aibridge_var[default_platform]">';
		foreach ($platforms as $pid => $pname) {
			$sel = ($default_platform === $pid) ? ' selected' : '';
			echo '<option value="' . htmlspecialchars($pid) . '"' . $sel . '>' . htmlspecialchars($pname) . '</option>';
		}
		echo '</select>';
		echo '<div class="aibridge-gen-desc">前台用户默认使用的 AI 平台，需先在「平台配置」中启用对应平台</div>';
		echo '</div></div>';

		// 默认模型
		echo '<div class="aibridge-gen-row">';
		echo '<div class="aibridge-gen-label">默认模型</div>';
		echo '<div class="aibridge-gen-control">';
		echo '<input type="text" name="aibridge_var[default_model]" value="' . htmlspecialchars($default_model) . '" placeholder="留空则使用平台配置中的模型">';
		echo '<div class="aibridge-gen-desc">覆盖平台配置中的默认模型，例如：gpt-4o、deepseek-chat</div>';
		echo '</div></div>';

		// 每日请求限制
		echo '<div class="aibridge-gen-row">';
		echo '<div class="aibridge-gen-label">每日最大请求数</div>';
		echo '<div class="aibridge-gen-control">';
		echo '<input type="text" name="aibridge_var[daily_limit]" value="' . $daily_limit . '" placeholder="0">';
		echo '<div class="aibridge-gen-desc">每个用户每天最多调用 AI 的次数，设为 0 表示不限制</div>';
		echo '</div></div>';

		// 单次最大 Token
		echo '<div class="aibridge-gen-row">';
		echo '<div class="aibridge-gen-label">单次最大 Token</div>';
		echo '<div class="aibridge-gen-control">';
		echo '<input type="text" name="aibridge_var[max_tokens]" value="' . $max_tokens . '" placeholder="4096">';
		echo '<div class="aibridge-gen-desc">每次请求允许生成的最大 Token 数量，建议 1024-8192</div>';
		echo '</div></div>';

		// 启用流式输出
		echo '<div class="aibridge-gen-row">';
		echo '<div class="aibridge-gen-label">启用流式输出</div>';
		echo '<div class="aibridge-gen-control">';
		echo '<div class="aibridge-radio-row">';
		echo '<label><input type="radio" name="aibridge_var[enable_stream]" value="1"' . ($enable_stream ? ' checked' : '') . '> 启用（SSE流式）</label>';
		echo '<label><input type="radio" name="aibridge_var[enable_stream]" value="0"' . (!$enable_stream ? ' checked' : '') . '> 禁用（等待完整回复）</label>';
		echo '</div>';
		echo '<div class="aibridge-gen-desc">启用流式输出可让用户实时看到 AI 的回复内容，体验更流畅（部分平台可能不支持）</div>';
		echo '</div></div>';

		// 允许使用的用户组
		echo '<div class="aibridge-gen-row">';
		echo '<div class="aibridge-gen-label">允许使用的用户组</div>';
		echo '<div class="aibridge-gen-control">';
		echo '<input type="text" name="aibridge_var[allowed_groups]" value="' . htmlspecialchars($allowed_groups) . '" placeholder="留空表示所有用户组均可使用">';
		echo '<div class="aibridge-gen-desc">多个用户组 ID 用英文逗号分隔，例如：2,3,7。留空则所有已登录用户均可使用</div>';
		echo '</div></div>';

		echo '</div></td></tr>';

		showsubmit('submit', '保存通用设置');
		showtablefooter();
		echo '</form>';
	}

	private static function save_settings() {
		$vars = isset($_POST['aibridge_var']) ? $_POST['aibridge_var'] : [];

		$allowed_keys = ['default_platform', 'default_model', 'daily_limit', 'max_tokens', 'enable_stream', 'allowed_groups'];

		foreach ($allowed_keys as $key) {
			if (!isset($vars[$key])) continue;
			$val = trim($vars[$key]);

			// 数值类型校验
			if (in_array($key, ['daily_limit', 'max_tokens', 'enable_stream'])) {
				$val = intval($val);
			}

			// 更新插件变量
			DB::query('UPDATE %t SET value=%s WHERE variable=%s AND pluginid=(SELECT id FROM %t WHERE identifier=%s LIMIT 1)',
				['common_pluginvar', $val, $key, 'common_plugin', 'aibridge']
			);
		}

		// 强制刷新插件缓存
		if (function_exists('updatesetting')) {
			updatesetting('plugins', '');
		}
		// 清除 aibridge 相关缓存
		memory('rm', 'aibridge_platforms');
		memory('rm', 'aibridge_enabled');

		cpmsg('通用设置已保存', 'action=plugins&operation=config&identifier=aibridge&pmod=general', 'succeed');
	}
}
