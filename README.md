### 用AI开发了这个“Discuz x5.0" AI 大模型接口插件

### 插件功能有缺陷 欢迎有动手能力达人来完善！！！

## 1. 应用概述

### 1.1 应用名称
Discuz X5.0 AI 接口插件（AI Bridge Plugin）

### 1.2 应用描述
本应用包含两部分：
1. **aibridge 插件**：Discuz X5.0 论坛系统的扩展插件，用于集成多平台 AI 大模型能力，为论坛用户和管理员提供 AI 对话、内容辅助等功能。插件标识符为 aibridge

### 1.3 支持的 AI 平台
- OpenAI（GPT-4o、GPT-4、GPT-3.5-turbo 等）
- 百度文心一言（ERNIE）
- 阿里通义千问（Qwen）
- 讯飞星火（Spark）
- 腾讯混元（Hunyuan）
- DeepSeek
- Anthropic Claude
- 自定义平台（Custom，兼容 OpenAI Chat Completions 格式）

## 2. 用户与使用场景

### 2.1 目标用户
- 论坛管理员：配置和管理 AI 平台接入
- 论坛普通用户：使用 AI 对话和内容辅助功能
- 演示体验用户：通过浏览器访问演示网站体验完整功能

### 2.2 核心使用场景
- 管理员在后台配置多个 AI 平台的 API 密钥和参数
- 用户在前台通过悬浮窗与 AI 进行多轮对话
- 用户在发帖时使用 AI 辅助功能润色、续写或翻译内容
- 用户查看帖子时获取 AI 智能回复建议
- 体验用户通过演示网站浏览论坛、查看帖子、使用 AI 助手、访问后台管理和 API 文档

## 3. 页面结构与功能说明

### 3.1 整体结构

```
应用根目录
├── aibridge 插件（PHP 插件包）
│   ├── 后台管理模块
│   │   ├── 平台配置管理页面
│   │   ├── 用量统计页面
│   │   └── Prompt 模板管理页面
│   └── 前台功能模块
│       ├── AI 对话悬浮窗
│       ├── 发帖编辑器 AI 辅助按钮
│       └── 帖子 AI 回复建议区域
└── 演示网站（React + Vite）
    ├── 论坛前台
    │   ├── 论坛首页
    │   ├── 板块页
    │   └── 帖子详情页
    ├── aibridge 后台管理
    │   ├── 平台配置页
    │   ├── Prompt 模板管理页
    │   └── 用量统计页
    └── API 接口文档展示页
```

### 3.2 aibridge 插件后台管理模块

#### 3.2.1 平台配置管理页面
- 展示所有支持的 AI 平台列表
- 每个平台包含以下配置项：
  - 平台名称（不可编辑）
  - 启用/禁用开关
  - API Key 输入框（加密存储）
  - API 地址输入框（自定义平台必填）
  - 模型选择下拉框
  - 额外配置参数（JSON 格式）
- 设置全局默认平台和模型
- 保存配置按钮

#### 3.2.2 用量统计页面
- 显示总调用次数
- 按平台统计调用次数和 Token 消耗
- 按用户组统计调用次数
- 时间范围筛选（今日、本周、本月、自定义）

#### 3.2.3 Prompt 模板管理页面
- 展示已创建的 Prompt 模板列表
- 新增模板功能：输入模板名称、系统 Prompt 内容
- 编辑和删除已有模板
- 模板可在前台 AI 对话中快速选用

#### 3.2.4 权限与限制设置
- 按用户组设置 AI 功能使用权限（勾选可用用户组）
- 设置每用户每日最大请求次数
- 设置每次请求最大 Token 数

### 3.3 aibridge 插件前台功能模块

#### 3.3.1 AI 对话悬浮窗
- 前台页面右下角显示悬浮按钮
- 点击按钮展开对话框
- 对话框包含：
  - 消息展示区域（显示用户消息和 AI 回复）
  - 输入框（用户输入消息）
  - 发送按钮
  - Prompt 模板选择下拉框（可选）
  - 清空对话按钮
- 支持多轮对话，保留对话历史
- 显示 AI 回复加载状态

#### 3.3.2 发帖编辑器 AI 辅助按钮
- 在发帖编辑器工具栏新增 AI 按钮
- 用户选中编辑器中的文本后点击 AI 按钮
- 弹出操作菜单：
  - 润色
  - 续写
  - 翻译
- 选择操作后，AI 处理选中内容并将结果插入编辑器

#### 3.3.3 帖子 AI 回复建议区域
- 在帖子内容末尾显示「AI 智能回复建议」区域
- 点击「生成建议」按钮，AI 根据帖子内容生成回复建议
- 显示生成的回复建议文本
- 用户可点击「使用此回复」按钮，将建议内容填入回复框

### 3.4 aibridge 插件核心 API 接口

#### 3.4.1 aibridge/chat 接口
- 接收参数：
  - 用户消息内容
  - 指定平台（可选，默认使用全局默认平台）
  - 指定模型（可选）
  - 对话历史 ID（可选，用于多轮对话）
- 处理流程：
  - 验证用户权限和频率限制
  - 调用指定平台 API
  - 记录调用日志
  - 保存对话历史
- 返回 AI 回复内容
- 支持流式输出（可选配置）

#### 3.4.2 aibridge/platforms 接口
- 返回当前已启用的 AI 平台列表
- 包含平台名称、可用模型列表

### 3.5 aibridge 插件数据表结构

#### 3.5.1 cdb_aibridge_config 表
存储各平台配置信息，字段包括：
- platform（平台标识）
- api_key（API 密钥，加密存储）
- api_url（API 地址）
- model（模型名称）
- enabled（启用状态）
- extra_conf（额外配置，JSON 格式）

#### 3.5.2 cdb_aibridge_log 表
记录 AI 调用日志，字段包括：
- uid（用户 ID）
- platform（平台标识）
- model（模型名称）
- prompt_tokens（输入 Token 数）
- completion_tokens（输出 Token 数）
- created_at（调用时间）
- status（调用状态：成功/失败）

#### 3.5.3 cdb_aibridge_conversation 表
存储对话历史，字段包括：
- id（对话 ID）
- uid（用户 ID）
- platform（平台标识）
- messages_json（消息列表，JSON 格式）
- created_at（创建时间）
- updated_at（更新时间）

### 3.6 aibridge 插件文件结构

严格遵循 Discuz X5.0 插件规范，文件结构如下：

```
aibridge/
├── discuz_plugin_aibridge.json（插件描述文件）
├── install.php（安装脚本）
├── uninstall.php（卸载脚本）
├── enable.php（启用脚本）
├── disable.php（禁用脚本）
├── check.php（检查脚本）
├── upgrade.php（升级脚本）
├── hook.class.php（钩子处理类：plugin_aibridge、plugin_aibridge_forum）
├── admin.inc.php（后台主入口）
├── admin/
│   ├── admin_config.php（平台配置管理）
│   ├── admin_stats.php（用量统计）
│   └── admin_prompt.php（Prompt 模板管理）
├── table/
│   ├── table_aibridge_config.php（配置表操作类）
│   ├── table_aibridge_log.php（日志表操作类）
│   └── table_aibridge_conversation.php（对话表操作类）
├── lib/
│   ├── lib_aibridge.php（核心 AI 调用库）
│   ├── lib_openai.php（OpenAI 调用）
│   ├── lib_baidu.php（百度文心调用）
│   ├── lib_aliyun.php（阿里通义调用）
│   ├── lib_xunfei.php（讯飞星火调用）
│   ├── lib_tencent.php（腾讯混元调用）
│   ├── lib_deepseek.php（DeepSeek 调用）
│   ├── lib_claude.php（Anthropic Claude 调用）
│   └── lib_custom.php（自定义平台调用）
├── restful.class.php（RESTful API 接口）
├── cache/
│   └── cache_aibridge.php（缓存更新函数）
├── template/
│   ├── aibridge_chat.htm（AI 聊天悬浮窗模板）
│   └── aibridge_admin.htm（后台主模板）
├── static/
│   ├── aibridge.js（前台 JS）
│   └── aibridge.css（前台样式）
└── i18n/
    ├── SC_UTF8/
    │   └── lang_plugin.php（简体中文语言包）
    └── en/
        └── lang_plugin.php（英文语言包）
```
