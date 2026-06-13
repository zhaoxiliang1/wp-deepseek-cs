jQuery(function($) {
    'use strict';

    // 颜色选择器预览
    var colorInput = $('#dscs_widget_primary_color');
    if (colorInput.length) {
        colorInput.on('input', function() {
            $(this).next('code').text($(this).val());
        });
    }

    // 头像上传
    var fileFrame;
    var $avatarInput = $('#dscs_bot_avatar');
    var $avatarPreview = $('#dscs_avatar_preview');
    var $removeBtn = $('#dscs_remove_avatar');

    // 检查元素是否存在
    if (!$avatarInput.length) return;

    $('#dscs_upload_avatar').on('click', function(e) {
        e.preventDefault();

        if (fileFrame) {
            fileFrame.open();
            return;
        }

        try {
            fileFrame = wp.media({
                title: '选择客服头像',
                button: { text: '使用此图片' },
                multiple: false,
                library: { type: 'image' }
            });

            fileFrame.on('select', function() {
                var attachment = fileFrame.state().get('selection').first().toJSON();
                var imgUrl = attachment.url;

                // 更新输入框
                $avatarInput.val(imgUrl);

                // 更新预览
                if ($avatarPreview.length) {
                    $avatarPreview.html('<img src="' + imgUrl + '" style="max-width:64px;border-radius:50%;border:2px solid #e2e8f0;" />');
                }

                // 显示移除按钮
                if ($removeBtn.length) {
                    $removeBtn.show();
                }
            });

            fileFrame.open();
        } catch(e) {
            console.error('头像上传出错:', e);
            alert('头像上传功能加载失败，请手动输入图片 URL');
        }
    });

    // 移除头像
    $removeBtn.on('click', function() {
        $avatarInput.val('');
        if ($avatarPreview.length) $avatarPreview.empty();
        $(this).hide();
    });

    // 手动输入预览
    $avatarInput.on('input', function() {
        var url = $(this).val().trim();
        if (url && $avatarPreview.length) {
            $avatarPreview.html('<img src="' + url + '" style="max-width:64px;border-radius:50%;border:2px solid #e2e8f0;" />');
            if ($removeBtn.length) $removeBtn.show();
        } else {
            if ($avatarPreview.length) $avatarPreview.empty();
            if ($removeBtn.length) $removeBtn.hide();
        }
    });
});
