<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$pdo = require __DIR__ . '/db.php';
require __DIR__ . '/lib/guard.php';
$action = $_GET['action'] ?? '';

function json($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function error($msg) { json(['code' => 0, 'msg' => $msg]); }

try {
    switch ($action) {
        case 'get_stats':
            get_stats($pdo);
            break;
        case 'get_report':
            get_report($pdo);
            break;
        case 'get_keywords':
            get_keywords($pdo);
            break;
        case 'add_keyword':
            api_guard();
            add_keyword($pdo);
            break;
        case 'update_keyword':
            api_guard();
            update_keyword($pdo);
            break;
        case 'delete_keyword':
            api_guard();
            delete_keyword($pdo);
            break;
        case 'get_platforms':
            get_platforms($pdo);
            break;
        case 'get_companies':
            get_companies($pdo);
            break;
        case 'run_crawl':
            api_guard();
            run_crawl($pdo);
            break;
        case 'get_report_detail':
            get_report_detail($pdo);
            break;
        case 'get_crawl_status':
            get_crawl_status($pdo);
            break;
        case 'generate_share_token':
            api_guard();
            generate_share_token($pdo);
            break;
        case 'get_share_report':
            get_share_report($pdo);
            break;
        case 'geo_analyze':
            api_guard();
            geo_analyze($pdo);
            break;
        default:
            error('未知操作');
    }
} catch (Exception $e) {
    error($e->getMessage());
}

function get_companies($pdo) {
    $companies = $pdo->query("SELECT id, name, description, variants FROM companies ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    json(['code' => 1, 'data' => $companies]);
}

function get_stats($pdo) {
    $companyId = (int)($_GET['company_id'] ?? 0);
    
    if ($companyId > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports r JOIN keywords k ON r.keyword_id = k.id WHERE k.company_id = ?");
        $stmt->execute([$companyId]);
        $totalQuestions = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM keywords WHERE type = 'manual' AND company_id = ?");
        $stmt->execute([$companyId]);
        $totalManual = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM keywords WHERE type = 'ai' AND company_id = ?");
        $stmt->execute([$companyId]);
        $totalAi = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.pkey, p.color_start, p.color_end, COUNT(r.id) as cnt
            FROM platforms p
            LEFT JOIN reports r ON r.platform_id = p.id
            LEFT JOIN keywords k ON r.keyword_id = k.id AND k.company_id = ?
            GROUP BY p.id ORDER BY p.sort_order");
        $stmt->execute([$companyId]);
        $platformData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $totalQuestions = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        $totalManual = $pdo->query("SELECT COUNT(*) FROM keywords WHERE type = 'manual'")->fetchColumn();
        $totalAi = $pdo->query("SELECT COUNT(*) FROM keywords WHERE type = 'ai'")->fetchColumn();
        $platformData = $pdo->query("SELECT p.id, p.name, p.pkey, p.color_start, p.color_end, COUNT(r.id) as cnt
            FROM platforms p
            LEFT JOIN reports r ON r.platform_id = p.id
            GROUP BY p.id ORDER BY p.sort_order")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $totalPlatforms = $pdo->query("SELECT COUNT(*) FROM platforms")->fetchColumn();

    json([
        'code' => 1,
        'data' => [
            'total_questions' => (int)$totalQuestions,
            'total_keywords' => (int)($totalManual + $totalAi),
            'total_platforms' => (int)$totalPlatforms,
            'total_ai_keywords' => (int)$totalAi,
            'platform_data' => $platformData
        ]
    ]);
}

function get_keywords($pdo) {
    $companyId = (int)($_GET['company_id'] ?? 0);
    if ($companyId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, type, query_text, company_id, created_at FROM keywords WHERE company_id = ? ORDER BY id DESC");
        $stmt->execute([$companyId]);
        $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $keywords = $pdo->query("SELECT id, name, type, query_text, company_id, created_at FROM keywords ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    json(['code' => 1, 'data' => $keywords]);
}

function get_platforms($pdo) {
    $platforms = $pdo->query("SELECT id, name, pkey, color_start, color_end, sort_order FROM platforms ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
    json(['code' => 1, 'data' => $platforms]);
}

function add_keyword($pdo) {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'manual';
    $queryText = trim($_POST['query_text'] ?? '');
    $companyId = (int)($_POST['company_id'] ?? 1);
    if (empty($name)) error('关键词不能为空');
    if (!in_array($type, ['manual', 'ai'])) error('类型无效');

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO keywords (name, type, query_text, company_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $type, $queryText, $companyId]);
    if ($stmt->rowCount() == 0) error('关键词已存在');

    json(['code' => 1, 'msg' => '添加成功', 'data' => ['id' => $pdo->lastInsertId(), 'name' => $name, 'type' => $type, 'query_text' => $queryText]]);
}

function update_keyword($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'manual';
    $queryText = trim($_POST['query_text'] ?? '');
    if ($id <= 0) error('无效的关键词ID');
    if (empty($name)) error('关键词不能为空');

    $stmt = $pdo->prepare("UPDATE keywords SET name = ?, type = ?, query_text = ? WHERE id = ?");
    $stmt->execute([$name, $type, $queryText, $id]);
    if ($stmt->rowCount() == 0) error('关键词不存在或未变更');

    json(['code' => 1, 'msg' => '更新成功']);
}

function delete_keyword($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) error('无效的关键词ID');
    $stmt = $pdo->prepare("DELETE FROM keywords WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() == 0) error('关键词不存在');
    json(['code' => 1, 'msg' => '删除成功']);
}

function get_report($pdo) {
    $page = max(1, (int)($_POST['page'] ?? 1));
    $keyword = trim($_POST['keyword'] ?? '');
    $platform = (int)($_POST['platform'] ?? 0);
    $mobile = (int)($_POST['mobile'] ?? -1);
    $company = (int)($_POST['company'] ?? 0);
    $sort = (int)($_POST['question_sort'] ?? 0);
    $perPage = 15;

    $where = [];
    $params = [];

    if (!empty($keyword)) {
        $where[] = "(k.name LIKE ? OR r.question LIKE ? OR r.response_text LIKE ? OR r.response_snippet LIKE ?)";
        $kwSql = "%$keyword%";
        $params[] = $kwSql;
        $params[] = $kwSql;
        $params[] = $kwSql;
        $params[] = $kwSql;
    }
    if ($platform > 0) {
        $where[] = "r.platform_id = ?";
        $params[] = $platform;
    }
    if ($mobile >= 0) {
        $where[] = "r.is_mobile = ?";
        $params[] = $mobile;
    }
    if ($company > 0) {
        $where[] = "k.company_id = ?";
        $params[] = $company;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $orderSQL = "ORDER BY r.inclusion_date DESC";
    if ($sort == 1) $orderSQL = "ORDER BY r.question ASC";
    elseif ($sort == 2) $orderSQL = "ORDER BY r.question DESC";

    // 总数
    $countSQL = "SELECT COUNT(*) FROM reports r JOIN keywords k ON r.keyword_id = k.id $whereSQL";
    $stmt = $pdo->prepare($countSQL);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $dataSQL = "
        SELECT r.id, k.name as keyword, r.question, p.name as platform_name, p.pkey,
               r.is_mobile, r.inclusion_date as shoulu_date, r.share_url, r.share_token,
               k.type as keyword_type, r.response_snippet, r.matched, r.hit_names,
               c.name as company_name
        FROM reports r
        JOIN keywords k ON r.keyword_id = k.id
        JOIN platforms p ON r.platform_id = p.id
        LEFT JOIN companies c ON k.company_id = c.id
        $whereSQL
        $orderSQL
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($dataSQL);
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($list as &$row) {
        $row['is_mobile'] = $row['is_mobile'] ? '移动端' : '电脑端';
        $row['matched'] = (int)($row['matched'] ?? 0);
        $row['hit_names'] = $row['hit_names'] ?? '';
        $row['share_token'] = $row['share_token'] ?? '';
        $snippet = $row['response_snippet'] ?? '';
        if (mb_strlen($snippet) > 150) {
            $snippet = mb_substr($snippet, 0, 150) . '…';
        }
        $row['response_snippet'] = $snippet;
    }

    json([
        'code' => 1,
        'data' => [
            'list' => $list,
            'count' => (int)$total,
            'all_page' => ceil($total / $perPage)
        ]
    ]);
}

function get_report_detail($pdo) {
    $id = (int)($_GET["id"] ?? 0);
    if ($id <= 0) error("无效的报告ID");
    $stmt = $pdo->prepare("
        SELECT r.id, k.name as keyword, p.name as platform_name, r.question,
               r.response_text, r.share_url, r.share_token, r.matched, r.hit_names,
               c.name as company_name
        FROM reports r
        JOIN keywords k ON r.keyword_id = k.id
        JOIN platforms p ON r.platform_id = p.id
        LEFT JOIN companies c ON k.company_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) error("报告不存在");
    $row['share_token'] = $row['share_token'] ?? '';
    json(["code" => 1, "data" => $row]);
}

function generate_share_token($pdo) {
    $reportId = (int)($_POST['report_id'] ?? 0);
    if ($reportId <= 0) error('参数错误');
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE reports SET share_token = ? WHERE id = ?");
    $stmt->execute([$token, $reportId]);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $url = "$protocol://$host/share/?t=$token";
    json(['code' => 1, 'msg' => '分享链接已生成', 'data' => ['url' => $url, 'token' => $token]]);
}

function get_share_report($pdo) {
    $token = $_GET['token'] ?? '';
    if (empty($token)) error('无效的分享token');
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
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) error('分享链接不存在或已失效');
    json(['code' => 1, 'data' => $row]);
}

function run_crawl($pdo) {
    $platform = $_POST['platform'] ?? '';
    $keyword = $_POST['keyword'] ?? '';
    $no_clear = ($_POST['no_clear'] ?? false) === 'true';

    $lockFile = __DIR__ . '/data/crawl.lock';
    if (file_exists($lockFile)) {
        $lockTime = (int)file_get_contents($lockFile);
        if (time() - $lockTime < 3600) {
            $pid = trim(@file_get_contents(__DIR__ . '/data/crawl.pid'));
            if ($pid && function_exists('posix_kill')) {
                if (posix_kill((int)$pid, 0)) {
                    json(['code' => 0, 'msg' => '爬虫正在运行中，请等待完成']);
                    return;
                }
            }
        }
    }

    file_put_contents($lockFile, time());

    $cmd = 'python3 ' . escapeshellcmd(__DIR__ . '/crawler_v4.py') . ' --crawl';
    if (!empty($platform)) $cmd .= ' -p ' . escapeshellarg($platform);
    if (!empty($keyword)) $cmd .= ' -k ' . escapeshellarg($keyword);
    if ($no_clear) $cmd .= ' --no-clear';
    $cmd .= ' --json';
    $cmd .= ' > ' . escapeshellarg(__DIR__ . '/data/crawl_result.json') . ' 2>&1 & echo $!';

    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0) {
        @unlink($lockFile);
        json(['code' => 0, 'msg' => '启动爬虫失败']);
        return;
    }

    $pid = trim($output[0] ?? '');
    if ($pid) {
        file_put_contents(__DIR__ . '/data/crawl.pid', $pid);
    }

    json(['code' => 1, 'msg' => '爬虫已启动，正在后台运行...', 'data' => ['pid' => $pid]]);
}

function geo_analyze($pdo) {
    $keyword = trim($_POST['keyword'] ?? $_GET['keyword'] ?? '');
    if (empty($keyword)) error('请提供关键词');
    
    $script = __DIR__ . '/geo_analyzer.py';
    $cmd = 'python3 ' . escapeshellarg($script) . ' ' . escapeshellarg($keyword) . ' 2>&1';
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    
    $result = implode("
", $output);
    $data = json_decode($result, true);
    
    if (!$data || isset($data['error'])) {
        $msg = $data['error'] ?? '分析失败，请检查 API 配置';
        error($msg);
    }
    
    json(['code' => 1, 'data' => $data]);
}

function get_crawl_status($pdo) {
    $lockFile = __DIR__ . '/data/crawl.lock';
    $resultFile = __DIR__ . '/data/crawl_result.json';
    $running = false;

    if (file_exists($lockFile)) {
        $lockTime = (int)file_get_contents($lockFile);
        if (time() - $lockTime < 3600) {
            $pid = trim(@file_get_contents(__DIR__ . '/data/crawl.pid'));
            if ($pid && function_exists('posix_kill')) {
                if (posix_kill((int)$pid, 0)) {
                    $running = true;
                }
            } elseif ($pid && PHP_OS_FAMILY !== 'Darwin' && PHP_OS_FAMILY !== 'Linux') {
                $running = false;
            }
        }
    }

    $result = null;
    if (file_exists($resultFile)) {
        $content = file_get_contents($resultFile);
        if ($content) {
            $result = json_decode($content, true);
        }
    }

    json([
        'code' => 1,
        'data' => [
            'running' => $running,
            'result' => $result,
        ]
    ]);
}
