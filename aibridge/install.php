<?php

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$createSql = <<<EOF

DROP TABLE IF EXISTS cdb_aibridge_config;
CREATE TABLE cdb_aibridge_config (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL DEFAULT '',
  `api_key` text NOT NULL,
  `api_url` varchar(255) NOT NULL DEFAULT '',
  `model` varchar(100) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `extra_conf` text,
  `created_at` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS cdb_aibridge_log;
CREATE TABLE cdb_aibridge_log (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `platform` varchar(50) NOT NULL DEFAULT '',
  `model` varchar(100) NOT NULL DEFAULT '',
  `prompt_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `completion_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `error_msg` text,
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_platform` (`platform`),
  KEY `idx_created` (`created_at`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS cdb_aibridge_conversation;
CREATE TABLE cdb_aibridge_conversation (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `platform` varchar(50) NOT NULL DEFAULT '',
  `messages_json` longtext NOT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uid` (`uid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS cdb_aibridge_prompt;
CREATE TABLE cdb_aibridge_prompt (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `system_prompt` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

EOF;

runquery($createSql);

// 插入默认平台配置（全部禁用，需管理员在后台配置 API Key 后手动启用）
$platforms = [
    ['platform' => 'openai',   'api_url' => 'https://api.openai.com/v1/chat/completions',                                                   'model' => 'gpt-4o-mini'],
    ['platform' => 'baidu',    'api_url' => 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro',              'model' => 'ernie-4.0-8k'],
    ['platform' => 'aliyun',   'api_url' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',                'model' => 'qwen-turbo'],
    ['platform' => 'xunfei',   'api_url' => 'https://spark-api.xf-yun.com/v3.5/chat',                                                        'model' => 'generalv3.5'],
    ['platform' => 'tencent',  'api_url' => 'https://hunyuan.tencentcloudapi.com',                                                           'model' => 'hunyuan-lite'],
    ['platform' => 'deepseek', 'api_url' => 'https://api.deepseek.com/v1/chat/completions',                                                  'model' => 'deepseek-chat'],
    ['platform' => 'claude',   'api_url' => 'https://api.anthropic.com/v1/messages',                                                         'model' => 'claude-3-5-sonnet-20241022'],
    ['platform' => 'custom',   'api_url' => '',                                                                                               'model' => ''],
];

$now = time();
foreach ($platforms as $p) {
    DB::insert('aibridge_config', [
        'platform'   => $p['platform'],
        'api_key'    => '',
        'api_url'    => $p['api_url'],
        'model'      => $p['model'],
        'enabled'    => 0,
        'extra_conf' => '{"temperature":0.7}',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

// 插入默认 Prompt 模板
$prompts = [
    [
        'name'          => '论坛助手',
        'system_prompt' => '你是一个友好的论坛助手，帮助用户解答问题、提供建议。请用简洁明了的语言回答，避免冗长。',
        'is_default'    => 1,
    ],
    [
        'name'          => '文章润色',
        'system_prompt' => '你是一个专业的文字编辑，请对用户提供的文本进行润色和优化，使其更加流畅、专业，保持原文核心意思不变。直接返回润色后的文本，不要添加解释说明。',
        'is_default'    => 0,
    ],
    [
        'name'          => '翻译助手',
        'system_prompt' => '你是一个专业的翻译助手。如果用户提供的是中文，将其翻译成英文；如果是英文或其他语言，将其翻译成中文。直接返回翻译结果，不要添加解释。',
        'is_default'    => 0,
    ],
    [
        'name'          => '内容续写',
        'system_prompt' => '你是一个创意写作助手，请根据用户提供的文本开头，自然地续写后续内容，保持与原文风格和语气一致。直接输出续写内容，不要重复原文开头。',
        'is_default'    => 0,
    ],
    [
        'name'          => '代码助手',
        'system_prompt' => '你是一个专业的程序员助手，擅长各种编程语言。请帮助用户解答代码问题、优化代码或生成代码。给出简洁、可运行的代码，并添加必要注释。',
        'is_default'    => 0,
    ],
];

foreach ($prompts as $pr) {
    DB::insert('aibridge_prompt', [
        'name'          => $pr['name'],
        'system_prompt' => $pr['system_prompt'],
        'is_default'    => $pr['is_default'],
        'created_at'    => $now,
    ]);
}

// 注册后台管理菜单
if(method_exists('menu', 'platform_add')) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
    <name><![CDATA[AI桥接管理]]></name>
    <title><![CDATA[AI桥接管理]]></title>
    <desc><![CDATA[多平台AI大模型接口管理]]></desc>
    <logo><![CDATA[<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>]]></logo>
    <defaultId><![CDATA[plugin_aibridge:admin:config]]></defaultId>
    <menu>
        <menuId>平台配置</menuId>
        <sub>
            <subId>plugin_aibridge:admin:config</subId>
            <title>平台配置</title>
        </sub>
    </menu>
    <menu>
        <menuId>通用设置</menuId>
        <sub>
            <subId>plugin_aibridge:admin:general</subId>
            <title>通用设置</title>
        </sub>
    </menu>
    <menu>
        <menuId>Prompt模板</menuId>
        <sub>
            <subId>plugin_aibridge:admin:prompt</subId>
            <title>模板管理</title>
        </sub>
    </menu>
    <menu>
        <menuId>用量统计</menuId>
        <sub>
            <subId>plugin_aibridge:admin:stats</subId>
            <title>用量统计</title>
        </sub>
    </menu>
</root>';

    menu::platform_add('aibridge', $xml);
}

echo $installlang['install'];

$finish = TRUE;