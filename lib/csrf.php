<?php
/**
 * CSRF 保护
 * 用法：
 *   表单页: <?= csrf_field() ?>
 *   处理页: csrf_verify() or die("invalid csrf")
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('CSRF_TOKEN_KEY', '_csrf_token');

function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['_csrf'] ?? '';
    if (empty($_SESSION[CSRF_TOKEN_KEY]) || empty($token)) return false;
    return hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}
