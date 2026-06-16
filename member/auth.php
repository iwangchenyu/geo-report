<?php
session_start();

function member_logged_in() {
    return isset($_SESSION['member_id']);
}

function current_member() {
    if (!member_logged_in()) return null;
    return [
        'id' => $_SESSION['member_id'],
        'username' => $_SESSION['member_username'] ?? '',
    ];
}

function require_member() {
    if (!member_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function member_login($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT id, username, password, company, status FROM members WHERE username = ?");
    $stmt->execute([$username]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) return ['error' => '用户名不存在'];
    if ($m['status'] == 0) return ['error' => '账号待审核，请等待管理员批准'];
    if ($m['status'] == 2) return ['error' => '账号已被禁用'];
    if (!password_verify($password, $m['password'])) return ['error' => '密码错误'];
    session_regenerate_id(true);
    $_SESSION['member_id'] = $m['id'];
    $_SESSION['member_username'] = $m['username'];
    return $m;
}

function member_logout() {
    session_destroy();
    header('Location: /dashboard.php');
    exit;
}
