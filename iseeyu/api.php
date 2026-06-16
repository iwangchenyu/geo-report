<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/../lib/csrf.php';

$pdo = require __DIR__ . '/../db.php';
$action = $_GET['action'] ?? '';

function json($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function error($msg) { json(['code' => 0, 'msg' => $msg]); }

// CSRF 校验（仅 POST 请求）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    json(['code' => 0, 'msg' => '安全校验失败，请刷新页面重试']);
}

try {
    switch ($action) {
        // ---- 公司管理 ----
        case 'get_companies':
            $companies = $pdo->query("SELECT * FROM companies ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            json(['code' => 1, 'data' => $companies]);
        case 'add_company':
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $variants = trim($_POST['variants'] ?? '');
            if (empty($name)) error('公司名称不能为空');
            $stmt = $pdo->prepare("INSERT INTO companies (name, description, variants) VALUES (?, ?, ?)");
            $stmt->execute([$name, $desc, $variants]);
            sync_config($pdo);
            json(['code' => 1, 'msg' => '公司添加成功', 'data' => ['id' => $pdo->lastInsertId()]]);
        case 'update_company':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $variants = trim($_POST['variants'] ?? '');
            if ($id <= 0 || empty($name)) error('参数错误');
            $stmt = $pdo->prepare("UPDATE companies SET name=?, description=?, variants=? WHERE id=?");
            $stmt->execute([$name, $desc, $variants, $id]);
            sync_config($pdo);
            json(['code' => 1, 'msg' => '公司更新成功']);
        case 'delete_company':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) error('参数错误');
            $pdo->prepare("DELETE FROM keywords WHERE company_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM companies WHERE id = ?")->execute([$id]);
            sync_config($pdo);
            json(['code' => 1, 'msg' => '公司已删除']);

        // ---- 关键词管理 ----
        case 'get_keywords':
            $company_id = (int)($_GET['company_id'] ?? 0);
            if ($company_id > 0) {
                $stmt = $pdo->prepare("SELECT k.*, c.name as company_name FROM keywords k LEFT JOIN companies c ON k.company_id = c.id WHERE k.company_id = ? ORDER BY k.id DESC");
                $stmt->execute([$company_id]);
            } else {
                $stmt = $pdo->query("SELECT k.*, c.name as company_name FROM keywords k LEFT JOIN companies c ON k.company_id = c.id ORDER BY k.id DESC");
            }
            json(['code' => 1, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        case 'add_keyword':
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'manual';
            $queryText = trim($_POST['query_text'] ?? '');
            $companyId = (int)($_POST['company_id'] ?? 1);
            if (empty($name)) error('关键词不能为空');
            if (!in_array($type, ['manual', 'ai'])) error('类型无效');
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO keywords (name, type, query_text, company_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $type, $queryText, $companyId]);
            if ($stmt->rowCount() == 0) error('关键词已存在');
            json(['code' => 1, 'msg' => '添加成功']);
        case 'update_keyword':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'manual';
            $queryText = trim($_POST['query_text'] ?? '');
            $companyId = (int)($_POST['company_id'] ?? 1);
            if ($id <= 0 || empty($name)) error('参数错误');
            $stmt = $pdo->prepare("UPDATE keywords SET name=?, type=?, query_text=?, company_id=? WHERE id=?");
            $stmt->execute([$name, $type, $queryText, $companyId, $id]);
            json(['code' => 1, 'msg' => '更新成功']);
        case 'delete_keyword':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) error('参数错误');
            $pdo->prepare("DELETE FROM keywords WHERE id = ?")->execute([$id]);
            json(['code' => 1, 'msg' => '删除成功']);

        // ---- API 配置 ----
        case 'get_api_config':
            get_api_config();
            break;
        case 'save_api_config':
            save_api_config();
            break;

        // ---- 报表操作 ----
        case 'delete_report':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) error('参数错误');
            $pdo->prepare("DELETE FROM reports WHERE id = ?")->execute([$id]);
            json(['code' => 1, 'msg' => '报表已删除']);
        case 'delete_reports_batch':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (empty($ids)) error('请选择要删除的报表');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM reports WHERE id IN ($placeholders)")->execute($ids);
            json(['code' => 1, 'msg' => '已删除 ' . count($ids) . ' 条报表']);
        case 'clear_reports':
            $company = (int)($_POST['company'] ?? 0);
            if ($company > 0) {
                $stmt = $pdo->prepare("DELETE FROM reports WHERE keyword_id IN (SELECT id FROM keywords WHERE company_id = ?)");
                $stmt->execute([$company]);
            } else {
                $pdo->exec("DELETE FROM reports");
            }
            json(['code' => 1, 'msg' => '报表数据已清空']);
        case 'export_csv':
            export_csv($pdo);
            break;

        // ---- 系统设置 ----
        case 'change_password':
            change_my_password_legacy();
            break;
        case 'get_screenshots':
            get_screenshots();
            break;

        // ---- 用户管理 ----
        case 'get_users':
            require_admin();
            $users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            json(['code' => 1, 'data' => $users]);
        case 'add_user':
            require_admin();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'editor';
            if (empty($username) || strlen($password) < 4) error('用户名不能为空，密码至少4位');
            if (!in_array($role, ['admin', 'editor'])) error('角色无效');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            try { $stmt->execute([$username, $hash, $role]); } catch (Exception $e) { error('用户名已存在'); }
            json(['code' => 1, 'msg' => '用户添加成功']);
        case 'update_user':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            $role = $_POST['role'] ?? 'editor';
            if ($id <= 0) error('参数错误');
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
            json(['code' => 1, 'msg' => '用户更新成功']);
        case 'reset_user_password':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            $password = $_POST['password'] ?? '';
            if ($id <= 0 || strlen($password) < 4) error('密码至少4位');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $id]);
            json(['code' => 1, 'msg' => '密码已重置']);
        case 'delete_user':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 1) error('不能删除默认管理员');
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            json(['code' => 1, 'msg' => '用户已删除']);
        case 'change_my_password':
            $old = $_POST['old_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $uid = $_SESSION['user_id'] ?? 0;
            if (strlen($new) < 4) error('新密码至少4位');
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$u || !password_verify($old, $u['password'])) error('旧密码不正确');
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $uid]);
            json(['code' => 1, 'msg' => '密码已修改']);
        // ---- 会员管理 ----
        case 'get_members':
            require_admin();
            $members = $pdo->query("SELECT id, username, company, status, created_at FROM members ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            json(['code' => 1, 'data' => $members]);
        case 'update_member':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            $company = trim($_POST['company'] ?? '');
            $status = (int)($_POST['status'] ?? 1);
            if ($id <= 0) error('参数错误');
            $pdo->prepare("UPDATE members SET company = ?, status = ? WHERE id = ?")->execute([$company, $status, $id]);
            json(['code' => 1, 'msg' => '会员信息已更新']);
        case 'approve_member':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            $company = trim($_POST['company'] ?? '');
            if ($id <= 0) error('参数错误');
            $pdo->prepare("UPDATE members SET status = 1, company = ? WHERE id = ?")->execute([$company, $id]);
            json(['code' => 1, 'msg' => '会员已批准']);
        case 'reject_member':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) error('参数错误');
            $pdo->prepare("UPDATE members SET status = 2 WHERE id = ?")->execute([$id]);
            json(['code' => 1, 'msg' => '会员已拒绝']);
        case 'member_pending_count':
            require_admin();
            $cnt = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 0")->fetchColumn();
            json(['code' => 1, 'data' => ['count' => (int)$cnt]]);
        case 'delete_member':
            require_admin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) error('参数错误');
            $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
            json(['code' => 1, 'msg' => '会员已删除']);
        default:
            error('未知操作');
    }
} catch (Exception $e) {
    error($e->getMessage());
}

// ===== 导出 =====
function export_csv($pdo) {
    $company = (int)($_GET['company'] ?? 0);
    $where = '';
    $params = [];
    if ($company > 0) {
        $where = "WHERE k.company_id = ?";
        $params[] = $company;
    }
    
    $stmt = $pdo->prepare("
        SELECT c.name as company, k.name as keyword, k.type, r.question,
               p.name as platform, CASE WHEN r.matched THEN '是' ELSE '否' END as matched,
               r.hit_names, r.response_snippet, r.inclusion_date
        FROM reports r
        JOIN keywords k ON r.keyword_id = k.id
        JOIN platforms p ON r.platform_id = p.id
        LEFT JOIN companies c ON k.company_id = c.id
        $where
        ORDER BY r.inclusion_date DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="geo-report-' . date('Ymd-His') . '.csv"');
    
    // BOM for Excel UTF-8
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

function get_screenshots() {
    $dir = __DIR__ . '/../data/screenshots';
    $files = [];
    if (is_dir($dir)) {
        $all = array_diff(scandir($dir), ['.', '..']);
        foreach ($all as $f) {
            if (preg_match('/\.(png|jpg|jpeg)$/i', $f)) {
                $path = $dir . '/' . $f;
                $files[] = [
                    'name' => $f,
                    'size' => filesize($path),
                    'time' => date('Y-m-d H:i:s', filemtime($path)),
                    'url' => '/data/screenshots/' . $f,
                ];
            }
        }
        // Sort by time desc
        usort($files, function($a, $b) { return strcmp($b['time'], $a['time']); });
    }
    json(['code' => 1, 'data' => $files]);
}

// ===== API 配置 =====
function get_api_config() {
    $configPath = __DIR__ . '/../config.yaml';
    if (!file_exists($configPath)) error('配置文件不存在');
    $content = file_get_contents($configPath);
    $apis = parse_api_config($content);
    $crawl = parse_crawl_config($content);
    
    foreach ($apis as $pkey => &$cfg) {
        $key = $cfg['api_key'] ?? '';
        $isEnv = (bool)preg_match('/^\$\{?\w+\}?$/', $key);
        $cfg['has_key'] = !empty($key) && !$isEnv;
        $cfg['is_env'] = $isEnv;
        $cfg['api_key_display'] = $isEnv ? $key : (empty($key) ? '' : substr($key, 0, 8) . '****' . substr($key, -4));
        if (!empty($cfg['secret_key'] ?? '')) {
            $sk = $cfg['secret_key'];
            $skIsEnv = (bool)preg_match('/^\$\{?\w+\}?$/', $sk);
            $cfg['secret_key_display'] = $skIsEnv ? $sk : (empty($sk) ? '' : substr($sk, 0, 6) . '****' . substr($sk, -4));
        }
    }
    
    json(['code' => 1, 'data' => ['apis' => $apis, 'crawl' => $crawl]]);
}

function parse_api_config($yaml) {
    $apis = [];
    $platforms = ['deepseek', 'kimi', 'qianwen', 'wenxin', 'zhipu', 'doubao', 'hunyuan', 'nano360'];
    $lines = explode("\n", $yaml);
    $current = null;
    $inApis = false;
    
    foreach ($lines as $line) {
        if (preg_match('/^apis:/', $line)) { $inApis = true; continue; }
        if ($inApis && preg_match('/^(\w+):/', $line)) return $apis;
        if (!$inApis) continue;
        
        if (preg_match('/^\s{2}(\w+):\s*$/', $line, $m)) {
            $current = $m[1];
            $apis[$current] = ['pkey' => $current, 'enabled' => true, 'api_key' => '', 'model' => ''];
            continue;
        }
        if (!$current) continue;
        
        if (preg_match('/^\s{4}name:\s*"(.*)"/', $line, $m)) $apis[$current]['name'] = $m[1];
        if (preg_match('/^\s{4}enabled:\s*(true|false)/', $line, $m)) $apis[$current]['enabled'] = $m[1] === 'true';
        if (preg_match('/^\s{4}api_key:\s*"(.*)"/', $line, $m)) $apis[$current]['api_key'] = $m[1];
        if (preg_match('/^\s{4}secret_key:\s*"(.*)"/', $line, $m)) $apis[$current]['secret_key'] = $m[1];
        if (preg_match('/^\s{4}model:\s*"(.*)"/', $line, $m)) $apis[$current]['model'] = $m[1];
        if (preg_match('/^\s{4}base_url:\s*"(.*)"/', $line, $m)) $apis[$current]['base_url'] = $m[1];
        if (preg_match('/^\s{4}key_url:\s*"(.*)"/', $line, $m)) $apis[$current]['key_url'] = $m[1];
    }
    return $apis;
}

function parse_crawl_config($yaml) {
    $crawl = ['prompt_template' => '你知道{keyword}吗？请介绍一下。', 'temperature' => 0.7, 'max_tokens' => 2000];
    if (preg_match('/prompt_template:\s*"(.*)"/', $yaml, $m)) $crawl['prompt_template'] = $m[1];
    if (preg_match('/temperature:\s*([\d.]+)/', $yaml, $m)) $crawl['temperature'] = (float)$m[1];
    if (preg_match('/max_tokens:\s*(\d+)/', $yaml, $m)) $crawl['max_tokens'] = (int)$m[1];
    return $crawl;
}

function save_api_config() {
    $configPath = __DIR__ . '/../config.yaml';
    if (!file_exists($configPath)) error('配置文件不存在');
    $content = file_get_contents($configPath);
    
    $pkey = $_POST['pkey'] ?? '';
    $api_key = trim($_POST['api_key'] ?? '');
    $secret_key = trim($_POST['secret_key'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $enabled = ($_POST['enabled'] ?? '1') === '1';
    $temperature = $_POST['temperature'] ?? null;
    $max_tokens = $_POST['max_tokens'] ?? null;
    $prompt_template = $_POST['prompt_template'] ?? null;
    
    if ($temperature !== null) {
        $content = preg_replace('/temperature:\s*[\d.]+/', 'temperature: ' . (float)$temperature, $content);
    }
    if ($max_tokens !== null) {
        $content = preg_replace('/max_tokens:\s*\d+/', 'max_tokens: ' . (int)$max_tokens, $content);
    }
    if ($prompt_template !== null && !empty($prompt_template)) {
        $content = preg_replace('/prompt_template:\s*".*"/', 'prompt_template: "' . str_replace('"', '\"', $prompt_template) . '"', $content);
    }
    
    if (!empty($pkey)) {
        $pattern = '/^  ' . preg_quote($pkey) . ':\s*\n(.*?)(?=^  \w+:\s*\n|^crawl:|$)/ms';
        if (preg_match($pattern, $content, $m)) {
            $block = $m[0];
            $newBlock = $block;
            $newBlock = preg_replace('/enabled:\s*(true|false)/', 'enabled: ' . ($enabled ? 'true' : 'false'), $newBlock);
            if (!empty($api_key) && $api_key !== '****') {
                $newBlock = preg_replace('/api_key:\s*"[^"]*"/', 'api_key: "' . $api_key . '"', $newBlock);
            }
            if (!empty($secret_key) && $secret_key !== '****') {
                if (preg_match('/secret_key:/', $newBlock)) {
                    $newBlock = preg_replace('/secret_key:\s*"[^"]*"/', 'secret_key: "' . $secret_key . '"', $newBlock);
                }
            }
            if (!empty($model)) {
                $newBlock = preg_replace('/model:\s*"[^"]*"/', 'model: "' . $model . '"', $newBlock);
            }
            $content = str_replace($block, $newBlock, $content);
        }
    }
    
    file_put_contents($configPath, $content);
    json(['code' => 1, 'msg' => '配置已保存']);
}

function sync_config($pdo) {
    $companies = $pdo->query("SELECT name, variants FROM companies")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($companies)) return;

    $first = $companies[0];
    $configPath = __DIR__ . '/../config.yaml';
    $content = file_get_contents($configPath);
    
    $content = preg_replace('/full_name:\s*".*"/', 'full_name: "' . $first['name'] . '"', $content);

    $allVariants = [];
    foreach ($companies as $c) {
        if (!empty($c['variants'])) {
            foreach (explode(',', $c['variants']) as $v) {
                $v = trim($v);
                if ($v) $allVariants[] = $v;
            }
        }
        if (!in_array($c['name'], $allVariants)) {
            $allVariants[] = $c['name'];
        }
    }
    $allVariants = array_unique($allVariants);
    
    $variantLines = implode("\n", array_map(function($v) {
        return '    - "' . $v . '"';
    }, $allVariants));
    
    $content = preg_replace('/  variants:\s*\n(\s+-.*\n)*/', "  variants:\n$variantLines\n", $content);

    file_put_contents($configPath, $content);
}
