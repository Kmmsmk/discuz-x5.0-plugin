<?php

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

// 加载插件必要文件
$plugin_dir = DISCUZ_ROOT . 'source/plugin/aibridge/';

// 加载 Table 类
require_once $plugin_dir . 'table/table_aibridge_config.php';
require_once $plugin_dir . 'table/table_aibridge_log.php';
require_once $plugin_dir . 'table/table_aibridge_conversation.php';

// 加载 Lib 类
require_once $plugin_dir . 'lib/lib_aibridge.php';
require_once $plugin_dir . 'lib/lib_openai.php';
require_once $plugin_dir . 'lib/lib_baidu.php';
require_once $plugin_dir . 'lib/lib_aliyun.php';
require_once $plugin_dir . 'lib/lib_xunfei.php';
require_once $plugin_dir . 'lib/lib_tencent.php';
require_once $plugin_dir . 'lib/lib_deepseek.php';
require_once $plugin_dir . 'lib/lib_claude.php';
require_once $plugin_dir . 'lib/lib_custom.php';

// 根据 pmod 参数路由到对应后台模块
$pmod = isset($_GET['pmod']) ? trim($_GET['pmod']) : 'config';

switch ($pmod) {
    case 'config':
        require_once $plugin_dir . 'admin/admin_config.php';
        \aibridge\admin\admin_config::run();
        break;
    case 'prompt':
        require_once $plugin_dir . 'admin/admin_prompt.php';
        \aibridge\admin\admin_prompt::run();
        break;
    case 'stats':
        require_once $plugin_dir . 'admin/admin_stats.php';
        \aibridge\admin\admin_stats::run();
        break;
    case 'general':
        require_once $plugin_dir . 'admin/admin_general.php';
        \aibridge\admin\admin_general::run();
        break;
    default:
        require_once $plugin_dir . 'admin/admin_config.php';
        \aibridge\admin\admin_config::run();
        break;
}
