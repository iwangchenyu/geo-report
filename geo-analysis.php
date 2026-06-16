<?php
$pdo = require __DIR__ . '/db.php';

// 各平台收录 + 命中统计
$platformStats = $pdo->query("
    SELECT p.id, p.name, p.pkey, p.color_start, p.color_end,
           COUNT(r.id) as total,
           COUNT(CASE WHEN r.matched = 1 THEN 1 END) as matched
    FROM platforms p
    LEFT JOIN reports r ON r.platform_id = p.id
    GROUP BY p.id ORDER BY p.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// 各公司各平台命中统计
$companies = $pdo->query("SELECT id, name FROM companies")->fetchAll(PDO::FETCH_ASSOC);
$platforms = $pdo->query("SELECT id, name, pkey, color_start, color_end FROM platforms ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);

$companyPlatformData = [];
foreach ($companies as $c) {
    $row = ['name' => $c['name']];
    foreach ($platforms as $p) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as t, COUNT(CASE WHEN r.matched=1 THEN 1 END) as m
            FROM reports r
            JOIN keywords k ON r.keyword_id = k.id
            WHERE r.platform_id = ? AND k.company_id = ?
        ");
        $stmt->execute([$p['id'], $c['id']]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);
        $row['p' . $p['id']] = (int)$d['m'];
        $row['t' . $p['id']] = (int)$d['t'];
    }
    $companyPlatformData[] = $row;
}

// 关键词命中排行
$keywordRanking = $pdo->query("
    SELECT k.name, k.type, c.name as company_name,
           COUNT(r.id) as total,
           COUNT(CASE WHEN r.matched=1 THEN 1 END) as matched
    FROM keywords k
    JOIN companies c ON k.company_id = c.id
    LEFT JOIN reports r ON r.keyword_id = k.id
    GROUP BY k.id
    ORDER BY matched DESC, total DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// 时间趋势
$timeTrend = $pdo->query("
    SELECT substr(inclusion_date, 1, 7) as month,
           COUNT(*) as total,
           COUNT(CASE WHEN matched=1 THEN 1 END) as matched
    FROM reports
    WHERE inclusion_date != ''
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

$totalQueries = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$totalMatched = $pdo->query("SELECT COUNT(*) FROM reports WHERE matched = 1")->fetchColumn();
$overallRate = $totalQueries > 0 ? round($totalMatched / $totalQueries * 100, 1) : 0;

$platformRates = [];
foreach ($platformStats as $ps) {
    $platformRates[$ps['name']] = $ps['total'] > 0 ? round($ps['matched'] / $ps['total'] * 100, 1) : 0;
}

// 获取所有关键词用于输入提示
$allKeywords = $pdo->query("SELECT name FROM keywords ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="author" content="王尘宇, 西安蓝蜻蜓网络科技有限公司">
    <meta name="generator" content="GEO Report System by 王尘宇">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEO分析 — AI品牌可见度深度分析</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f6f8;color:#1d2129;line-height:1.6;min-height:100vh}

        .nav-bar{position:sticky;top:0;z-index:50;background:rgba(255,255,255,0.92);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,0.05);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between;margin-bottom:0}
        .nav-bar .logo{display:flex;align-items:center;gap:10px;text-decoration:none;font-size:17px;font-weight:700;color:#1a1a2e}
        .nav-bar .logo .logo-icon{width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#4f46e5,#818cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0}
        .nav-bar .links{display:flex;gap:2px;align-items:center}
        .nav-bar .links a{text-decoration:none;color:#6b7280;padding:7px 14px;border-radius:7px;font-size:14px;font-weight:500;transition:all 0.15s}
        .nav-bar .links a:hover{color:#1a1a2e;background:#f3f4f6}
        .nav-bar .links a.active{color:#4f46e5;background:#eef2ff;font-weight:600}
        @media(max-width:640px){.nav-bar{padding:0 14px}.nav-bar .links a{padding:5px 8px;font-size:12px}}

        .container{max-width:1300px;margin:0 auto;padding:24px 24px}

        /* ---- 搜索区 ---- */
        .search-hero{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);border-radius:16px;padding:48px;text-align:center;margin-bottom:32px;position:relative;overflow:hidden}
        .search-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 30% 50%,rgba(99,102,241,0.15) 0%,transparent 60%),radial-gradient(ellipse at 70% 50%,rgba(16,185,129,0.1) 0%,transparent 60%)}
        .search-hero>*{position:relative;z-index:1}
        .search-hero h2{font-size:28px;font-weight:700;color:#fff;margin-bottom:8px}
        .search-hero h2 span{color:#34d399}
        .search-hero p{color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:28px}
        .search-row{display:flex;gap:12px;max-width:640px;margin:0 auto;flex-wrap:wrap;justify-content:center}
        .search-input{flex:1;min-width:280px;padding:14px 20px;border-radius:12px;border:2px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.08);color:#fff;font-size:16px;outline:none;transition:all 0.2s;backdrop-filter:blur(10px)}
        .search-input::placeholder{color:rgba(255,255,255,0.35)}
        .search-input:focus{border-color:rgba(99,102,241,0.5);background:rgba(255,255,255,0.12)}
        .btn-analyze{padding:14px 32px;border-radius:12px;border:none;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;font-size:15px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all 0.2s}
        .btn-analyze:hover{background:linear-gradient(135deg,#4338ca,#4f46e5);box-shadow:0 4px 20px rgba(79,70,229,0.35);transform:translateY(-1px)}
        .btn-analyze:disabled{opacity:0.5;cursor:not-allowed;transform:none}
        .kw-tags{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:16px}
        .kw-tag{cursor:pointer;padding:4px 14px;border-radius:20px;background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.7);font-size:12px;border:1px solid rgba(255,255,255,0.08);transition:all 0.15s}
        .kw-tag:hover{background:rgba(255,255,255,0.18);color:#fff}

        /* ---- Loading ---- */
        .loading-overlay{display:none;text-align:center;padding:48px}
        .loading-overlay.show{display:block}
        .spinner{width:40px;height:40px;border:3px solid #e5e7eb;border-top-color:#4f46e5;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 16px}
        @keyframes spin{to{transform:rotate(360deg)}}
        .loading-text{color:#6b7280;font-size:14px}

        /* ---- Results ---- */
        #resultsSection{display:none}
        #resultsSection.show{display:block}

        /* KPI */
        .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
        @media(max-width:768px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
        .kpi-card{background:#fff;border-radius:12px;padding:20px 24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);display:flex;align-items:center;gap:16px}
        .kpi-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .kpi-val{font-size:26px;font-weight:700;color:#111827;line-height:1.1}
        .kpi-lbl{font-size:12px;color:#9ca3af;margin-top:2px}

        /* Cards */
        .card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px}
        .card h3{font-size:15px;font-weight:600;margin-bottom:16px;color:#374151;display:flex;align-items:center;gap:8px}
        .card h3 .icon{width:20px;height:20px;display:flex;align-items:center}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media(max-width:900px){.grid2{grid-template-columns:1fr}}

        /* Difficulty */
        .difficulty-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 20px;border-radius:20px;font-size:18px;font-weight:700}
        .difficulty-meter{height:12px;border-radius:6px;background:#e5e7eb;overflow:hidden;margin-top:12px}
        .difficulty-fill{height:100%;border-radius:6px;transition:width 0.8s ease-out}
        .diff-reasons{margin-top:12px;font-size:13px;color:#6b7280;line-height:1.8}

        /* Brand heatmap */
        .heatmap-wrap{overflow-x:auto}
        .heatmap-table{width:100%;border-collapse:collapse;font-size:13px}
        .heatmap-table th,.heatmap-table td{padding:10px 12px;text-align:center;border:1px solid #f3f4f6}
        .heatmap-table thead th{background:#f8f9fb;font-weight:500;color:#6b7280;font-size:12px}
        .heatmap-table thead th:first-child{text-align:left;min-width:120px}
        .heatmap-table tbody td:first-child{text-align:left;font-weight:500}
        .heat-cell{padding:4px 8px;border-radius:6px;display:inline-block;min-width:36px}
        .heat-high{background:#d1fae5;color:#065f46;font-weight:600}
        .heat-mid{background:#fef3c7;color:#92400e}
        .heat-low{background:#fee2e2;color:#991b1b}
        .heat-none{color:#d1d5db}

        /* Platform cards */
        .platform-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
        .pcard{background:#fff;border-radius:10px;padding:16px;border:1px solid #f3f4f6;transition:box-shadow 0.15s}
        .pcard:hover{box-shadow:0 2px 8px rgba(0,0,0,0.06)}
        .pcard-header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
        .pcard-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
        .pcard-name{font-weight:600;font-size:14px}
        .pcard-status{font-size:11px;padding:2px 8px;border-radius:10px;margin-left:auto}
        .status-ok{background:#d1fae5;color:#065f46}
        .status-fail{background:#fee2e2;color:#991b1b}
        .pcard-brands{font-size:12px;color:#6b7280;margin-top:8px}
        .pcard-brand-tag{display:inline-block;padding:1px 8px;border-radius:8px;background:#eef2ff;color:#4338ca;margin:2px 3px;font-size:11px}
        .pcard-sources{font-size:11px;color:#9ca3af;margin-top:6px}

        /* Recommendation cards */
        .rec-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
        .rec-card{padding:16px;border-radius:10px;border:1px solid #f3f4f6}
        .rec-high{border-left:4px solid #10b981;background:#f0fdf4}
        .rec-medium{border-left:4px solid #f59e0b;background:#fffbeb}
        .rec-action{font-size:12px;color:#6b7280;margin-top:6px;font-style:italic}

        /* Blue ocean */
        .blue-ocean-list{list-style:none}
        .blue-ocean-list li{padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;display:flex;align-items:flex-start;gap:8px}
        .blue-ocean-list li:last-child{border-bottom:none}
        .blue-ocean-list .bo-icon{color:#10b981;flex-shrink:0}

        /* tables */
        table{width:100%;border-collapse:collapse}
        thead th{text-align:left;padding:10px 14px;background:#f8f9fb;color:#6b7280;font-size:12px;font-weight:500}
        tbody td{padding:11px 14px;border-bottom:1px solid #f3f4f6;font-size:14px}
        tbody tr:hover{background:#fafbfc}
        .progress-bar{height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:80px}
        .progress-fill{height:100%;border-radius:3px;transition:width 0.5s}
        .tag{display:inline-block;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:500}
        .tag-manual{background:#dbeafe;color:#1d4ed8}
        .tag-ai{background:#fef3c7;color:#b45309}

        /* Section divider */
        .section-divider{display:flex;align-items:center;gap:16px;margin:40px 0 24px}
        .section-divider::before,.section-divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
        .section-divider span{font-size:13px;color:#9ca3af;font-weight:500;white-space:nowrap}

        footer{text-align:center;padding:24px;color:#9ca3af;font-size:13px}

        .info-tip{background:#f0f4ff;border-radius:8px;padding:12px 16px;font-size:13px;color:#4338ca;line-height:1.7;margin-bottom:16px}
    </style>
</head>
<body>
    <nav class="nav-bar">
        <a href="/index.php" class="logo">
            <span class="logo-icon">G</span> GEO 报表
        </a>
        <div class="links">
            <a href="/index.php">首页</a>
            <a href="/dashboard.php">数据看板</a>
            <a href="/geo-analysis.php" class="active">GEO分析</a>
            <a href="/iseeyu/">后台管理</a>
        </div>
    </nav>

    <div class="container">
        <!-- ===== 实时分析搜索区 ===== -->
        <div class="search-hero">
            <h2><span>GEO</span> 实时深度分析</h2>
            <p>输入关键词，AI自动查询8大平台，分析品牌竞争格局、内容来源、收录难度与发帖策略</p>
            <div class="search-row">
                <input type="text" id="analysisKeyword" class="search-input" placeholder="输入关键词，如：西安科技大学高新学院" list="kwSuggestions">
                <datalist id="kwSuggestions">
                    <?php foreach ($allKeywords as $kw): ?>
                    <option value="<?= htmlspecialchars($kw) ?>">
                    <?php endforeach; ?>
                </datalist>
                <button class="btn-analyze" id="btnAnalyze" onclick="startAnalysis()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    开始分析
                </button>
            </div>
            <div class="kw-tags">
                <span style="color:rgba(255,255,255,0.4);font-size:12px">快捷分析：</span>
                <?php foreach (array_slice($allKeywords, 0, 6) as $kw): ?>
                <span class="kw-tag" onclick="quickAnalyze('<?= htmlspecialchars($kw) ?>')"><?= htmlspecialchars($kw) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Loading -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
            <p class="loading-text" id="loadingText">正在查询 AI 平台...</p>
        </div>

        <!-- ===== 分析结果区 ===== -->
        <div id="resultsSection">
            <!-- KPI -->
            <div class="kpi-grid" id="kpiGrid"></div>

            <!-- Difficulty + Summary -->
            <div class="grid2">
                <div class="card" id="difficultyCard"></div>
                <div class="card" id="summaryCard"></div>
            </div>

            <!-- Brand Heatmap -->
            <div class="card">
                <h3><span class="icon">🔥</span> 品牌提及热力图（品牌 × AI平台）</h3>
                <div class="heatmap-wrap" id="heatmapWrap"></div>
            </div>

            <!-- Platform detail cards -->
            <div class="card">
                <h3><span class="icon">📡</span> 各平台详细分析</h3>
                <div class="platform-cards" id="platformCards"></div>
            </div>

            <!-- Source Analysis -->
            <div class="card" id="sourceAnalysisCard"></div>

            <!-- Recommendations -->
            <div class="grid2">
                <div class="card"><h3><span class="icon">🎯</span> 发帖平台推荐</h3><div id="recList"></div></div>
                <div class="card"><h3><span class="icon">📝</span> 内容策略建议</h3><div id="contentRecList"></div></div>
            </div>

            <!-- Blue Ocean -->
            <div class="card" id="blueOceanCard"></div>

            <div class="info-tip" id="analysisMeta"></div>
        </div>

        <!-- ===== 分隔线 ===== -->
        <div class="section-divider"><span>📊 历史数据总览</span></div>

        <!-- KPI (历史) -->
        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div><div><div class="kpi-val"><?= $totalQueries ?></div><div class="kpi-lbl">总查询次数</div></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#10b981,#34d399)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 12l3 3 5-5"/></svg></div><div><div class="kpi-val"><?= $totalMatched ?></div><div class="kpi-lbl">品牌命中</div></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="kpi-val"><?= $overallRate ?>%</div><div class="kpi-lbl">总体命中率</div></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg></div><div><div class="kpi-val"><?= count($platforms) ?></div><div class="kpi-lbl">监测AI平台</div></div></div>
        </div>

        <!-- Charts -->
        <div class="grid2">
            <div class="card"><h3>各平台收录量对比</h3><div style="position:relative"><canvas id="platformBarChart" height="260"></canvas></div></div>
            <div class="card"><h3>各平台品牌命中率</h3><div style="position:relative"><canvas id="platformRateChart" height="260"></canvas></div></div>
        </div>

        <?php if (count($companies) > 1): ?>
        <div class="card" style="margin-bottom:24px"><h3>公司 × 平台 品牌命中矩阵</h3><div style="position:relative"><canvas id="companyPlatformChart" height="<?= max(200, count($companies) * 40) ?>"></canvas></div></div>
        <?php endif; ?>

        <div class="grid2">
            <div class="card"><h3>收录趋势 (按月)</h3><div style="position:relative"><canvas id="trendChart" height="260"></canvas></div></div>
            <div class="card"><h3>平台收录量分布</h3><div style="position:relative"><canvas id="pieChart" height="260"></canvas></div></div>
        </div>

        <div class="card">
            <h3>关键词效果排行 TOP20</h3>
            <div style="overflow-x:auto"><table><thead><tr><th>排名</th><th>关键词</th><th>类型</th><th>所属公司</th><th>查询次数</th><th>命中次数</th><th>命中率</th><th>效果</th></tr></thead>
                <tbody><?php foreach ($keywordRanking as $i => $kw): $rate = $kw['total'] > 0 ? round($kw['matched'] / $kw['total'] * 100) : 0; $color = $rate >= 50 ? '#10b981' : ($rate >= 20 ? '#f59e0b' : '#ef4444'); ?>
                    <tr><td style="font-weight:600;color:#9ca3af">#<?= $i + 1 ?></td><td style="font-weight:500"><?= htmlspecialchars($kw['name']) ?></td><td><span class="tag <?= $kw['type'] === 'manual' ? 'tag-manual' : 'tag-ai' ?>"><?= $kw['type'] === 'manual' ? '手动' : '蒸馏' ?></span></td><td style="font-size:13px;color:#6b7280"><?= htmlspecialchars($kw['company_name']) ?></td><td><?= $kw['total'] ?></td><td><?= $kw['matched'] ?></td><td style="font-weight:600"><?= $rate ?>%</td><td><div class="progress-bar"><div class="progress-fill" style="width:<?= $rate ?>%;background:<?= $color ?>"></div></div></td></tr>
                <?php endforeach; ?></tbody></table></div>
        </div>
    </div>

    <footer>
        <div style="max-width:1200px;margin:0 auto 16px;padding:0 32px;text-align:center">
            <span style="font-size:12px;color:#9ca3af;margin-right:12px">友情链接：</span>
            <a href="http://www.wangchenyu.com" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">王尘宇</a>
            <a href="http://www.qro.cn" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">QRO</a>
            <a href="http://www.mqs.net" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">萌骑士</a>
            <a href="http://www.4029.cn" target="_blank" style="font-size:12px;color:#6b7280;text-decoration:none;margin:0 8px">黄页网</a>
        </div>GEO 报表系统 · AI大模型品牌曝光收录深度分析</footer>
    <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:8px">GEO 报表系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>

    <script>
    // ===== 实时分析 JS =====
    const PLATFORM_COLORS = {
        'DeepSeek':'#4F46E5','豆包':'#EC4899','腾讯元宝':'#10B981','通义千问':'#8B5CF6',
        '文心一言':'#06B6D4','纳米AI':'#F59E0B','Kimi':'#7C3AED','智谱AI':'#84CC16',
        '腾讯元宝/混元':'#10B981'
    };

    function quickAnalyze(kw) {
        document.getElementById('analysisKeyword').value = kw;
        startAnalysis();
    }

    async function startAnalysis() {
        const kw = document.getElementById('analysisKeyword').value.trim();
        if (!kw) return alert('请输入关键词');

        const btn = document.getElementById('btnAnalyze');
        const loading = document.getElementById('loadingOverlay');
        const results = document.getElementById('resultsSection');
        const loadingText = document.getElementById('loadingText');

        btn.disabled = true;
        results.classList.remove('show');
        loading.classList.add('show');
        loadingText.textContent = '正在查询 8 大 AI 平台...';

        try {
            const resp = await fetch('../api.php?action=geo_analyze', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'keyword=' + encodeURIComponent(kw)
            });
            const json = await resp.json();
            if (json.code !== 1) { alert(json.msg || '分析失败'); return; }
            renderResults(json.data);
            results.classList.add('show');
            results.scrollIntoView({behavior:'smooth'});
        } catch(e) {
            alert('分析请求失败: ' + e.message);
        } finally {
            loading.classList.remove('show');
            btn.disabled = false;
        }
    }

    function renderResults(data) {
        const pr = data.platform_results || [];
        const diff = data.difficulty || {};
        const recs = data.recommendations || [];
        const crecs = data.content_recommendations || [];
        const bo = data.blue_ocean || [];
        const successCount = data.success_count || 0;

        // KPI
        const primaryBrands = new Set();
        const competitorBrands = new Set();
        pr.forEach(r => (r.brands||[]).forEach(b => {
            if (b.is_primary) primaryBrands.add(b.name);
            else competitorBrands.add(b.name);
        }));

        document.getElementById('kpiGrid').innerHTML = `
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div><div><div class="kpi-val">${successCount}/${data.total_platforms}</div><div class="kpi-lbl">平台响应成功</div></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#10b981,#34d399)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="kpi-val">${primaryBrands.size}</div><div class="kpi-lbl">品牌出现</div></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="kpi-val">${competitorBrands.size}</div><div class="kpi-lbl">竞品品牌出现</div></div></div>
            <div class="kpi-card"><div class="kpi-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="kpi-val">${data.elapsed_seconds}s</div><div class="kpi-lbl">分析耗时</div></div></div>
        `;

        // Difficulty
        document.getElementById('difficultyCard').innerHTML = `
            <h3><span class="icon">📊</span> 收录难度评估</h3>
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px">
                <span class="difficulty-badge" style="background:${diff.color||'#9ca3af'}15;color:${diff.color||'#9ca3af'}">${diff.label||'未知'}</span>
                <span style="font-size:24px;font-weight:700;color:${diff.color||'#9ca3af'}">${diff.score||0}/100</span>
            </div>
            <div class="difficulty-meter"><div class="difficulty-fill" style="width:${diff.score||0}%;background:${diff.color||'#9ca3af'}"></div></div>
            <div class="diff-reasons">${(diff.reasons||[]).map(r => '• ' + r).join('<br>')}</div>
        `;

        // Summary
        const totalSources = new Set();
        const allContentTypes = new Set();
        pr.forEach(r => { Object.keys(r.sources||{}).forEach(s => totalSources.add(s)); (r.content_types||[]).forEach(c => allContentTypes.add(c)); });
        document.getElementById('summaryCard').innerHTML = `
            <h3><span class="icon">📋</span> 分析摘要</h3>
            <div style="font-size:14px;line-height:2">
                <div>🔍 分析关键词：<strong>${data.keyword}</strong></div>
                <div>📡 查询平台：${data.total_platforms} 个，成功 ${successCount} 个</div>
                <div>🏷️ 发现品牌：${primaryBrands.size + competitorBrands.size} 个（自有 ${primaryBrands.size}，竞品 ${competitorBrands.size}）</div>
                <div>📚 引用来源：${totalSources.size} 类</div>
                <div>📝 内容类型：${allContentTypes.size} 种</div>
                <div>⏱️ 分析耗时：${data.elapsed_seconds} 秒</div>
            </div>
        `;

        // Brand heatmap
        const allBrands = [];
        const seen = new Set();
        pr.forEach(r => (r.brands||[]).forEach(b => { if (!seen.has(b.name)) { seen.add(b.name); allBrands.push(b.name); } }));
        const platformNames = pr.filter(r => r.success).map(r => r.platform);

        let heatmapHTML = '<table class="heatmap-table"><thead><tr><th>品牌</th>';
        platformNames.forEach(p => heatmapHTML += `<th>${p}</th>`);
        heatmapHTML += '</tr></thead><tbody>';

        allBrands.forEach(brand => {
            heatmapHTML += `<tr><td>${brand}</td>`;
            pr.filter(r => r.success).forEach(r => {
                const b = (r.brands||[]).find(b => b.name === brand);
                if (b) {
                    const cls = b.count >= 3 ? 'heat-high' : (b.count >= 2 ? 'heat-mid' : 'heat-low');
                    heatmapHTML += `<td><span class="heat-cell ${cls}">${b.count}</span></td>`;
                } else {
                    heatmapHTML += `<td><span class="heat-none">-</span></td>`;
                }
            });
            heatmapHTML += '</tr>';
        });
        heatmapHTML += '</tbody></table>';
        document.getElementById('heatmapWrap').innerHTML = heatmapHTML;

        // Platform cards
        let pcardsHTML = '';
        pr.forEach(r => {
            const color = PLATFORM_COLORS[r.platform] || '#9ca3af';
            const brandTags = (r.brands||[]).slice(0, 6).map(b =>
                `<span class="pcard-brand-tag">${b.name}×${b.count}</span>`
            ).join('');
            const sourceTags = Object.keys(r.sources||{}).map(s =>
                `<span style="color:#6b7280;background:#f3f4f6;padding:1px 6px;border-radius:4px;margin-right:4px;font-size:10px">${s}</span>`
            ).join('');
            pcardsHTML += `
            <div class="pcard">
                <div class="pcard-header">
                    <span class="pcard-dot" style="background:${color}"></span>
                    <span class="pcard-name">${r.platform}</span>
                    <span class="pcard-status ${r.success?'status-ok':'status-fail'}">${r.success?'✅ 成功':'❌ ' + (r.error||'失败')}</span>
                </div>
                ${r.success ? `
                <div style="font-size:12px;color:#9ca3af">回复字数: ${r.response_length} | 内容类型: ${(r.content_types||[]).join('、')||'未知'}</div>
                <div class="pcard-brands">${brandTags || '<span style="color:#d1d5db">未检测到品牌</span>'}</div>
                <div class="pcard-sources">引用来源: ${sourceTags || '无'}</div>
                ${r.urls && r.urls.length > 0 ? `<div class="pcard-sources" style="margin-top:4px">🔗 ${r.urls.map(u => '<a href="'+u+'" target="_blank" style="color:#4f46e5;font-size:10px">'+u.substring(0,40)+'...</a>').join(' ')}</div>` : ''}
                ` : `<div style="font-size:12px;color:#ef4444">${r.error||'查询失败'}</div>`}
            </div>`;
        });
        document.getElementById('platformCards').innerHTML = pcardsHTML;

        // Source analysis
        const allSources = {};
        pr.forEach(r => { Object.entries(r.sources||{}).forEach(([cat, items]) => { if (!allSources[cat]) allSources[cat] = new Set(); items.forEach(i => allSources[cat].add(i)); }); });
        let sourceHTML = '<h3><span class="icon">📚</span> AI 引用来源分析</h3><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">';
        Object.entries(allSources).forEach(([cat, items]) => {
            sourceHTML += `<div style="padding:12px;border-radius:8px;background:#f9fafb"><div style="font-weight:600;font-size:14px;margin-bottom:6px">${cat}</div><div style="font-size:12px;color:#6b7280">${[...items].join('、')}</div></div>`;
        });
        sourceHTML += '</div>';
        document.getElementById('sourceAnalysisCard').innerHTML = sourceHTML;

        // Recommendations
        document.getElementById('recList').innerHTML = recs.length > 0 ? recs.map(r => `
            <div class="rec-card rec-${r.priority}"><div style="font-weight:600;font-size:14px;margin-bottom:4px">${r.platform} <span style="font-size:11px;color:${r.priority==='high'?'#10b981':'#f59e0b'}">${r.priority==='high'?'🔴 优先':'🟡 建议'}</span></div><div style="font-size:13px;color:#4b5563">${r.reason}</div><div class="rec-action">💡 ${r.action}</div></div>
        `).join('') : '<div style="color:#9ca3af;font-size:13px">暂无推荐，请先配置 API Key 并完成首次爬取</div>';

        document.getElementById('contentRecList').innerHTML = crecs.length > 0 ? '<ul style="list-style:none">' + crecs.map(c => `<li style="padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:14px">📝 ${c}</li>`).join('') + '</ul>' : '<div style="color:#9ca3af;font-size:13px">内容覆盖较全面</div>';

        // Blue ocean
        document.getElementById('blueOceanCard').innerHTML = `
            <h3><span class="icon">🌊</span> 蓝海机会发现</h3>
            ${bo.length > 0 ? '<ul class="blue-ocean-list">' + bo.map(b => `<li><span class="bo-icon">💎</span> ${b}</li>`).join('') + '</ul>' : '<div style="color:#9ca3af;font-size:13px">当前关键词竞争格局已成形，可尝试长尾关键词</div>'}
        `;

        // Meta
        document.getElementById('analysisMeta').innerHTML = `分析时间: ${data.timestamp} | 关键词: <strong>${data.keyword}</strong> | 耗时: ${data.elapsed_seconds}秒`;
    }

    // ===== 历史图表 JS =====
    const platformLabels = <?= json_encode(array_column($platformStats, 'name')) ?>;
    const platformColors = <?= json_encode(array_column($platformStats, 'color_start')) ?>;

    new Chart(document.getElementById('platformBarChart'), {
        type: 'bar',
        data: { labels: platformLabels, datasets: [
            { label: '总查询', data: <?= json_encode(array_column($platformStats, 'total')) ?>, backgroundColor: '#e5e7eb', borderRadius: 4, order: 2 },
            { label: '品牌命中', data: <?= json_encode(array_column($platformStats, 'matched')) ?>, backgroundColor: platformColors, borderRadius: 4, order: 1 }
        ]},
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 16, font: { size: 11 } } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f3f4f6' } } } }
    });

    new Chart(document.getElementById('platformRateChart'), {
        type: 'bar',
        data: { labels: platformLabels, datasets: [{ label: '命中率 %', data: <?= json_encode(array_map(function($ps) { return $ps['total'] > 0 ? round($ps['matched'] / $ps['total'] * 100, 1) : 0; }, $platformStats)) ?>, backgroundColor: platformColors, borderRadius: 6 }]},
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, max: 100, grid: { color: '#f3f4f6' }, ticks: { callback: v => v + '%' } } } }
    });

    <?php if (count($companies) > 1): ?>
    new Chart(document.getElementById('companyPlatformChart'), {
        type: 'bar',
        data: { labels: <?= json_encode(array_column($companies, 'name')) ?>, datasets: <?= json_encode(array_map(function($p) use ($companyPlatformData) { return ['label' => $p['name'], 'data' => array_map(function($cp) use ($p) { return $cp['p' . $p['id']] ?? 0; }, $companyPlatformData), 'backgroundColor' => $p['color_start'], 'borderRadius' => 3]; }, $platforms)) ?> },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 12, font: { size: 10 } } } }, scales: { x: { stacked: true, grid: { color: '#f3f4f6' }, title: { display: true, text: '品牌命中次数' } }, y: { stacked: true, grid: { display: false } } } }
    });
    <?php endif; ?>

    <?php if (!empty($timeTrend)): ?>
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: { labels: <?= json_encode(array_column($timeTrend, 'month')) ?>, datasets: [
            { label: '总查询', data: <?= json_encode(array_column($timeTrend, 'total')) ?>, borderColor: '#9ca3af', backgroundColor: 'transparent', tension: 0.3, borderDash: [5,5], pointRadius: 3 },
            { label: '品牌命中', data: <?= json_encode(array_column($timeTrend, 'matched')) ?>, borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.08)', fill: true, tension: 0.3, pointRadius: 4 }
        ]},
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 16, font: { size: 11 } } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f3f4f6' } } } }
    });
    <?php endif; ?>

    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: { labels: platformLabels, datasets: [{ data: <?= json_encode(array_column($platformStats, 'total')) ?>, backgroundColor: platformColors, borderWidth: 0, hoverOffset: 6 }]},
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 12, font: { size: 11 } } } } }
    });
    </script>
</body>
</html>
