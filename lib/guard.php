<?php
/**
 * API 鉴权守卫
 * 对写入操作检查 API_SECRET（环境变量或 data/.api_secret 文件）
 */

function api_guard(): void {
    // 只对 POST/PUT/DELETE 请求做检查
    if ($_SERVER['REQUEST_METHOD'] === 'GET') return;

    $secret = getenv('API_SECRET');
    if (!$secret) {
        $secretFile = __DIR__ . '/../data/.api_secret';
        if (file_exists($secretFile)) {
            $secret = trim(file_get_contents($secretFile));
        }
    }

    // 未配置 secret 时放行（向后兼容），但不建议生产环境这样
    if (empty($secret)) return;

    $token = $_SERVER['HTTP_X_API_TOKEN'] ?? $_POST['_api_token'] ?? $_GET['_api_token'] ?? '';

    if (!hash_equals($secret, $token)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['code' => 0, 'msg' => '未授权：缺少有效的 API Token'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
