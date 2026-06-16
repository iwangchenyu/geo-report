<?php
require __DIR__ . '/member/auth.php';
$pdo = require __DIR__ . '/db.php';

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || strlen($password) < 4) {
        $error = '用户名不能为空，密码至少4位';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            // 新注册默认待审核 status=0
            $stmt = $pdo->prepare("INSERT INTO members (username, password, company, status) VALUES (?, ?, '', 0)");
            $stmt->execute([$username, $hash]);
            $success = '注册成功！请等待管理员审核后登录。';
        } catch (Exception $e) {
            $error = '用户名已存在';
        }
    }
}
if (member_logged_in()) {
    header('Location: member/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册会员 - GEO报表系统</title>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:#fff;border-radius:14px;padding:40px;width:100%;max-width:400px;box-shadow:0 2px 16px rgba(0,0,0,0.06);margin:20px}
        .card h1{font-size:22px;font-weight:700;text-align:center;margin-bottom:8px}
        .card h1 span{color:#4f46e5}
        .card .sub{text-align:center;font-size:13px;color:#9ca3af;margin-bottom:28px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:5px}
        .form-group input{width:100%;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none}
        .form-group input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.06)}
        .btn{width:100%;padding:10px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff}
        .btn:hover{background:#4338ca}
        .msg{padding:8px 12px;border-radius:6px;font-size:13px;margin-bottom:16px;text-align:center}
        .msg-error{background:#fef2f2;color:#dc2626}
        .msg-success{background:#d1fae5;color:#065f46}
        .links{text-align:center;margin-top:20px;font-size:13px;color:#6b7280}
        .links a{color:#4f46e5;text-decoration:none;font-weight:500}
        .notice{background:#fef3c7;border-radius:8px;padding:12px;font-size:12px;color:#92400e;line-height:1.7;margin-top:16px;text-align:center}
    </style>
</head>
<body>
    <div style="text-align:center;padding:20px 0 0">
        <a href="/index.php" style="text-decoration:none;font-size:20px;font-weight:700;color:#1a1a2e">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#4f46e5,#818cf8);color:#fff;font-size:13px;margin-right:8px;vertical-align:middle">G</span>GEO 报表
        </a>
    </div>

    <div class="card">
        <h1><span>GEO</span> 报表系统</h1>
        <p class="sub">注册会员，查看品牌 AI 收录数据</p>
        <?php if ($error): ?><div class="msg msg-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="msg msg-success"><?= $success ?></div>
            <div class="notice">注册后需等待管理员审核通过并分配企业，届时即可登录查看数据。</div>
        <?php else: ?>
        <form method="POST">
            <div class="form-group"><label>用户名</label><input type="text" name="username" placeholder="设置登录用户名" required></div>
            <div class="form-group"><label>密码</label><input type="password" name="password" placeholder="至少4位" required minlength="4"></div>
            <button class="btn" type="submit">注册会员</button>
        </form>
        <?php endif; ?>
        <p class="links">已有账号？<a href="/member/login.php">立即登录</a> &nbsp;|&nbsp; <a href="/index.php">返回首页</a></p>
    </div>
    <p style="text-align:center;margin-top:16px;font-size:12px;color:#9ca3af">GEO 报表系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>
</body>
</html>
