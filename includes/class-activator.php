<?php
/**
 * 插件激活器 - 创建数据库表
 */
class DSCS_Activator {

    public static function activate() {
        self::create_tables();
        self::set_default_options();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 对话表
        $table_conversations = $wpdb->prefix . 'dscs_conversations';
        $sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL UNIQUE,
            dify_conversation_id VARCHAR(64) DEFAULT '' COMMENT 'Dify 侧会话 ID',
            visitor_ip VARCHAR(45) DEFAULT '',
            visitor_user_agent TEXT DEFAULT '',
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_session (session_id),
            INDEX idx_dify (dify_conversation_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) $charset_collate;";

        // 消息表
        $table_messages = $wpdb->prefix . 'dscs_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            role VARCHAR(20) NOT NULL COMMENT 'user / assistant',
            content LONGTEXT NOT NULL,
            dify_message_id VARCHAR(64) DEFAULT '' COMMENT 'Dify 侧消息 ID',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session (session_id),
            INDEX idx_created (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_conversations);
        dbDelta($sql_messages);
    }

    private static function set_default_options() {
        $defaults = [
            'dscs_api_key'         => '',
            'dscs_api_base_url'    => 'http://chat.kairuie.com',
            'dscs_widget_title'    => 'AI 智能客服',
            'dscs_widget_greeting' => '您好！我是 AI 客服助手，请问有什么可以帮您？',
            'dscs_widget_primary_color' => '#4F46E5',
            'dscs_widget_position' => 'right',
            'dscs_widget_mode'     => 'float',
            'dscs_bot_avatar'      => '',
            'dscs_bot_label'       => '凯瑞尔客服',
            'dscs_enable_history'  => '1',
            'dscs_user'            => 'wordpress_visitor',
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
