# Dify AI 客服 — WordPress 智能客服插件

基于 Dify Chatflow/Agent 的 WordPress 智能客服插件，支持浮窗聊天、对话记录保存、产品报价查询。

## 功能特性

- 🤖 **Dify AI 驱动** — 对接 Dify Chatflow / Agent API，AI 自动回复
- 💬 **浮窗聊天** — 前端悬浮气泡样式，绿色主题，自适应移动端
- 📄 **对话记录** — 保存用户聊天历史，支持后台查看
- 🔗 **多页面集成** — 自定义页面 ID，可在不同页面配置不同客服场景
- ⚙️ **后台管理** — WordPress 管理面板完整配置，无需写代码
- 🛡️ **安全性** — 数据权限隔离，仅管理员可查看对话记录

## 系统要求

- WordPress 5.0+
- PHP 7.4+
- Dify 服务（自部署或云端）

## 安装

1. 下载插件 ZIP 包
2. 进入 WordPress 后台 → 插件 → 安装插件 → 上传插件
3. 激活插件
4. 进入设置页面配置 Dify API 地址和 Key

## 配置

插件激活后，在 WordPress 左侧菜单找到 **Dify AI 客服**，进入设置页面：

| 配置项 | 说明 |
|--------|------|
| Dify API 地址 | 你的 Dify 服务地址（如 `https://chat.kairurie.com`） |
| API Key | Dify 应用的 API Key |
| 客服名称 | 聊天浮窗显示的客服名称 |
| 欢迎语 | 用户首次打开时显示的欢迎消息 |
| 页面 ID | 自定义页面标识（可选） |

## 文件结构

```
wp-deepseek-cs/
├── wp-deepseek-cs.php              # 主插件文件
├── admin/                          # 后台管理
│   ├── css/admin.css
│   ├── js/admin.js
│   └── partials/
├── includes/                       # 核心逻辑
│   ├── class-activator.php         # 激活/卸载钩子
│   ├── class-admin.php             # 后台管理页面
│   ├── class-api.php               # Dify API 通信
│   ├── class-chat-widget.php       # 前端聊天浮窗
│   ├── class-conversation.php      # 对话记录管理
│   ├── class-deactivator.php       # 停用钩子
│   └── class-knowledge-base.php    # 知识库集成
└── public/                         # 前端资源
    ├── css/chat-widget.css
    └── js/chat-widget.js
```

## 开发者

本插件由 WorkBuddy AI 辅助开发，用于 [kairuie.com.cn](https://kairuie.com.cn) 官网在线客服系统。

## License

MIT
