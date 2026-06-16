<?php
require __DIR__ . '/auth.php';
require_login();
$pdo = require __DIR__ . '/../db.php';

$tab = $_GET['tab'] ?? 'reports';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>GEO报表系统 - 管理后台</title>
    <link rel="stylesheet" href="/assets/style.css">
<style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','PingFang SC','Microsoft YaHei',sans-serif;background:#f2f3f7;color:#1e293b;min-height:100vh;-webkit-font-smoothing:antialiased}
        
        .topbar{background:#111827;color:#fff;padding:0 28px;height:56px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,0.12);position:relative;z-index:50}
        .topbar .brand{font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;letter-spacing:-0.01em}
        .topbar .brand span{color:#34d399;background:rgba(52,211,153,0.12);padding:3px 8px;border-radius:5px;font-size:14px}
        .topbar .actions{display:flex;align-items:center;gap:8px}
        .topbar .actions a{color:#94a3b8;text-decoration:none;font-size:13px;padding:5px 12px;border-radius:6px;transition:all 0.2s}
        .topbar .actions a:hover{background:rgba(255,255,255,0.08);color:#e2e8f0}
        
        .layout{display:flex;min-height:calc(100vh - 56px)}
        .sidebar{width:224px;background:#fff;border-right:1px solid #e8ecf1;flex-shrink:0;display:flex;flex-direction:column;padding:0;overflow-y:auto}
        .sidebar-header{display:flex;align-items:center;gap:6px;padding:16px 20px 6px;font-size:11px;font-weight:600;color:#94a3b8;letter-spacing:0.08em;text-transform:uppercase;border-bottom:1px solid #f1f5f9;margin:0 0 4px}.sidebar-header svg{width:13px;height:13px;opacity:0.6;flex-shrink:0}
        .sidebar .nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;margin:1px 8px;font-size:13.5px;color:#475569;text-decoration:none;border-radius:8px;transition:all 0.18s;position:relative}
        .sidebar .nav-item:hover{background:#f1f5f9;color:#1e293b}
        .sidebar .nav-item.active{background:linear-gradient(135deg,#eef2ff,#f5f3ff);color:#4f46e5;font-weight:500}
        .sidebar .nav-item.active::before{content:'';position:absolute;left:0px;top:50%;transform:translateY(-50%);width:3px;height:20px;background:#4f46e5;border-radius:0 3px 3px 0}
        .sidebar .nav-item svg{width:18px;height:18px;flex-shrink:0;opacity:0.7}
        .sidebar .nav-item.active svg{opacity:1}
        
        .main{flex:1;padding:20px 24px 0;overflow-x:auto;min-width:0;display:flex;flex-direction:column}
        
        .panel{background:#fff;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,0.04),0 2px 8px rgba(0,0,0,0.03);padding:20px;margin-bottom:16px;border:1px solid #f1f5f9}
        .panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
        .panel-header h3{font-size:15px;font-weight:600;color:#1e293b}
        
        .btn{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;border:none;cursor:pointer;transition:all 0.18s;display:inline-flex;align-items:center;gap:5px;line-height:1.4;user-select:none;white-space:nowrap}
        .btn:active{transform:scale(0.97)}
        .btn-primary{background:#4f46e5;color:#fff;box-shadow:0 1px 2px rgba(79,70,229,0.3)}
        .btn-primary:hover{background:#4338ca;box-shadow:0 2px 6px rgba(79,70,229,0.35)}
        .btn-outline{background:#fff;color:#334155;border:1px solid #d1d5db}
        .btn-outline:hover{background:#f8fafc;border-color:#94a3b8;color:#1e293b}
        .btn-danger{color:#dc2626;background:#fff;border:1px solid #fecaca}
        .btn-danger:hover{background:#fef2f2;border-color:#fca5a5}
        .btn-sm{padding:4px 10px;font-size:12px;border-radius:6px}
        .btn-success{background:#10b981;color:#fff;box-shadow:0 1px 2px rgba(16,185,129,0.25)}
        .btn-success:hover{background:#059669;box-shadow:0 2px 6px rgba(16,185,129,0.3)}
        .btn-warning{background:#f59e0b;color:#fff;box-shadow:0 1px 2px rgba(245,158,11,0.25)}
        .btn-warning:hover{background:#d97706;box-shadow:0 2px 6px rgba(245,158,11,0.3)}
        .btn:disabled{opacity:0.55;cursor:not-allowed;pointer-events:none}
        
        table{width:100%;border-collapse:collapse}
        thead th{text-align:left;padding:10px 14px;background:#f8fafc;color:#64748b;font-size:11.5px;font-weight:600;white-space:nowrap;text-transform:uppercase;letter-spacing:0.04em;border-bottom:2px solid #e2e8f0}
        tbody td{padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:13.5px;color:#334155;vertical-align:middle}
        tbody tr{transition:background 0.15s}
        tbody tr:hover{background:#f8fafc}
        tbody tr:last-child td{border-bottom:none}
        
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,0.45);z-index:100;justify-content:center;align-items:center;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px)}
        .modal-overlay.show{display:flex}
        .modal{background:#fff;border-radius:14px;padding:28px 30px;width:90%;max-width:520px;box-shadow:0 25px 80px rgba(0,0,0,0.18);max-height:85vh;overflow-y:auto;animation:modalIn 0.2s ease-out}
        @keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(0.98)}to{opacity:1;transform:translateY(0) scale(1)}}
        .modal.wide{max-width:750px}
        .modal h3{font-size:16px;font-weight:600;margin-bottom:22px;color:#1e293b}
        .form-group{margin-bottom:15px}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px}
        .form-group input,.form-group textarea,.form-group select{width:100%;padding:8.5px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;outline:none;font-family:inherit;color:#334155;background:#fff;transition:border-color 0.18s,box-shadow 0.18s}
        .form-group textarea{resize:vertical;min-height:60px}
        .form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
        .form-group input::placeholder,.form-group textarea::placeholder{color:#cbd5e1}
        .form-group .hint{font-size:11px;color:#94a3b8;margin-top:4px}
        .form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:22px}
        
        .desc-text{font-size:13px;color:#64748b;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .tag{display:inline-block;font-size:11px;padding:2.5px 8px;border-radius:12px;font-weight:500;letter-spacing:0.01em}
        .tag-manual{background:#dbeafe;color:#1d4ed8}
        .tag-sm{font-size:10px;padding:1.5px 6px}.tag-ai{background:#fef3c7;color:#b45309}
        
        .toast{position:fixed;top:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 26px;border-radius:10px;font-size:13.5px;z-index:200;opacity:0;transition:opacity 0.2s,transform 0.2s;pointer-events:none;box-shadow:0 10px 40px rgba(0,0,0,0.2)}
        .toast.show{opacity:1;transform:translateX(-50%)}
        
        .empty-state{text-align:center;padding:48px 24px;color:#94a3b8}
        .empty-state::before{content:'';display:block;width:48px;height:48px;margin:0 auto 14px;background:#f1f5f9;border-radius:50%;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23cbd5e1' stroke-width='1.5'%3E%3Cpath d='M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z'%3E%3C/path%3E%3Cpolyline points='13 2 13 9 20 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:center}

        .platform-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
        .platform-tab{font-size:12px;border:1px solid #e2e8f0;border-radius:8px;padding:5px 12px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;background:#fff;transition:all 0.18s;color:#475569}
        .platform-tab:hover{border-color:#a5b4fc;background:#fafafe}
        .platform-tab.active{background:#eef2ff;border-color:#6366f1;color:#4338ca;font-weight:500}

        .match-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 8px;border-radius:12px}
        .match-badge.yes{background:#d1fae5;color:#065f46}
        .match-badge.no{background:#fee2e2;color:#991b1b}
        .match-badge.partial{background:#fef3c7;color:#92400e}
        .match-badge .dot{width:5px;height:5px;border-radius:50%}
        .match-badge.yes .dot{background:#10b981}
        .match-badge.no .dot{background:#ef4444}

        .snippet-text{font-size:12px;color:#64748b;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-style:italic}
        .share-url{background:#f0f4ff;border-radius:8px;padding:8px 12px;font-family:monospace;font-size:12px;color:#4338ca;word-break:break-all;display:flex;align-items:center;justify-content:space-between;gap:8px}

        .search-wrap{position:relative;display:inline-flex}
        .search-wrap svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8;pointer-events:none}
        .search-input{border:1px solid #d1d5db;border-radius:8px;padding:6.5px 12px 6.5px 30px;font-size:13px;width:220px;outline:none;transition:border-color 0.18s,box-shadow 0.18s}
        .search-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
        .search-input::placeholder{color:#cbd5e1}
        select.filter-select{padding:6.5px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;outline:none;color:#475569;background:#fff;cursor:pointer;transition:border-color 0.18s}
        select.filter-select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1)}

        .status-dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:5px;vertical-align:middle}
        .status-dot.running{background:#f59e0b;animation:pulse 1.2s ease-in-out infinite}
        .status-dot.done{background:#10b981}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:0.35}}
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

        .detail-section{margin-bottom:14px}
        .detail-section .dlabel{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px}
        .detail-section .dcontent{font-size:13px;line-height:1.75;color:#334155;background:#f8fafc;padding:12px 14px;border-radius:8px;max-height:280px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;border:1px solid #f1f5f9}

        .pagination{display:flex;justify-content:flex-end;align-items:center;margin-top:18px;gap:4px}
        .pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border-radius:7px;font-size:12.5px;text-decoration:none;color:#475569;border:1px solid #e2e8f0;cursor:pointer;transition:all 0.15s}
        .pagination a:hover{background:#f1f5f9;border-color:#cbd5e1}
        .pagination .active{background:#4f46e5;color:#fff;border-color:#4f46e5;font-weight:500}
        .pagination .disabled{color:#cbd5e1;border-color:#f1f5f9;pointer-events:none}


        /* ===== 统计卡片 ===== */
        .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px}
        .stat-card{background:#fff;border-radius:12px;padding:18px 22px;border:1px solid #f1f5f9;box-shadow:0 1px 2px rgba(0,0,0,0.03);display:flex;align-items:center;gap:16px;transition:all 0.2s}
        .stat-card:hover{box-shadow:0 2px 8px rgba(0,0,0,0.06);transform:translateY(-1px)}
        .stat-card .stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
        .stat-card .stat-icon.blue{background:#eef2ff;color:#4f46e5}
        .stat-card .stat-icon.green{background:#d1fae5;color:#059669}
        .stat-card .stat-icon.amber{background:#fef3c7;color:#d97706}
        .stat-card .stat-icon.red{background:#fee2e2;color:#dc2626}
        .stat-card .stat-value{font-size:22px;font-weight:700;color:#1e293b;line-height:1.2}
        .stat-card .stat-label{font-size:12px;color:#64748b;margin-top:2px}
        
        /* ===== 改进的分页 ===== */
        .pagination{display:flex;justify-content:space-between;align-items:center;margin-top:18px;flex-wrap:wrap;gap:10px}
        .pagination .pagination-info{font-size:12px;color:#64748b}
        .pagination .pagination-pages{display:flex;align-items:center;gap:4px}
        .pagination a,.pagination span.page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border-radius:7px;font-size:12.5px;text-decoration:none;color:#475569;border:1px solid #e2e8f0;cursor:pointer;transition:all 0.15s;background:#fff}
        .pagination a:hover{background:#f1f5f9;border-color:#cbd5e1}
        .pagination .active{background:#4f46e5;color:#fff;border-color:#4f46e5;font-weight:500}
        .pagination .disabled{cursor:default;color:#cbd5e1;border-color:#f1f5f9;pointer-events:none}
        
        /* ===== 改进的页脚 ===== */
        .app-footer{text-align:center;padding:14px 24px;color:#94a3b8;font-size:12px;border-top:1px solid #e8ecf1;background:#fafbfc;margin-top:auto}
        .app-footer a{color:#64748b;text-decoration:none;transition:color 0.15s}
        .app-footer a:hover{color:#4f46e5}
        
        /* ===== 改进的表格列宽 ===== */
        table{width:100%;border-collapse:collapse;table-layout:fixed}
        thead th{text-align:left;padding:10px 14px;background:#f8fafc;color:#64748b;font-size:11.5px;font-weight:600;white-space:nowrap;text-transform:uppercase;letter-spacing:0.04em;border-bottom:2px solid #e2e8f0;position:sticky;top:0;z-index:1;font-size:11px}
        tbody td{padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:13.5px;color:#334155;vertical-align:middle}
        tbody tr{transition:background 0.15s}
        tbody tr:nth-child(even){background:#fafbfc}
        tbody tr:hover{background:#eef2ff}
        tbody tr:last-child td{border-bottom:none}
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand"><span>GEO</span> 报表系统 · 管理后台</div>
        <div class="actions">
            <a href="/" target="_blank">前台看板</a>
            <a href="?logout=1">退出登录</a>
        </div></div>
    
    <div class="layout">
        <div class="sidebar"><div class="sidebar-header"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;opacity:0.6"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>导航菜单</div>
            <a href="?tab=reports" class="nav-item <?= $tab=='reports'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                报表查询
            </a>
            <a href="?tab=companies" class="nav-item <?= $tab=='companies'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                公司管理
            </a>
            <a href="?tab=keywords" class="nav-item <?= $tab=='keywords'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                关键词管理
            </a>
            <a href="?tab=apiconfig" class="nav-item <?= $tab=='apiconfig'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                API配置
            </a>
            <a href="?tab=screenshots" class="nav-item <?= $tab=='screenshots'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                截图浏览
            </a>
            <a href="?tab=users" class="nav-item <?= $tab=='users'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                用户管理
                <span id="pendingBadge" style="display:none;background:#ef4444;color:#fff;font-size:10px;padding:1px 6px;border-radius:10px;margin-left:auto">0</span>
            </a>
            <a href="?tab=settings" class="nav-item <?= $tab=='settings'?'active':'' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                系统设置
            </a>
        </div>
        
        <div class="main">

<?php if ($tab === 'reports'): ?>
            <!-- ===== 爬取 + 报表查询 ===== -->
            <div class="panel" style="padding:16px 24px">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <h3 style="font-size:15px;font-weight:600">AI平台收录查询</h3>
                        <span id="crawlStatus" style="font-size:12px;color:#9ca3af">就绪</span>
                        <div id="crawlProgress" style="display:none;width:100px;height:5px;background:#e5e7eb;border-radius:3px;overflow:hidden">
                            <div id="crawlProgressBar" style="height:100%;background:linear-gradient(135deg,#4f46e5,#818cf8);width:0%;transition:width 0.5s"></div>
                        </div>
                    </div>
                    <button class="btn btn-warning" id="btnCrawl" onclick="triggerCrawl()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        一键爬取
                    </button>
                </div>
            </div>

            <!-- ===== 统计卡片 ===== -->
            <div class="stats-row" id="statsRow" style="display:none">
                <div class="stat-card" style="cursor:pointer" onclick="document.getElementById('reportCompanyFilter').value=0;document.getElementById('reportPlatformFilter').value=0;document.getElementById('reportSearch').value='';loadReports()">
                    <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                    <div><div class="stat-value" id="statTotal">0</div><div class="stat-label">总记录数</div></div>
                </div>
                <div class="stat-card" style="cursor:pointer" onclick="document.getElementById('reportPlatformFilter').value=0;document.getElementById('reportSearch').value='matched';loadReports()">
                    <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                    <div><div class="stat-value" id="statMatched">0</div><div class="stat-label">已收录</div></div>
                </div>
                <div class="stat-card" style="cursor:pointer" onclick="document.getElementById('reportPlatformFilter').value=0;document.getElementById('reportSearch').value='unmatched';loadReports()">
                    <div class="stat-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                    <div><div class="stat-value" id="statUnmatched">0</div><div class="stat-label">未收录</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
                    <div><div class="stat-value" id="statCompanies">0</div><div class="stat-label">涉及公司</div></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>报表数据</h3>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <select class="filter-select" id="reportCompanyFilter" onchange="loadReports()"><option value="0">全部公司</option></select>
                        <select class="filter-select" id="reportPlatformFilter" onchange="loadReports()"><option value="0">全部平台</option></select>
                        <div class="search-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" class="search-input" id="reportSearch" placeholder="搜索关键词/回复..." onkeydown="if(event.key==='Enter')loadReports()">
                        </div>
                    </div>
                </div>
                <table>
                    <thead><tr>
                        <th style="width:8%">公司</th><th style="width:11%">关键词</th><th style="width:16%">查询句子</th><th style="width:7%">平台</th><th style="width:6%">收录</th><th style="width:30%">AI回复预览</th><th style="width:11%">时间</th><th style="width:11%">操作</th>
                    </tr></thead>
                    <tbody id="reportTbody"><tr><td colspan="8" class="empty-state">加载中...</td></tr></tbody>
                </table>
                <div class="pagination" id="reportPagination"></div>
            </div>

            <!-- 详情弹窗 -->
            <div class="modal-overlay" id="detailModal">
                <div class="modal wide">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
                        <h3 id="detailTitle" style="margin-bottom:0">AI回复详情</h3>
                        <button class="btn btn-outline btn-sm" onclick="closeDetailModal()">关闭</button>
                    </div>
                    <div class="detail-section"><div class="dlabel">查询句子</div><div class="dcontent" id="detailQuestion" style="font-style:italic;max-height:60px"></div></div>
                    <div class="detail-section" id="detailHitSection" style="display:none"><div class="dlabel">命中公司名</div><div class="dcontent" id="detailHitNames" style="color:#065f46;background:#d1fae5;font-weight:500"></div></div>
                    <div class="detail-section"><div class="dlabel">AI完整回复</div><div class="dcontent" id="detailResponse">加载中...</div></div>
                    <div id="shareSection" style="margin-top:12px;display:none">
                        <div class="dlabel" style="margin-bottom:6px">分享链接</div>
                        <div id="shareUrlBox"></div>
                        <button class="btn btn-success btn-sm" id="btnGenShare" onclick="generateShareLink()" style="margin-top:8px">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                            生成分享链接
                        </button>
                    </div>
                </div>
            </div>

<?php elseif ($tab === 'companies'): ?>
            <div class="panel">
                <div class="panel-header">
                    <h3>公司列表</h3>
                    <button class="btn btn-primary" onclick="openCompanyModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        添加公司
                    </button>
                </div>
                <table>
                    <thead><tr><th>ID</th><th>公司名称</th><th>简介</th><th>变体/别名</th><th>创建时间</th><th>操作</th></tr></thead>
                    <tbody id="companyTbody"><tr><td colspan="6" class="empty-state">加载中...</td></tr></tbody>
                </table>
            </div>
            
            <div class="modal-overlay" id="companyModal">
                <div class="modal">
                    <h3 id="companyModalTitle">添加公司</h3>
                    <input type="hidden" id="editCompanyId">
                    <div class="form-group"><label>公司名称 *</label><input type="text" id="companyName" placeholder="例如: 西安科技大学高新学院"></div>
                    <div class="form-group"><label>公司简介</label><textarea id="companyDesc" placeholder="公司的简要介绍..."></textarea></div>
                    <div class="form-group"><label>名称变体/别名</label><input type="text" id="companyVariants" placeholder="多个用逗号分隔"><div class="hint">用于AI回复命中检测</div></div>
                    <div class="form-actions"><button class="btn btn-outline" onclick="closeCompanyModal()">取消</button><button class="btn btn-primary" onclick="saveCompany()">保存</button></div>
                </div>
            </div>

<?php elseif ($tab === 'keywords'): ?>
            <div class="panel">
                <div class="panel-header">
                    <h3>关键词管理</h3>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select id="keywordFilterCompany" class="filter-select" onchange="loadKeywords()"><option value="0">全部公司</option></select>
                        <button class="btn btn-primary" onclick="openKeywordModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            添加关键词
                        </button>
                    </div>
                </div>
                <table>
                    <thead><tr><th>ID</th><th>关键词</th><th>类型</th><th>所属公司</th><th>查询句子</th><th>创建时间</th><th>操作</th></tr></thead>
                    <tbody id="keywordTbody"><tr><td colspan="7" class="empty-state">加载中...</td></tr></tbody>
                </table>
            </div>
            
            <div class="modal-overlay" id="keywordModal">
                <div class="modal">
                    <h3 id="kwModalTitle">添加关键词</h3>
                    <input type="hidden" id="editKeywordId">
                    <div class="form-group"><label>关键词名称 *</label><input type="text" id="keywordName" placeholder="例如: 西安科技大学高新学院"></div>
                    <div class="form-group"><label>所属公司</label><select id="keywordCompany"><option value="1">加载中...</option></select></div>
                    <div class="form-group"><label>类型</label><select id="keywordType"><option value="manual">手动添加</option><option value="ai">AI蒸馏词</option></select></div>
                    <div class="form-group"><label>查询句子</label><textarea id="keywordQueryText" placeholder="自定义向AI提问的句子。留空则使用默认模板"></textarea><div class="hint">用于替代默认提问模板</div></div>
                    <div class="form-actions"><button class="btn btn-outline" onclick="closeKeywordModal()">取消</button><button class="btn btn-primary" onclick="saveKeyword()">保存</button></div>
                </div>
            </div>
<?php endif; ?>

<?php if ($tab === 'apiconfig'): ?>
            <!-- ===== API配置 ===== -->
            <div class="panel">
                <div class="panel-header"><h3>爬取参数</h3></div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">
                    <div class="form-group"><label>默认提问模板</label><input type="text" id="promptTemplate" placeholder="你知道{keyword}吗？"><div class="hint">用 {keyword} 代表关键词</div></div>
                    <div class="form-group"><label>Temperature</label><input type="number" id="crawlTemperature" step="0.1" min="0" max="2" placeholder="0.7"><div class="hint">0-2，越高越随机</div></div>
                    <div class="form-group"><label>Max Tokens</label><input type="number" id="crawlMaxTokens" min="100" max="8000" placeholder="2000"><div class="hint">最大回复长度</div></div>
                </div>
                <button class="btn btn-primary" onclick="saveCrawlConfig()">保存爬取参数</button>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>8大AI平台 API Key 配置</h3></div>
                <table>
                    <thead><tr><th>平台</th><th>状态</th><th>API Key</th><th>模型</th><th>获取Key</th><th>操作</th></tr></thead>
                    <tbody id="apiConfigTbody"><tr><td colspan="6" class="empty-state">加载中...</td></tr></tbody>
                </table>
            </div>

            <!-- API 编辑弹窗 -->
            <div class="modal-overlay" id="apiEditModal">
                <div class="modal">
                    <h3 id="apiEditTitle">配置 API Key</h3>
                    <input type="hidden" id="editApiPkey">
                    <div class="form-group"><label>API Key</label><input type="text" id="editApiKey" placeholder="sk-xxxxxxxx"><div class="hint">直接输入 API Key，或使用 ${VAR} 引用环境变量</div></div>
                    <div class="form-group" id="secretKeyGroup" style="display:none"><label>Secret Key（文心一言需要）</label><input type="text" id="editSecretKey" placeholder="输入 Secret Key"></div>
                    <div class="form-group"><label>模型</label><input type="text" id="editModel" placeholder="deepseek-chat"></div>
                    <div class="form-group"><label>启用状态</label><select id="editEnabled"><option value="1">启用</option><option value="0">禁用</option></select></div>
                    <div class="form-actions">
                        <button class="btn btn-outline" onclick="closeApiEditModal()">取消</button>
                        <button class="btn btn-primary" onclick="saveApiConfig()">保存</button>
                    </div>
                </div>
            </div>
<?php endif; ?>

<?php if ($tab === 'screenshots'): ?>
            <div class="panel">
                <div class="panel-header"><h3>截图存档</h3></div>
                <div id="screenshotGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">
                    <div class="empty-state">加载中...</div>
                </div>
            </div>
<?php endif; ?>

<?php if ($tab === 'users'): ?>
            <?php if (is_admin()): ?>
            <div class="panel">
                <div class="panel-header">
                    <h3>后台用户</h3>
                    <button class="btn btn-primary" onclick="openUserModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        添加用户
                    </button>
                </div>
                <table>
                    <thead><tr><th>ID</th><th>用户名</th><th>角色</th><th>创建时间</th><th>操作</th></tr></thead>
                    <tbody id="userTbody"><tr><td colspan="5" class="empty-state">加载中...</td></tr></tbody>
                </table>
            </div>
            <div class="modal-overlay" id="userModal">
                <div class="modal">
                    <h3 id="userModalTitle">添加用户</h3>
                    <input type="hidden" id="editUserId">
                    <div class="form-group"><label>用户名 *</label><input type="text" id="userUsername" placeholder="登录用户名"></div>
                    <div class="form-group" id="userPasswordGroup"><label>密码 *</label><input type="password" id="userPassword" placeholder="至少4位"></div>
                    <div class="form-group"><label>角色</label><select id="userRole"><option value="editor">编辑（只读）</option><option value="admin">管理员（全部权限）</option></select></div>
                    <div class="form-actions">
                        <button class="btn btn-outline" onclick="closeUserModal()">取消</button>
                        <button class="btn btn-primary" onclick="saveUser()">保存</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header"><h3>前台会员</h3></div>
                <table>
                    <thead><tr><th>ID</th><th>用户名</th><th>所属企业</th><th>状态</th><th>注册时间</th><th>操作</th></tr></thead>
                    <tbody id="memberTbody"><tr><td colspan="6" class="empty-state">加载中...</td></tr></tbody>
                </table>
            </div>
            <div class="modal-overlay" id="memberModal">
                <div class="modal">
                    <h3>编辑会员</h3>
                    <input type="hidden" id="editMemberId">
                    <div class="form-group"><label>用户名</label><input type="text" id="memberUsername" disabled></div>
                    <div class="form-group"><label>所属企业</label><input type="text" id="memberCompany" placeholder="分配企业名称"></div>
                    <div class="form-group"><label>状态</label><select id="memberStatus"><option value="1">启用</option><option value="0">禁用</option></select></div>
                    <div class="form-actions">
                        <button class="btn btn-outline" onclick="closeMemberModal()">取消</button>
                        <button class="btn btn-primary" onclick="saveMember()">保存</button>
                    </div>
                </div>
            </div>
<?php endif; ?>


<?php if ($tab === 'settings'): ?>
            <div class="panel" style="max-width:500px">
                <h3 style="font-size:16px;font-weight:600;margin-bottom:20px">修改管理员密码</h3>
                <div class="form-group"><label>旧密码</label><input type="password" id="oldPassword" placeholder="输入当前密码"></div>
                <div class="form-group"><label>新密码</label><input type="password" id="newPassword" placeholder="至少4位"></div>
                <button class="btn btn-primary" onclick="changePassword()">修改密码</button>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>数据管理</h3></div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
                    <select id="clearCompanySelect" class="filter-select"><option value="0">全部公司</option></select>
                    <button class="btn btn-danger" onclick="clearReports()">清空报表数据</button>
                    <span style="font-size:12px;color:#9ca3af">清空后不可恢复，请谨慎操作</span>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>导出数据</h3></div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
                    <select id="exportCompanySelect" class="filter-select"><option value="0">全部公司</option></select>
                    <button class="btn btn-primary" onclick="exportCSV()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        导出 CSV
                    </button>
                </div>
            </div>


            <div class="panel">
                <h3 style="font-size:16px;font-weight:600;margin-bottom:12px">定时爬取</h3>
                <p style="font-size:13px;color:#6b7280;line-height:1.7;margin-bottom:12px">
                    在服务器上设置 cron 定时任务，实现每天自动查询 AI 平台收录情况。
                </p>
                <div style="background:#f3f4f6;border-radius:8px;padding:14px;font-family:monospace;font-size:12px;line-height:1.8;color:#374151">
                    # 每天 8:00、14:00、20:00 自动爬取 3 次<br>
                    0 8,14,20 * * * cd /Users/wangshifu/Desktop/wangchenyu/baobiao && python3 crawler_v4.py --crawl --json >> data/crawl_cron.log 2>&1
                </div>
            </div>
<?php endif; ?>
        </div></div>
    
    <div class="toast" id="toast"></div>
    
    <script>
    // CSRF: 自动为所有 POST fetch 请求附加 token
    const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const _origFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (options.method && options.method.toUpperCase() === 'POST') {
            if (typeof options.body === 'string' && !options.body.includes('_csrf=')) {
                options.body += '&_csrf=' + encodeURIComponent(_csrfToken);
            } else if (options.body instanceof URLSearchParams && !options.body.has('_csrf')) {
                options.body.append('_csrf', _csrfToken);
            } else if (options.body instanceof FormData && !options.body.has('_csrf')) {
                options.body.append('_csrf', _csrfToken);
            } else if (!options.body) {
                options.body = '_csrf=' + encodeURIComponent(_csrfToken);
            }
        }
        return _origFetch.call(this, url, options);
    };

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg; t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2000);
    }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ===== 全局：加载公司选项到各个下拉框 =====
    async function loadAllCompanyOptions() {
        const res = await fetch('api.php?action=get_companies').then(r => r.json());
        if (res.data) {
            const opts = res.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            const allOpts = '<option value="0">全部公司</option>' + opts;
            const els = ['reportCompanyFilter', 'keywordFilterCompany'];
            els.forEach(id => { const el = document.getElementById(id); if (el) el.innerHTML = allOpts; });
            const kwSel = document.getElementById('keywordCompany');
            if (kwSel) kwSel.innerHTML = opts;
        }
    }

    async function loadPlatformOptions() {
        const res = await fetch('../api.php?action=get_platforms').then(r => r.json());
        if (res.data) {
            const opts = res.data.map(p => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('');
            const sel = document.getElementById('reportPlatformFilter');
            if (sel) sel.innerHTML = '<option value="0">全部平台</option>' + opts;
        }
    }

    // ===== 报表查询 Tab =====
    let reportPage = 1, currentDetailId = 0;

    async function loadReports() {
        const company = document.getElementById('reportCompanyFilter').value;
        const platform = document.getElementById('reportPlatformFilter').value;
        const keyword = document.getElementById('reportSearch').value.trim();
        const body = new URLSearchParams({page: reportPage, platform, company, mobile: -1, question_sort: 0, keyword});
        const res = await fetch('../api.php?action=get_report', { method: 'POST', body }).then(r => r.json());
        if (res.code !== 1) { showToast(res.msg); return; }
        const d = res.data;
        const tbody = document.getElementById('reportTbody');
        if (d.list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="empty-state">暂无数据</td></tr>';
        } else {
            tbody.innerHTML = d.list.map(r => {
                const mc = r.matched ? 'yes' : 'no';
                const mt = r.matched ? (r.hit_names ? '已提及' : '已收录') : '未收录';
                const sn = r.response_snippet || '(无回复)';
                return `<tr style="cursor:pointer" onclick="openReportDetail(${r.id})">
                    <td style="font-size:12px;color:#6b7280">${escapeHtml(r.company_name||'')}</td>
                    <td style="font-weight:500"><span class="tag ${r.keyword_type==='manual'?'tag-manual':'tag-ai'}">${r.keyword_type==='manual'?'手动':'蒸馏'}</span>${escapeHtml(r.keyword)}</td>
                    <td style="font-size:12px;color:#4b5563;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(r.question)}</td>
                    <td>${escapeHtml(r.platform_name)}</td>
                    <td><span class="match-badge ${mc}"><span class="dot"></span>${mt}</span></td>
                    <td><span class="snippet-text">${escapeHtml(sn)}</span></td>
                    <td style="font-size:12px;color:#9ca3af">${r.shoulu_date||''}</td>
                    <td onclick="event.stopPropagation()" style="white-space:nowrap">
                        ${r.share_token ? `<button class="btn btn-success btn-sm" onclick="copyShareUrl('${r.share_token}')">复制分享</button>` : `<button class="btn btn-outline btn-sm" onclick="openReportDetail(${r.id})">分享</button>`}
                        <button class="btn btn-danger btn-sm" onclick="deleteReport(${r.id})" title="删除" style="margin-left:4px">×</button>
                    </td>
                </tr>`;
            }).join('');
        }
        // Pagination
        const pg = document.getElementById('reportPagination');
        if (d.count === 0) { pg.innerHTML = ''; return; }
        let h = '';
        if (reportPage > 1) h += `<a onclick="goReportPage(${reportPage-1})">上一页</a>`;
        else h += '<span class="disabled">上一页</span>';
        for (let i = 1; i <= d.all_page; i++) {
            h += i === reportPage ? `<span class="active">${i}</span>` : `<a onclick="goReportPage(${i})">${i}</a>`;
        }
        if (reportPage < d.all_page) h += `<a onclick="goReportPage(${reportPage+1})">下一页</a>`;
        else h += '<span class="disabled">下一页</span>';
        pg.innerHTML = h;
        // Update stats cards
        if (d.stats) {
            document.getElementById('statTotal').textContent = d.stats.total || 0;
            document.getElementById('statMatched').textContent = d.stats.matched || 0;
            document.getElementById('statUnmatched').textContent = d.stats.unmatched || 0;
            document.getElementById('statCompanies').textContent = d.stats.companies || 0;
            document.getElementById('statsRow').style.display = 'grid';
        } else {
            document.getElementById('statsRow').style.display = 'none';
        }
    }
    function goReportPage(p) { reportPage = p; loadReports(); }

    async function openReportDetail(id) {
        currentDetailId = id;
        document.getElementById('detailResponse').textContent = '加载中...';
        document.getElementById('shareUrlBox').innerHTML = '';
        document.getElementById('detailModal').classList.add('show');
        const res = await fetch('../api.php?action=get_report_detail&id=' + id).then(r => r.json());
        if (res.code === 1) {
            const d = res.data;
            document.getElementById('detailTitle').textContent = d.keyword + ' @ ' + d.platform_name;
            document.getElementById('detailQuestion').textContent = d.question;
            document.getElementById('detailResponse').textContent = d.response_text || '(暂无AI回复)';
            if (d.hit_names) {
                document.getElementById('detailHitSection').style.display = 'block';
                document.getElementById('detailHitNames').textContent = d.hit_names;
            } else {
                document.getElementById('detailHitSection').style.display = 'none';
            }
            document.getElementById('shareSection').style.display = 'block';
            if (d.share_token) {
                const url = window.location.origin + '/share/?t=' + d.share_token;
                document.getElementById('shareUrlBox').innerHTML = `<div class="share-url">${url} <button class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('${url}');showToast('已复制')">复制</button></div>`;
                document.getElementById('btnGenShare').textContent = '重新生成';
            } else {
                document.getElementById('btnGenShare').textContent = '生成分享链接';
            }
        }
    }
    function closeDetailModal() { document.getElementById('detailModal').classList.remove('show'); }
    const dmEl = document.getElementById('detailModal'); if (dmEl) dmEl.addEventListener('click', function(e) { if (e.target === this) closeDetailModal(); });

    async function generateShareLink() {
        if (currentDetailId <= 0) return;
        const body = new URLSearchParams({report_id: currentDetailId});
        const res = await fetch('../api.php?action=generate_share_token', { method: 'POST', body }).then(r => r.json());
        if (res.code === 1) {
            document.getElementById('shareUrlBox').innerHTML = `<div class="share-url">${res.data.url} <button class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('${res.data.url}');showToast('已复制')">复制</button></div>`;
            document.getElementById('btnGenShare').textContent = '重新生成';
            showToast('分享链接已生成');
            loadReports();
        } else showToast(res.msg);
    }

    function copyShareUrl(token) {
        const url = window.location.origin + '/share/?t=' + token;
        navigator.clipboard.writeText(url).then(() => showToast('已复制分享链接'));
    }

    async function deleteReport(id) {
        if (!confirm('确定删除这条报表记录？')) return;
        const res = await fetch('api.php?action=delete_report', { method: 'POST', body: new URLSearchParams({id}) }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) loadReports();
    }

    // ===== 爬虫 =====
    let crawlTimer = null;
    async function triggerCrawl() {
        const btn = document.getElementById('btnCrawl');
        btn.disabled = true;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> 启动中...';
        document.getElementById('crawlStatus').textContent = '正在启动...';
        document.getElementById('crawlProgress').style.display = 'block';
        const res = await fetch('../api.php?action=run_crawl', { method: 'POST' }).then(r => r.json());
        if (res.code === 1) {
            showToast(res.msg);
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot running"></span> 爬取中...';
            pollCrawlStatus();
        } else {
            showToast(res.msg);
            document.getElementById('crawlStatus').textContent = res.msg;
            document.getElementById('crawlProgress').style.display = 'none';
            resetCrawlBtn();
        }
    }
    async function pollCrawlStatus() {
        const res = await fetch('../api.php?action=get_crawl_status').then(r => r.json());
        if (res.code !== 1) return;
        const d = res.data;
        if (d.running) {
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot running"></span> 爬取中...';
            document.getElementById('crawlProgressBar').style.width = '60%';
            crawlTimer = setTimeout(pollCrawlStatus, 3000);
        } else if (d.result) {
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot done"></span> 完成';
            document.getElementById('crawlProgressBar').style.width = '100%';
            loadReports();
            resetCrawlBtn();
            setTimeout(() => {
                document.getElementById('crawlProgress').style.display = 'none';
                document.getElementById('crawlProgressBar').style.width = '0%';
                document.getElementById('crawlStatus').textContent = '就绪';
            }, 5000);
        } else {
            document.getElementById('crawlStatus').textContent = '就绪';
            document.getElementById('crawlProgress').style.display = 'none';
            resetCrawlBtn();
        }
    }
    function resetCrawlBtn() {
        const btn = document.getElementById('btnCrawl');
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg> 一键爬取';
    }

    // ===== 公司 CRUD =====
    async function loadCompanies() {
        const res = await fetch('api.php?action=get_companies').then(r => r.json());
        const tbody = document.getElementById('companyTbody');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">暂无公司数据</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(c => `
            <tr><td>${c.id}</td><td style="font-weight:500">${escapeHtml(c.name)}</td><td><span class="desc-text" title="${escapeHtml(c.description||'')}">${escapeHtml(c.description||'无')}</span></td><td style="font-size:12px;color:#6b7280">${escapeHtml(c.variants||'无')}</td><td style="font-size:12px;color:#9ca3af">${c.created_at||''}</td><td>
                <button class="btn btn-outline btn-sm" onclick="editCompany(${c.id},'${escapeHtml(c.name)}','${escapeHtml(c.description||'')}','${escapeHtml(c.variants||'')}')">编辑</button>
                <button class="btn btn-danger btn-sm" onclick="deleteCompany(${c.id})">删除</button>
            </td></tr>`).join('');
    }
    function openCompanyModal(data) {
        if (data) {
            document.getElementById('companyModalTitle').textContent = '编辑公司';
            document.getElementById('editCompanyId').value = data.id;
            document.getElementById('companyName').value = data.name;
            document.getElementById('companyDesc').value = data.desc;
            document.getElementById('companyVariants').value = data.variants;
        } else {
            document.getElementById('companyModalTitle').textContent = '添加公司';
            document.getElementById('editCompanyId').value = '';
            document.getElementById('companyName').value = '';
            document.getElementById('companyDesc').value = '';
            document.getElementById('companyVariants').value = '';
        }
        document.getElementById('companyModal').classList.add('show');
    }
    function closeCompanyModal() { document.getElementById('companyModal').classList.remove('show'); }
    const cmEl = document.getElementById('companyModal'); if (cmEl) cmEl.addEventListener('click', function(e) { if (e.target === this) closeCompanyModal(); });
    function editCompany(id, name, desc, variants) { openCompanyModal({id, name, desc, variants}); }
    async function saveCompany() {
        const id = document.getElementById('editCompanyId').value;
        const name = document.getElementById('companyName').value.trim();
        const desc = document.getElementById('companyDesc').value.trim();
        const variants = document.getElementById('companyVariants').value.trim();
        if (!name) { showToast('请输入公司名称'); return; }
        const action = id ? 'update_company' : 'add_company';
        const body = new URLSearchParams({name, description: desc, variants});
        if (id) body.append('id', id);
        const res = await fetch('api.php?action=' + action, { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) { closeCompanyModal(); loadCompanies(); loadAllCompanyOptions(); }
    }
    async function deleteCompany(id) {
        if (!confirm('确定删除该公司？关联关键词也会一并删除。')) return;
        const res = await fetch('api.php?action=delete_company', { method: 'POST', body: new URLSearchParams({id}) }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) { loadCompanies(); loadAllCompanyOptions(); }
    }

    // ===== API 配置 =====
    async function loadApiConfig() {
        const res = await fetch('api.php?action=get_api_config').then(r => r.json());
        if (res.code !== 1) return;
        const d = res.data;
        // Set crawl params
        document.getElementById('promptTemplate').value = d.crawl.prompt_template || '';
        document.getElementById('crawlTemperature').value = d.crawl.temperature || 0.7;
        document.getElementById('crawlMaxTokens').value = d.crawl.max_tokens || 2000;
        // Render platform table
        const apis = d.apis;
        const tbody = document.getElementById('apiConfigTbody');
        const pkeys = ['deepseek','kimi','qianwen','wenxin','zhipu','doubao','hunyuan','nano360'];
        tbody.innerHTML = pkeys.map(pk => {
            const a = apis[pk] || {};
            let statusIcon = '<span style="color:#d1d5db">● 已禁用</span>';
            if (a.enabled) {
                if (a.has_key) statusIcon = '<span style="color:#10b981;font-weight:500">● 已配置</span>';
                else if (a.is_env) statusIcon = '<span style="color:#6366f1">● 环境变量</span>';
                else statusIcon = '<span style="color:#f59e0b">● 待配置</span>';
            }
            const keyDisplay = a.api_key_display || '-';
            return `<tr>
                <td style="font-weight:600">${escapeHtml(a.name||pk)}</td>
                <td>${statusIcon}</td>
                <td style="font-family:monospace;font-size:12px">${escapeHtml(keyDisplay)}</td>
                <td style="font-size:12px">${escapeHtml(a.model||'-')}</td>
                <td><a href="${escapeHtml(a.key_url||'#')}" target="_blank" style="font-size:11px;color:#4f46e5">获取Key →</a></td>
                <td><button class="btn btn-outline btn-sm" onclick="openApiEdit('${pk}')">配置</button></td>
            </tr>`;
        }).join('');
    }

    function openApiEdit(pkey) {
        document.getElementById('editApiPkey').value = pkey;
        document.getElementById('editApiKey').value = '';
        document.getElementById('editSecretKey').value = '';
        document.getElementById('editModel').value = '';
        document.getElementById('editEnabled').value = '1';
        if (pkey === 'wenxin') document.getElementById('secretKeyGroup').style.display = 'block';
        else document.getElementById('secretKeyGroup').style.display = 'none';
        // Load current values
        fetch('api.php?action=get_api_config').then(r => r.json()).then(res => {
            if (res.code === 1 && res.data.apis[pkey]) {
                const a = res.data.apis[pkey];
                const name = a.name || pkey;
                document.getElementById('apiEditTitle').textContent = '配置 ' + name + ' API';
                document.getElementById('editModel').value = a.model || '';
                document.getElementById('editEnabled').value = a.enabled ? '1' : '0';
                // Show current key if exists
                if (a.has_key) {
                    document.getElementById('editApiKey').placeholder = a.api_key_display || '(已设置)';
                } else if (a.is_env) {
                    document.getElementById('editApiKey').placeholder = a.api_key_display || '(使用环境变量)';
                } else {
                    document.getElementById('editApiKey').placeholder = '输入 API Key';
                }
                if (a.secret_key_display) {
                    document.getElementById('editSecretKey').placeholder = a.secret_key_display;
                }
            }
        });
        document.getElementById('apiEditModal').classList.add('show');
    }

    function closeApiEditModal() { document.getElementById('apiEditModal').classList.remove('show'); }
    const aeEl = document.getElementById('apiEditModal'); if (aeEl) aeEl.addEventListener('click', function(e) { if (e.target === this) closeApiEditModal(); });

    async function saveApiConfig() {
        const pkey = document.getElementById('editApiPkey').value;
        const api_key = document.getElementById('editApiKey').value.trim();
        const secret_key = document.getElementById('editSecretKey').value.trim();
        const model = document.getElementById('editModel').value.trim();
        const enabled = document.getElementById('editEnabled').value;
        const body = new URLSearchParams({pkey, enabled});
        if (api_key) body.append('api_key', api_key);
        if (secret_key) body.append('secret_key', secret_key);
        if (model) body.append('model', model);
        const res = await fetch('api.php?action=save_api_config', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) { closeApiEditModal(); loadApiConfig(); }
    }

    async function saveCrawlConfig() {
        const temperature = document.getElementById('crawlTemperature').value;
        const max_tokens = document.getElementById('crawlMaxTokens').value;
        const prompt_template = document.getElementById('promptTemplate').value.trim();
        const body = new URLSearchParams({temperature, max_tokens, prompt_template, pkey: ''});
        const res = await fetch('api.php?action=save_api_config', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        loadApiConfig();
    }

    // ===== 关键词 CRUD =====
    async function loadKeywords() {
        const cid = document.getElementById('keywordFilterCompany').value;
        const res = await fetch('api.php?action=get_keywords&company_id=' + cid).then(r => r.json());
        const tbody = document.getElementById('keywordTbody');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">暂无关键词数据</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(k => `
            <tr><td>${k.id}</td><td style="font-weight:500">${escapeHtml(k.name)}</td><td><span class="tag ${k.type==='manual'?'tag-manual':'tag-ai'}">${k.type==='manual'?'手动':'蒸馏'}</span></td><td style="font-size:13px">${escapeHtml(k.company_name||'未绑定')}</td><td style="font-size:12px;color:#6b7280;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(k.query_text||'使用默认模板')}</td><td style="font-size:12px;color:#9ca3af">${k.created_at||''}</td><td>
                <button class="btn btn-outline btn-sm" onclick="editKeyword(${k.id},'${escapeHtml(k.name)}','${k.type}','${escapeHtml(k.query_text||'')}',${k.company_id||1})">编辑</button>
                <button class="btn btn-danger btn-sm" onclick="deleteKeyword(${k.id})">删除</button>
            </td></tr>`).join('');
    }
    function openKeywordModal(data) {
        if (data) {
            document.getElementById('kwModalTitle').textContent = '编辑关键词';
            document.getElementById('editKeywordId').value = data.id;
            document.getElementById('keywordName').value = data.name;
            document.getElementById('keywordType').value = data.type;
            document.getElementById('keywordQueryText').value = data.queryText;
            document.getElementById('keywordCompany').value = data.companyId;
        } else {
            document.getElementById('kwModalTitle').textContent = '添加关键词';
            document.getElementById('editKeywordId').value = '';
            document.getElementById('keywordName').value = '';
            document.getElementById('keywordType').value = 'manual';
            document.getElementById('keywordQueryText').value = '';
        }
        document.getElementById('keywordModal').classList.add('show');
    }
    function closeKeywordModal() { document.getElementById('keywordModal').classList.remove('show'); }
    const kmEl = document.getElementById('keywordModal'); if (kmEl) kmEl.addEventListener('click', function(e) { if (e.target === this) closeKeywordModal(); });
    function editKeyword(id, name, type, queryText, companyId) { openKeywordModal({id, name, type, queryText, companyId}); }
    async function saveKeyword() {
        const id = document.getElementById('editKeywordId').value;
        const name = document.getElementById('keywordName').value.trim();
        const type = document.getElementById('keywordType').value;
        const queryText = document.getElementById('keywordQueryText').value.trim();
        const companyId = document.getElementById('keywordCompany').value;
        if (!name) { showToast('请输入关键词'); return; }
        const action = id ? 'update_keyword' : 'add_keyword';
        const body = new URLSearchParams({name, type, query_text: queryText, company_id: companyId});
        if (id) body.append('id', id);
        const res = await fetch('api.php?action=' + action, { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) { closeKeywordModal(); loadKeywords(); }
    }
    async function deleteKeyword(id) {
        if (!confirm('确定删除该关键词？')) return;
        const res = await fetch('api.php?action=delete_keyword', { method: 'POST', body: new URLSearchParams({id}) }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) loadKeywords();
    }

    // ===== 系统设置 =====
    async function changePassword() {
        const oldPwd = document.getElementById('oldPassword').value;
        const newPwd = document.getElementById('newPassword').value;
        if (newPwd.length < 4) { showToast('新密码至少4位'); return; }
        const body = new URLSearchParams({old_password: oldPwd, new_password: newPwd});
        const res = await fetch('api.php?action=change_my_password', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) {
            document.getElementById('oldPassword').value = '';
            document.getElementById('newPassword').value = '';
            setTimeout(() => { window.location = 'login.php'; }, 1500);
        }
    }

    async function clearReports() {
        const company = document.getElementById('clearCompanySelect').value;
        const msg = company > 0 ? '确定清空该公司所有报表数据？此操作不可恢复！' : '确定清空所有报表数据？此操作不可恢复！';
        if (!confirm(msg)) return;
        const body = new URLSearchParams({company});
        const res = await fetch('api.php?action=clear_reports', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1 && document.getElementById('reportTbody')) loadReports();
    }

    function exportCSV() {
        const company = document.getElementById('exportCompanySelect').value;
        window.open('api.php?action=export_csv&company=' + company, '_blank');
    }

    async function loadSettingsCompanies() {
        const res = await fetch('api.php?action=get_companies').then(r => r.json());
        if (res.data) {
            const opts = res.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            const allOpts = '<option value="0">全部公司</option>' + opts;
            ['clearCompanySelect', 'exportCompanySelect'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = allOpts;
            });
        }
    }

    // ===== 截图浏览 =====
    async function loadScreenshots() {
        const res = await fetch('api.php?action=get_screenshots').then(r => r.json());
        const grid = document.getElementById('screenshotGrid');
        if (!res.data || res.data.length === 0) {
            grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1">暂无截图，爬取后自动生成</div>';
            return;
        }
        grid.innerHTML = res.data.map(s => `
            <div style="background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;cursor:pointer;transition:all 0.2s" 
                 onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'"
                 onclick="window.open('${s.url}','_blank')">
                <img src="${s.url}" style="width:100%;height:160px;object-fit:cover;display:block" loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22160%22><rect fill=%22%23f3f4f6%22 width=%22200%22 height=%22160%22/><text fill=%22%239ca3af%22 x=%22100%22 y=%2285%22 text-anchor=%22middle%22 font-size=%2212%22>加载失败</text></svg>'">
                <div style="padding:10px 12px">
                    <div style="font-size:12px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escapeHtml(s.name)}">${escapeHtml(s.name)}</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:3px">${s.time} · ${(s.size/1024).toFixed(1)}KB</div>
                </div>
            </div>`).join('');
    }

    // ===== 用户管理 =====
    async function loadUsers() {
        const res = await fetch('api.php?action=get_users').then(r => r.json());
        const tbody = document.getElementById('userTbody');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state">暂无用户</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(u => `
            <tr>
                <td>${u.id}</td>
                <td style="font-weight:500">${escapeHtml(u.username)}</td>
                <td><span style="font-size:12px;padding:2px 10px;border-radius:10px;background:${u.role==='admin'?'#d1fae5':'#fef3c7'};color:${u.role==='admin'?'#065f46':'#b45309'}">${u.role==='admin'?'管理员':'编辑'}</span></td>
                <td style="font-size:12px;color:#9ca3af">${u.created_at||''}</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="editUser(${u.id},'${escapeHtml(u.username)}','${u.role}')">编辑</button>
                    <button class="btn btn-outline btn-sm" onclick="resetUserPwd(${u.id},'${escapeHtml(u.username)}')">重置密码</button>
                    ${u.id > 1 ? '<button class="btn btn-danger btn-sm" onclick="deleteUser('+u.id+')">删除</button>' : ''}
                </td>
            </tr>`).join('');
    }

    function openUserModal(data) {
        if (data) {
            document.getElementById('userModalTitle').textContent = '编辑用户 ' + escapeHtml(data.username);
            document.getElementById('editUserId').value = data.id;
            document.getElementById('userUsername').value = data.username;
            document.getElementById('userUsername').disabled = true;
            document.getElementById('userPasswordGroup').style.display = 'none';
            document.getElementById('userRole').value = data.role;
        } else {
            document.getElementById('userModalTitle').textContent = '添加用户';
            document.getElementById('editUserId').value = '';
            document.getElementById('userUsername').value = '';
            document.getElementById('userUsername').disabled = false;
            document.getElementById('userPasswordGroup').style.display = 'block';
            document.getElementById('userPassword').value = '';
            document.getElementById('userRole').value = 'editor';
        }
        document.getElementById('userModal').classList.add('show');
    }

    function closeUserModal() { document.getElementById('userModal').classList.remove('show'); }
    if (document.getElementById('userModal')) {
        document.getElementById('userModal').addEventListener('click', function(e) { if (e.target === this) closeUserModal(); });
    }

    function editUser(id, username, role) { openUserModal({id, username, role}); }

    async function saveUser() {
        const id = document.getElementById('editUserId').value;
        const username = document.getElementById('userUsername').value.trim();
        const password = document.getElementById('userPassword').value;
        const role = document.getElementById('userRole').value;
        if (id) {
            const body = new URLSearchParams({id, role});
            const res = await fetch('api.php?action=update_user', { method: 'POST', body }).then(r => r.json());
            showToast(res.msg);
            if (res.code === 1) { closeUserModal(); loadUsers(); }
        } else {
            if (!username || password.length < 4) { showToast('用户名和密码（至少4位）不能为空'); return; }
            const body = new URLSearchParams({username, password, role});
            const res = await fetch('api.php?action=add_user', { method: 'POST', body }).then(r => r.json());
            showToast(res.msg);
            if (res.code === 1) { closeUserModal(); loadUsers(); }
        }
    }

    async function resetUserPwd(id, username) {
        const pwd = prompt('为 ' + username + ' 设置新密码（至少4位）：');
        if (!pwd || pwd.length < 4) { showToast('密码至少4位'); return; }
        const body = new URLSearchParams({id, password: pwd});
        const res = await fetch('api.php?action=reset_user_password', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
    }

    async function deleteUser(id) {
        if (!confirm('确定删除该用户？')) return;
        const body = new URLSearchParams({id});
        const res = await fetch('api.php?action=delete_user', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) loadUsers();
    }

    // ===== 会员管理 =====
    async function loadMembers() {
        const res = await fetch('api.php?action=get_members').then(r => r.json());
        const tbody = document.getElementById('memberTbody');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">暂无会员</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(m => {
            let statusHtml = '';
            if (m.status == 0) statusHtml = '<span style="color:#f59e0b;font-weight:500">⏳ 待审核</span>';
            else if (m.status == 1) statusHtml = '<span style="color:#10b981">● 已启用</span>';
            else statusHtml = '<span style="color:#ef4444">○ 已拒绝</span>';
            
            let actions = '';
            if (m.status == 0) {
                actions = `<button class="btn btn-success btn-sm" onclick="approveMember(${m.id})">批准</button>
                    <button class="btn btn-danger btn-sm" onclick="rejectMember(${m.id})">拒绝</button>`;
            } else {
                actions = `<button class="btn btn-outline btn-sm" onclick="editMember(${m.id},'${escapeHtml(m.username)}','${escapeHtml(m.company||'')}',${m.status})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMember(${m.id})">删除</button>`;
            }
            
            return `<tr>
                <td>${m.id}</td>
                <td style="font-weight:500">${escapeHtml(m.username)}</td>
                <td>${escapeHtml(m.company||'未绑定')}</td>
                <td>${statusHtml}</td>
                <td style="font-size:12px;color:#9ca3af">${m.created_at||''}</td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }

    function editMember(id, username, company, status) {
        document.getElementById('editMemberId').value = id;
        document.getElementById('memberUsername').value = username;
        document.getElementById('memberCompany').value = company;
        document.getElementById('memberStatus').value = status;
        document.getElementById('memberModal').classList.add('show');
    }
    function closeMemberModal() { document.getElementById('memberModal').classList.remove('show'); }
    if (document.getElementById('memberModal')) {
        document.getElementById('memberModal').addEventListener('click', function(e) { if (e.target === this) closeMemberModal(); });
    }

    async function saveMember() {
        const id = document.getElementById('editMemberId').value;
        const company = document.getElementById('memberCompany').value.trim();
        const status = document.getElementById('memberStatus').value;
        const body = new URLSearchParams({id, company, status});
        const res = await fetch('api.php?action=update_member', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) { closeMemberModal(); loadMembers(); }
    }

    async function approveMember(id) {
        const company = prompt('请为此会员分配企业名称：');
        if (company === null) return;
        const body = new URLSearchParams({id, company});
        const res = await fetch('api.php?action=approve_member', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) loadMembers();
    }

    async function rejectMember(id) {
        if (!confirm('确定拒绝该会员？')) return;
        const body = new URLSearchParams({id});
        const res = await fetch('api.php?action=reject_member', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) loadMembers();
    }

    async function deleteMember(id) {
        if (!confirm('确定删除该会员？')) return;
        const body = new URLSearchParams({id});
        const res = await fetch('api.php?action=delete_member', { method: 'POST', body }).then(r => r.json());
        showToast(res.msg);
        if (res.code === 1) loadMembers();
    }

    async function loadPendingCount() {
        try {
            const res = await fetch('api.php?action=member_pending_count').then(r => r.json());
            if (res.code === 1 && res.data.count > 0) {
                const badge = document.getElementById('pendingBadge');
                if (badge) { badge.textContent = res.data.count; badge.style.display = 'inline'; }
            }
        } catch(e) {}
    }

    // ===== Init =====
    loadAllCompanyOptions();
    loadPlatformOptions();
    <?php if ($tab === 'reports'): ?>
    loadReports();
    // Check crawl status on load
    (async function() {
        const res = await fetch('../api.php?action=get_crawl_status').then(r => r.json());
        if (res.code === 1 && res.data.running) {
            document.getElementById('crawlStatus').innerHTML = '<span class="status-dot running"></span> 爬取中...';
            document.getElementById('crawlProgress').style.display = 'block';
            document.getElementById('crawlProgressBar').style.width = '30%';
            document.getElementById('btnCrawl').disabled = true;
            pollCrawlStatus();
        }
    })();
    <?php elseif ($tab === 'companies'): ?>
    loadCompanies();
    <?php elseif ($tab === 'keywords'): ?>
    loadKeywords();
    <?php elseif ($tab === 'apiconfig'): ?>
    loadApiConfig();
    <?php elseif ($tab === 'screenshots'): ?>
    loadScreenshots();
    <?php elseif ($tab === 'users'): ?>
    loadUsers();
    loadMembers();
    loadPendingCount();
    <?php elseif ($tab === 'settings'): ?>
    loadSettingsCompanies();
    <?php endif; ?>

    // Logout
    <?php if (isset($_GET['logout'])): ?>
    fetch('logout.php').then(() => { window.location = 'login.php'; });
    <?php endif; ?>
    </script>

    <div class="app-footer">GEO 报表系统 &copy; 2026 &nbsp;|&nbsp; <a href="http://www.wangchenyu.com" target="_blank">王尘宇</a> &nbsp;|&nbsp; 西安蓝蜻蜓网络科技有限公司 &nbsp;|&nbsp; <a href="https://github.com/iwangchenyu/wangchenyu-geo-editor" target="_blank">GitHub</a></div>
</body>
</html>
