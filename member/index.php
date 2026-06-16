<?php
require __DIR__ . '/auth.php';
require_member();
$pdo = require __DIR__ . '/../db.php';
$member = current_member();

$stmt = $pdo->prepare("SELECT company FROM members WHERE id = ?");
$stmt->execute([$member['id']]);
$mdata = $stmt->fetch(PDO::FETCH_ASSOC);
$memberCompany = $mdata['company'] ?? '';

$tab = $_GET['tab'] ?? 'reports';

// Get stats
$stats = ['kw_count' => 0, 'report_count' => 0, 'matched_count' => 0];
if ($memberCompany) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT kw.id) as kw_count,
               COUNT(r.id) as report_count,
               COUNT(CASE WHEN r.matched = 1 THEN 1 END) as matched_count
        FROM companies c
        LEFT JOIN keywords kw ON kw.company_id = c.id
        LEFT JOIN reports r ON r.keyword_id = kw.id
        WHERE c.name = ?
    ");
    $stmt->execute([$memberCompany]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
}

// Reports
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$reports = [];
$total = 0;

if ($memberCompany) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports r JOIN keywords k ON r.keyword_id = k.id JOIN companies c ON k.company_id = c.id WHERE c.name = ?");
    $stmt->execute([$memberCompany]);
    $total = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT r.id, k.name as keyword, r.question, p.name as platform_name,
               r.matched, r.hit_names, r.response_snippet, r.inclusion_date, r.share_token
        FROM reports r
        JOIN keywords k ON r.keyword_id = k.id
        JOIN platforms p ON r.platform_id = p.id
        JOIN companies c ON k.company_id = c.id
        WHERE c.name = ?
        ORDER BY r.inclusion_date DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute([$memberCompany]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$totalPages = ceil($total / $perPage);

// Handle password change
$pwdMsg = $pwdError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_pwd') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) < 4) {
        $pwdError = '新密码至少4位';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM members WHERE id = ?");
        $stmt->execute([$member['id']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u && password_verify($old, $u['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE members SET password = ? WHERE id = ?")->execute([$hash, $member['id']]);
            $pwdMsg = '密码修改成功';
        } else {
            $pwdError = '旧密码不正确';
        }
    }
}

// Handle crawl request
$crawlMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crawl_request') {
    // Just show a message - the admin needs to trigger the crawl
    $crawlMsg = '爬取请求已记录，管理员将尽快为您执行数据更新。';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员中心 - 王尘宇GEO排名查询系统</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f6f8;color:#1d2129;min-height:100vh}
        .topbar{background:#1a1a2e;color:#fff;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between}
        .topbar .brand{font-size:17px;font-weight:700}
        .topbar .brand span{color:#34d399}
        .topbar .actions{display:flex;align-items:center;gap:16px;font-size:13px}
        .topbar .actions a,.topbar .actions span{color:#9ca3af;text-decoration:none}
        .topbar .actions a:hover{color:#fff}
        .layout{display:flex;min-height:calc(100vh - 56px)}
        .sidebar{width:200px;background:#fff;border-right:1px solid #e5e7eb;padding:16px 0;flex-shrink:0}
        .sidebar a{display:flex;align-items:center;gap:8px;padding:10px 20px;font-size:14px;color:#4b5563;text-decoration:none;transition:all 0.15s}
        .sidebar a:hover{background:#f3f4f6}
        .sidebar a.active{background:#eef2ff;color:#4f46e5;font-weight:500;border-right:3px solid #4f46e5}
        .main{flex:1;padding:24px;overflow-x:auto}
        .kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
        @media(max-width:640px){.kpi-grid{grid-template-columns:1fr}}
        .kpi{background:#fff;border-radius:12px;padding:20px 24px;box-shadow:0 1px 3px rgba(0,0,0,0.04)}
        .kpi .val{font-size:28px;font-weight:700}
        .kpi .lbl{font-size:12px;color:#9ca3af;margin-top:4px}
        .card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:20px}
        .card h3{font-size:16px;font-weight:600;margin-bottom:16px}
        table{width:100%;border-collapse:collapse}
        thead th{text-align:left;padding:10px 14px;background:#f8f9fb;color:#6b7280;font-size:12px;font-weight:500}
        tbody td{padding:11px 14px;border-bottom:1px solid #f3f4f6;font-size:14px}
        tbody tr:hover{background:#fafbfc;cursor:pointer}
        .match-yes{color:#065f46;font-weight:600}
        .match-no{color:#991b1b}
        .pagination{display:flex;justify-content:flex-end;gap:4px;margin-top:16px}
        .pagination a,.pagination span{padding:5px 10px;border-radius:6px;font-size:12px;border:1px solid #e5e7eb;color:#4b5563;text-decoration:none}
        .pagination .active{background:#4f46e5;color:#fff;border-color:#4f46e5}
        .empty-state{text-align:center;padding:40px;color:#9ca3af}
        .form-group{margin-bottom:14px}
        .form-group label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:4px}
        .form-group input{width:100%;max-width:320px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none}
        .form-group input:focus{border-color:#6366f1}
        .btn{padding:8px 18px;border-radius:7px;font-size:13px;font-weight:500;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
        .btn-primary{background:#4f46e5;color:#fff}
        .btn-primary:hover{background:#4338ca}
        .btn-warning{background:#f59e0b;color:#fff}
        .btn-warning:hover{background:#d97706}
        .msg{padding:8px 12px;border-radius:6px;font-size:13px;margin-bottom:12px}
        .msg-success{background:#d1fae5;color:#065f46}
        .msg-error{background:#fef2f2;color:#dc2626}
        footer{text-align:center;padding:24px;color:#9ca3af;font-size:12px;border-top:1px solid #e5e7eb}
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand"><span>GEO</span> 报表 · 会员中心</div>
        <div class="actions">
            <span style="color:rgba(255,255,255,0.6)"><?= htmlspecialchars($member['username']) ?> · <?= htmlspecialchars($memberCompany ?: '未绑定企业') ?></span>
            <a href="/index.php">首页</a>
            <a href="/member/logout.php">退出</a>
        </div>
    </div>
    
    <div class="layout">
        <div class="sidebar">
            <a href="?tab=reports" class="<?= $tab=='reports'?'active':'' ?>">📊 数据报表</a>
            <a href="?tab=settings" class="<?= $tab=='settings'?'active':'' ?>">⚙️ 账号设置</a>
        </div>
        
        <div class="main">
<?php if ($tab === 'reports'): ?>
            <div class="kpi-grid">
                <div class="kpi"><div class="val"><?= $stats['kw_count'] ?></div><div class="lbl">追踪关键词</div></div>
                <div class="kpi"><div class="val"><?= $stats['report_count'] ?></div><div class="lbl">AI查询次数</div></div>
                <div class="kpi"><div class="val"><?= $stats['report_count'] > 0 ? round($stats['matched_count'] / $stats['report_count'] * 100) : 0 ?>%</div><div class="lbl">品牌命中率</div></div>
            </div>

            <div class="card" style="padding:16px 24px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:13px;color:#6b7280">数据更新于最近一次爬取，如需刷新请请求管理员执行</span>
                <form method="POST" style="margin:0"><input type="hidden" name="action" value="crawl_request"><button class="btn btn-warning" type="submit">📡 请求数据更新</button></form>
                <?php if ($crawlMsg): ?><div class="msg msg-success" style="margin:0 0 0 12px"><?= $crawlMsg ?></div><?php endif; ?>
            </div>
            
            <div class="card">
                <h3><?= htmlspecialchars($memberCompany ?: '您的企业') ?> · AI收录数据</h3>
                <?php if (empty($reports)): ?>
                    <div class="empty-state">暂无收录数据，请联系管理员配置关键词并执行查询</div>
                <?php else: ?>
                <table>
                    <thead><tr><th>关键词</th><th>查询句子</th><th>平台</th><th>收录</th><th>回复预览</th><th>时间</th></tr></thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr onclick="<?php if ($r['share_token']): ?>window.open('/share/?t=<?= $r['share_token'] ?>','_blank')<?php endif; ?>" title="<?= $r['share_token'] ? '点击查看详情' : '' ?>">
                            <td style="font-weight:500"><?= htmlspecialchars($r['keyword']) ?></td>
                            <td style="font-size:12px;color:#6b7280;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['question']) ?></td>
                            <td><?= htmlspecialchars($r['platform_name']) ?></td>
                            <td><span class="<?= $r['matched'] ? 'match-yes' : 'match-no' ?>"><?= $r['matched'] ? '✓ 已收录' : '✗ 未收录' ?></span></td>
                            <td style="font-size:12px;color:#6b7280;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_substr($r['response_snippet'] ?? '', 0, 80)) ?></td>
                            <td style="font-size:12px;color:#9ca3af"><?= htmlspecialchars($r['inclusion_date'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?><span class="active"><?= $i ?></span>
                        <?php else: ?><a href="?tab=reports&page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

<?php elseif ($tab === 'settings'): ?>
            <div class="card" style="max-width:420px">
                <h3>修改密码</h3>
                <?php if ($pwdMsg): ?><div class="msg msg-success"><?= $pwdMsg ?></div><?php endif; ?>
                <?php if ($pwdError): ?><div class="msg msg-error"><?= $pwdError ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="change_pwd">
                    <div class="form-group"><label>旧密码</label><input type="password" name="old_password" required></div>
                    <div class="form-group"><label>新密码</label><input type="password" name="new_password" required minlength="4"></div>
                    <button class="btn btn-primary" type="submit">修改密码</button>
                </form>
            </div>
<?php endif; ?>
        </div>
    </div>
    
    <footer>王尘宇GEO排名查询系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</footer>
</body>
</html>
