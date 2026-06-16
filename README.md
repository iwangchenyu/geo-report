# 王尘宇GEO排名查询系统

西安蓝蜻蜓网络科技有限公司 · AI大模型品牌可见度监测平台，支持 8 大平台品牌收录检测、实时分析、可视化报表与会员数据服务。

## 功能

### 品牌收录监测
- **8大AI平台全覆盖** — DeepSeek、豆包、腾讯元宝、通义千问、文心一言、Kimi、智谱AI
- **品牌变体命中识别** — 自动检测回复中是否出现公司全称/简称，支持多个品牌变体
- **API直调爬虫** — 零浏览器依赖，ThreadPoolExecutor 并发，速度快、免维护

### 数据分析
- **GEO实时分析** — 竞品对比、来源分析（百科/社区/新闻/学术/技术/社交/视频）
- **时间趋势** — 按月统计收录变化、命中率走势
- **关键词排行** — 各关键词在平台上的收录与命中排行

### 报表与分享
- **可视化仪表盘** — Chart.js 图表，平台分布/命中率/收录趋势
- **CSV导出** — 筛选后一键导出完整报表
- **分享链接** — 单条报告生成独立分享页，适合发给客户

### 管理与协作
- **多公司支持** — 一套系统管理多个品牌，数据隔离
- **会员系统** — 注册→管理员审核→分配企业→会员查看专属数据
- **管理后台** — 公司/关键词/报表/用户/会员/API配置一站式管理
- **API Key 在线配置** — 无需改文件，后台直接保存

## 技术栈

- **前端**: PHP + HTML + Chart.js
- **后端 API**: PHP (SQLite)
- **爬虫引擎**: Python 3（API直调 + ThreadPoolExecutor 并发）
- **数据库**: SQLite (WAL 模式)
- **认证**: Session-based（会员 + 管理员双体系）
- **安全**: CSRF 保护 / API Token 鉴权 / Session 固定防护

## 快速开始

```bash
# 配置 AI 平台的 API Key（至少配一个）
export ZHIPU_API_KEY=xxx      # 智谱AI
export HUNYUAN_API_KEY=xxx     # 腾讯混元
# 其他平台见 config.yaml

# 启动服务
php -S 0.0.0.0:8080

# 运行爬虫
python3 crawler_v4.py --crawl

# 实时分析
python3 geo_analyzer.py "西安科技大学高新学院"
```

浏览器打开 `http://localhost:8080`

## 演示站

演示站：[wenhaolian.com](https://wenhaolian.com)

- 首页可直接浏览，无需登录
- 管理后台：`/iseeyu/`，默认账号 `iseeyu`（首次登录请修改密码）
- 会员入口：`/member/login.php`

## 配置 AI 接口

在管理后台「系统设置」中在线配置，或在 `config.yaml` 中设置环境变量：

| 接口 | 获取地址 |
|------|---------|
| 智谱AI | https://open.bigmodel.cn |
| 腾讯混元 | https://console.cloud.tencent.com/hunyuan |
| DeepSeek | https://platform.deepseek.com |
| 通义千问 | https://bailian.console.aliyun.com |
| Kimi | https://platform.moonshot.cn |
| 豆包 | https://console.volcengine.com/ark |
| 文心一言 | https://console.bce.baidu.com/ai |

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
├── crawl_cron.sh          # 定时爬取脚本
├── lib/
│   ├── migrate.php        # 数据库迁移 & 种子数据
│   ├── csrf.php           # CSRF 防护
│   └── guard.php          # API Token 鉴权
├── iseeyu/                # 管理后台
├── member/                # 会员中心
├── share/                 # 分享页
└── assets/
    └── style.css          # 共享样式
```

## 部署

### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name wenhaolian.com;
    root /var/www/geo-report;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /data/ {
        deny all;
    }

    location /lib/ {
        deny all;
    }
}
```

### 定时爬取

```bash
# 添加到 crontab，每天 8:00、14:00、20:00 自动爬取
0 8,14,20 * * * /bin/bash /var/www/geo-report/crawl_cron.sh
```

## 作者

**王尘宇** · 西安蓝蜻蜓网络科技有限公司

- 网站：[wangchenyu.com](https://wangchenyu.com) | [qro.cn](https://qro.cn) | [mqs.net](https://mqs.net)
- 邮箱：[314111741@qq.com](mailto:314111741@qq.com)
- 微信：wangshifucn

## License

MIT
