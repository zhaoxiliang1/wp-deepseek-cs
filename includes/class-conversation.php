<?php
/**
 * 对话记录管理类
 */
class DSCS_Conversation {

    /**
     * 创建或获取会话
     */
    public static function get_or_create_session($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dscs_conversations';

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s",
            $session_id
        ));

        if ($session) {
            return $session;
        }

        $wpdb->insert($table, [
            'session_id'         => $session_id,
            'dify_conversation_id' => '',
            'visitor_ip'         => self::get_client_ip(),
            'visitor_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s",
            $session_id
        ));
    }

    /**
     * 保存或更新 Dify conversation_id
     */
    public static function update_dify_conversation_id($session_id, $dify_conv_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dscs_conversations';

        return $wpdb->update(
            $table,
            ['dify_conversation_id' => $dify_conv_id],
            ['session_id' => $session_id]
        );
    }

    /**
     * 获取 Dify conversation_id
     */
    public static function get_dify_conversation_id($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dscs_conversations';

        $row = $wpdb->get_var($wpdb->prepare(
            "SELECT dify_conversation_id FROM $table WHERE session_id = %s",
            $session_id
        ));

        return $row ?: '';
    }

    /**
     * 保存消息
     */
    public static function save_message($session_id, $role, $content, $dify_message_id = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'dscs_messages';

        return $wpdb->insert($table, [
            'session_id'     => $session_id,
            'role'           => $role,
            'content'        => $content,
            'dify_message_id'=> $dify_message_id,
        ]);
    }

    /**
     * 获取会话历史消息
     */
    public static function get_messages($session_id, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'dscs_messages';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT role, content, created_at FROM $table
             WHERE session_id = %s
             ORDER BY created_at ASC
             LIMIT %d",
            $session_id,
            intval($limit)
        ));
    }

    /**
     * 获取访客 IP
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }
}
