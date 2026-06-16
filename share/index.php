<?php
$pdo = require __DIR__ . '/../db.php';
$token = $_GET['t'] ?? '';

$error_msg = '';
if (empty($token)) {
    $error_msg = '无效的分享链接';
} else {
    // Generate report details by token
    $stmt = $pdo->prepare("
        SELECT r.id, r.question, r.response_text, r.response_snippet, r.matched, 
               r.hit_names, r.inclusion_date, r.is_mobile,
               k.name as keyword, k.type as keyword_type,
               p.name as platform_name, p.color_start, p.color_end,
               c.name as company_name, c.description as company_description
        FROM reports r
        JOIN keywords k ON r.keyword_id = k.id
        JOIN platforms p ON r.platform_id = p.id
        LEFT JOIN companies c ON k.company_id = c.id
        WHERE r.share_token = ?
    ");
    $stmt->execute([$token]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$report) {
        $error_msg = '分享链接不存在或已失效';
    }
}

if ($error_msg): ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEO报表 - 分享链接</title>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f6f8;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:#fff;border-radius:12px;padding:40px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,0.06);max-width:400px;width:90%}
        .card .icon{font-size:48px;margin-bottom:16px}
        .card h2{font-size:18px;color:#374151;margin-bottom:8px}
        .card p{font-size:14px;color:#9ca3af;line-height:1.6}
        .card a{color:#4f46e5;text-decoration:none;font-weight:500}
    </style>
</head>
<body>
<div class="card">
    <div class="icon">🔗</div>
    <h2><?= $error_msg ?></h2>
    <p>请确认分享链接是否正确，或联系管理员获取有效的分享链接。</p>
    <p style="margin-top:16px"><a href="/index.php">返回首页</a></p>
</div>
<p style="text-align:center;margin-top:24px;font-size:12px;color:#9ca3af">GEO 报表系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="author" content="王尘宇, 西安蓝蜻蜓网络科技有限公司">
    <meta name="generator" content="GEO Report System by 王尘宇">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEO报表 - AI收录详情</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f6f8;color:#1d2129;line-height:1.7;min-height:100vh}
        .banner{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff;padding:36px 24px;text-align:center}
        .banner h1{font-size:24px;font-weight:700}
        .banner h1 span{color:#34d399}
        .banner p{font-size:14px;opacity:0.7;margin-top:4px}
        .container{max-width:800px;margin:0 auto;padding:24px 20px}
        .card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.05);padding:24px;margin-bottom:16px}
        .card h3{font-size:15px;font-weight:600;margin-bottom:12px;color:#374151}
        .meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
        @media(max-width:500px){.meta-grid{grid-template-columns:1fr}}
        .meta-item .label{font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em}
        .meta-item .value{font-size:14px;color:#374151;margin-top:2px}
        .question-box{background:#f0f4ff;border-radius:8px;padding:14px;font-size:14px;color:#4338ca;font-style:italic;margin-bottom:16px}
        .response-box{background:#f9fafb;border-radius:8px;padding:16px;font-size:14px;line-height:1.8;white-space:pre-wrap;word-break:break-word;max-height:500px;overflow-y:auto;color:#374151}
        .match-badge{display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:600;padding:4px 12px;border-radius:12px}
        .match-yes{background:#d1fae5;color:#065f46}
        .match-no{background:#fee2e2;color:#991b1b}
        .match-dot{width:6px;height:6px;border-radius:50%}
        .match-yes .match-dot{background:#10b981}
        .match-no .match-dot{background:#ef4444}
        .hit-tags{margin-top:8px;display:flex;flex-wrap:wrap;gap:6px}
        .hit-tag{font-size:11px;background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:10px;font-weight:500}
        footer{text-align:center;padding:24px;color:#9ca3af;font-size:12px}
    </style>
</head>
<body>
    <div class="banner">
        <h1><span>GEO</span> 报表系统</h1>
        <p>AI大模型品牌曝光收录详情</p>
    </div>
    
    <div class="container">
        <div class="card">
            <h3>📋 基本信息</h3>
            <div class="meta-grid">
                <div class="meta-item">
                    <div class="label">公司/品牌</div>
                    <div class="value"><?= htmlspecialchars($report['company_name'] ?? '未知') ?></div>
                </div>
                <div class="meta-item">
                    <div class="label">AI平台</div>
                    <div class="value"><?= htmlspecialchars($report['platform_name']) ?></div>
                </div>
                <div class="meta-item">
                    <div class="label">关键词</div>
                    <div class="value"><?= htmlspecialchars($report['keyword']) ?></div>
                </div>
                <div class="meta-item">
                    <div class="label">查询时间</div>
                    <div class="value"><?= htmlspecialchars($report['inclusion_date'] ?? '') ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>🔍 查询句子</h3>
            <div class="question-box"><?= htmlspecialchars($report['question']) ?></div>
        </div>
        
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <h3 style="margin-bottom:0">💬 AI 回复</h3>
                <span class="match-badge <?= $report['matched'] ? 'match-yes' : 'match-no' ?>">
                    <span class="match-dot"></span>
                    <?= $report['matched'] ? '已收录' : '未收录' ?>
                </span>
            </div>
            <?php if ($report['hit_names']): ?>
            <div class="hit-tags">
                <?php foreach (explode(',', $report['hit_names']) as $hit): ?>
                <span class="hit-tag">命中: <?= htmlspecialchars($hit) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="response-box"><?= nl2br(htmlspecialchars($report['response_text'] ?? '暂无AI回复数据')) ?></div>
        </div>
        
        <?php if ($report['company_description']): ?>
        <div class="card">
            <h3>🏢 公司简介</h3>
            <p style="font-size:14px;color:#4b5563"><?= htmlspecialchars($report['company_description']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <footer>数据由 GEO 报表系统自动采集 · AI大模型搜索结果千人千面，以系统记录为准</footer>
    <p style="text-align:center;margin-top:16px;font-size:12px;color:#9ca3af">GEO 报表系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>
</body>
</html>
<?php endif; ?>
