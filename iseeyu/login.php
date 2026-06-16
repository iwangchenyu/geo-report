<?php
require __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (do_login($username, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = '用户名或密码错误';
}

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>王尘宇GEO排名查询系统 - 后台登录</title>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .login-wrap{width:100%;max-width:400px;padding:20px}
        .login-card{background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
        .login-card .logo{text-align:center;margin-bottom:32px}
        .login-card .logo h1{font-size:24px;font-weight:700;color:#1a1a2e}
        .login-card .logo h1 span{color:#4f46e5}
        .login-card .logo p{font-size:13px;color:#9ca3af;margin-top:6px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px}
        .form-group input{width:100%;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none;transition:border-color 0.15s}
        .form-group input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.08)}
        .btn{width:100%;padding:10px;border:none;border-radius:8px;font-size:15px;font-weight:500;cursor:pointer;transition:all 0.2s;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff}
        .btn:hover{background:linear-gradient(135deg,#4338ca,#4f46e5);box-shadow:0 2px 8px rgba(79,70,229,0.3)}
        .error{background:#fef2f2;color:#dc2626;padding:8px 12px;border-radius:6px;font-size:13px;margin-bottom:16px;text-align:center}
        .hint{text-align:center;font-size:12px;color:#9ca3af;margin-top:16px}
    </style>
</head>
<body>
    <div style="text-align:center;padding:20px 0 0">
        <a href="/index.php" style="text-decoration:none;font-size:20px;font-weight:700;color:#1a1a2e">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#4f46e5,#818cf8);color:#fff;font-size:13px;margin-right:8px;vertical-align:middle">G</span>王尘宇GEO排名查询系统
        </a>
    </div>

    <div class="login-wrap">
        <div class="login-card">
            <div class="logo">
                <h1><span>GEO</span> 报表系统</h1>
                <p>AI大模型品牌曝光收录管理后台</p>
            </div>
            <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" placeholder="请输入用户名" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" placeholder="请输入密码">
                </div>
                <button class="btn" type="submit">登录后台</button>
            </form>

        </div>
    </div>
    <p style="text-align:center;margin-top:24px;font-size:12px;color:#9ca3af">王尘宇GEO排名查询系统 &copy; 2026 | <a href="http://www.wangchenyu.com" target="_blank" style="color:#9ca3af">王尘宇</a> | 西安蓝蜻蜓网络科技有限公司</p>
</body>
</html>
