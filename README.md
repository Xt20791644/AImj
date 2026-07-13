# 🎬 AI短剧 — AI驱动的短剧创作平台

输入一个故事，AI 自动完成剧本拆解、角色提取、分镜生成、画面渲染、配音合成 — 一站式短剧制作。

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 前端 | Vue 3 + Vite + Element Plus + Pinia + Vue Router | SPA 单页应用 |
| 后端 | Laravel 12 (PHP 8.2) | REST API |
| 数据库 | MySQL 8.0 | 用户/积分/作品数据 |
| AI 生图/视频 | 可灵AI (Kling) | 真人写实风格短剧 |
| AI 配音 | CosyVoice（阿里云百炼） | 20+ 预置音色 + 音色克隆 |
| 文本管线 | opencode-storyclaw | 剧本→角色→分镜→提示词 |
| 视频合成 | FFmpeg | 拼接/混音/字幕 |
| 部署 | Docker Compose | 一键启动开发环境 |

## 项目结构

```
aiduanju/
├── frontend/                # Vue 3 前端
│   ├── src/
│   │   ├── api/             # Axios API 封装
│   │   ├── layouts/         # 布局组件
│   │   ├── router/          # 路由配置
│   │   ├── stores/          # Pinia 状态管理
│   │   └── views/           # 页面组件
│   │       ├── auth/        # 登录/注册
│   │       └── admin/       # 管理后台
│   └── vite.config.js
├── backend/                 # Laravel 后端
│   ├── app/
│   │   ├── Http/Controllers/  # API 控制器
│   │   ├── Models/           # 数据模型
│   │   ├── Services/         # AI 服务封装
│   │   └── Jobs/             # 异步任务
│   ├── database/migrations/  # 数据库迁移
│   ├── routes/api.php        # API 路由
│   └── Dockerfile
└── docker-compose.yml       # Docker 开发环境
```

## 快速开始

### 前置条件

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 已安装

### 1. 克隆项目

```bash
git clone https://github.com/Xt20791644/AImj.git
cd AImj
```

### 2. 配置环境变量

```bash
cp backend/.env.example backend/.env
```

编辑 `backend/.env`，填入 API Key：

```env
KLING_API_KEY=你的可灵API Key
KLING_API_SECRET=你的可灵API Secret
COSYVOICE_API_KEY=你的CosyVoice API Key (阿里云百炼)
```

### 3. 启动服务

```bash
docker-compose up -d
```

首次启动会自动：
- 拉取镜像并构建
- 安装 PHP 依赖 (composer install)
- 启动 MySQL + PHP-FPM + Nginx + 队列 Worker
- 前端 npm install + dev server

### 4. 初始化数据库

```bash
# 运行迁移
docker exec aiduanju-backend php artisan migrate

# 填充测试数据（管理员 + 示例作品）
docker exec aiduanju-backend php artisan db:seed
```

### 5. 访问

| 服务 | 地址 |
|------|------|
| 前端页面 | http://localhost:5173 |
| 后端 API | http://localhost:8000 |
| 管理后台 | http://localhost:5173/admin |

### 测试账号

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 管理员 | admin@aiduanju.com | admin123 |
| 测试用户 | test@aiduanju.com | test123 |

## 功能模块

### 用户端

- ✅ 注册/登录
- ✅ 故事输入 → AI 自动生成短剧
- ✅ 作品广场浏览
- ✅ 积分余额查看、充值、消费记录
- ✅ 个人中心

### 管理后台

- ✅ 数据概览（用户数/作品数/营收/积分）
- ✅ 用户管理（列表/删除）
- ✅ 积分充值（手动给用户充值）

### 生成管线

```
用户输入故事 → ① 剧本分析 → ② 角色提取 → ③ 分镜生成
→ ④ 可灵AI生图 → ⑤ 可灵AI生视频 → ⑥ CosyVoice配音
→ ⑦ FFmpeg合成 → 完成通知
```

## API 接口

### 认证

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/auth/register | 注册 |
| POST | /api/auth/login | 登录 |
| GET | /api/auth/me | 当前用户信息 |

### 积分

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/credits/balance | 积分余额 |
| GET | /api/credits/transactions | 交易记录 |

### 作品

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/works | 作品列表 |
| POST | /api/works | 创建作品 |
| GET | /api/works/{id} | 作品详情（含进度） |
| GET | /api/works/{id}/timeline | 生成时间线 |

### 管理 (需 Admin)

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/admin/stats | 数据概览 |
| GET | /api/admin/users | 用户列表 |
| POST | /api/admin/users/{id}/recharge | 给用户充值 |
| DELETE | /api/admin/users/{id} | 删除用户 |
| GET | /api/admin/transactions | 交易记录 |

## 许可证

MIT
