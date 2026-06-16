<?php
$pdo = require __DIR__ . '/db.php';

$companies = $pdo->query("
    SELECT c.id, c.name, c.description, c.variants,
        COUNT(DISTINCT kw.id) as kw_count,
        COUNT(DISTINCT r.id) as report_count,
        COUNT(DISTINCT CASE WHEN r.matched = 1 THEN r.id END) as matched_count,
        COUNT(DISTINCT r.platform_id) as platform_count
    FROM companies c
    LEFT JOIN keywords kw ON kw.company_id = c.id
    LEFT JOIN reports r ON r.keyword_id = kw.id
    GROUP BY c.id ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$shareReports = $pdo->query("
    SELECT r.id, r.share_token, k.name as keyword, c.name as company_name, 
           p.name as platform_name, p.color_start, r.matched
    FROM reports r
    JOIN keywords k ON r.keyword_id = k.id
    JOIN companies c ON k.company_id = c.id
    JOIN platforms p ON r.platform_id = p.id
    WHERE r.share_token != ''
    ORDER BY r.inclusion_date DESC LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

$platforms = $pdo->query("SELECT * FROM platforms ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$totalReports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$totalMatched = $pdo->query("SELECT COUNT(*) FROM reports WHERE matched = 1")->fetchColumn();
$totalKeywords = $pdo->query("SELECT COUNT(*) FROM keywords")->fetchColumn();
$matchRate = $totalReports > 0 ? round($totalMatched / $totalReports * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="author" content="王尘宇, 西安蓝蜻蜓网络科技有限公司">
    <meta name="generator" content="王尘宇GEO排名查询系统 by 王尘宇">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>王尘宇GEO排名查询系统 — AI品牌可见度监测与智能分析平台</title>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        :root {
            --bg: #fafbfc;
            --surface: #ffffff;
            --text: #1a1a2e;
            --text-secondary: #6b7280;
            --border: #e5e7eb;
            --accent: #4f46e5;
            --accent-light: #818cf8;
            --green: #10b981;
            --green-light: #d1fae5;
            --amber: #f59e0b;
            --radius: 12px;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:'Inter','PingFang SC','Noto Sans SC','Microsoft YaHei',sans-serif;background:var(--bg);color:var(--text);line-height:1.7;-webkit-font-smoothing:antialiased}

        /* Nav */
        .nav-bar{position:fixed;top:0;left:0;right:0;z-index:50;background:rgba(255,255,255,0.92);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.05);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between}
        .nav-bar .logo{display:flex;align-items:center;gap:10px;text-decoration:none;font-size:17px;font-weight:700;color:#1a1a2e}
        .nav-bar .logo .logo-icon{width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#4f46e5,#818cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0}
        .nav-bar .links{display:flex;gap:2px;align-items:center}
        .nav-bar .links a{text-decoration:none;color:#6b7280;padding:7px 14px;border-radius:7px;font-size:14px;font-weight:500;transition:all 0.15s}
        .nav-bar .links a:hover{color:#1a1a2e;background:#f3f4f6}
        .nav-bar .links a.active{color:#4f46e5;background:#eef2ff;font-weight:600}
        .nav-bar .links a.btn-nav{background:#4f46e5;color:#fff;padding:8px 18px;border-radius:7px;font-weight:600}
        .nav-bar .links a.btn-nav:hover{background:#4338ca;box-shadow:0 2px 12px rgba(79,70,229,0.25)}
        @media(max-width:640px){.nav-bar{padding:0 14px}.nav-bar .links a{padding:5px 8px;font-size:12px}}

        section{padding:100px 0}
        .container{max-width:1200px;margin:0 auto;padding:0 32px}

        /* Hero */
        .hero{padding:160px 0 120px;text-align:center;position:relative;overflow:hidden}
        .hero::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(ellipse at 50% 0%,rgba(79,70,229,0.04) 0%,transparent 50%),radial-gradient(ellipse at 80% 80%,rgba(16,185,129,0.03) 0%,transparent 50%)}
        .hero .container{position:relative;z-index:1}
        .hero .eyebrow{display:inline-block;padding:5px 18px;border-radius:20px;background:rgba(79,70,229,0.06);color:var(--accent);font-size:13px;font-weight:600;letter-spacing:0.06em;margin-bottom:28px}
        .hero h1{font-size:clamp(36px,6vw,56px);font-weight:800;letter-spacing:-1.5px;line-height:1.15;margin-bottom:20px}
        .hero h1 .gradient{background:linear-gradient(135deg,var(--accent) 0%,var(--green) 70%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero .subtitle{font-size:18px;color:var(--text-secondary);max-width:640px;margin:0 auto 40px;line-height:1.8}
        .hero .actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        .btn{padding:12px 28px;border-radius:10px;font-size:15px;font-weight:600;text-decoration:none;transition:all 0.2s;display:inline-flex;align-items:center;gap:8px;cursor:pointer;border:none}
        .btn-primary{background:var(--accent);color:#fff}
        .btn-primary:hover{background:#4338ca;box-shadow:0 4px 24px rgba(79,70,229,0.3);transform:translateY(-1px)}
        .btn-secondary{background:#fff;color:var(--text);border:1px solid var(--border);box-shadow:0 1px 2px rgba(0,0,0,0.03)}
        .btn-secondary:hover{background:#f9fafb;border-color:#d1d5db}

        /* KPI */
        .kpi-strip{max-width:960px;margin:-48px auto 0;padding:0 32px;position:relative;z-index:2;display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
        @media(max-width:768px){.kpi-strip{grid-template-columns:repeat(2,1fr)}}
        .kpi-item{background:var(--surface);border-radius:var(--radius);padding:24px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.04)}
        .kpi-item .num{font-size:32px;font-weight:800;color:var(--text)}
        .kpi-item .lbl{font-size:13px;color:var(--text-secondary);margin-top:4px;font-weight:500}

        /* Section header */
        .section-header{text-align:center;margin-bottom:64px}
        .section-header h2{font-size:32px;font-weight:700;letter-spacing:-0.5px;margin-bottom:12px}
        .section-header p{color:var(--text-secondary);font-size:16px;max-width:560px;margin:0 auto}

        /* Feature grid */
        .feature-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
        .feature-card{background:var(--surface);border-radius:var(--radius);padding:32px;border:1px solid rgba(0,0,0,0.04);box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.25s}
        .feature-card:hover{box-shadow:0 8px 30px rgba(0,0,0,0.06);transform:translateY(-2px)}
        .feature-card .icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:20px}
        .feature-card .icon svg{width:22px;height:22px;color:#fff}
        .feature-card h3{font-size:17px;font-weight:600;margin-bottom:8px}
        .feature-card p{font-size:14px;color:var(--text-secondary);line-height:1.7}

        /* Platform section */
        .platform-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px}
        .platform-card{background:var(--surface);border-radius:10px;padding:20px 16px;text-align:center;border:1px solid rgba(0,0,0,0.04);transition:all 0.2s}
        .platform-card:hover{box-shadow:0 4px 16px rgba(0,0,0,0.06);transform:translateY(-1px)}
        .platform-card .dot{width:32px;height:32px;border-radius:50%;margin:0 auto 10px}
        .platform-card .name{font-size:13px;font-weight:600}

        /* Company cards */
        .company-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px}
        .company-card{background:var(--surface);border-radius:var(--radius);border:1px solid rgba(0,0,0,0.04);overflow:hidden;transition:all 0.25s;box-shadow:0 1px 3px rgba(0,0,0,0.03)}
        .company-card:hover{box-shadow:0 8px 30px rgba(0,0,0,0.06);transform:translateY(-2px)}
        .company-card .card-body{padding:28px 24px 0}
        .company-card .card-body h3{font-size:18px;font-weight:700;margin-bottom:6px}
        .company-card .card-body .company-desc{font-size:13px;color:var(--text-secondary);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:16px}
        .company-card .card-stats{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid #f3f4f6;padding:16px 24px}
        .card-stat{text-align:center}
        .card-stat .val{font-size:20px;font-weight:700}
        .card-stat .lbl{font-size:11px;color:var(--text-secondary);margin-top:2px}
        .company-card .card-footer{padding:0 24px 20px;display:flex;gap:8px}
        .company-card .card-footer .btn{flex:1;justify-content:center;padding:9px 16px;font-size:13px;border-radius:8px}

        /* Share links */
        .share-section{background:var(--surface);border-radius:var(--radius);border:1px solid rgba(0,0,0,0.04);overflow:hidden}
        .share-table{width:100%;border-collapse:collapse}
        .share-table thead th{text-align:left;padding:14px 20px;background:#f8f9fb;color:var(--text-secondary);font-size:12px;font-weight:600;letter-spacing:0.03em}
        .share-table tbody td{padding:14px 20px;border-bottom:1px solid #f3f4f6;font-size:14px;vertical-align:middle}
        .share-table tbody tr:last-child td{border-bottom:none}
        .share-table tbody tr:hover{background:#fafbfc}
        .match-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px}
        .match-yes{background:var(--green-light);color:#065f46}
        .match-no{background:#fee2e2;color:#991b1b}
        .match-dot{width:6px;height:6px;border-radius:50%}
        .match-yes .match-dot{background:var(--green)}
        .match-no .match-dot{background:#ef4444}
        .mono-link{font-family:'SF Mono','Fira Code',monospace;font-size:12px;color:var(--accent);text-decoration:none}
        .mono-link:hover{text-decoration:underline}
        .btn-xs{padding:4px 12px;font-size:11px;border-radius:6px;background:#f3f4f6;border:1px solid var(--border);color:var(--text-secondary);cursor:pointer;font-weight:500;transition:all 0.15s}
        .btn-xs:hover{background:#e5e7eb;color:var(--text)}
        .empty-state{text-align:center;padding:48px 24px;color:var(--text-secondary)}

        /* CTA */
        .cta{text-align:center;background:linear-gradient(160deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff;padding:80px 32px;border-radius:20px;margin:0 32px}
        .cta h2{font-size:30px;font-weight:700;margin-bottom:12px}
        .cta p{opacity:0.65;font-size:16px;margin-bottom:28px}
        .cta .btn-primary{background:#fff;color:var(--accent);font-weight:700}
        .cta .btn-primary:hover{background:#f0f0ff;box-shadow:0 4px 24px rgba(255,255,255,0.15)}

        footer{text-align:center;padding:40px 32px;color:var(--text-secondary);font-size:13px}
        footer a{color:var(--accent);text-decoration:none}
        footer a:hover{text-decoration:underline}

        .toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#1f2937;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;z-index:200;opacity:0;transition:opacity 0.25s;pointer-events:none}
        .toast.show{opacity:1}

        @media(max-width:640px){
            .nav-bar .links a:not(.btn-nav){display:none}
            section{padding:64px 0}
            .hero{padding:120px 0 80px}
            .hero h1{font-size:30px}
            .container{padding:0 20px}
            .feature-grid{grid-template-columns:1fr}
            .company-grid{grid-template-columns:1fr}
            .platform-grid{grid-template-columns:repeat(4,1fr)}
            .cta{margin:0 16px;padding:56px 24px}
            .kpi-strip{margin:-32px 16px 0;grid-template-columns:repeat(2,1fr);gap:10px}
        }
    </style>
</head>
<body>
    <nav class="nav-bar">
        <a href="#hero" class="logo">
            <span class="logo-icon">G</span> 王尘宇GEO排名查询系统
        </a>
        <div class="links">
            <a href="#features">功能</a>
            <a href="#platforms">AI平台</a>
            <a href="#companies">企业</a>
            <a href="#shares">分享链接</a>
            <a href="/geo-analysis.php">GEO分析</a>
            <a href="/register.php">注册</a>
            <a href="/member/login.php">登录</a>
            <a href="/iseeyu/" class="btn-nav">后台</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero" id="hero">
        <div class="container">
            <div class="eyebrow">GENERATIVE ENGINE OPTIMIZATION</div>
            <h1>监测品牌在<span class="gradient">AI大模型</span>中的可见度</h1>
            <p class="subtitle">
                自动查询 DeepSeek、豆包、Kimi 等 8 大 AI 平台，检测品牌名在 AI 回复中是否被提及。
                从关键词配置到数据看板，一站式 王尘宇GEO排名查询系统解决方案。
            </p>
            <div class="actions">
                <a href="/dashboard.php" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    打开数据看板
                </a>
                <a href="#features" class="btn btn-secondary">了解更多 ↓</a>
            </div>
        </div>
    </section>

    <!-- KPI -->
    <div class="kpi-strip">
        <div class="kpi-item"><div class="num"><?= count($companies) ?></div><div class="lbl">监测企业</div></div>
        <div class="kpi-item"><div class="num"><?= count($platforms) ?></div><div class="lbl">AI平台</div></div>
        <div class="kpi-item"><div class="num"><?= $totalKeywords ?></div><div class="lbl">追踪关键词</div></div>
        <div class="kpi-item"><div class="num"><?= $matchRate ?>%</div><div class="lbl">品牌命中率</div></div>
    </div>

    <!-- Features -->
    <section id="features">
        <div class="container">
            <div class="section-header">
                <h2>一站式 GEO 监测能力</h2>
                <p>从关键词配置到数据看板，覆盖品牌 AI 可见度管理的全流程</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="icon" style="background:linear-gradient(135deg,#4f46e5,#818cf8)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <h3>多关键词配置</h3>
                    <p>手动关键词 + AI 蒸馏词句双模式。支持自定义提问句子，精确控制 AI 查询内容。</p>
                </div>
                <div class="feature-card">
                    <div class="icon" style="background:linear-gradient(135deg,#10b981,#34d399)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <h3>品牌变体命中检测</h3>
                    <p>设置公司全称和多个别名，自动扫描 AI 回复中是否提及任意变体，精准判断收录状态。</p>
                </div>
                <div class="feature-card">
                    <div class="icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    </div>
                    <h3>8 平台并发查询</h3>
                    <p>一键爬取 DeepSeek、豆包、腾讯元宝、通义千问、文心一言、智谱AI、Kimi、纳米AI，实时获取结果。</p>
                </div>
                <div class="feature-card">
                    <div class="icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 001 1.73"/><path d="M14 2v6h6"/><path d="M6 16h4"/><path d="M6 12h8"/></svg>
                    </div>
                    <h3>AI 回复原文存档</h3>
                    <p>完整保存每次 AI 查询的回复内容，支持全文搜索，随时回溯查看历史数据。</p>
                </div>
                <div class="feature-card">
                    <div class="icon" style="background:linear-gradient(135deg,#ec4899,#f472b6)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    </div>
                    <h3>一键分享链接</h3>
                    <p>每条 AI 回复可生成独立分享链接，将收录详情发送给客户或团队成员查看。</p>
                </div>
                <div class="feature-card">
                    <div class="icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h3>数据可视化分析</h3>
                    <p>平台命中率对比、时间趋势图、公司×平台矩阵、关键词效果排行，数据驱动 GEO 优化决策。</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Platforms -->
    <section id="platforms" style="background:#fff">
        <div class="container">
            <div class="section-header">
                <h2>覆盖 <?= count($platforms) ?> 大国内 AI 平台</h2>
                <p>全部通过官方 API 直连，无需浏览器自动化，稳定高效</p>
            </div>
            <div class="platform-grid">
                <?php foreach ($platforms as $p): ?>
                <div class="platform-card">
                    <div class="dot" style="background:linear-gradient(135deg,<?= $p['color_start'] ?>,<?= $p['color_end'] ?>)"></div>
                    <div class="name"><?= htmlspecialchars($p['name']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Companies -->
    <?php if (!empty($companies)): ?>
    <section id="companies">
        <div class="container">
            <div class="section-header">
                <h2>监测企业</h2>
                <p>独立配置关键词与名称变体，每家企业拥有专属数据视图</p>
            </div>
            <div class="company-grid">
                <?php foreach ($companies as $c): 
                    $rate = $c['report_count'] > 0 ? round($c['matched_count'] / $c['report_count'] * 100) : 0;
                ?>
                <div class="company-card">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($c['name']) ?></h3>
                        <?php if ($c['description']): ?>
                        <p class="company-desc"><?= htmlspecialchars($c['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-stats">
                        <div class="card-stat"><div class="val"><?= $c['kw_count'] ?></div><div class="lbl">关键词</div></div>
                        <div class="card-stat"><div class="val"><?= $c['report_count'] ?></div><div class="lbl">查询次数</div></div>
                        <div class="card-stat"><div class="val" style="color:<?= $rate >= 50 ? 'var(--green)' : ($rate >= 20 ? 'var(--amber)' : 'inherit') ?>"><?= $rate ?>%</div><div class="lbl">命中率</div></div>
                    </div>
                    <div class="card-footer">
                        <a href="/dashboard.php?company=<?= $c['id'] ?>" class="btn btn-primary" style="font-size:13px;padding:9px 16px">查看数据 →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Share links -->
    <?php if (!empty($shareReports)): ?>
    <section id="shares" style="background:#fff">
        <div class="container">
            <div class="section-header">
                <h2>公开分享链接</h2>
                <p>已生成的 AI 收录详情分享链接，可复制发送给任意用户查看</p>
            </div>
            <div class="share-section">
                <table class="share-table">
                    <thead><tr>
                        <th>企业</th><th>关键词</th><th>AI平台</th><th>收录状态</th><th>分享链接</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($shareReports as $sr): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($sr['company_name']) ?></td>
                            <td><?= htmlspecialchars($sr['keyword']) ?></td>
                            <td><?= htmlspecialchars($sr['platform_name']) ?></td>
                            <td>
                                <span class="match-badge <?= $sr['matched'] ? 'match-yes' : 'match-no' ?>">
                                    <span class="match-dot"></span><?= $sr['matched'] ? '已收录' : '未收录' ?>
                                </span>
                            </td>
                            <td>
                                <a href="/share/?t=<?= $sr['share_token'] ?>" target="_blank" class="mono-link">/share/?t=<?= substr($sr['share_token'], 0, 10) ?>…</a>
                                <button class="btn-xs" onclick="copyUrl('/share/?t=<?= $sr['share_token'] ?>')" style="margin-left:8px">复制</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section>
        <div class="cta">
            <h2>开始监测你的品牌 AI 可见度</h2>
            <p>配置公司信息与关键词，一键查询 8 大 AI 平台收录情况</p>
            <a href="/iseeyu/" class="btn btn-primary">进入管理后台 →</a>
        </div>
    </section>

    <footer>
        <div style="max-width:1200px;margin:0 auto 16px;padding:0 32px;text-align:center">
            <span style="font-size:12px;color:#9ca3af;margin-right:12px">友情链接：</span>
            
            <a href="http://www.qro.cn" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">QRO</a>
            <a href="http://www.mqs.net" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">萌骑士</a>
            <a href="http://www.4029.cn" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">黄页网</a>
        </div>
                <p>王尘宇GEO排名查询系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>
    </footer>

    <div class="toast" id="toast"></div>
    <script>
    function toast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg; t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 1800);
    }
    function copyUrl(path) {
        navigator.clipboard.writeText(location.origin + path).then(() => toast('链接已复制'));
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({behavior:'smooth',block:'start'}); }
        });
    });

    // Nav shadow on scroll
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.nav');
        nav.style.boxShadow = window.scrollY > 10 ? '0 1px 8px rgba(0,0,0,0.06)' : 'none';
    });
    </script>
</body>
</html>
