<?php
if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$scriptlang['aibridge'] = array(
    // Frontend chat widget
    'chat_title'         => 'AI Assistant',
    'send'               => 'Send',
    'clear'              => 'Clear Chat',
    'placeholder'        => 'Type your question...',
    'loading'            => 'AI is thinking...',
    'no_permission'      => 'You do not have permission to use AI features',
    'rate_limit'         => 'Daily usage limit reached',
    'error'              => 'AI service error, please try again later',
    // Editor actions
    'polish'             => 'Polish Text',
    'continue'           => 'Continue Writing',
    'translate'          => 'Translate',
    'ask'                => 'Ask AI',
    // Reply suggestions
    'suggest'            => 'Generate Suggestion',
    'suggest_title'      => 'AI Smart Reply Suggestions',
    'use_reply'          => 'Use This Reply',
    'generating'         => 'Generating...',
    'regenerate'         => 'Regenerate',
    // Common
    'copy'               => 'Copy',
    'close'              => 'Close',
    'replace'            => 'Replace Original',
    'insert_editor'      => 'Insert into Editor',
    // Backend
    'platform_config'    => 'Platform Config',
    'general_settings'   => 'General Settings',
    'prompt_manage'      => 'Prompt Templates',
    'usage_stats'        => 'Usage Stats',
    'save_success'       => 'Saved successfully',
    'delete_confirm'     => 'Confirm delete?',
    'name_required'      => 'Name is required',
    'prompt_required'    => 'Prompt content is required',
    'api_key_set'        => 'Configured',
    'api_key_empty'      => 'Not configured',
    'enabled'            => 'Enabled',
    'disabled'           => 'Disabled',
);

$templatelang['aibridge'] = array(
    'chat_title'         => 'AI Assistant',
    'send'               => 'Send',
    'clear'              => 'Clear Chat',
    'placeholder'        => 'Type your question...',
    'loading'            => 'AI is thinking...',
    'suggest_title'      => 'AI Smart Reply Suggestions',
    'generate'           => 'Generate Suggestion',
    'use_reply'          => 'Use This Reply',
    'select_platform'    => 'Select Platform',
    'select_mode'        => 'Select Mode',
    'default_chat'       => 'Default Chat',
);

$systemlang['aibridge'] = array(
    'file' => array(
        'chat_title' => 'AI Assistant',
    ),
);

$installlang['aibridge'] = array(
    'name'        => 'AI Bridge Plugin',
    'license'     => 'MIT',
    'intro'       => 'Multi-platform AI LLM Bridge Plugin',
    'description' => 'Integrate OpenAI, Baidu ERNIE, Alibaba Qwen, iFlytek Spark, Tencent Hunyuan, DeepSeek, Claude and more',
    'copyright'   => 'AI Bridge Team',
    'version'     => '1.0.0',
    'check'       => 'Check',
    'install'     => 'Install',
    'uninstall'   => 'Uninstall',
    'enable'      => 'Enable',
    'disable'     => 'Disable',
    'upgrade'     => 'Upgrade',
    'admin'       => 'AI Bridge Management',
);
