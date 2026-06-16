<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="author" content="王尘宇, 西安蓝蜻蜓网络科技有限公司">
    <meta name="generator" content="王尘宇GEO排名查询系统 by 王尘宇">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>王尘宇GEO排名查询系统 - AI收录统计</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f6f8;color:#1d2129;line-height:1.6;min-height:100vh}
        .container{max-width:1400px;margin:0 auto;padding:0 20px}

        /* Banner */
        .banner{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff;padding:56px 0;text-align:center;margin-bottom:28px;position:relative;overflow:hidden}
        .banner::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 30% 50%,rgba(99,102,241,0.15) 0%,transparent 60%),radial-gradient(ellipse at 70% 50%,rgba(16,185,129,0.1) 0%,transparent 60%)}
        .banner .container{position:relative;z-index:1}
        .banner h1{font-size:32px;font-weight:700;margin-bottom:8px;letter-spacing:-0.5px}
        .banner h1 span{color:#34d399}
        .banner p{font-size:16px;opacity:0.8}
        .banner .update-time{font-size:13px;opacity:0.55;margin-top:6px}
        .company-selector{margin-top:16px;display:inline-flex;align-items:center;gap:8px}
        .company-selector select{padding:6px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.1);color:#fff;font-size:14px;outline:none;cursor:pointer}
        .company-selector select option{color:#1d2129;background:#fff}
        .iseeyu-link{color:rgba(255,255,255,0.5);font-size:12px;text-decoration:none;margin-left:12px}
        .iseeyu-link:hover{color:#fff}

        /* Buttons */
        .btn{padding:8px 18px;border-radius:8px;font-size:14px;font-weight:500;border:none;cursor:pointer;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px}
        .btn-primary{background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff}
        .btn-primary:hover{background:linear-gradient(135deg,#4338ca,#4f46e5);box-shadow:0 2px 8px rgba(79,70,229,0.3)}
        .btn-danger{background:#fff;color:#ef4444;border:1px solid #fecaca}
        .btn-danger:hover{background:#fef2f2}
        .btn-sm{padding:5px 12px;font-size:12px}
        .btn-outline{background:#fff;color:#374151;border:1px solid #e5e7eb}
        .btn-outline:hover{background:#f3f4f6}
        .btn-success{background:#10b981;color:#fff}
        .btn-success:hover{background:#059669}

        /* Cards */
        .card{background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.06);padding:24px;transition:box-shadow 0.2s}
        .card:hover{box-shadow:0 4px 16px rgba(0,0,0,0.08)}

        /* Stat cards */
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
        @media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:500px){.stats-grid{grid-template-columns:1fr}}
        .stat-card{display:flex;justify-content:space-between;align-items:flex-start}
        .stat-card .label{font-size:13px;color:#6b7280;margin-bottom:4px;font-weight:500}
        .stat-card .value{font-size:30px;font-weight:700;color:#111827;line-height:1.2}
        .stat-card .desc{font-size:12px;color:#9ca3af;margin-top:2px}
        .stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .stat-icon svg{width:22px;height:22px;color:#fff}

        /* Charts */
        .charts-grid{display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:24px}
        @media(max-width:900px){.charts-grid{grid-template-columns:1fr}}
        .chart-wrap{position:relative;width:100%}
        .chart-wrap canvas{width:100%!important}
        .legend-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:16px}
        .legend-item{display:flex;align-items:center;font-size:13px;color:#4b5563}
        .legend-dot{width:10px;height:10px;border-radius:3px;margin-right:8px;flex-shrink:0}

        /* Section header */
        .section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px}
        .section-header h3{font-size:17px;font-weight:600}

        /* Platform tabs */
        .platform-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px}
        .platform-tab{font-size:12px;border:1px solid #e5e7eb;border-radius:8px;padding:6px 12px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;background:#fff;transition:all 0.15s;color:#4b5563}
        .platform-tab:hover{border-color:#c7d2fe;background:#fafafe}
        .platform-tab.active{background:#eef2ff;border-color:#6366f1;color:#4338ca;font-weight:500}
        .platform-tab .count{font-size:11px;color:#9ca3af}

        /* Search */
        .search-wrap{position:relative}
        .search-wrap svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;pointer-events:none}
        .search-input{border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px 8px 34px;font-size:14px;width:300px;outline:none;transition:border-color 0.15s}
        .search-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.08)}

        /* Table */
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        thead th{text-align:left;padding:10px 16px;background:#f8f9fb;color:#6b7280;font-size:12px;font-weight:500;white-space:nowrap}
        thead th:first-child{border-radius:8px 0 0 0}
        thead th:last-child{border-radius:0 8px 0 0}
        tbody td{padding:12px 16px;border-bottom:1px solid #f3f4f6;font-size:14px;color:#1d2129;vertical-align:top}
        tbody tr:hover{background:#fafbfc}
        tbody tr{cursor:pointer}
        tbody td .kw-tag{display:inline-block;font-size:11px;padding:1px 8px;border-radius:10px;margin-right:6px;font-weight:500}
        .kw-tag.manual{background:#dbeafe;color:#1d4ed8}
        .kw-tag.ai{background:#fef3c7;color:#b45309}
        .link-blue{color:#4f46e5;text-decoration:none;font-weight:500}
        .link-blue:hover{text-decoration:underline}
        .sort-icon{cursor:pointer;width:14px;height:14px;margin-left:4px;vertical-align:middle;opacity:0.5;transition:opacity 0.15s}
        .sort-icon:hover,.sort-icon.active{opacity:1}

        /* Matched status */
        .match-badge{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:12px}
        .match-badge.yes{background:#d1fae5;color:#065f46}
        .match-badge.no{background:#fee2e2;color:#991b1b}
        .match-badge .dot{width:6px;height:6px;border-radius:50%}
        .match-badge.yes .dot{background:#10b981}
        .match-badge.no .dot{background:#ef4444}

        /* Response snippet */
        .snippet-text{font-size:12px;color:#6b7280;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-style:italic}

        /* Pagination */
        .pagination{display:flex;justify-content:flex-end;align-items:center;margin-top:16px;gap:4px;flex-wrap:wrap}
        .pagination a,.pagination span{display:inline-block;padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;color:#4b5563;border:1px solid #e5e7eb;cursor:pointer}
        .pagination a:hover{background:#f3f4f6;color:#1d2129}
        .pagination .active{background:#4f46e5;color:#fff;border-color:#4f46e5}
        .pagination .disabled{color:#d1d5db;cursor:default;pointer-events:none}

        /* Modal */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:100;justify-content:center;align-items:center}
        .modal-overlay.show{display:flex}
        .modal{background:#fff;border-radius:14px;padding:28px;width:90%;max-width:560px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15)}
        .modal h3{font-size:18px;font-weight:600;margin-bottom:20px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none;font-family:inherit}
        .form-group textarea{resize:vertical;min-height:60px;line-height:1.5}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.08)}
        .form-group .hint{font-size:11px;color:#9ca3af;margin-top:4px}
        .form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}
        .kw-list{margin-top:16px;max-height:300px;overflow-y:auto}
        .kw-item{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border-radius:8px;margin-bottom:4px;background:#f9fafb;font-size:14px;transition:background 0.15s}
        .kw-item:hover{background:#f3f4f6}
        .kw-item .kw-info{flex:1;min-width:0}
        .kw-item .kw-name{font-weight:500;margin-bottom:2px}
        .kw-item .kw-query{font-size:11px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .kw-item .kw-actions{display:flex;gap:4px;flex-shrink:0;margin-left:12px}

        /* Detail modal */
        .detail-modal .modal{max-width:700px}
        .detail-section{margin-bottom:16px}
        .detail-section .label{font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
        .detail-section .content{font-size:14px;line-height:1.7;color:#374151;background:#f9fafb;padding:14px;border-radius:8px;max-height:320px;overflow-y:auto;white-space:pre-wrap;word-break:break-word}

        /* Share */
        .share-url{background:#f0f4ff;border-radius:8px;padding:10px 14px;font-family:monospace;font-size:13px;color:#4338ca;word-break:break-all;display:flex;align-items:center;justify-content:space-between;gap:8px}
        .share-url .copy-btn{flex-shrink:0}

        /* Toast */
        .toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#1f2937;color:#fff;padding:10px 24px;border-radius:8px;font-size:14px;z-index:200;opacity:0;transition:opacity 0.25s;pointer-events:none}
        .toast.show{opacity:1}

        footer{text-align:center;padding:32px 0;color:#9ca3af;font-size:13px}

        .empty-state{text-align:center;padding:40px;color:#9ca3af}
        .empty-state svg{width:48px;height:48px;margin-bottom:12px;opacity:0.4}

        .status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:4px}
        .status-dot.running{background:#f59e0b;animation:pulse 1.2s ease-in-out infinite}
        .status-dot.done{background:#10b981}
        .status-dot.error{background:#ef4444}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

        .sortable-th{cursor:pointer;user-select:none}
        .sortable-th:hover{color:#374151}
        .nav-bar{position:sticky;top:0;z-index:50;background:rgba(255,255,255,0.92);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.05);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between;margin-bottom:0}
        .nav-bar .logo{display:flex;align-items:center;gap:10px;text-decoration:none;font-size:17px;font-weight:700;color:#1a1a2e}
        .nav-bar .logo .logo-icon{width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#4f46e5,#818cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0}
        .nav-bar .links{display:flex;gap:2px;align-items:center}
        .nav-bar .links a{text-decoration:none;color:#6b7280;padding:7px 14px;border-radius:7px;font-size:14px;font-weight:500;transition:all 0.15s}
        .nav-bar .links a:hover{color:#1a1a2e;background:#f3f4f6}
        .nav-bar .links a.active{color:#4f46e5;background:#eef2ff;font-weight:600}
        @media(max-width:640px){.nav-bar{padding:0 14px}.nav-bar .links a{padding:5px 8px;font-size:12px}}
    </style>
</head>
<body>
    <nav class="nav-bar">
        <a href="/index.php" class="logo">
            <span class="logo-icon">G</span> 王尘宇GEO排名查询系统
        </a>
        <div class="links">
            <a href="/index.php">首页</a>
            <a href="/dashboard.php" class="active">数据看板</a>
            <a href="/geo-analysis.php">GEO分析</a>
            <a href="/iseeyu/">后台管理</a>
        </div>
    </nav>

    <!-- Banner -->
    <div class="banner">
        <div class="container">
            <h1><span>GEO</span> 报表系统</h1>
            <p>AI大模型品牌曝光收录统计 — 支持关键词 & 蒸馏句子</p>
            <div class="company-selector">
                <select id="globalCompanySelect" onchange="onCompanyChange()"><option value="0">全部公司</option></select>
<!-- nav moved to top bar -->
            </div>
            <p class="update-time" id="updateTime">加载中...</p>
        </div>
    </div>

    <main class="container">

        <!-- 爬取控制栏 -->
        <div class="card" style="margin-bottom:24px;padding:20px 24px">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px">
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#4f46e5,#818cf8);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <h4 style="font-size:15px;font-weight:600;margin-bottom:4px">AI平台收录检测</h4>
                        <p style="font-size:12px;color:#6b7280;line-height:1.5">对 8 大 AI 平台自动查询，保存 AI 回复原文。<br>通过 API Key 直调，运行 <code style="background:#f3f4f6;padding:1px 6px;border-radius:4px;font-size:11px">python3 crawler_v4.py --crawl</code> 开始爬取。</p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                    <span id="crawlStatus" style="font-size:12px;color:#9ca3af"></span>
                    <div id="crawlProgress" style="display:none;width:120px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden">
                        <div id="crawlProgressBar" style="height:100%;background:linear-gradient(135deg,#4f46e5,#818cf8);width:0%;transition:width 0.5s;border-radius:3px"></div>
                    </div>
                    <button class="btn btn-primary" id="btnCrawl" onclick="triggerCrawl()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        一键爬取
                    </button>
                </div>
            </div>
        </div>

        <!-- 指标卡片 -->
        <div class="stats-grid" id="statsGrid">
            <div class="card stat-card">
                <div class="stat-info"><p class="label">问题总量</p><p class="value" id="statQuestions">-</p><p class="desc">收录问题总数</p></div>
                <div class="stat-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
            </div>
            <div class="card stat-card">
                <div class="stat-info"><p class="label">收录总量</p><p class="value" id="statTotal">-</p><p class="desc">全平台收录量</p></div>
                <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 12l3 3 5-5"/></svg></div>
            </div>
            <div class="card stat-card">
                <div class="stat-info"><p class="label">训练平台</p><p class="value" id="statPlatforms">-</p><p class="desc">AI平台数量</p></div>
                <div class="stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg></div>
            </div>
            <div class="card stat-card">
                <div class="stat-info"><p class="label">蒸馏词/句</p><p class="value" id="statAi">-</p><p class="desc">AI蒸馏词句数量</p></div>
                <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
            </div>
        </div>

        <!-- 图表 -->
        <div class="charts-grid">
            <div class="card">
                <h3 style="font-size:16px;font-weight:600;margin-bottom:16px">各平台收录占比</h3>
                <div class="chart-wrap" style="max-width:280px;margin:0 auto">
                    <canvas id="pieChart"></canvas>
                </div>
                <div class="legend-grid" id="pieLegend"></div>
            </div>
            <div class="card">
                <h3 style="font-size:16px;font-weight:600;margin-bottom:16px">各平台收录数量</h3>
                <div class="chart-wrap">
                    <canvas id="barChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- 关键词报表 -->
        <div class="card">
            <div class="section-header">
                <h3>关键词详细数据</h3>
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="search-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input type="text" class="search-input" id="searchInput" placeholder="搜索关键词、问题或AI回复内容...">
                    </div>
                    <button class="btn btn-primary" onclick="openKeywordModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        管理关键词
                    </button>
                </div>
            </div>

            <!-- 平台筛选 -->
            <div class="platform-tabs" id="platformTabs"></div>

            <!-- 表格 -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:14%">主关键词</th>
                            <th style="width:20%" class="sortable-th" onclick="toggleSort()">查询句子/拓展词 <svg class="sort-icon" id="sortIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l4-4 4 4"/><path d="M16 15l-4 4-4-4"/></svg></th>
                            <th style="width:8%">平台</th>
                            <th style="width:8%">收录</th>
                            <th style="width:24%">AI回复预览</th>
                            <th style="width:8%">来源</th>
                            <th style="width:10%">查询时间</th>
                            <th style="width:8%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>

        <footer>
        <div style="max-width:1200px;margin:0 auto 16px;padding:0 32px;text-align:center">
            <span style="font-size:12px;color:#9ca3af;margin-right:12px">友情链接：</span>
            
            <a href="http://www.wangchenyu.com" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">王尘宇</a>
            <a href="http://www.qro.cn" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">QRO</a>
            <a href="http://www.mqs.net" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">萌骑士</a>
            <a href="http://www.4029.cn" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">黄页网</a>
        </div>AI大模型搜索结果千人千面，报表检测结果以系统记录为准，若有波动属于合理范围。点击行可查看AI完整回复。</footer>
    <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:8px">王尘宇GEO排名查询系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>
    </main>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <!-- 关键词管理弹窗 -->
    <div class="modal-overlay" id="keywordModal">
        <div class="modal">
            <h3 id="kwModalTitle">添加关键词</h3>
            <input type="hidden" id="editKeywordId" value="">
            <div class="form-group">
                <label>所属公司</label>
                <select id="newKeywordCompany"><option value="1">加载中...</option></select>
            </div>
            <div class="form-group">
                <label>关键词名称 *</label>
                <input type="text" id="newKeywordName" placeholder="例如: 西安科技大学高新学院">
            </div>
            <div class="form-group">
                <label>类型</label>
                <select id="newKeywordType">
                    <option value="manual">手动添加</option>
                    <option value="ai">AI蒸馏词</option>
                </select>
            </div>
            <div class="form-group">
                <label>查询句子 (蒸馏句子)</label>
                <textarea id="newQueryText" placeholder="自定义向AI提问的句子。留空则使用默认: 你知道【关键词】吗？请介绍一下。&#10;&#10;例如: 请介绍西安科技大学高新学院的办学特色、优势专业和就业情况"></textarea>
                <div class="hint">用于替代默认提问模板，可写蒸馏后的完整句子</div>
            </div>
            <div class="form-actions">
                <button class="btn btn-outline" onclick="closeKeywordModal()">取消</button>
                <button class="btn btn-primary" id="btnSaveKeyword" onclick="saveKeyword()">添加</button>
            </div>
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid #f3f4f6">
                <h4 style="font-size:14px;font-weight:600;color:#6b7280;margin-bottom:10px">已有关键词 <span style="font-weight:400;color:#9ca3af;font-size:12px">(点击可编辑)</span></h4>
                <div class="kw-list" id="kwList">加载中...</div>
            </div>
        </div>
    </div>

    <!-- 回复详情弹窗 -->
    <div class="modal-overlay detail-modal" id="detailModal">
        <div class="modal">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px">
                <h3 id="detailTitle" style="margin-bottom:0">AI回复详情</h3>
                <button class="btn btn-outline btn-sm" onclick="closeDetailModal()">关闭</button>
            </div>
            <div class="detail-section">
                <div class="label">查询句子</div>
                <div class="content" id="detailQuestion" style="font-style:italic;max-height:80px"></div>
            </div>
            <div class="detail-section" id="detailHitSection" style="display:none">
                <div class="label">🏷️ 命中公司名</div>
                <div class="content" id="detailHitNames" style="color:#065f46;background:#d1fae5;font-weight:500"></div>
            </div>
            <div class="detail-section">
                <div class="label">AI 完整回复</div>
                <div class="content" id="detailResponse">加载中...</div>
            </div>
            <div id="detailLink" style="margin-top:12px"></div>
            <div id="shareSection" style="margin-top:16px;display:none">
                <div class="label" style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">📤 分享链接</div>
                <div id="shareUrlBox"></div>
                <div style="margin-top:8px">
                    <button class="btn btn-success btn-sm" id="btnGenerateShare" onclick="generateShareLink()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                        生成分享链接
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentPage = 1, currentPlatform = 0, currentMobile = -1, currentSort = 0, currentCompany = 0;
    let currentDetailReportId = 0;
    let pieChart, barChart;

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg; t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2000);
    }

    // Company selector
    async function loadCompanySelector() {
        const res = await fetch('api.php?action=get_companies').then(r => r.json());
        if (res.code !== 1) return;
        const sel = document.getElementById('globalCompanySelect');
        const kwSel = document.getElementById('newKeywordCompany');
        let opts = '<option value="0">全部公司</option>';
        res.data.forEach(c => { opts += `<option value="${c.id}">${escapeHtml(c.name)}</option>`; });
        sel.innerHTML = opts;
        kwSel.innerHTML = res.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
    }

    function onCompanyChange() {
        currentCompany = parseInt(document.getElementById('globalCompanySelect').value);
        currentPage = 1;
        loadStats();
        loadReport();
    }

    // Load stats + charts
    async function loadStats() {
        let url = 'api.php?action=get_stats';
        if (currentCompany > 0) url += '&company_id=' + currentCompany;
        const res = await fetch(url).then(r => r.json());
        if (res.code !== 1) return;
        const d = res.data;
        document.getElementById('statQuestions').textContent = d.total_questions;
        document.getElementById('statTotal').textContent = d.total_questions;
        document.getElementById('statPlatforms').textContent = d.total_platforms;
        document.getElementById('statAi').textContent = d.total_ai_keywords;
        document.getElementById('updateTime').textContent = '最后更新：' + new Date().toLocaleString('zh-CN');

        const labels = d.platform_data.map(p => p.name);
        const data = d.platform_data.map(p => p.cnt);

        const pieCtx = document.getElementById('pieChart').getContext('2d');
        if (pieChart) pieChart.destroy();
        const gradients = d.platform_data.map(p => {
            const g = pieCtx.createLinearGradient(0,0,100,100);
            g.addColorStop(0, p.color_start); g.addColorStop(1, p.color_end);
            return g;
        });
        pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: gradients, borderWidth: 0, hoverOffset: 6 }] },
            options: { responsive: true, maintainAspectRatio: true, cutout: '65%', plugins: { legend: { display: false } } }
        });

        document.getElementById('pieLegend').innerHTML = d.platform_data.map(p => `
            <div class="legend-item">
                <span class="legend-dot" style="background:${p.color_start}"></span>${p.name} (${p.cnt})
            </div>`).join('');

        const barCtx = document.getElementById('barChart').getContext('2d');
        if (barChart) barChart.destroy();
        barChart = new Chart(barCtx, {
            type: 'bar',
            data: { labels, datasets: [{ label: '收录量', data, backgroundColor: gradients, borderRadius: 6 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } }
            }
        });
    }

    // Load platforms for tabs
    async function loadPlatformTabs() {
        const res = await fetch('api.php?action=get_platforms').then(r => r.json());
        if (res.code !== 1) return;
        const tabs = document.getElementById('platformTabs');
        let html = `<span class="platform-tab active" data-plat="0" data-mob="-1">全部</span>`;
        res.data.forEach(p => {
            html += `<span class="platform-tab" data-plat="${p.id}" data-mob="0">${p.name} <span class="count">PC</span></span>`;
            html += `<span class="platform-tab" data-plat="${p.id}" data-mob="1">${p.name} <span class="count">移动</span></span>`;
        });
        tabs.innerHTML = html;
        tabs.querySelectorAll('.platform-tab').forEach(el => {
            el.addEventListener('click', function() {
                tabs.querySelectorAll('.platform-tab').forEach(e => e.classList.remove('active'));
                this.classList.add('active');
                currentPlatform = parseInt(this.dataset.plat);
                currentMobile = parseInt(this.dataset.mob);
                currentPage = 1;
                loadReport();
            });
        });
    }

    // Load report table
    async function loadReport() {
        const keyword = document.getElementById('searchInput').value.trim();
        const formData = new FormData();
        formData.append('page', currentPage);
        formData.append('platform', currentPlatform);
        formData.append('mobile', currentMobile);
        formData.append('keyword', keyword);
        formData.append('question_sort', currentSort);
        if (currentCompany > 0) formData.append('company', currentCompany);

        const res = await fetch('api.php?action=get_report', { method: 'POST', body: formData }).then(r => r.json());
        if (res.code !== 1) { showToast(res.msg); return; }
        const d = res.data;

        const tbody = document.getElementById('tableBody');
        if (d.list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><p>暂无数据</p></div></td></tr>`;
        } else {
            tbody.innerHTML = d.list.map(r => {
                const matchedClass = r.matched ? 'yes' : 'no';
                const matchedText = r.matched ? (r.hit_names ? '已提及' : '已收录') : '未收录';
                const snippet = r.response_snippet || '(无回复数据)';
                const companyTag = r.company_name ? `<span style="font-size:11px;color:#9ca3af;margin-right:4px">${escapeHtml(r.company_name)}</span>` : '';
                return `
                <tr onclick="openDetail(${r.id})" title="点击查看AI完整回复">
                    <td>${companyTag}<span class="kw-tag ${r.keyword_type}">${r.keyword_type === 'manual' ? '手动' : '蒸馏'}</span>${r.keyword}</td>
                    <td style="font-size:13px;color:#4b5563">${escapeHtml(r.question)}</td>
                    <td>${r.platform_name}</td>
                    <td><span class="match-badge ${matchedClass}"><span class="dot"></span>${matchedText}</span></td>
                    <td><span class="snippet-text">${escapeHtml(snippet)}</span></td>
                    <td>${r.is_mobile}</td>
                    <td style="color:#9ca3af;font-size:13px">${r.shoulu_date}</td>
                    <td>
                        ${r.share_token ? `<button class="btn btn-success btn-sm" onclick="event.stopPropagation();copyShareLink('${r.share_token}')" title="复制分享链接">分享</button>` : `<button class="btn btn-outline btn-sm" onclick="event.stopPropagation();openDetail(${r.id})">详情</button>`}
                    </td>
                </tr>`;
            }).join('');
        }

        renderPagination(d.all_page, d.count);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderPagination(allPage, count) {
        const pg = document.getElementById('pagination');
        if (count === 0) { pg.innerHTML = ''; return; }
        let html = '';
        if (currentPage > 1) html += `<a onclick="goPage(${currentPage - 1})">上一页</a>`;
        else html += `<span class="disabled">上一页</span>`;

        for (let i = 1; i <= allPage; i++) {
            if (i === currentPage) html += `<span class="active">${i}</span>`;
            else html += `<a onclick="goPage(${i})">${i}</a>`;
        }

        if (currentPage < allPage) html += `<a onclick="goPage(${currentPage + 1})">下一页</a>`;
        else html += `<span class="disabled">下一页</span>`;
        pg.innerHTML = html;
    }

    function goPage(p) { currentPage = p; loadReport(); }

    function toggleSort() {
        currentSort = currentSort === 0 ? 1 : (currentSort === 1 ? 2 : 0);
        const icon = document.getElementById('sortIcon');
        icon.classList.toggle('active', currentSort !== 0);
        if (currentSort === 1) icon.style.transform = 'rotate(180deg)';
        else if (currentSort === 2) icon.style.transform = 'rotate(0deg)';
        else icon.style.transform = '';
        currentPage = 1;
        loadReport();
    }

    // Search
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { currentPage = 1; loadReport(); }
    });

    // ===== 回复详情弹窗 =====
    async function openDetail(reportId) {
        const modal = document.getElementById('detailModal');
        currentDetailReportId = reportId;
        document.getElementById('detailResponse').textContent = '加载中...';
        document.getElementById('detailLink').innerHTML = '';
        document.getElementById('shareSection').style.display = 'none';
        document.getElementById('shareUrlBox').innerHTML = '';
        modal.classList.add('show');

        const res = await fetch(`api.php?action=get_report_detail&id=${reportId}`).then(r => r.json());
        if (res.code === 1) {
            const d = res.data;
            document.getElementById('detailTitle').textContent = `${d.keyword} @ ${d.platform_name}`;
            document.getElementById('detailQuestion').textContent = d.question;
            document.getElementById('detailResponse').textContent = d.response_text || '(暂无AI回复数据)';
            if (d.hit_names) {
                document.getElementById('detailHitSection').style.display = 'block';
                document.getElementById('detailHitNames').textContent = d.hit_names;
            } else {
                document.getElementById('detailHitSection').style.display = 'none';
            }
            // Share section
            document.getElementById('shareSection').style.display = 'block';
            if (d.share_token) {
                const url = window.location.origin + '/share/?t=' + d.share_token;
                document.getElementById('shareUrlBox').innerHTML = `<div class="share-url">${url} <button class="btn btn-outline btn-sm copy-btn" onclick="navigator.clipboard.writeText('${url}');showToast('已复制链接')">复制</button></div>`;
                document.getElementById('btnGenerateShare').textContent = '重新生成分享链接';
            } else {
                document.getElementById('btnGenerateShare').textContent = '生成分享链接';
            }
        } else {
            document.getElementById('detailResponse').textContent = '加载失败';
        }
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('show');
    }

    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) closeDetailModal();
    });

    async function generateShareLink() {
        if (currentDetailReportId <= 0) return;
        const body = new URLSearchParams({report_id: currentDetailReportId});
        const res = await fetch('api.php?action=generate_share_token', { method: 'POST', body }).then(r => r.json());
        if (res.code === 1) {
            const url = res.data.url;
            document.getElementById('shareUrlBox').innerHTML = `<div class="share-url">${url} <button class="btn btn-outline btn-sm copy-btn" onclick="navigator.clipboard.writeText('${url}');showToast('已复制链接')">复制</button></div>`;
            showToast('分享链接已生成');
            loadReport(); // refresh table to show share button
        } else {
            showToast(res.msg);
        }
    }

    function copyShareLink(token) {
        const url = window.location.origin + '/share/?t=' + token;
        navigator.clipboard.writeText(url).then(() => showToast('已复制分享链接'));
    }

    // ===== 关键词管理弹窗 =====
    function openKeywordModal(editData) {
        if (editData) {
            document.getElementById('kwModalTitle').textContent = '编辑关键词';
            document.getElementById('editKeywordId').value = editData.id;
            document.getElementById('newKeywordName').value = editData.name;
            document.getElementById('newKeywordType').value = editData.type;
            document.getElementById('newQueryText').value = editData.query_text || '';
            document.getElementById('newKeywordCompany').value = editData.company_id || 1;
            document.getElementById('btnSaveKeyword').textContent = '保存修改';
        } else {
            document.getElementById('kwModalTitle').textContent = '添加关键词';
            document.getElementById('editKeywordId').value = '';
            document.getElementById('newKeywordName').value = '';
            document.getElementById('newKeywordType').value = 'manual';
            document.getElementById('newQueryText').value = '';
            document.getElementById('btnSaveKeyword').textContent = '添加';
        }
        document.getElementById('keywordModal').classList.add('show');
        loadKeywordList();
    }

    function closeKeywordModal() {
        document.getElementById('keywordModal').classList.remove('show');
    }

    document.getElementById('keywordModal').addEventListener('click', function(e) {
        if (e.target === this) closeKeywordModal();
    });

    async function loadKeywordList() {
        let url = 'api.php?action=get_keywords';
        if (currentCompany > 0) url += '&company_id=' + currentCompany;
        const res = await fetch(url).then(r => r.json());
        if (res.code !== 1) return;
        const list = document.getElementById('kwList');
        if (res.data.length === 0) {
            list.innerHTML = '<div class="empty-state" style="padding:20px">暂无关键词</div>';
            return;
        }
        list.innerHTML = res.data.map(k => `
            <div class="kw-item">
                <div class="kw-info" style="cursor:pointer" onclick="editKeyword(${k.id}, '${escapeHtml(k.name)}', '${k.type}', '${escapeHtml(k.query_text||'')}', ${k.company_id||1})">
                    <div class="kw-name"><span class="kw-tag ${k.type}">${k.type === 'manual' ? '手动' : '蒸馏'}</span>${k.name}</div>
                    ${k.query_text ? `<div class="kw-query">查询句: ${escapeHtml(k.query_text)}</div>` : '<div class="kw-query" style="color:#d1d5db">使用默认提问模板</div>'}
                </div>
                <div class="kw-actions">
                    <button class="btn btn-outline btn-sm" onclick="event.stopPropagation();editKeyword(${k.id}, '${escapeHtml(k.name)}', '${k.type}', '${escapeHtml(k.query_text||'')}', ${k.company_id||1})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="event.stopPropagation();deleteKeyword(${k.id})">删除</button>
                </div>
            </div>`).join('');
    }

    function editKeyword(id, name, type, queryText, companyId) {
        openKeywordModal({id, name, type, query_text: queryText, company_id: companyId});
    }

    async function saveKeyword() {
        const id = document.getElementById('editKeywordId').value;
        const name = document.getElementById('newKeywordName').value.trim();
        const type = document.getElementById('newKeywordType').value;
        const queryText = document.getElementById('newQueryText').value.trim();
        const companyId = document.getElementById('newKeywordCompany').value;

        if (!name) { showToast('请输入关键词'); return; }

        const formData = new FormData();
        const action = id ? 'update_keyword' : 'add_keyword';
        formData.append('name', name);
        formData.append('type', type);
        formData.append('query_text', queryText);
        formData.append('company_id', companyId);
        if (id) formData.append('id', id);

        const res = await fetch(`api.php?action=${action}`, { method: 'POST', body: formData }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) {
            openKeywordModal();
            loadStats();
        }
    }

    async function deleteKeyword(id) {
        if (!confirm('确定删除该关键词？关联的收录数据也会一并删除。')) return;
        const formData = new FormData();
        formData.append('id', id);
        const res = await fetch('api.php?action=delete_keyword', { method: 'POST', body: formData }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) { loadKeywordList(); loadStats(); loadReport(); }
    }

    // ========== 爬虫功能 ==========
    let crawlTimer = null;

    async function triggerCrawl() {
        const btn = document.getElementById('btnCrawl');
        btn.disabled = true;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> 启动中...';

        document.getElementById('crawlStatus').textContent = '正在启动爬虫...';
        document.getElementById('crawlProgress').style.display = 'block';

        const res = await fetch('api.php?action=run_crawl', { method: 'POST' }).then(r => r.json());
        if (res.code === 1) {
            showToast(res.msg);
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot running"></span> 爬取中...';
            pollCrawlStatus();
        } else {
            showToast(res.msg);
            document.getElementById('crawlStatus').textContent = res.msg;
            document.getElementById('crawlProgress').style.display = 'none';
            resetCrawlButton();
        }
    }

    async function pollCrawlStatus() {
        const res = await fetch('api.php?action=get_crawl_status').then(r => r.json());
        if (res.code !== 1) return;

        const data = res.data;
        const statusEl = document.getElementById('crawlStatus');
        const progressEl = document.getElementById('crawlProgress');
        const progressBar = document.getElementById('crawlProgressBar');

        if (data.running) {
            statusEl.innerHTML = '<span class="status-dot running"></span> 正在爬取中...';
            progressEl.style.display = 'block';
            progressBar.style.width = '60%';
            crawlTimer = setTimeout(pollCrawlStatus, 3000);
        } else if (data.result) {
            statusEl.innerHTML = '<span class="status-dot done"></span> 爬取完成！';
            progressEl.style.display = 'block';
            progressBar.style.width = '100%';
            loadStats();
            loadReport();
            resetCrawlButton();
            setTimeout(() => {
                document.getElementById('crawlProgress').style.display = 'none';
                progressBar.style.width = '0%';
                statusEl.textContent = '最后爬取: ' + new Date().toLocaleString('zh-CN');
            }, 5000);
        } else {
            statusEl.textContent = '就绪';
            progressEl.style.display = 'none';
            resetCrawlButton();
        }
    }

    function resetCrawlButton() {
        const btn = document.getElementById('btnCrawl');
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg> 一键爬取';
    }

    // 页面加载时检查状态
    (async function() {
        const res = await fetch('api.php?action=get_crawl_status').then(r => r.json());
        if (res.code === 1 && res.data.running) {
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot running"></span> 爬取中...';
            document.getElementById('crawlProgress').style.display = 'block';
            document.getElementById('crawlProgressBar').style.width = '30%';
            document.getElementById('btnCrawl').disabled = true;
            pollCrawlStatus();
        } else if (res.code === 1 && res.data.result) {
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot done"></span> 爬取完成';
            const result = res.data.result;
            const ts = result._timestamp || '';
            if (ts) document.getElementById('crawlStatus').textContent = '最后爬取: ' + ts;
        }
    })();

    // Init
    loadCompanySelector();
    loadStats();
    loadPlatformTabs();
    loadReport();
    </script>
</body>
</html>
