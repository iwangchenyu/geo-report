# 王尘宇GEO排名查询系统

AI大模型品牌可见度监测与智能分析平台 — 多平台查询、品牌收录检测、可视化报表。

## 演示

[https://wenhaolian.com](https://wenhaolian.com)

## 功能

- **8大AI平台收录检测**：DeepSeek、豆包、腾讯元宝、通义千问、文心一言、Kimi、智谱AI 全覆盖
- **品牌变体命中识别**：自动检测回复中是否出现公司全称/简称
- **多公司管理**：支持多个品牌独立监测
- **GEO实时分析**：竞品对比、来源分析、关键词排行
- **分享链接**：单条报告可生成独立分享页
- **会员系统**：注册→审核→查看专属数据
- **管理后台**：公司/关键词/报表/用户/API配置一站式管理

## 技术栈

| 层 | 技术 |
|---|---|
| 前端 | PHP + HTML + Chart.js |
| 后端 API | PHP (SQLite) |
| 爬虫引擎 | Python (API直调 + ThreadPoolExecutor) |
| 数据库 | SQLite (WAL模式) |
| 认证 | Session-based (会员 + 管理员双体系) |

## 快速开始

### 1. 环境要求

- PHP 7.4+
- Python 3.8+
- SQLite 3

### 2. 配置 API Key

```bash
export ZHIPU_API_KEY=xxx      # 智谱AI
export HUNYUAN_API_KEY=xxx     # 腾讯混元
# 其他平台同理，见 config.yaml
```

### 3. 启动服务

```bash
php -S 0.0.0.0:8080
```

浏览器打开 `http://localhost:8080`

### 4. 运行爬虫

```bash
# 查看平台状态
python3 crawler_v4.py --status

# 全量爬取
python3 crawler_v4.py --crawl

# 指定平台/关键词
python3 crawler_v4.py --crawl -p zhipu -k "科大高新"
```

### 5. 实时分析

```bash
python3 geo_analyzer.py "西安科技大学高新学院"
```

## 项目结构

```
├── index.php              # 首页
├── dashboard.php          # 数据仪表盘
├── geo-analysis.php       # GEO分析页
├── register.php           # 会员注册
├── api.php                # 公开 API
├── db.php                 # 数据库连接
├── config.yaml            # AI平台配置
├── crawler_v4.py          # 爬虫引擎 (API直调)
├── geo_analyzer.py        # 实时分析引擎
├── lib/
│   ├── migrate.php        # 数据库迁移
│   ├── csrf.php           # CSRF 防护
│   └── guard.php          # API 鉴权
├── iseeyu/                # 管理后台
├── member/                # 会员中心
├── share/                 # 分享页
└── assets/
    └── style.css          # 共享样式
```

## 管理后台

访问 `/iseeyu/`，默认账号 `iseeyu`（首次登录后请修改密码）。支持：

- 公司/品牌管理
- 关键词管理（手动 + AI生成）
- 报表查看/导出CSV
- API Key 在线配置
- 用户/会员管理
- 一键触发爬虫

## License

MIT

---

作者：王尘宇 | [www.wangchenyu.com](http://www.wangchenyu.com) | 西安蓝蜻蜓网络科技有限公司
