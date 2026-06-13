<?php
/**
 * Dify Chat Messages API 集成类
 */
class DSCS_API {

    /**
     * 发送聊天消息到 Dify
     *
     * @param string $query           用户消息
     * @param string $conversation_id Dify 会话 ID（续聊时传）
     * @param string $user            用户标识
     * @return array
     */
    public static function chat($query, $conversation_id = '', $user = 'wordpress_visitor') {
        $api_key  = get_option('dscs_api_key', '');
        $base_url = get_option('dscs_api_base_url', 'http://chat.kairuie.com');

        if (empty($api_key)) {
            return ['error' => 'Dify API Key 未配置，请在后台设置'];
        }

        $base_url = rtrim($base_url, '/');

        $body = wp_json_encode([
            'query'           => $query,
            'inputs'          => new stdClass(),
            'response_mode'   => 'blocking',
            'user'            => $user,
            'conversation_id' => $conversation_id ?: '',
        ]);

        $response = wp_remote_post($base_url . '/v1/chat-messages', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => $body,
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return ['error' => '请求失败: ' . $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body_raw    = wp_remote_retrieve_body($response);
        $data        = json_decode($body_raw, true);

        if ($status_code !== 200) {
            $err_msg = $data['error']['message'] ?? $data['message'] ?? '未知错误 (HTTP ' . $status_code . ')';
            return ['error' => 'Dify API 返回错误: ' . $err_msg];
        }

        return [
            'answer'          => $data['answer'] ?? '',
            'conversation_id' => $data['conversation_id'] ?? '',
            'message_id'      => $data['message_id'] ?? '',
            'created_at'      => $data['created_at'] ?? 0,
        ];
    }

    /**
     * 测试 API 连接
     */
    public static function test_connection() {
        $api_key  = get_option('dscs_api_key', '');
        $base_url = get_option('dscs_api_base_url', 'http://chat.kairuie.com');

        if (empty($api_key)) {
            return ['ok' => false, 'msg' => '未配置 API Key'];
        }

        $base_url = rtrim($base_url, '/');

        $response = wp_remote_get($base_url . '/v1/info', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'msg' => '无法连接: ' . $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            return ['ok' => true, 'msg' => 'API 连接正常'];
        }

        // 部分 Dify 版本可能没有 /v1/info，用 chat-messages 发条空消息验证
        $body = wp_json_encode([
            'query'         => 'ping',
            'inputs'        => new stdClass(),
            'response_mode' => 'blocking',
            'user'          => 'test',
        ]);

        $resp2 = wp_remote_post($base_url . '/v1/chat-messages', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => $body,
            'timeout' => 10,
        ]);

        if (is_wp_error($resp2)) {
            return ['ok' => false, 'msg' => '无法连接: ' . $resp2->get_error_message()];
        }

        $code2 = wp_remote_retrieve_response_code($resp2);
        if ($code2 === 200) {
            return ['ok' => true, 'msg' => 'API 连接正常'];
        }

        $body2 = json_decode(wp_remote_retrieve_body($resp2), true);
        $err   = $body2['error']['message'] ?? $body2['message'] ?? "HTTP $code2";
        return ['ok' => false, 'msg' => "连接失败: $err"];
    }
}
