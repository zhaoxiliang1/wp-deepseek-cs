<?php
/**
 * 插件停用器
 */
class DSCS_Deactivator {

    public static function deactivate() {
        // 清理定时任务
        wp_clear_scheduled_hook('dscs_cleanup_old_conversations');
    }
}
