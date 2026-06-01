<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class plugin_aibridge {

    // 全局公共钩子
    function common() {
        // 预留公共逻辑
    }

    /**
     * 前台 header 钩子：注入 AI 悬浮窗
     * 钩子类型 11 对应 common_header
     */
    function common_header() {
        global $_G;

        // 未登录不显示
        if (empty($_G['uid'])) return;

        // 检查权限
        if (!\aibridge\lib_aibridge::check_permission($_G['uid'])) return;

        // 检查是否有启用的平台
        $platforms = \aibridge\lib_aibridge::get_enabled_platforms();
        if (empty($platforms)) return;

        // 获取 Prompt 模板列表（供前台下拉选择）
        $prompts = DB::fetch_all('SELECT id, name FROM %t ORDER BY id ASC', ['aibridge_prompt']);

        $plugin_url = $_G['siteurl'] . 'source/plugin/aibridge/static/';

        // 注入 CSS
        echo '<link rel="stylesheet" href="' . $plugin_url . 'aibridge.css?v=1.0.0">' . "\n";

        // 注入悬浮窗 HTML（使用 include template 或直接输出）
        // 平台选项 HTML
        $platform_options_html = '';
        foreach ($platforms as $pid => $p) {
            $pname = \aibridge\lib_aibridge::$platform_names[$pid] ?? $pid;
            $platform_options_html .= '<option value="' . htmlspecialchars($pid) . '">' . htmlspecialchars($pname) . '</option>';
        }

        // Prompt 选项 HTML
        $prompt_options_html = '<option value="0">默认对话</option>';
        foreach ($prompts as $p) {
            $prompt_options_html .= '<option value="' . intval($p['id']) . '">' . htmlspecialchars($p['name']) . '</option>';
        }

        // 输出悬浮窗 HTML
        echo self::render_chat_widget($platform_options_html, $prompt_options_html);

        // 注入 JS
        echo '<script src="' . $plugin_url . 'aibridge.js?v=1.0.0"></script>' . "\n";
        echo '<script>var aibridgeConfig = {siteurl: "' . addslashes($_G['siteurl']) . '", uid: "' . intval($_G['uid']) . '"};</script>' . "\n";
    }

    /**
     * 渲染 AI 悬浮对话窗 HTML
     */
    private static function render_chat_widget($platform_options_html, $prompt_options_html) {
        $html = '
<!-- AI Bridge 悬浮对话窗 -->
<div id="aibridge-widget" class="aibridge-widget">
    <div id="aibridge-fab" class="aibridge-fab" onclick="aibridgeToggle()" title="AI 助手">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="aibridge-fab-label">AI</span>
    </div>
    <div id="aibridge-panel" class="aibridge-panel" style="display:none;">
        <div class="aibridge-header">
            <div class="aibridge-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                AI 助手
            </div>
            <div class="aibridge-header-actions">
                <select id="aibridge-platform-select" class="aibridge-select" title="选择AI平台">' . $platform_options_html . '</select>
                <select id="aibridge-prompt-select" class="aibridge-select" title="选择对话模式">' . $prompt_options_html . '</select>
                <button type="button" class="aibridge-btn-icon" onclick="aibridgeClear()" title="清空对话">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
                <button type="button" class="aibridge-btn-icon" onclick="aibridgeToggle()" title="关闭">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <div id="aibridge-messages" class="aibridge-messages">
            <div class="aibridge-welcome">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.35;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <p>您好！我是 AI 助手，有什么可以帮您的吗？</p>
                <p style="font-size:11px;opacity:0.7;">按 Enter 发送，Shift+Enter 换行</p>
            </div>
        </div>
        <div class="aibridge-input-area">
            <textarea id="aibridge-input" class="aibridge-input" placeholder="输入您的问题..." rows="1" onkeydown="aibridgeKeyDown(event)"></textarea>
            <button type="button" id="aibridge-send" class="aibridge-send-btn" onclick="aibridgeSend()" title="发送">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>
</div>' . "\n";
        return $html;
    }
}

class mobileplugin_aibridge extends plugin_aibridge {}

class plugin_aibridge_forum {

    /**
     * 编辑器工具栏注入 AI 辅助按钮
     * 钩子类型 28 对应 post_jsoneditor_toolbar
     */
    function post_jsoneditor_toolbar() {
        return '<div id="toolbar-aibridge" style="display:inline-block;">
            <button type="button"
                    class="Button Button--plain Button--style"
                    onclick="return aibridgeEditorAction(event)"
                    title="AI 写作辅助">
                <div class="button-area">
                    <span style="display:inline-flex;align-items:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </span>
                    <span class="button-text">AI助手</span>
                </div>
            </button>
        </div>';
    }

    /**
     * 帖子末尾 AI 回复建议区域
     * 钩子类型 12 对应 viewthread_display_end
     */
    function viewthread_display_end() {
        global $_G, $postlist;

        if (empty($_G['uid'])) return '';
        if (!\aibridge\lib_aibridge::check_permission($_G['uid'])) return '';

        // 获取帖子第一楼内容
        $thread_content = '';
        $thread_subject = '';
        if (!empty($postlist)) {
            foreach ($postlist as $post) {
                if (!empty($post['first']) && $post['first'] == 1) {
                    $thread_content = strip_tags($post['message']);
                    $thread_content = preg_replace('/\s+/', ' ', $thread_content);
                    $thread_content = trim(mb_substr($thread_content, 0, 2000));
                    $thread_subject = strip_tags($post['subject'] ?? '');
                    break;
                }
            }
        }

        if (empty($thread_content)) return '';

        $content_escaped = htmlspecialchars($thread_content, ENT_QUOTES, 'UTF-8');
        $subject_escaped = htmlspecialchars($thread_subject, ENT_QUOTES, 'UTF-8');

        return '<div class="aibridge-suggest" id="aibridge-suggest">
    <div class="aibridge-suggest-header">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <span>AI 智能回复建议</span>
    </div>
    <div class="aibridge-suggest-body" id="aibridge-suggest-body">
        <p class="aibridge-suggest-intro">AI 将根据帖子内容为您生成一条回复建议，可直接使用或作为参考。</p>
        <button type="button" class="aibridge-suggest-btn"
                onclick="aibridgeGenerateSuggestion(this)"
                data-content="' . $content_escaped . '"
                data-subject="' . $subject_escaped . '">
            ✦ 生成 AI 回复建议
        </button>
    </div>
</div>' . "\n";
    }
}