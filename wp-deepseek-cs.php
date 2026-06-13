<?php
/**
 * Plugin Name: Dify AI 客服
 * Plugin URI: https://example.com/dify-cs
 * Description: 基于 Dify Chatflow/Agent 的智能客服插件，支持浮窗聊天、对话记录保存
 * Version: 1.5.0
 * Author: WorkBuddy
 * Text Domain: dify-cs
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义常量
define('DSCS_VERSION', '1.5.0');
define('DSCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DSCS_PLUGIN_URL', plugin_dir_url(__FILE__));

// 自动加载
require_once DSCS_PLUGIN_DIR . 'includes/class-activator.php';
require_once DSCS_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once DSCS_PLUGIN_DIR . 'includes/class-api.php';
require_once DSCS_PLUGIN_DIR . 'includes/class-conversation.php';
require_once DSCS_PLUGIN_DIR . 'includes/class-chat-widget.php';
require_once DSCS_PLUGIN_DIR . 'includes/class-admin.php';

// 注册激活/停用钩子
register_activation_hook(__FILE__, ['DSCS_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['DSCS_Deactivator', 'deactivate']);

// 初始化插件
function dscs_init() {
    load_plugin_textdomain('dify-cs', false, dirname(plugin_basename(__FILE__)) . '/languages');

    DSCS_Admin::init();
    DSCS_Chat_Widget::init();
}

add_action('plugins_loaded', 'dscs_init');
