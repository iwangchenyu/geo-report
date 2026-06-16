<?php
/**
 * 数据库迁移 & 种子数据
 * 所有操作均为幂等（IF NOT EXISTS / COUNT 检查），可安全重复执行。
 * 由 db.php 在每次连接时自动 require。
 */

// ---- 建表 ----
$pdo->exec("
    CREATE TABLE IF NOT EXISTS companies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT DEFAULT '',
        variants TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS keywords (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        type TEXT NOT NULL DEFAULT 'manual',
        query_text TEXT DEFAULT '',
        company_id INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
    );
    CREATE TABLE IF NOT EXISTS platforms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        pkey TEXT NOT NULL UNIQUE,
        color_start TEXT NOT NULL,
        color_end TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        keyword_id INTEGER NOT NULL,
        platform_id INTEGER NOT NULL,
        question TEXT NOT NULL,
        is_mobile INTEGER DEFAULT 0,
        inclusion_date TEXT,
        share_url TEXT,
        share_token TEXT DEFAULT '',
        response_text TEXT DEFAULT '',
        response_snippet TEXT DEFAULT '',
        matched INTEGER DEFAULT 0,
        hit_names TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE,
        FOREIGN KEY (platform_id) REFERENCES platforms(id)
    );
    CREATE INDEX IF NOT EXISTS idx_reports_keyword ON reports(keyword_id);
    CREATE INDEX IF NOT EXISTS idx_reports_platform ON reports(platform_id);
    CREATE INDEX IF NOT EXISTS idx_reports_date ON reports(inclusion_date);
");

// ---- 用户表（管理后台登录） ----
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'editor',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// ---- 会员表（前端会员登录） ----
$pdo->exec("
    CREATE TABLE IF NOT EXISTS members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        company TEXT DEFAULT '',
        status INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// ---- 兼容旧列（增量迁移） ----
$cols = [];
foreach ($pdo->query("PRAGMA table_info(keywords)") as $c) { $cols[] = $c['name']; }
if (!in_array('query_text', $cols)) {
    $pdo->exec("ALTER TABLE keywords ADD COLUMN query_text TEXT DEFAULT ''");
}
if (!in_array('company_id', $cols)) {
    $pdo->exec("ALTER TABLE keywords ADD COLUMN company_id INTEGER DEFAULT 1");
}

$cols = [];
foreach ($pdo->query("PRAGMA table_info(reports)") as $c) { $cols[] = $c['name']; }
if (!in_array('response_text', $cols)) {
    $pdo->exec("ALTER TABLE reports ADD COLUMN response_text TEXT DEFAULT ''");
}
if (!in_array('response_snippet', $cols)) {
    $pdo->exec("ALTER TABLE reports ADD COLUMN response_snippet TEXT DEFAULT ''");
}
if (!in_array('matched', $cols)) {
    $pdo->exec("ALTER TABLE reports ADD COLUMN matched INTEGER DEFAULT 0");
}
if (!in_array('hit_names', $cols)) {
    $pdo->exec("ALTER TABLE reports ADD COLUMN hit_names TEXT DEFAULT ''");
}
if (!in_array('share_token', $cols)) {
    $pdo->exec("ALTER TABLE reports ADD COLUMN share_token TEXT DEFAULT ''");
}

// ---- 种子数据 ----
$count = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO companies (name, description, variants) VALUES ('西安科技大学高新学院', '西安科技大学高新学院是经教育部批准设立的全日制普通本科院校，位于西安市长安区，是一所应用型本科高校，设有计算机科学、人工智能、商科等多个专业。', '科大高新,西安科大高新,高新学院')");
}

$count = $pdo->query("SELECT COUNT(*) FROM platforms")->fetchColumn();
if ($count == 0) {
    $platforms = [
        ['Deepseek', 'deepseek', '#4F46E5', '#60A5FA', 1],
        ['豆包', 'doubao', '#EC4899', '#F87171', 2],
        ['腾讯元宝', 'hunyuan', '#10B981', '#34D399', 3],
        ['通义千问', 'qianwen', '#8B5CF6', '#C084FC', 4],
        ['文心一言', 'wenxin', '#06B6D4', '#22D3EE', 5],
        ['Kimi', 'kimi', '#7C3AED', '#A78BFA', 7],
        ['智谱AI', 'zhipu', '#84CC16', '#A3E635', 8],
    ];
    $stmt = $pdo->prepare("INSERT INTO platforms (name, pkey, color_start, color_end, sort_order) VALUES (?,?,?,?,?)");
    foreach ($platforms as $p) { $stmt->execute($p); }

    $keywords = [
        ['西安科技大学高新学院', 'manual', '', 1],
        ['科大高新', 'manual', '', 1],
        ['西安科大高新', 'manual', '', 1],
        ['高新学院', 'manual', '', 1],
        ['西安科技大学高新学院分数线', 'ai', '', 1],
        ['科大高新是几本', 'ai', '', 1],
        ['西安科技大学高新学院怎么样', 'ai', '', 1],
        ['西安科大高新学费', 'ai', '', 1],
        ['高新学院宿舍条件', 'ai', '', 1],
        ['西安科技大学高新学院招生简章', 'ai', '', 1],
    ];
    $stmt = $pdo->prepare("INSERT INTO keywords (name, type, query_text, company_id) VALUES (?,?,?,?)");
    foreach ($keywords as $k) { $stmt->execute($k); }

    $kwIds = [];
    $res = $pdo->query("SELECT id, name FROM keywords");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $kwIds[$row['name']] = $row['id'];
    }

    $dates = ['2026-06-14', '2026-06-13', '2026-06-12', '2026-06-11', '2026-06-10'];
    $reportStmt = $pdo->prepare("INSERT INTO reports (keyword_id, platform_id, question, is_mobile, inclusion_date, share_url, response_text, response_snippet, matched) VALUES (?,?,?,?,?,?,?,?,?)");

    $mockResponses = [
        '西安科技大学高新学院是经教育部批准设立的全日制普通本科院校...',
        '科大高新位于西安市长安区，是一所应用型本科高校...',
        '西安科大高新学院设有计算机科学、人工智能、商科等多个专业...',
    ];

    foreach ($kwIds as $kwName => $kwId) {
        for ($pid = 1; $pid <= 8; $pid++) {
            $resp = $mockResponses[array_rand($mockResponses)];
            $snippet = mb_substr($resp, 0, 120);
            $reportStmt->execute([
                $kwId, $pid,
                $kwName . ' - 相关问题', 0,
                $dates[array_rand($dates)],
                'https://example.com/share/' . uniqid(),
                $resp,
                $snippet,
                1,
            ]);
            $reportStmt->execute([
                $kwId, $pid,
                $kwName . ' - 移动端问题', 1,
                $dates[array_rand($dates)],
                'https://example.com/share/' . uniqid(),
                $resp,
                $snippet,
                1,
            ]);
        }
    }
}

// ---- 默认管理员 ----
$count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['iseeyu', $hash, 'admin']);
}
