<?php

namespace aibridge\admin;

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

class admin_prompt {

    public static function run() {
        $op = isset($_GET['op']) ? trim($_GET['op']) : 'list';

        if ($op === 'list') {
            self::show_list();
        } elseif ($op === 'add') {
            self::show_form(0);
        } elseif ($op === 'edit') {
            self::show_form(intval($_GET['id'] ?? 0));
        } elseif ($op === 'delete') {
            self::delete_prompt();
        } elseif ($op === 'save') {
            self::save_prompt();
        } else {
            self::show_list();
        }
    }

    private static function nav_links($active = 'prompt') {
        echo '<div style="margin-bottom:16px;">';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=config" style="margin-right:12px;font-size:13px;color:#334155;">平台配置</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=general" style="margin-right:12px;font-size:13px;color:#334155;">通用设置</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=prompt" style="margin-right:12px;font-size:13px;color:' . ($active === 'prompt' ? '#0F766E' : '#334155') . ';font-weight:' . ($active === 'prompt' ? '600' : 'normal') . ';">Prompt模板</a>';
        echo '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=stats" style="margin-right:12px;font-size:13px;color:#334155;">用量统计</a>';
        echo '</div>';
    }

    private static function show_list() {
        $prompts = DB::fetch_all('SELECT * FROM %t ORDER BY id ASC', ['aibridge_prompt']);
        $base_url = ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=prompt';

        echo '<style>
        .aibridge-table { width:100%; border-collapse:collapse; font-size:13px; }
        .aibridge-table th { padding:10px 12px; background:#f8fafc; border-bottom:2px solid #e2e8f0; text-align:left; color:#334155; font-weight:600; }
        .aibridge-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#0f172a; vertical-align:top; }
        .aibridge-table tr:hover td { background:#f8fafc; }
        .aibridge-action-btn { display:inline-block; padding:3px 10px; border:1px solid #e2e8f0; border-radius:3px; font-size:12px; text-decoration:none; color:#334155; background:#fff; cursor:pointer; }
        .aibridge-action-btn:hover { background:#f1f5f9; }
        .aibridge-action-btn.danger { border-color:#fecaca; color:#dc2626; }
        .aibridge-action-btn.danger:hover { background:#fef2f2; }
        .aibridge-add-btn { display:inline-flex; align-items:center; gap:4px; padding:6px 16px; background:#0F766E; color:#fff; border:none; border-radius:3px; font-size:13px; text-decoration:none; cursor:pointer; }
        .aibridge-add-btn:hover { background:#0d6b63; color:#fff; }
        .aibridge-badge-default { display:inline-block; padding:2px 6px; border-radius:10px; font-size:11px; background:#dbeafe; color:#1d4ed8; }
        </style>';

        self::nav_links('prompt');
        showtableheader('Prompt 模板管理', 'fixpadding');

        echo '<tr><td colspan="2">';
        echo '<table class="aibridge-table">';
        echo '<thead><tr>';
        echo '<th style="width:50px;">ID</th>';
        echo '<th style="width:180px;">模板名称</th>';
        echo '<th>系统 Prompt 内容</th>';
        echo '<th style="width:100px;">操作</th>';
        echo '</tr></thead><tbody>';

        if (!empty($prompts)) {
            foreach ($prompts as $p) {
                $preview = mb_strlen($p['system_prompt']) > 100 ? mb_substr($p['system_prompt'], 0, 100) . '...' : $p['system_prompt'];
                echo '<tr>';
                echo '<td>' . $p['id'] . '</td>';
                echo '<td>' . htmlspecialchars($p['name']);
                if ($p['is_default']) {
                    echo ' <span class="aibridge-badge-default">默认</span>';
                }
                echo '</td>';
                echo '<td style="color:#64748b;">' . htmlspecialchars($preview) . '</td>';
                echo '<td>';
                echo '<a href="' . $base_url . '&op=edit&id=' . $p['id'] . '" class="aibridge-action-btn">编辑</a> ';
                if (!$p['is_default']) {
                    echo '<a href="' . $base_url . '&op=delete&id=' . $p['id'] . '" class="aibridge-action-btn danger" onclick="return confirm(\'确定删除此模板？\')">删除</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px;">暂无模板，请点击下方按钮新增</td></tr>';
        }

        echo '</tbody></table>';
        echo '<div style="margin-top:14px;"><a href="' . $base_url . '&op=add" class="aibridge-add-btn">+ 新增模板</a></div>';
        echo '</td></tr>';

        showtablefooter();
    }

    private static function show_form($id = 0) {
        $prompt = [];
        if ($id > 0) {
            $prompt = DB::fetch_first('SELECT * FROM %t WHERE id=%d', ['aibridge_prompt', $id]);
            if (!$prompt) {
                cpmsg('模板不存在', 'action=plugins&operation=config&identifier=aibridge&pmod=prompt&op=list', 'error');
                return;
            }
        }

        $title = $id > 0 ? '编辑模板' : '新增模板';
        $base_action = 'plugins&operation=config&identifier=aibridge&pmod=prompt&op=save';

        echo '<style>
        .aibridge-form-wrap { padding: 8px 0; }
        .aibridge-form-row { display: flex; margin-bottom: 16px; }
        .aibridge-form-label { width: 120px; font-size: 13px; color: #334155; padding-top: 6px; flex-shrink: 0; font-weight: 500; }
        .aibridge-form-label .req { color: #dc2626; }
        .aibridge-form-control { flex: 1; }
        .aibridge-form-control input[type="text"] { width: 100%; max-width: 400px; padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 13px; color: #0f172a; box-sizing: border-box; }
        .aibridge-form-control input[type="text"]:focus { border-color: #0F766E; outline: none; }
        .aibridge-form-control textarea { width: 100%; max-width: 560px; height: 160px; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 13px; color: #0f172a; resize: vertical; font-family: inherit; box-sizing: border-box; }
        .aibridge-form-control textarea:focus { border-color: #0F766E; outline: none; }
        .aibridge-form-desc { font-size: 11px; color: #94a3b8; margin-top: 4px; }
        </style>';

        self::nav_links('prompt');

        showformheader($base_action);
        showtableheader($title, 'fixpadding');

        echo '<tr><td colspan="2"><div class="aibridge-form-wrap">';

        // 模板名称
        echo '<div class="aibridge-form-row">';
        echo '<div class="aibridge-form-label">模板名称 <span class="req">*</span></div>';
        echo '<div class="aibridge-form-control">';
        echo '<input type="text" name="name" value="' . htmlspecialchars($prompt['name'] ?? '') . '" placeholder="例如：客服助手、代码专家" maxlength="100">';
        echo '<div class="aibridge-form-desc">模板名称将显示在前台对话框的下拉选择中</div>';
        echo '</div></div>';

        // 系统 Prompt
        echo '<div class="aibridge-form-row">';
        echo '<div class="aibridge-form-label">系统 Prompt <span class="req">*</span></div>';
        echo '<div class="aibridge-form-control">';
        echo '<textarea name="system_prompt" placeholder="请输入系统级提示词，例如：你是一个专业的客服助手，请用礼貌友好的语气回答用户问题...">' . htmlspecialchars($prompt['system_prompt'] ?? '') . '</textarea>';
        echo '<div class="aibridge-form-desc">System Prompt 将作为 AI 对话的角色设定，引导 AI 的回复风格和内容范围</div>';
        echo '</div></div>';

        // 设为默认
        $is_default = intval($prompt['is_default'] ?? 0);
        echo '<div class="aibridge-form-row">';
        echo '<div class="aibridge-form-label">设为默认</div>';
        echo '<div class="aibridge-form-control" style="padding-top:6px;">';
        echo '<input type="radio" name="is_default" id="default_1" value="1"' . ($is_default ? ' checked' : '') . '> <label for="default_1">是</label>&nbsp;&nbsp;';
        echo '<input type="radio" name="is_default" id="default_0" value="0"' . (!$is_default ? ' checked' : '') . '> <label for="default_0">否</label>';
        echo '<div class="aibridge-form-desc">设为默认后，前台对话框默认使用此模板</div>';
        echo '</div></div>';

        echo '</div></td></tr>';

        echo '<input type="hidden" name="id" value="' . intval($id) . '">';
        showsubmit('submit', $id > 0 ? '更新模板' : '创建模板');
        showtablefooter();
        showformfooter();

        echo '<p style="margin-top:10px;font-size:13px;"><a href="' . ADMINSCRIPT . '?action=plugins&operation=config&identifier=aibridge&pmod=prompt">&laquo; 返回模板列表</a></p>';
    }

    private static function save_prompt() {
        if(!submitcheck('submit')) {
            self::show_form(0);
            return;
        }

        $id            = intval($_POST['id'] ?? 0);
        $name          = trim($_POST['name'] ?? '');
        $system_prompt = trim($_POST['system_prompt'] ?? '');
        $is_default    = intval($_POST['is_default'] ?? 0);

        if (empty($name)) {
            cpmsg('模板名称不能为空', '', 'error');
            return;
        }
        if (empty($system_prompt)) {
            cpmsg('Prompt 内容不能为空', '', 'error');
            return;
        }

        // 如果设为默认，先清除其他模板的默认标记
        if ($is_default) {
            DB::update('aibridge_prompt', ['is_default' => 0], '1=1');
        }

        if ($id > 0) {
            DB::update('aibridge_prompt', [
                'name'          => $name,
                'system_prompt' => $system_prompt,
                'is_default'    => $is_default,
            ], "id='$id'");
        } else {
            DB::insert('aibridge_prompt', [
                'name'          => $name,
                'system_prompt' => $system_prompt,
                'is_default'    => $is_default,
                'created_at'    => time(),
            ]);
        }

        cpmsg('模板保存成功', 'action=plugins&operation=config&identifier=aibridge&pmod=prompt', 'succeed');
    }

    private static function delete_prompt() {
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0) {
            // 不允许删除默认模板
            $p = DB::fetch_first('SELECT is_default FROM %t WHERE id=%d', ['aibridge_prompt', $id]);
            if ($p && $p['is_default']) {
                cpmsg('默认模板不能删除', 'action=plugins&operation=config&identifier=aibridge&pmod=prompt', 'error');
                return;
            }
            DB::query('DELETE FROM %t WHERE id=%d', ['aibridge_prompt', $id]);
        }
        cpmsg('已删除', 'action=plugins&operation=config&identifier=aibridge&pmod=prompt', 'succeed');
    }
}