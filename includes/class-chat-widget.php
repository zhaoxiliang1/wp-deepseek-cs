<?php
/**
 * 前端浮窗聊天组件（Dify 版）
 * 支持：右下角气泡模式 / 侧边悬浮模式 / 自定义头像
 */
class DSCS_Chat_Widget {

    public static function init() {
        $mode = get_option('dscs_widget_mode', 'float');

        // 独立页面模式：不自动渲染浮窗，注册短代码
        if ($mode === 'page') {
            add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
            add_shortcode('dify_cs', [__CLASS__, 'render_page_chat']);
        } else {
            add_action('wp_footer', [__CLASS__, 'render_widget']);
            add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        }

        // AJAX handlers
        add_action('wp_ajax_dscs_send_message', [__CLASS__, 'handle_send_message']);
        add_action('wp_ajax_nopriv_dscs_send_message', [__CLASS__, 'handle_send_message']);
        add_action('wp_ajax_dscs_get_history', [__CLASS__, 'handle_get_history']);
        add_action('wp_ajax_nopriv_dscs_get_history', [__CLASS__, 'handle_get_history']);
    }

    public static function enqueue_assets() {
        wp_enqueue_style(
            'dscs-chat-widget',
            DSCS_PLUGIN_URL . 'public/css/chat-widget.css',
            [],
            DSCS_VERSION
        );

        wp_enqueue_script(
            'dscs-chat-widget',
            DSCS_PLUGIN_URL . 'public/js/chat-widget.js',
            ['jquery'],
            DSCS_VERSION,
            true
        );

        $mode      = get_option('dscs_widget_mode', 'float');
        $avatar    = get_option('dscs_bot_avatar', '');
        $bot_label = get_option('dscs_bot_label', '凯瑞尔客服');

        wp_localize_script('dscs-chat-widget', 'dscsData', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('dscs_nonce'),
            'sessionId'    => self::get_session_id(),
            'title'        => get_option('dscs_widget_title', 'AI 智能客服'),
            'greeting'     => get_option('dscs_widget_greeting', '您好！我是 AI 客服助手，请问有什么可以帮您？'),
            'primaryColor' => get_option('dscs_widget_primary_color', '#4F46E5'),
            'position'     => get_option('dscs_widget_position', 'right'),
            'mode'         => $mode,
            'botAvatar'    => $avatar,
            'botLabel'     => $bot_label,
        ]);
    }

    public static function render_widget() {
        $title    = get_option('dscs_widget_title', 'AI 智能客服');
        $position = get_option('dscs_widget_position', 'right');
        $mode     = get_option('dscs_widget_mode', 'float');
        $avatar   = get_option('dscs_bot_avatar', '');
        $bot_label = get_option('dscs_bot_label', '凯瑞尔客服');

        $is_mobile = wp_is_mobile();

        // 手机端：浮动按钮 + 全屏面板（初始隐藏），有关闭按钮
        if ($is_mobile && ($mode === 'sidebar' || $mode === 'float')) {
            self::render_mobile_widget();
            return;
        }

        $pos_class   = $position === 'left' ? 'dscs-left' : 'dscs-right';
        $mode_class  = $mode === 'sidebar' ? 'dscs-sidebar-mode' : 'dscs-float-mode';

        // 内联样式确保不受缓存和主题覆盖影响
        $inline_css = '';
        if ($mode === 'sidebar') {
            $inline_css = '
            #dscs-widget.dscs-sidebar-mode {
                position: fixed !important;
                top: 50% !important;
                right: 24px !important;
                left: auto !important;
                bottom: auto !important;
                transform: translateY(-50%) !important;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
            }
            #dscs-widget.dscs-sidebar-mode .dscs-sidebar-trigger {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
            }
            #dscs-widget.dscs-sidebar-mode #dscs-panel.dscs-panel {
                position: fixed !important;
                top: 50% !important;
                right: 100px !important;
                left: auto !important;
                bottom: auto !important;
                transform: translateY(-50%) !important;
                width: 720px !important;
                height: 850px !important;
                max-width: calc(100vw - 120px) !important;
                max-height: calc(100vh - 60px) !important;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 8px 40px rgba(0,0,0,0.2);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                animation: dscs-slide-up 0.3s ease-out;
            }
            #dscs-widget.dscs-sidebar-mode .dscs-toggle-label {
                display: block;
                text-align: center;
                font-size: 13px;
                font-weight: 700;
                color: #16a34a !important;
                white-space: nowrap;
                margin-top: 4px;
                letter-spacing: 0.5px;
            }';
        } else {
            $inline_css = '
            #dscs-widget.dscs-float-mode {
                position: fixed !important;
                bottom: 24px !important;
                right: 24px !important;
                left: auto !important;
                top: auto !important;
                z-index: 999999;
            }
            #dscs-widget.dscs-float-mode .dscs-float-wrapper {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
            }
            #dscs-widget.dscs-float-mode .dscs-panel {
                position: fixed;
                bottom: 96px;
                right: 24px;
                width: 720px !important;
                max-width: calc(100vw - 48px) !important;
                height: 980px !important;
                max-height: calc(100vh - 100px) !important;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 8px 40px rgba(0,0,0,0.15);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            #dscs-widget.dscs-float-mode .dscs-toggle-label {
                display: block;
                text-align: center;
                font-size: 13px;
                font-weight: 700;
                color: #16a34a !important;
                white-space: nowrap;
                margin-top: 4px;
                letter-spacing: 0.5px;
            }';
        }
        ?>
        <style id="dscs-inline-style">
            <?php echo $inline_css; ?>
        </style>
        <div id="dscs-widget" class="<?php echo esc_attr("$pos_class $mode_class"); ?>">

            <?php if ($mode === 'sidebar'): ?>
            <!-- === 侧边悬浮模式：头像按钮在右侧中间，点击展开面板 === -->
            <div class="dscs-sidebar-trigger">
                <button id="dscs-toggle-btn" class="dscs-toggle-btn" aria-label="打开客服聊天">
                    <?php if (!empty($avatar)): ?>
                        <img class="dscs-toggle-avatar" src="<?php echo esc_attr($avatar); ?>" alt="客服" />
                    <?php else: ?>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    <?php endif; ?>
                </button>
                <?php if (!empty($bot_label)): ?>
                    <span class="dscs-toggle-label"><?php echo esc_html($bot_label); ?></span>
                <?php endif; ?>
            </div>

            <!-- 聊天面板（初始隐藏） -->
            <div id="dscs-panel" class="dscs-panel" style="display:none;">
                <div class="dscs-header">
                    <div class="dscs-header-info">
                        <div class="dscs-avatar">
                            <?php if (!empty($avatar)): ?>
                                <img src="<?php echo esc_attr($avatar); ?>" alt="客服头像" />
                            <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>
                                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <span class="dscs-title"><?php echo esc_html($title); ?></span>
                    </div>
                    <button id="dscs-close-btn" class="dscs-close-btn" aria-label="关闭聊天">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div id="dscs-messages" class="dscs-messages">
                    <div class="dscs-msg dscs-msg-bot">
                        <?php if (!empty($avatar)): ?>
                            <img class="dscs-msg-avatar-img" src="<?php echo esc_attr($avatar); ?>" alt="avatar" />
                        <?php endif; ?>
                        <div class="dscs-msg-content"><?php echo esc_html(get_option('dscs_widget_greeting', '您好！我是 AI 客服助手，请问有什么可以帮您？')); ?></div>
                    </div>
                </div>

                <div class="dscs-input-area">
                    <textarea id="dscs-input" class="dscs-input" placeholder="输入您的问题..." rows="1"></textarea>
                    <button id="dscs-send-btn" class="dscs-send-btn" aria-label="发送消息">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <div class="dscs-footer">
                    <span>Powered by Dify</span>
                </div>
            </div>

            <?php else: ?>
            <!-- === 浮窗气泡模式 === -->
            <div class="dscs-float-wrapper">
                <button id="dscs-toggle-btn" class="dscs-toggle-btn" aria-label="打开客服聊天">
                    <?php if (!empty($avatar)): ?>
                        <img class="dscs-toggle-avatar" src="<?php echo esc_attr($avatar); ?>" alt="客服" />
                    <?php else: ?>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    <?php endif; ?>
                    <span class="dscs-badge" id="dscs-unread-badge" style="display:none;">1</span>
                </button>
                <?php if (!empty($bot_label)): ?>
                    <span class="dscs-toggle-label"><?php echo esc_html($bot_label); ?></span>
                <?php endif; ?>
            </div>

            <div id="dscs-panel" class="dscs-panel" style="display:none;">
                <div class="dscs-header">
                    <div class="dscs-header-info">
                        <div class="dscs-avatar">
                            <?php if (!empty($avatar)): ?>
                                <img src="<?php echo esc_attr($avatar); ?>" alt="客服头像" />
                            <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>
                                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <span class="dscs-title"><?php echo esc_html($title); ?></span>
                    </div>
                    <button id="dscs-close-btn" class="dscs-close-btn" aria-label="关闭聊天">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div id="dscs-messages" class="dscs-messages">
                    <div class="dscs-msg dscs-msg-bot">
                        <?php if (!empty($avatar)): ?>
                            <img class="dscs-msg-avatar-img" src="<?php echo esc_attr($avatar); ?>" alt="avatar" />
                        <?php endif; ?>
                        <div class="dscs-msg-content"><?php echo esc_html(get_option('dscs_widget_greeting', '您好！我是 AI 客服助手，请问有什么可以帮您？')); ?></div>
                    </div>
                </div>

                <div class="dscs-input-area">
                    <textarea id="dscs-input" class="dscs-input" placeholder="输入您的问题..." rows="1"></textarea>
                    <button id="dscs-send-btn" class="dscs-send-btn" aria-label="发送消息">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <div class="dscs-footer">
                    <span>Powered by Dify</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ========== AJAX Handlers ==========

    public static function handle_send_message() {
        check_ajax_referer('dscs_nonce', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $message    = trim(wp_unslash($_POST['message'] ?? ''));

        if (empty($session_id) || empty($message)) {
            wp_send_json_error(['message' => '参数错误']);
        }

        if (mb_strlen($message) > 2000) {
            wp_send_json_error(['message' => '消息不能超过 2000 字']);
        }

        $session = DSCS_Conversation::get_or_create_session($session_id);
        $dify_conv_id = DSCS_Conversation::get_dify_conversation_id($session_id);

        DSCS_Conversation::save_message($session_id, 'user', $message);

        $user_identifier = get_option('dscs_user', 'wordpress_visitor') . '_' . substr($session_id, 0, 12);
        $response = DSCS_API::chat($message, $dify_conv_id, $user_identifier);

        if (isset($response['error'])) {
            DSCS_Conversation::save_message($session_id, 'assistant', '抱歉，暂时无法回复：' . $response['error']);
            wp_send_json_error(['message' => $response['error']]);
        }

        if (!empty($response['conversation_id']) && $response['conversation_id'] !== $dify_conv_id) {
            DSCS_Conversation::update_dify_conversation_id($session_id, $response['conversation_id']);
        }

        DSCS_Conversation::save_message($session_id, 'assistant', $response['answer'], $response['message_id'] ?? '');

        wp_send_json_success([
            'reply'           => $response['answer'],
            'conversation_id' => $response['conversation_id'] ?? '',
        ]);
    }

    public static function handle_get_history() {
        check_ajax_referer('dscs_nonce', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        if (empty($session_id)) {
            wp_send_json_error(['message' => '参数错误']);
        }

        $messages = DSCS_Conversation::get_messages($session_id);
        wp_send_json_success(['messages' => $messages]);
    }

    private static function get_session_id() {
        if (!isset($_COOKIE['dscs_session'])) {
            $session_id = 'dscs_' . bin2hex(random_bytes(16));
            setcookie('dscs_session', $session_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            $_COOKIE['dscs_session'] = $session_id;
        } else {
            $session_id = $_COOKIE['dscs_session'];
        }
        return $session_id;
    }

    /**
     * 独立页面短代码渲染 [dify_cs]
     * 全屏聊天界面，手机端友好
     */
    public static function render_page_chat() {
        $title    = get_option('dscs_widget_title', 'AI 智能客服');
        $avatar   = get_option('dscs_bot_avatar', '');
        $bot_label = get_option('dscs_bot_label', '凯瑞尔客服');
        $greeting = get_option('dscs_widget_greeting', '您好！我是 AI 客服助手，请问有什么可以帮您？');
        ?>
        <div id="dscs-page-chat" class="dscs-page-chat" style="--dscs-primary: <?php echo esc_attr(get_option('dscs_widget_primary_color', '#4F46E5')); ?>;">
            <!-- 头部 -->
            <div class="dscs-page-header">
                <div class="dscs-page-avatar">
                    <?php if (!empty($avatar)): ?>
                        <img src="<?php echo esc_attr($avatar); ?>" alt="客服头像" />
                    <?php else: ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="dscs-page-header-text">
                    <span class="dscs-page-title"><?php echo esc_html($title); ?></span>
                    <?php if (!empty($bot_label)): ?>
                        <span class="dscs-page-label"><?php echo esc_html($bot_label); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 消息区域 -->
            <div id="dscs-page-messages" class="dscs-page-messages">
                <div class="dscs-msg dscs-msg-bot">
                    <?php if (!empty($avatar)): ?>
                        <img class="dscs-msg-avatar-img" src="<?php echo esc_attr($avatar); ?>" alt="avatar" />
                    <?php endif; ?>
                    <div class="dscs-msg-content"><?php echo esc_html($greeting); ?></div>
                </div>
            </div>

            <!-- 输入区域 -->
            <div class="dscs-page-input-area">
                <textarea id="dscs-page-input" class="dscs-page-input" placeholder="输入您的问题..." rows="1"></textarea>
                <button id="dscs-page-send-btn" class="dscs-page-send-btn" aria-label="发送消息">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>

        <style>
        #dscs-page-chat.dscs-page-chat {
            display: flex;
            flex-direction: column;
            height: 600px;
            max-height: 80vh;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1e293b;
            margin: 16px 0;
        }
        #dscs-page-chat .dscs-page-header {
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        #dscs-page-chat .dscs-page-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        #dscs-page-chat .dscs-page-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #dscs-page-chat .dscs-page-header-text {
            display: flex;
            flex-direction: column;
        }
        #dscs-page-chat .dscs-page-title {
            font-weight: 600;
            font-size: 16px;
        }
        #dscs-page-chat .dscs-page-label {
            font-size: 12px;
            color: #16a34a;
            font-weight: 600;
        }
        #dscs-page-chat .dscs-page-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        #dscs-page-chat .dscs-page-messages .dscs-msg {
            max-width: 85%;
        }
        #dscs-page-chat .dscs-page-messages .dscs-msg-bot {
            align-self: flex-start;
            display: flex;
            align-items: flex-end;
            gap: 4px;
        }
        #dscs-page-chat .dscs-page-messages .dscs-msg-user {
            align-self: flex-end;
        }
        #dscs-page-chat .dscs-page-messages .dscs-msg-content {
            padding: 10px 14px;
            border-radius: 12px;
            word-wrap: break-word;
            white-space: pre-wrap;
            font-size: 14px;
        }
        #dscs-page-chat .dscs-page-messages .dscs-msg-bot .dscs-msg-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }
        #dscs-page-chat .dscs-page-messages .dscs-msg-user .dscs-msg-content {
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        #dscs-page-chat .dscs-page-input-area {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 12px 16px;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        #dscs-page-chat .dscs-page-input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            outline: none;
            max-height: 120px;
            line-height: 1.5;
        }
        #dscs-page-chat .dscs-page-input:focus {
            border-color: var(--dscs-primary, #4F46E5);
        }
        #dscs-page-chat .dscs-page-send-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: none;
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        /* 手机端全屏覆盖（wp_footer 渲染时） */
        @media (max-width: 480px) {
            #dscs-page-chat.dscs-page-chat {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                height: 100vh;
                width: 100vw;
                max-height: none;
                border-radius: 0;
                margin: 0;
                border: none;
                z-index: 999999;
            }
            #dscs-page-chat .dscs-page-messages {
                padding-bottom: 20px;
            }
        }
        </style>

        <script>
        jQuery(function($) {
            var $msgs = $('#dscs-page-messages');
            var $input = $('#dscs-page-input');
            var $send = $('#dscs-page-send-btn');
            var isLoading = false;
            var sessionId = 'dscs_page_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);

            if (typeof dscsData !== 'undefined' && dscsData.sessionId) {
                sessionId = dscsData.sessionId + '_page';
            }

            $input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMsg();
                }
            });

            $send.on('click', sendMsg);

            function sendMsg() {
                var msg = $input.val().trim();
                if (!msg || isLoading) return;

                appendMsg('user', msg);
                $input.val('');
                showTyping();
                isLoading = true;
                $send.prop('disabled', true);

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'dscs_send_message',
                    nonce: '<?php echo wp_create_nonce('dscs_nonce'); ?>',
                    session_id: sessionId,
                    message: msg
                }, function(res) {
                    hideTyping();
                    isLoading = false;
                    $send.prop('disabled', false);
                    if (res.success) {
                        appendMsg('bot', res.data.reply);
                    } else {
                        appendMsg('error', res.data.message || '回复失败');
                    }
                }).fail(function() {
                    hideTyping();
                    isLoading = false;
                    $send.prop('disabled', false);
                    appendMsg('error', '网络错误，请重试');
                });
            }

            function appendMsg(role, content) {
                var cls = 'dscs-msg';
                var avatarHtml = '';
                if (role === 'user') {
                    cls += ' dscs-msg-user';
                } else if (role === 'error') {
                    cls += ' dscs-msg-error';
                } else {
                    cls += ' dscs-msg-bot';
                    if ('<?php echo !empty($avatar) ? "1" : "0"; ?>' === '1') {
                        avatarHtml = '<img class="dscs-msg-avatar-img" src="<?php echo esc_js($avatar); ?>" alt="avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;" />';
                    }
                }
                var html = formatMsg(content);
                $msgs.append('<div class="' + cls + '">' + avatarHtml + '<div class="dscs-msg-content">' + html + '</div></div>');
                scrollBottom();
            }

            function showTyping() {
                $msgs.append('<div class="dscs-typing" id="dscs-page-typing"><span></span><span></span><span></span></div>');
                scrollBottom();
            }

            function hideTyping() { $('#dscs-page-typing').remove(); }
            function scrollBottom() { $msgs.scrollTop($msgs[0].scrollHeight); }

            function formatMsg(text) {
                if (!text) return '';
                var d = document.createElement('div');
                d.appendChild(document.createTextNode(text));
                text = d.innerHTML;
                text = text.replace(/```(\w*)\n([\s\S]*?)```/g, function(m, l, c) { return '<pre><code>' + c.trim() + '</code></pre>'; });
                text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
                text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
                var ps = text.split(/\n\n+/);
                text = ps.map(function(p) {
                    p = p.trim(); if (!p) return '';
                    if (p.match(/^[-*]\s/m)) return '<ul>' + p.replace(/^[-*]\s/gm, '<li>') + '</ul>';
                    return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
                }).join('');
                return text;
            }
        });
        </script>
        <?php
    }

    /**
     * 手机端渲染：浮动按钮 + 全屏面板
     */
    public static function render_mobile_widget() {
        $title     = get_option('dscs_widget_title', 'AI 智能客服');
        $avatar    = get_option('dscs_bot_avatar', '');
        $bot_label = get_option('dscs_bot_label', '凯瑞尔客服');
        $greeting  = get_option('dscs_widget_greeting', '您好！我是 AI 客服助手，请问有什么可以帮您？');
        $color     = get_option('dscs_widget_primary_color', '#4F46E5');
        ?>
        <div id="dscs-mobile-widget" style="--dscs-primary: <?php echo esc_attr($color); ?>;">
            <!-- 浮动触发按钮 -->
            <button id="dscs-mobile-toggle" class="dscs-mobile-toggle">
                <?php if (!empty($avatar)): ?>
                    <img src="<?php echo esc_attr($avatar); ?>" alt="客服" />
                <?php else: ?>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                <?php endif; ?>
            </button>
            <?php if (!empty($bot_label)): ?>
                <span class="dscs-mobile-tag"><?php echo esc_html($bot_label); ?></span>
            <?php endif; ?>

            <!-- 全屏聊天面板（初始隐藏） -->
            <div id="dscs-mobile-panel" class="dscs-mobile-panel" style="display:none;">
                <!-- 头部 -->
                <div class="dscs-mobile-header">
                    <div class="dscs-mobile-header-left">
                        <div class="dscs-mobile-avatar">
                            <?php if (!empty($avatar)): ?>
                                <img src="<?php echo esc_attr($avatar); ?>" alt="客服头像" />
                            <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>
                                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="dscs-mobile-title"><?php echo esc_html($title); ?></div>
                            <?php if (!empty($bot_label)): ?>
                                <div class="dscs-mobile-label"><?php echo esc_html($bot_label); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button id="dscs-mobile-close" class="dscs-mobile-close" aria-label="关闭">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- 消息区域 -->
                <div id="dscs-mobile-msgs" class="dscs-mobile-msgs">
                    <div class="dscs-msg dscs-msg-bot">
                        <?php if (!empty($avatar)): ?>
                            <img class="dscs-msg-avatar-img" src="<?php echo esc_attr($avatar); ?>" alt="avatar" />
                        <?php endif; ?>
                        <div class="dscs-msg-content"><?php echo esc_html($greeting); ?></div>
                    </div>
                </div>

                <!-- 输入区域 -->
                <div class="dscs-mobile-input-area">
                    <textarea id="dscs-mobile-input" class="dscs-mobile-input" placeholder="输入您的问题..." rows="1"></textarea>
                    <button id="dscs-mobile-send" class="dscs-mobile-send" aria-label="发送">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <style>
        /* 浮动触发按钮 */
        #dscs-mobile-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .dscs-mobile-toggle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        }
        .dscs-mobile-toggle img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
        }
        .dscs-mobile-toggle svg {
            width: 28px;
            height: 28px;
        }
        .dscs-mobile-tag {
            font-size: 12px;
            font-weight: 700;
            color: #16a34a;
            white-space: nowrap;
            text-align: center;
        }
        /* 全屏面板 */
        .dscs-mobile-panel {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            z-index: 999999;
            animation: dscs-slide-up 0.25s ease-out;
        }
        .dscs-mobile-header {
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .dscs-mobile-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dscs-mobile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dscs-mobile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dscs-mobile-title {
            font-weight: 600;
            font-size: 16px;
        }
        .dscs-mobile-label {
            font-size: 11px;
            color: rgba(255,255,255,0.9);
        }
        .dscs-mobile-close {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dscs-mobile-close:active {
            background: rgba(255,255,255,0.15);
        }
        .dscs-mobile-msgs {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .dscs-mobile-msgs .dscs-msg {
            max-width: 85%;
        }
        .dscs-mobile-msgs .dscs-msg-bot {
            align-self: flex-start;
            display: flex;
            align-items: flex-end;
            gap: 4px;
        }
        .dscs-mobile-msgs .dscs-msg-user {
            align-self: flex-end;
        }
        .dscs-mobile-msgs .dscs-msg-content {
            padding: 10px 14px;
            border-radius: 12px;
            word-wrap: break-word;
            white-space: pre-wrap;
            font-size: 15px;
            line-height: 1.6;
        }
        .dscs-mobile-msgs .dscs-msg-bot .dscs-msg-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }
        .dscs-mobile-msgs .dscs-msg-user .dscs-msg-content {
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .dscs-mobile-input-area {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 10px 14px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            background: #fff;
            border-top: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .dscs-mobile-input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
            font-family: inherit;
            resize: none;
            outline: none;
            max-height: 100px;
            line-height: 1.5;
        }
        .dscs-mobile-input:focus {
            border-color: var(--dscs-primary, #4F46E5);
        }
        .dscs-mobile-send {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: none;
            background: var(--dscs-primary, #4F46E5);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dscs-mobile-send:disabled {
            opacity: 0.5;
        }
        .dscs-typing {
            align-self: flex-start;
            display: flex;
            gap: 4px;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            border-bottom-left-radius: 4px;
        }
        .dscs-typing span {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            animation: dscs-typing-dot 1.4s infinite;
        }
        .dscs-typing span:nth-child(2) { animation-delay: 0.2s; }
        .dscs-typing span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dscs-typing-dot {
            0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
            30% { opacity: 1; transform: scale(1); }
        }
        @keyframes dscs-slide-up {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        </style>

        <script>
        jQuery(function($) {
            var $panel = $('#dscs-mobile-panel');
            var $msgs  = $('#dscs-mobile-msgs');
            var $input = $('#dscs-mobile-input');
            var $send  = $('#dscs-mobile-send');
            var isOpen = false;
            var isLoading = false;
            var sessionId = 'dscs_mob_' + Date.now();

            if (typeof dscsData !== 'undefined' && dscsData.sessionId) {
                sessionId = dscsData.sessionId + '_mob';
            }

            // 打开面板
            $('#dscs-mobile-toggle').on('click', function() {
                isOpen = true;
                $panel.show();
                setTimeout(function() { $input.focus(); }, 300);
            });

            // 关闭面板（X按钮）
            $('#dscs-mobile-close').on('click', function() {
                isOpen = false;
                $panel.hide();
            });

            // 发送逻辑
            $input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
            });
            $send.on('click', send);

            function send() {
                var msg = $input.val().trim();
                if (!msg || isLoading) return;
                appendMsg('user', msg);
                $input.val('');
                showTyping();
                isLoading = true; $send.prop('disabled', true);
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'dscs_send_message',
                    nonce: '<?php echo wp_create_nonce('dscs_nonce'); ?>',
                    session_id: sessionId,
                    message: msg
                }, function(res) {
                    hideTyping(); isLoading = false; $send.prop('disabled', false);
                    if (res.success) appendMsg('bot', res.data.reply);
                    else appendMsg('error', res.data.message || '回复失败');
                }).fail(function() {
                    hideTyping(); isLoading = false; $send.prop('disabled', false);
                    appendMsg('error', '网络错误，请重试');
                });
            }

            function appendMsg(role, content) {
                var cls = 'dscs-msg';
                var av = '';
                if (role === 'user') cls += ' dscs-msg-user';
                else if (role === 'error') cls += ' dscs-msg-msg-error';
                else {
                    cls += ' dscs-msg-bot';
                    if ('<?php echo !empty($avatar) ? "1" : "0"; ?>' === '1') {
                        av = '<img class="dscs-msg-avatar-img" src="<?php echo esc_js($avatar); ?>" alt="a" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;" />';
                    }
                }
                var html = fmt(content);
                $msgs.append('<div class="' + cls + '">' + av + '<div class="dscs-msg-content">' + html + '</div></div>');
                $msgs.scrollTop($msgs[0].scrollHeight);
            }

            function showTyping() {
                $msgs.append('<div class="dscs-typing" id="dscs-m-typing"><span></span><span></span><span></span></div>');
                $msgs.scrollTop($msgs[0].scrollHeight);
            }
            function hideTyping() { $('#dscs-m-typing').remove(); }

            function fmt(text) {
                if (!text) return '';
                var d = document.createElement('div');
                d.appendChild(document.createTextNode(text));
                text = d.innerHTML;
                text = text.replace(/```(\w*)\n([\s\S]*?)```/g, function(m, l, c) { return '<pre><code>' + c.trim() + '</code></pre>'; });
                text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
                text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
                var ps = text.split(/\n\n+/);
                text = ps.map(function(p) {
                    p = p.trim(); if (!p) return '';
                    if (p.match(/^[-*]\s/m)) return '<ul>' + p.replace(/^[-*]\s/gm, '<li>') + '</ul>';
                    return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
                }).join('');
                return text;
            }
        });
        </script>
        <?php
    }
}
