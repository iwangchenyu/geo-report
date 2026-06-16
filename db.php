<?php
// 数据库连接
$dbFile = __DIR__ . '/data/report.db';
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) { mkdir($dataDir, 0755, true); }

$pdo = new PDO("sqlite:$dbFile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA journal_mode=WAL");
$pdo->exec("PRAGMA foreign_keys=ON");

// 自动迁移 & 种子数据（所有操作幂等）
require __DIR__ . '/lib/migrate.php';

return $pdo;
