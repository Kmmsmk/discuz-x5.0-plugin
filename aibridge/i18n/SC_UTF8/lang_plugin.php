<?php
if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$scriptlang['aibridge'] = array(
    // 前台对话窗
    'chat_title'         => 'AI 助手',
    'send'               => '发送',
    'clear'              => '清空对话',
    'placeholder'        => '输入您的问题...',
    'loading'            => 'AI 正在思考...',
    'no_permission'      => '您没有使用 AI 功能的权限',
    'rate_limit'         => '今日使用次数已达上限',
    'error'              => 'AI 服务异常，请稍后重试',
    // 编辑器操作
    'polish'             => '润色文本',
    'continue'           => '续写内容',
    'translate'          => '翻译文本',
    'ask'                => 'AI 问答',
    // 回复建议
    'suggest'            => '生成建议',
    'suggest_title'      => 'AI 智能回复建议',
    'use_reply'          => '使用此回复',
    'generating'         => '正在生成...',
    'regenerate'         => '重新生成',
    // 通用
    'copy'               => '复制',
    'close'              => '关闭',
    'replace'            => '替换原文',
    'insert_editor'      => '插入到编辑器',
    // 后台
    'platform_config'    => '平台配置',
    'general_settings'   => '通用设置',
    'prompt_manage'      => 'Prompt模板',
    'usage_stats'        => '用量统计',
    'save_success'       => '保存成功',
    'delete_confirm'     => '确定删除？',
    'name_required'      => '名称不能为空',
    'prompt_required'    => 'Prompt内容不能为空',
    'api_key_set'        => '已设置',
    'api_key_empty'      => '未配置',
    'enabled'            => '已启用',
    'disabled'           => '未启用',
);

$templatelang['aibridge'] = array(
    'chat_title'         => 'AI 助手',
    'send'               => '发送',
    'clear'              => '清空对话',
    'placeholder'        => '输入您的问题...',
    'loading'            => 'AI 正在思考...',
    'suggest_title'      => 'AI 智能回复建议',
    'generate'           => '生成建议',
    'use_reply'          => '使用此回复',
    'select_platform'    => '选择平台',
    'select_mode'        => '选择对话模式',
    'default_chat'       => '默认对话',
);

$systemlang['aibridge'] = array(
    'file' => array(
        'chat_title' => 'AI 助手',
    ),
);

$installlang['aibridge'] = array(
    'name'        => 'AI 接口桥接插件',
    'license'     => 'MIT',
    'intro'       => '多平台AI大模型接口桥接插件',
    'description' => '集成OpenAI、百度文心、阿里通义、讯飞星火、腾讯混元、DeepSeek、Claude等多平台AI能力',
    'copyright'   => 'AI Bridge Team',
    'version'     => '1.0.0',
    'check'       => '检查',
    'install'     => '安装',
    'uninstall'   => '卸载',
    'enable'      => '开启',
    'disable'     => '关闭',
    'upgrade'     => '升级',
    'admin'       => 'AI桥接管理',
);
