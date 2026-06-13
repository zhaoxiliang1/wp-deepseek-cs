/**
 * Dify AI 客服 - 前端聊天组件
 * 浮窗和侧边模式共用同一套 JS 逻辑
 */
(function($) {
    'use strict';

    // DOM 元素（两模式共用）
    var $toggle = $('#dscs-toggle-btn');
    var $panel  = $('#dscs-panel');
    var $close  = $('#dscs-close-btn');
    var $msgs   = $('#dscs-messages');
    var $input  = $('#dscs-input');
    var $send   = $('#dscs-send-btn');
    var $badge  = $('#dscs-unread-badge');

    var isOpen = false;
    var isLoading = false;

    // 设置主题色
    $('#dscs-widget')[0].style.setProperty('--dscs-primary', dscsData.primaryColor);

    // 切换面板
    $toggle.on('click', function() {
        if (isOpen) {
            closePanel();
        } else {
            openPanel();
        }
    });

    $close.on('click', closePanel);

    // 点击面板外部关闭（侧边模式不关闭）
    if (dscsData.mode !== 'sidebar') {
        $(document).on('click', function(e) {
            if (isOpen && !$(e.target).closest('#dscs-widget').length) {
                closePanel();
            }
        });
    }

    function openPanel() {
        isOpen = true;
        $panel.show();
        $badge.hide();
        $input.focus();
        autoResizeInput();
    }

    function closePanel() {
        isOpen = false;
        $panel.hide();
    }

    // 输入框自动调整高度
    $input.on('input', autoResizeInput);

    function autoResizeInput() {
        $input.css('height', 'auto');
        $input.css('height', Math.min($input[0].scrollHeight, 120) + 'px');
    }

    // Ctrl+Enter 发送
    $input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $send.on('click', sendMessage);

    function sendMessage() {
        var message = $input.val().trim();
        if (!message || isLoading) return;

        appendMessage('user', message);
        $input.val('');
        autoResizeInput();

        showTyping();

        isLoading = true;
        $send.prop('disabled', true);

        $.post(dscsData.ajaxUrl, {
            action: 'dscs_send_message',
            nonce: dscsData.nonce,
            session_id: dscsData.sessionId,
            message: message
        }, function(res) {
            hideTyping();
            isLoading = false;
            $send.prop('disabled', false);

            if (res.success) {
                appendMessage('bot', res.data.reply);
                $input.focus();
            } else {
                appendMessage('error', res.data.message || '抱歉，回复失败，请稍后再试。');
            }
        }).fail(function() {
            hideTyping();
            isLoading = false;
            $send.prop('disabled', false);
            appendMessage('error', '网络错误，请检查连接后重试。');
        });
    }

    function appendMessage(role, content) {
        var msgClass = 'dscs-msg';
        var avatarHtml = '';

        if (role === 'user') {
            msgClass += ' dscs-msg-user';
        } else if (role === 'error') {
            msgClass += ' dscs-msg dscs-msg-error';
        } else {
            msgClass += ' dscs-msg-bot';
            if (dscsData.botAvatar) {
                avatarHtml = '<img class="dscs-msg-avatar-img" src="' + dscsData.botAvatar + '" alt="avatar" />';
            }
        }

        var html = formatMessage(content);
        var $msg = $(
            '<div class="' + msgClass + '">' +
                avatarHtml +
                '<div class="dscs-msg-content">' + html + '</div>' +
            '</div>'
        );
        $msgs.append($msg);
        scrollToBottom();
    }

    function showTyping() {
        $msgs.append(
            '<div class="dscs-typing" id="dscs-typing-indicator">' +
                '<span></span><span></span><span></span>' +
            '</div>'
        );
        scrollToBottom();
    }

    function hideTyping() {
        $('#dscs-typing-indicator').remove();
    }

    function scrollToBottom() {
        $msgs.scrollTop($msgs[0].scrollHeight);
    }

    function formatMessage(text) {
        if (!text) return '';

        text = escapeHtml(text);

        text = text.replace(/```(\w*)\n([\s\S]*?)```/g, function(match, lang, code) {
            return '<pre><code>' + escapeHtml(code.trim()) + '</code></pre>';
        });

        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
        text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

        var paragraphs = text.split(/\n\n+/);
        text = paragraphs.map(function(p) {
            p = p.trim();
            if (!p) return '';
            if (p.startsWith('<pre>') || p.startsWith('<ul>') || p.startsWith('<ol>')) {
                return p;
            }
            if (p.match(/^[-*]\s/m)) {
                return '<ul>' + p.replace(/^[-*]\s/gm, '<li>') + '</ul>';
            }
            return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
        }).join('');

        text = text.replace(/<li>/g, '<li>').replace(/(<\/li>)?<\/ul>/g, '</li></ul>');
        return text;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})(jQuery);
