/**
 * AI Bridge 前台交互脚本 v1.0.0
 * 功能：AI悬浮对话窗、编辑器AI辅助、帖子AI回复建议
 */
(function() {
    'use strict';

    var panelOpen  = false;
    var isLoading  = false;
    var cfg        = window.aibridgeConfig || {};
    var siteurl    = (cfg.siteurl || '').replace(/\/?$/, '/');

    // =====================================================
    // 悬浮窗开关
    // =====================================================
    window.aibridgeToggle = function() {
        var panel = document.getElementById('aibridge-panel');
        var fab   = document.getElementById('aibridge-fab');
        if (!panel || !fab) return;

        panelOpen = !panelOpen;
        if (panelOpen) {
            panel.style.display = 'flex';
            fab.classList.add('aibridge-fab--open');
            setTimeout(function() {
                var input = document.getElementById('aibridge-input');
                if (input) input.focus();
            }, 120);
        } else {
            panel.style.display = 'none';
            fab.classList.remove('aibridge-fab--open');
        }
    };

    // =====================================================
    // 发送消息
    // =====================================================
    window.aibridgeSend = function() {
        if (isLoading) return;

        var input = document.getElementById('aibridge-input');
        if (!input) return;

        var message = input.value.trim();
        if (!message) return;

        var promptSelect   = document.getElementById('aibridge-prompt-select');
        var platformSelect = document.getElementById('aibridge-platform-select');
        var promptId       = promptSelect   ? promptSelect.value   : '0';
        var platform       = platformSelect ? platformSelect.value : '';

        // 显示用户消息
        appendMessage('user', escapeHtml(message));
        input.value = '';
        input.style.height = 'auto';

        // 显示 AI 加载状态
        isLoading = true;
        var loadingEl = appendMessage('ai', '<span class="aibridge-typing-dots"><span></span><span></span><span></span></span>');
        updateSendBtn(true);

        var fd = new FormData();
        fd.append('message',     message);
        fd.append('prompt_id',   promptId);
        fd.append('action_type', 'chat');
        if (platform) fd.append('platform', platform);

        apiPost('api/aibridge/chat', fd)
            .then(function(data) {
                removeEl(loadingEl);
                if (data.ret === 0) {
                    appendMessage('ai', renderMarkdown(data.content));
                    // 更新剩余次数提示
                    if (data.remaining >= 0) {
                        updateQuotaHint(data.remaining);
                    }
                } else {
                    appendMessage('ai', errorHtml(data.msg || 'AI 服务异常，请稍后重试'));
                }
            })
            .catch(function() {
                removeEl(loadingEl);
                appendMessage('ai', errorHtml('网络连接失败，请检查网络后重试'));
            })
            .finally(function() {
                isLoading = false;
                updateSendBtn(false);
            });
    };

    // =====================================================
    // 键盘事件
    // =====================================================
    window.aibridgeKeyDown = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            aibridgeSend();
        }
    };

    // =====================================================
    // 清空对话
    // =====================================================
    window.aibridgeClear = function() {
        if (!confirm('确定要清空对话历史吗？')) return;

        var messagesEl = document.getElementById('aibridge-messages');
        if (messagesEl) {
            messagesEl.innerHTML = welcomeHtml();
        }

        // 通知后端清空
        apiPost('api/aibridge/clear', new FormData()).catch(function() {});
    };

    // =====================================================
    // 编辑器 AI 辅助按钮
    // =====================================================
    window.aibridgeEditorAction = function(event) {
        event.preventDefault();
        event.stopPropagation();

        var btn  = event.currentTarget;
        var rect = btn.getBoundingClientRect();

        // 关闭已有菜单
        closeEditorMenus();

        var menu = document.createElement('div');
        menu.className  = 'aibridge-editor-menu';
        menu.id         = 'aibridge-editor-menu';
        menu.style.left = (rect.left + window.scrollX) + 'px';
        menu.style.top  = (rect.bottom + window.scrollY + 6) + 'px';

        var actions = [
            { label: '✦ AI 问答',  type: 'ask',       icon: '💬', desc: '向 AI 提问' },
            { label: '✦ 润色文本', type: 'polish',    icon: '✨', desc: '优化选中文字' },
            { label: '✦ 续写内容', type: 'continue',  icon: '📝', desc: '续写选中内容' },
            { label: '✦ 翻译文本', type: 'translate', icon: '🌐', desc: '翻译选中内容' },
        ];

        actions.forEach(function(action) {
            var item = document.createElement('button');
            item.className   = 'aibridge-editor-menu-item';
            item.type        = 'button';
            item.innerHTML   = '<span class="aibridge-menu-icon">' + action.icon + '</span>' +
                               '<span class="aibridge-menu-label">' + action.label + '</span>';
            item.title       = action.desc;
            item.addEventListener('click', function() {
                closeEditorMenus();
                if (action.type === 'ask') {
                    showEditorAskDialog();
                } else {
                    handleEditorTextAction(action.type);
                }
            });
            menu.appendChild(item);
        });

        document.body.appendChild(menu);

        // 点击外部关闭
        setTimeout(function() {
            document.addEventListener('click', editorMenuOutsideClick);
        }, 50);

        return false;
    };

    function editorMenuOutsideClick(e) {
        var menu = document.getElementById('aibridge-editor-menu');
        if (menu && !menu.contains(e.target)) {
            closeEditorMenus();
        }
    }

    function closeEditorMenus() {
        var menu = document.getElementById('aibridge-editor-menu');
        if (menu) menu.parentNode.removeChild(menu);
        document.removeEventListener('click', editorMenuOutsideClick);
        // 关闭结果弹窗（如果存在）
        var dialog = document.getElementById('aibridge-editor-dialog');
        if (dialog) dialog.parentNode.removeChild(dialog);
    }

    // 编辑器文本操作（润色/续写/翻译）
    function handleEditorTextAction(actionType) {
        // 获取编辑器选中内容
        var selectedText = getEditorSelectedText();

        if (!selectedText) {
            showEditorToast('请先在编辑器中选中需要处理的文字');
            return;
        }

        showEditorResultDialog(actionType, selectedText);
    }

    // 获取编辑器中选中的文字
    function getEditorSelectedText() {
        // Discuz X5 JSON 编辑器
        var sel = window.getSelection();
        if (sel && sel.toString().trim()) {
            return sel.toString().trim();
        }
        // 传统 textarea 编辑器
        var textarea = document.querySelector('#e_textarea, textarea[name="message"], #fastpostmessage');
        if (textarea) {
            var start = textarea.selectionStart;
            var end   = textarea.selectionEnd;
            if (start !== end) {
                return textarea.value.substring(start, end).trim();
            }
        }
        return '';
    }

    // 编辑器问答弹窗
    function showEditorAskDialog() {
        closeEditorMenus();

        var dialog = buildDialog('aibridge-editor-dialog', 'AI 写作助手',
            '<div class="aibridge-dialog-body">' +
            '<textarea id="aibridge-ask-input" class="aibridge-dialog-textarea" placeholder="请输入您的问题或写作需求，AI 将为您提供辅助..." rows="3"></textarea>' +
            '<div id="aibridge-ask-result" class="aibridge-dialog-result" style="display:none;"></div>' +
            '</div>',
            [
                { id: 'aibridge-ask-btn', label: '✦ 发送给 AI', primary: true, onClick: function() {
                    var input = document.getElementById('aibridge-ask-input');
                    var msg = input ? input.value.trim() : '';
                    if (!msg) { showEditorToast('请输入问题内容'); return; }
                    var resultEl = document.getElementById('aibridge-ask-result');
                    if (resultEl) {
                        resultEl.style.display = 'block';
                        resultEl.innerHTML = '<div class="aibridge-dialog-loading"><span class="aibridge-typing-dots"><span></span><span></span><span></span></span> AI 正在思考...</div>';
                    }
                    var fd = new FormData();
                    fd.append('message', msg);
                    fd.append('action_type', 'chat');
                    apiPost('api/aibridge/chat', fd).then(function(data) {
                        if (resultEl) {
                            if (data.ret === 0) {
                                resultEl.innerHTML = '<div class="aibridge-dialog-result-content">' + renderMarkdown(data.content) + '</div>' +
                                    '<div class="aibridge-dialog-result-actions">' +
                                    '<button type="button" class="aibridge-dialog-action-btn" onclick="aibridgeCopyResult(this)">复制结果</button>' +
                                    '<button type="button" class="aibridge-dialog-action-btn primary" onclick="aibridgeInsertResult(this)">插入到编辑器</button>' +
                                    '</div>';
                                resultEl.querySelector('[onclick="aibridgeInsertResult(this)"]')._resultText = data.content;
                                resultEl.querySelector('[onclick="aibridgeCopyResult(this)"]')._resultText  = data.content;
                            } else {
                                resultEl.innerHTML = errorHtml(data.msg || 'AI 服务异常');
                            }
                        }
                    }).catch(function() {
                        if (resultEl) resultEl.innerHTML = errorHtml('网络连接失败');
                    });
                }},
                { id: 'aibridge-ask-close', label: '关闭', primary: false, onClick: function() {
                    closeEditorMenus();
                }}
            ]
        );

        document.body.appendChild(dialog);
        setTimeout(function() {
            var ta = document.getElementById('aibridge-ask-input');
            if (ta) ta.focus();
        }, 100);
    }

    // 编辑器润色/续写/翻译结果弹窗
    function showEditorResultDialog(actionType, selectedText) {
        var titles = { polish: '润色文本', continue: '续写内容', translate: '翻译文本' };
        var title  = titles[actionType] || 'AI 处理';

        var dialog = buildDialog('aibridge-editor-dialog', title,
            '<div class="aibridge-dialog-body">' +
            '<div class="aibridge-dialog-original"><div class="aibridge-dialog-label">原文：</div>' +
            '<div class="aibridge-dialog-original-text">' + escapeHtml(mb_substr(selectedText, 0, 300)) + '</div></div>' +
            '<div id="aibridge-action-result" class="aibridge-dialog-result"><div class="aibridge-dialog-loading"><span class="aibridge-typing-dots"><span></span><span></span><span></span></span> AI 处理中...</div></div>' +
            '</div>',
            [
                { id: 'aibridge-result-replace', label: '替换原文', primary: true, onClick: function() {
                    var resultEl = document.getElementById('aibridge-action-result');
                    if (!resultEl || !resultEl._resultText) return;
                    insertOrReplaceEditorText(selectedText, resultEl._resultText);
                    closeEditorMenus();
                    showEditorToast('已替换原文');
                }},
                { id: 'aibridge-result-copy', label: '复制结果', primary: false, onClick: function() {
                    var resultEl = document.getElementById('aibridge-action-result');
                    if (!resultEl || !resultEl._resultText) return;
                    copyToClipboard(resultEl._resultText);
                    showEditorToast('已复制到剪贴板');
                }},
                { id: 'aibridge-result-close', label: '关闭', primary: false, onClick: function() {
                    closeEditorMenus();
                }}
            ]
        );

        document.body.appendChild(dialog);

        // 立即调用 AI
        var prompts = {
            polish:    '请润色以下文本，使其更加流畅专业：\n\n',
            continue:  '请续写以下内容，保持风格一致：\n\n',
            translate: '请翻译以下文本：\n\n'
        };
        var message = (prompts[actionType] || '') + selectedText;
        var fd = new FormData();
        fd.append('message', message);
        fd.append('action_type', actionType);

        apiPost('api/aibridge/chat', fd).then(function(data) {
            var resultEl = document.getElementById('aibridge-action-result');
            if (!resultEl) return;
            if (data.ret === 0) {
                resultEl._resultText = data.content;
                resultEl.innerHTML = '<div class="aibridge-dialog-result-content">' + escapeHtml(data.content) + '</div>';
            } else {
                resultEl.innerHTML = errorHtml(data.msg || 'AI 处理失败');
            }
        }).catch(function() {
            var resultEl = document.getElementById('aibridge-action-result');
            if (resultEl) resultEl.innerHTML = errorHtml('网络连接失败');
        });
    }

    // 在编辑器中插入/替换文字
    function insertOrReplaceEditorText(original, newText) {
        // 传统 textarea 编辑器
        var textarea = document.querySelector('#e_textarea, textarea[name="message"], #fastpostmessage');
        if (textarea) {
            var val = textarea.value;
            var idx = val.indexOf(original);
            if (idx >= 0) {
                textarea.value = val.substring(0, idx) + newText + val.substring(idx + original.length);
            } else {
                textarea.value += '\n' + newText;
            }
            textarea.focus();
            return;
        }
        // contenteditable 编辑器（JSON 编辑器）
        var sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            var range = sel.getRangeAt(0);
            range.deleteContents();
            var textNode = document.createTextNode(newText);
            range.insertNode(textNode);
            range.setStartAfter(textNode);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    // 结果复制（供弹窗内按钮调用）
    window.aibridgeCopyResult = function(btn) {
        var text = btn._resultText || '';
        if (!text) {
            var contentEl = btn.closest('.aibridge-dialog-result').querySelector('.aibridge-dialog-result-content');
            if (contentEl) text = contentEl.textContent;
        }
        copyToClipboard(text);
        var orig = btn.textContent;
        btn.textContent = '✓ 已复制';
        setTimeout(function() { btn.textContent = orig; }, 2000);
    };

    // 结果插入编辑器（供弹窗内按钮调用）
    window.aibridgeInsertResult = function(btn) {
        var text = btn._resultText || '';
        if (!text) {
            var contentEl = btn.closest('.aibridge-dialog-result').querySelector('.aibridge-dialog-result-content');
            if (contentEl) text = contentEl.textContent;
        }
        var textarea = document.querySelector('#e_textarea, textarea[name="message"], #fastpostmessage');
        if (textarea) {
            var pos = textarea.selectionStart || textarea.value.length;
            textarea.value = textarea.value.substring(0, pos) + '\n' + text + textarea.value.substring(pos);
            textarea.focus();
            showEditorToast('已插入到编辑器');
        } else {
            copyToClipboard(text);
            showEditorToast('已复制到剪贴板');
        }
    };

    // =====================================================
    // 帖子 AI 回复建议
    // =====================================================
    window.aibridgeGenerateSuggestion = function(btn) {
        var content = btn.getAttribute('data-content') || '';
        var subject = btn.getAttribute('data-subject') || '';
        if (!content) return;

        btn.disabled    = true;
        btn.textContent = '⏳ AI 生成中...';

        var bodyEl = document.getElementById('aibridge-suggest-body');
        if (!bodyEl) return;

        // 构建消息：标题 + 正文
        var message = subject ? '帖子标题：' + subject + '\n\n帖子内容：\n' + content : content;
        var fd = new FormData();
        fd.append('message',     message);
        fd.append('action_type', 'suggest');

        apiPost('api/aibridge/chat', fd)
            .then(function(data) {
                if (data.ret === 0) {
                    bodyEl.innerHTML =
                        '<div class="aibridge-suggest-result">' +
                        '<div class="aibridge-suggest-result-text" id="aibridge-reply-text">' + escapeHtml(data.content) + '</div>' +
                        '<div class="aibridge-suggest-result-actions">' +
                        '<button type="button" class="aibridge-suggest-action-btn secondary" onclick="aibridgeRegenerateSuggestion(this)" data-content="' + htmlAttrEscape(content) + '" data-subject="' + htmlAttrEscape(subject) + '">↻ 重新生成</button>' +
                        '<button type="button" class="aibridge-suggest-action-btn primary" onclick="aibridgeUseReply(this)">✓ 使用此回复</button>' +
                        '</div>' +
                        '</div>';
                } else {
                    bodyEl.innerHTML =
                        errorHtml(data.msg || '生成失败') +
                        '<div style="margin-top:8px;"><button type="button" class="aibridge-suggest-btn" onclick="aibridgeRegenerateSuggestion(this)" data-content="' + htmlAttrEscape(content) + '" data-subject="' + htmlAttrEscape(subject) + '">重试</button></div>';
                }
            })
            .catch(function() {
                bodyEl.innerHTML =
                    errorHtml('网络连接失败') +
                    '<div style="margin-top:8px;"><button type="button" class="aibridge-suggest-btn" onclick="aibridgeRegenerateSuggestion(this)" data-content="' + htmlAttrEscape(content) + '" data-subject="' + htmlAttrEscape(subject) + '">重试</button></div>';
            });
    };

    // 重新生成建议
    window.aibridgeRegenerateSuggestion = function(btn) {
        var content = btn.getAttribute('data-content') || '';
        var subject = btn.getAttribute('data-subject') || '';
        var bodyEl  = document.getElementById('aibridge-suggest-body');
        if (!bodyEl) return;

        // 重置为初始状态
        bodyEl.innerHTML =
            '<p class="aibridge-suggest-intro">AI 将根据帖子内容为您生成一条回复建议，可直接使用或作为参考。</p>' +
            '<button type="button" class="aibridge-suggest-btn" onclick="aibridgeGenerateSuggestion(this)" data-content="' + htmlAttrEscape(content) + '" data-subject="' + htmlAttrEscape(subject) + '">✦ 生成 AI 回复建议</button>';

        // 立即触发生成
        var newBtn = bodyEl.querySelector('.aibridge-suggest-btn');
        if (newBtn) aibridgeGenerateSuggestion(newBtn);
    };

    // 使用回复建议填入回复框
    window.aibridgeUseReply = function(btn) {
        var textEl = document.getElementById('aibridge-reply-text');
        if (!textEl) return;
        var text = textEl.textContent.trim();

        // 尝试填入 Discuz 快速回复框
        var replyBox = document.querySelector('#fastpostmessage, #postform textarea[name="message"], .reply-textarea');
        if (replyBox) {
            replyBox.value = text;
            replyBox.focus();
            // 滚动到回复框
            replyBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            showSuggestToast('已填入回复框');
        } else {
            // 降级到复制
            copyToClipboard(text);
            var orig = btn.textContent;
            btn.textContent = '✓ 已复制到剪贴板';
            setTimeout(function() { btn.textContent = orig; }, 2500);
        }
    };

    // =====================================================
    // 工具函数
    // =====================================================

    // API POST 请求
    function apiPost(path, formData) {
        return fetch(siteurl + path, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    // 向消息区域追加一条消息
    function appendMessage(role, content) {
        var messagesEl = document.getElementById('aibridge-messages');
        if (!messagesEl) return null;

        // 移除欢迎提示
        var welcome = messagesEl.querySelector('.aibridge-welcome');
        if (welcome) welcome.parentNode.removeChild(welcome);

        var msgDiv = document.createElement('div');
        msgDiv.className = 'aibridge-msg ' + (role === 'user' ? 'aibridge-msg-user' : 'aibridge-msg-ai');

        var label = document.createElement('div');
        label.className   = 'aibridge-msg-label';
        label.textContent = role === 'user' ? '我' : 'AI';

        var contentDiv = document.createElement('div');
        contentDiv.className = 'aibridge-msg-content';
        contentDiv.innerHTML = content;

        msgDiv.appendChild(label);
        msgDiv.appendChild(contentDiv);
        messagesEl.appendChild(msgDiv);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return msgDiv;
    }

    // 更新发送按钮状态
    function updateSendBtn(loading) {
        var btn = document.getElementById('aibridge-send');
        if (!btn) return;
        btn.disabled = loading;
        if (loading) {
            btn.innerHTML = '<span class="aibridge-typing-dots small"><span></span><span></span><span></span></span>';
        } else {
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
        }
    }

    // 更新剩余次数提示
    function updateQuotaHint(remaining) {
        var hint = document.getElementById('aibridge-quota-hint');
        if (!hint) {
            hint = document.createElement('div');
            hint.id = 'aibridge-quota-hint';
            hint.className = 'aibridge-quota-hint';
            var inputArea = document.querySelector('.aibridge-input-area');
            if (inputArea) inputArea.parentNode.insertBefore(hint, inputArea);
        }
        hint.textContent = '今日剩余：' + remaining + ' 次';
    }

    // 欢迎界面 HTML
    function welcomeHtml() {
        return '<div class="aibridge-welcome">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.35;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>' +
            '<p>您好！我是 AI 助手，有什么可以帮您的吗？</p>' +
            '<p style="font-size:11px;opacity:0.7;">按 Enter 发送，Shift+Enter 换行</p>' +
            '</div>';
    }

    // 错误信息 HTML
    function errorHtml(msg) {
        return '<span class="aibridge-error">' + escapeHtml(msg) + '</span>';
    }

    // 简单 Markdown 渲染（粗体/代码/换行）
    function renderMarkdown(text) {
        if (!text) return '';
        var html = escapeHtml(text);
        // 代码块
        html = html.replace(/```[\s\S]*?```/g, function(m) {
            return '<pre class="aibridge-code">' + m.replace(/^```\w*\n?/, '').replace(/```$/, '') + '</pre>';
        });
        // 行内代码
        html = html.replace(/`([^`]+)`/g, '<code class="aibridge-inline-code">$1</code>');
        // 粗体
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // 换行
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    // HTML 转义
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // HTML 属性值转义
    function htmlAttrEscape(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // 字符串截断（简单实现）
    function mb_substr(str, maxLen) {
        if (!str) return '';
        return str.length > maxLen ? str.substring(0, maxLen) + '...' : str;
    }

    // 移除元素
    function removeEl(el) {
        if (el && el.parentNode) el.parentNode.removeChild(el);
    }

    // 复制到剪贴板
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).catch(function() { legacyCopy(text); });
        } else {
            legacyCopy(text);
        }
    }
    function legacyCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }

    // 编辑器区域的 Toast 提示
    function showEditorToast(msg) {
        showToast(msg, 'aibridge-editor-toast');
    }
    function showSuggestToast(msg) {
        showToast(msg, 'aibridge-suggest-toast');
    }
    function showToast(msg, id) {
        var existing = document.getElementById(id);
        if (existing) existing.parentNode.removeChild(existing);
        var toast = document.createElement('div');
        toast.id        = id;
        toast.className = 'aibridge-toast';
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.classList.add('aibridge-toast--show');
        }, 10);
        setTimeout(function() {
            toast.classList.remove('aibridge-toast--show');
            setTimeout(function() { removeEl(toast); }, 300);
        }, 2500);
    }

    // 构建通用弹窗
    function buildDialog(id, title, bodyHtml, buttons) {
        // 遮罩层
        var overlay = document.createElement('div');
        overlay.id        = id;
        overlay.className = 'aibridge-dialog-overlay';

        var dialog = document.createElement('div');
        dialog.className = 'aibridge-dialog';

        // 头部
        var header = document.createElement('div');
        header.className = 'aibridge-dialog-header';
        header.innerHTML = '<span class="aibridge-dialog-title">' + escapeHtml(title) + '</span>' +
            '<button type="button" class="aibridge-dialog-close" onclick="(function(){var o=document.getElementById(\'' + id + '\');if(o)o.parentNode.removeChild(o);})()">✕</button>';

        // 内容
        var content = document.createElement('div');
        content.innerHTML = bodyHtml;

        // 按钮组
        var footer = document.createElement('div');
        footer.className = 'aibridge-dialog-footer';
        buttons.forEach(function(b) {
            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.id        = b.id;
            btn.className = 'aibridge-dialog-btn' + (b.primary ? ' primary' : '');
            btn.textContent = b.label;
            btn.addEventListener('click', b.onClick);
            footer.appendChild(btn);
        });

        dialog.appendChild(header);
        dialog.appendChild(content);
        dialog.appendChild(footer);
        overlay.appendChild(dialog);
        return overlay;
    }

    // =====================================================
    // 自动调整输入框高度
    // =====================================================
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'aibridge-input') {
            e.target.style.height = 'auto';
            e.target.style.height = Math.min(e.target.scrollHeight, 100) + 'px';
        }
    });

})();