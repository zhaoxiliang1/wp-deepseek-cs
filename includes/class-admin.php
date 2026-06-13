<?php
/**
 * 后台管理页面（Dify 版）
 */
class DSCS_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Dify AI 客服',
            'AI 客服',
            'manage_options',
            'dify-cs',
            [__CLASS__, 'render_settings_page'],
            'dashicons-format-chat',
            30
        );
    }

    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'dify-cs') === false && strpos($hook, 'deepseek-cs') === false) {
            return;
        }
        wp_enqueue_media(); // 媒体上传器
        wp_enqueue_style('dscs-admin', DSCS_PLUGIN_URL . 'admin/css/admin.css', [], DSCS_VERSION);
        wp_enqueue_script('dscs-admin', DSCS_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], DSCS_VERSION, true);
    }

    // ========== 设置页面 ==========

    public static function render_settings_page() {
        // 保存设置
        if (isset($_POST['dscs_save_settings']) && check_admin_referer('dscs_settings')) {
            update_option('dscs_api_key', sanitize_text_field($_POST['dscs_api_key'] ?? ''));
            update_option('dscs_api_base_url', rtrim(sanitize_text_field($_POST['dscs_api_base_url'] ?? 'http://chat.kairuie.com'), '/'));
            update_option('dscs_widget_title', sanitize_text_field($_POST['dscs_widget_title'] ?? 'AI 智能客服'));
            update_option('dscs_widget_greeting', sanitize_textarea_field($_POST['dscs_widget_greeting'] ?? ''));
            update_option('dscs_widget_primary_color', sanitize_hex_color($_POST['dscs_widget_primary_color'] ?? '#4F46E5'));
            update_option('dscs_widget_position', $_POST['dscs_widget_position'] === 'left' ? 'left' : 'right');
            update_option('dscs_widget_mode', in_array($_POST['dscs_widget_mode'], ['float','sidebar','page']) ? $_POST['dscs_widget_mode'] : 'float');
            update_option('dscs_bot_avatar', esc_url_raw($_POST['dscs_bot_avatar'] ?? ''));
            update_option('dscs_bot_label', sanitize_text_field($_POST['dscs_bot_label'] ?? '凯瑞尔客服'));
            update_option('dscs_enable_history', isset($_POST['dscs_enable_history']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>设置已保存。</p></div>';
        }

        $api_key       = get_option('dscs_api_key', '');
        $api_base_url  = get_option('dscs_api_base_url', 'http://chat.kairuie.com');
        $widget_title  = get_option('dscs_widget_title', 'AI 智能客服');
        $widget_greeting = get_option('dscs_widget_greeting', '您好！我是 AI 客服助手，请问有什么可以帮您？');
        $primary_color = get_option('dscs_widget_primary_color', '#4F46E5');
        $position      = get_option('dscs_widget_position', 'right');
        $widget_mode   = get_option('dscs_widget_mode', 'float'); // float | sidebar
        $bot_avatar    = get_option('dscs_bot_avatar', '');
        $bot_label     = get_option('dscs_bot_label', '凯瑞尔客服');
        $enable_history= get_option('dscs_enable_history', '1');

        // 测试连接
        $api_status = DSCS_API::test_connection();
        ?>
        <div class="wrap dscs-admin-wrap">
            <h1>Dify AI 客服 — 设置</h1>

            <form method="post" action="">
                <?php wp_nonce_field('dscs_settings'); ?>

                <!-- API 配置 -->
                <div class="dscs-card">
                    <h2>🔌 Dify API 配置</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="dscs_api_base_url">Dify 基础地址</label></th>
                            <td>
                                <input type="url" id="dscs_api_base_url" name="dscs_api_base_url"
                                       value="<?php echo esc_attr($api_base_url); ?>" class="regular-text" />
                                <p class="description">
                                    你的 Dify WebApp 地址前缀，例如 <code>http://chat.kairuie.com</code>
                                    （当前已配置为你的客服地址 ✅）
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="dscs_api_key">API Secret Key</label></th>
                            <td>
                                <input type="password" id="dscs_api_key" name="dscs_api_key"
                                       value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                                <p class="description">
                                    在 Dify 后台 → 该应用 → API 访问 页面获取
                                    <?php if ($api_status !== null): ?>
                                        <span class="dscs-status <?php echo $api_status['ok'] ? 'dscs-ok' : 'dscs-err'; ?>">
                                            ● <?php echo esc_html($api_status['msg']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- 聊天窗口外观 -->
                <div class="dscs-card">
                    <h2>🎨 聊天窗口外观</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="dscs_widget_title">窗口标题</label></th>
                            <td><input type="text" id="dscs_widget_title" name="dscs_widget_title"
                                       value="<?php echo esc_attr($widget_title); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="dscs_widget_greeting">开场问候语</label></th>
                            <td><textarea id="dscs_widget_greeting" name="dscs_widget_greeting"
                                          rows="2" class="large-text"><?php echo esc_textarea($widget_greeting); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="dscs_widget_primary_color">主题色</label></th>
                            <td>
                                <input type="color" id="dscs_widget_primary_color" name="dscs_widget_primary_color"
                                       value="<?php echo esc_attr($primary_color); ?>" />
                                <code><?php echo esc_html($primary_color); ?></code>
                            </td>
                        </tr>
                        <tr>
                            <th>浮窗位置</th>
                            <td>
                                <label><input type="radio" name="dscs_widget_position" value="right"
                                    <?php checked($position, 'right'); ?> /> 右下角</label>
                                &nbsp;&nbsp;
                                <label><input type="radio" name="dscs_widget_position" value="left"
                                    <?php checked($position, 'left'); ?> /> 左下角</label>
                            </td>
                        </tr>
                        <tr>
                            <th>显示模式</th>
                            <td>
                                <label><input type="radio" name="dscs_widget_mode" value="float"
                                    <?php checked($widget_mode, 'float'); ?> /> 右下角气泡（点击展开）</label>
                                <br>
                                <label><input type="radio" name="dscs_widget_mode" value="sidebar"
                                    <?php checked($widget_mode, 'sidebar'); ?> /> 侧边悬浮（固定在页面侧边随滚动）</label>
                                <br>
                                <label><input type="radio" name="dscs_widget_mode" value="page"
                                    <?php checked($widget_mode, 'page'); ?> /> 独立页面（使用短代码 <code>[dify_cs]</code> 插入到任意页面）</label>
                                <p class="description">「独立页面」适合手机端访问，新建一个页面并插入短代码 <code>[dify_cs]</code>，全屏聊天体验更佳</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="dscs_bot_avatar">客服头像</label></th>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="text" id="dscs_bot_avatar" name="dscs_bot_avatar"
                                           value="<?php echo esc_attr($bot_avatar); ?>" class="regular-text" placeholder="输入图片 URL 或点击上传" />
                                    <button type="button" id="dscs_upload_avatar" class="button">上传图片</button>
                                    <button type="button" id="dscs_remove_avatar" class="button" <?php echo empty($bot_avatar) ? 'style="display:none"' : ''; ?>>移除</button>
                                </div>
                                <div id="dscs_avatar_preview" style="margin-top:8px;">
                                    <?php if (!empty($bot_avatar)): ?>
                                        <img src="<?php echo esc_attr($bot_avatar); ?>" style="max-width:64px;border-radius:50%;border:2px solid #e2e8f0;" />
                                    <?php endif; ?>
                                </div>
                                <p class="description">推荐 100×100 以上的方形图片，会自动裁剪为圆形</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="dscs_bot_label">头像下方文字</label></th>
                            <td>
                                <input type="text" id="dscs_bot_label" name="dscs_bot_label"
                                       value="<?php echo esc_attr($bot_label); ?>" class="regular-text" placeholder="凯瑞尔客服" />
                                <p class="description">显示在头像下方的品牌文字，例如「凯瑞尔客服」（会显示为绿色）</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- 功能开关 -->
                <div class="dscs-card">
                    <h2>⚙️ 功能设置</h2>
                    <table class="form-table">
                        <tr>
                            <th>记录对话历史</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dscs_enable_history" value="1"
                                        <?php checked($enable_history, '1'); ?> />
                                    将聊天记录保存到 WordPress 数据库
                                </label>
                                <p class="description">
                                    知识库由 Dify 管理，请前往
                                    <a href="http://chat.kairuie.com" target="_blank">Dify 应用后台</a>
                                    配置 Prompt 和知识库
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="dscs_save_settings" class="button button-primary">
                        保存设置
                    </button>
                </p>
            </form>

            <div class="dscs-card" style="background:#f0f6fc;border-left:4px solid #2271b1;">
                <h3>📋 快速部署指引</h3>
                <ol style="margin:8px 0 0 18px;line-height:2;">
                    <li>✅ 确认上方 Dify API 地址为 <code>http://chat.kairuie.com</code></li>
                    <li>🔑 填入 Dify 应用后台获取的 <strong>Secret Key</strong></li>
                    <li>🎨 按需调整外观、显示模式、客服头像</li>
                    <li>💾 点击保存设置，前端所有页面自动出现客服聊天</li>
                    <li>📚 知识库请直接在 <a href="http://chat.kairuie.com" target="_blank">Dify 后台</a> 中管理</li>
                </ol>
            </div>
        </div>
        <?php
    }
}
