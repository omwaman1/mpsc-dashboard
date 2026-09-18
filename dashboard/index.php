<?php
session_start();
define('DASHBOARD_PASSWORD', 'omwman');

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['dashboard_auth']);
    session_destroy();
    header('Location: index.php');
    exit;
}

$authError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_password'])) {
    if ($_POST['dashboard_password'] === DASHBOARD_PASSWORD) {
        $_SESSION['dashboard_auth'] = true;
        header('Location: index.php');
        exit;
    } else {
        $authError = 'पासवर्ड चुकीचा आहे! (Invalid Password)';
    }
}

$isAuthenticated = !empty($_SESSION['dashboard_auth']) && $_SESSION['dashboard_auth'] === true;

if (!$isAuthenticated):
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPSC Abhyas Admin Dashboard | Lock Screen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #090d16;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .login-card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 32px 28px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            text-align: center;
        }
        .lock-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            font-size: 26px;
            color: white;
            box-shadow: 0 10px 25px rgba(37,99,235,0.4);
        }
        .title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 6px;
            color: white;
        }
        .subtitle {
            font-size: 12.5px;
            color: #94a3b8;
            margin-bottom: 24px;
        }
        .input-group {
            margin-bottom: 18px;
            text-align: left;
        }
        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
        }
        .input-box {
            width: 100%;
            background: #1e293b;
            border: 1.5px solid #334155;
            border-radius: 10px;
            padding: 12px 14px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.15s ease;
        }
        .input-box:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .btn-unlock {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            margin-bottom: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="lock-icon"><i class="fa-solid fa-lock"></i></div>
        <h1 class="title">Dashboard Protected</h1>
        <p class="subtitle">एमपीएससी अभ्यास - Admin Access Lock</p>

        <?php if (!empty($authError)): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($authError); ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="input-group">
                <label class="input-label">Secret Password (गुप्त पासवर्ड)</label>
                <input type="password" name="dashboard_password" class="input-box" placeholder="Enter password..." required autofocus>
            </div>
            <button type="submit" class="btn-unlock"><i class="fa-solid fa-key"></i> Unlock Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPSC Subject & Topic Explorer Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0f172a;        /* Dark Slate 900 */
            --bg-canvas: #090d16;      /* Solid Neutral Canvas */
            --bg-card: #1e293b;        /* Slate 800 */
            --bg-card-hover: #334155;  /* Slate 700 */
            --border-color: #334155;    /* Crisp Solid Border */
            --border-highlight: #3b82f6;
            
            --primary: #2563eb;
            --accent-cyan: #0284c7;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #ef4444;
            
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            
            --sidebar-width: 320px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            font-size: 13px;
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* COMPACT CMS HEADER */
        header {
            min-height: 56px;
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo {
            width: 34px;
            height: 34px;
            background: #2563eb;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .brand-title {
            font-size: 16px;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.3px;
        }

        .brand-badge {
            font-size: 11px;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #10b981;
            border-radius: 50%;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .stats-summary {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #1e293b;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #334155;
            font-size: 12px;
            color: #94a3b8;
        }

        .stat-item i {
            color: #38bdf8;
        }

        .stat-val {
            font-weight: 700;
            color: #f8fafc;
        }

        .btn-action {
            background: #2563eb;
            color: white;
            border: 1px solid #3b82f6;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s ease;
        }

        .btn-action:hover {
            background: #1d4ed8;
        }

        .btn-jobs {
            background: #d97706;
            border-color: #f59e0b;
        }
        .btn-jobs:hover { background: #b45309; }

        .btn-bulk {
            background: #059669;
            border-color: #10b981;
        }
        .btn-bulk:hover { background: #047857; }

        /* MAIN LAYOUT */
        .app-container {
            display: flex;
            flex: 1;
            min-height: calc(100vh - 56px);
        }

        /* SIDEBAR (Compact CMS Tree) */
        .sidebar {
            width: 320px;
            min-width: 320px;
            flex-shrink: 0;
            background: #0f172a;
            border-right: 1px solid #1e293b;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 10px 12px;
            border-bottom: 1px solid #1e293b;
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 12px;
        }

        .search-input {
            width: 100%;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 7px 10px 7px 32px;
            color: #f8fafc;
            font-size: 12.5px;
            outline: none;
        }

        .search-input:focus {
            border-color: #3b82f6;
        }

        .subject-tree-container {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        /* TREE ITEM STYLES */
        .tree-subject-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            margin-bottom: 6px;
            overflow: hidden;
        }

        .tree-subject-header {
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
            transition: background 0.15s ease;
        }

        .tree-subject-header:hover {
            background: #334155;
        }

        .tree-subject-header.active {
            background: #334155;
            border-left: 3px solid #38bdf8;
        }

        .subject-info {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
        }

        .sub-icon {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #38bdf8;
            flex-shrink: 0;
        }

        .sub-names {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sub-title-e {
            font-weight: 600;
            font-size: 12.5px;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sub-title-m {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tree-badges {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-count {
            font-size: 10.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            background: #0f172a;
            color: #94a3b8;
            border: 1px solid #334155;
        }

        .btn-sub-sync {
            background: rgba(6, 182, 212, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(6, 182, 212, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .btn-sub-sync:hover {
            background: #0284c7;
            color: white;
        }

        .toggle-arrow {
            font-size: 10px;
            color: #64748b;
            transition: transform 0.15s ease;
        }

        .toggle-arrow.open {
            transform: rotate(90deg);
        }

        /* RECURSIVE TREE NODES */
        .tree-children {
            display: none;
            padding-left: 10px;
            border-left: 1px solid #334155;
            margin-left: 16px;
            margin-bottom: 6px;
        }

        .tree-children.open {
            display: block;
        }

        .tree-topic-item {
            padding: 5px 8px;
            margin: 2px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-size: 12px;
            color: #cbd5e1;
        }

        .tree-topic-item:hover {
            background: #334155;
            color: white;
        }

        .tree-topic-item.active {
            background: #2563eb;
            color: white;
            font-weight: 600;
        }

        /* MAIN CONTENT AREA */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding: 16px;
            gap: 14px;
            background: #020617;
        }

        /* TOPICS HORIZONTAL STRIP */
        .topics-section {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 10px 14px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .topics-scroll {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .topic-chip {
            background: #1e293b;
            border: 1px solid #334155;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #cbd5e1;
            white-space: nowrap;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .topic-chip:hover {
            background: #334155;
            color: white;
        }

        .topic-chip.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            font-weight: 600;
        }

        /* CONTROLS BAR */
        .controls-bar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .question-search-box {
            position: relative;
            flex: 1;
        }

        .question-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .question-search-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 9px 12px 9px 36px;
            color: white;
            font-size: 13px;
            outline: none;
        }

        .question-search-input:focus {
            border-color: #3b82f6;
        }

        /* CMS QUESTION CARDS */
        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .question-card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: border-color 0.15s ease;
        }

        .question-card:hover {
            border-color: #3b82f6;
        }

        .q-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 8px;
        }

        .q-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .q-id-badge {
            font-family: monospace;
            font-weight: 700;
            font-size: 12px;
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid rgba(56, 189, 248, 0.25);
        }

        .lang-badge-btn {
            font-size: 11px;
            font-weight: 600;
            background: #1e293b;
            color: #38bdf8;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #334155;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .lang-badge-btn:hover {
            background: #2563eb;
            color: white;
        }

        .q-tag {
            font-size: 11px;
            font-weight: 600;
            background: #1e293b;
            color: #f59e0b;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #334155;
        }

        .q-text {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.55;
            color: #f8fafc;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .option-item {
            background: #1e293b;
            border: 1px solid #334155;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #cbd5e1;
        }

        .option-item.correct {
            background: rgba(16, 185, 129, 0.12);
            border-color: #10b981;
            color: #34d399;
            font-weight: 600;
        }

        .opt-num {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0;
            color: #f8fafc;
        }

        .option-item.correct .opt-num {
            background: #10b981;
            color: white;
        }

        .solution-box {
            background: rgba(6, 182, 212, 0.08);
            border-left: 3px solid #0284c7;
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            font-size: 12.5px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        /* PAGINATION */
        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
        }

        .page-info {
            font-size: 12.5px;
            color: #94a3b8;
        }

        .page-buttons {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-page {
            background: #1e293b;
            border: 1px solid #334155;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn-page:hover:not(:disabled) {
            background: #2563eb;
            border-color: #2563eb;
        }

        .btn-page:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-page.active {
            background: #2563eb;
            border-color: #2563eb;
            font-weight: 700;
        }

        /* LOADING SPINNER */
        .loading-state {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            font-size: 13px;
        }

        .loading-spinner {
            font-size: 24px;
            color: #38bdf8;
            margin-bottom: 8px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 16px;
            width: 100%;
            max-width: 860px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            overflow: hidden;
            box-sizing: border-box;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 16px 20px;
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 22px;
            cursor: pointer;
            line-height: 1;
            padding: 4px;
        }
        .modal-close:hover { color: white; }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            background: #090d16;
            border-top: 1px solid #1e293b;
            padding: 14px 20px;
            flex-shrink: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-control {
            width: 100%;
            box-sizing: border-box;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 9px 12px;
            color: #f8fafc;
            font-size: 13px;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }

        /* WORDPRESS STYLE LEFT NAVIGATION BAR */
        .wp-nav-bar {
            width: 220px;
            min-width: 220px;
            background: #090d16;
            border-right: 1px solid #1e293b;
            display: flex;
            flex-direction: column;
            padding: 10px 0;
            flex-shrink: 0;
            user-select: none;
            overflow-y: auto;
        }

        .wp-nav-title {
            padding: 10px 14px 4px 14px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
        }

        .wp-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            color: #94a3b8;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .wp-nav-item:hover {
            background: #1e293b;
            color: #f8fafc;
        }

        .wp-nav-item.active {
            background: #1e293b;
            color: #38bdf8;
            border-left-color: #38bdf8;
            font-weight: 600;
        }

        .wp-nav-item i {
            width: 16px;
            text-align: center;
            font-size: 13px;
        }

        .wp-table-container {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            overflow: hidden;
        }

        .wp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            text-align: left;
        }

        .wp-table th {
            background: #1e293b;
            color: #38bdf8;
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 1px solid #334155;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .wp-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #1e293b;
            color: #e2e8f0;
            vertical-align: middle;
        }

        .wp-table tr:hover td {
            background: #1e293b;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header>
        <div class="brand">
            <div class="brand-logo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div class="brand-title">MPSC Master Engine</div>
                <div style="font-size: 11px; color: var(--text-dim);">TiDB Cloud Multi-Level Explorer</div>
            </div>
            <div class="brand-badge">
                <div class="pulse-dot"></div>
                Live TiDB Sync
            </div>
        </div>

        <div class="header-right">
            <div class="stats-summary">
                <div class="stat-item">
                    <i class="fa-solid fa-book"></i>
                    <span>Subjects: <span class="stat-val" id="stat-subjects">--</span></span>
                </div>
                <div class="stat-item">
                    <i class="fa-solid fa-folder-tree"></i>
                    <span>Topics: <span class="stat-val" id="stat-topics">--</span></span>
                </div>
                <div class="stat-item">
                    <i class="fa-solid fa-clipboard-question"></i>
                    <span>Questions: <span class="stat-val" id="stat-questions">--</span></span>
                </div>
                <div class="stat-item">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Jobs: <span class="stat-val" id="stat-jobs">--</span></span>
                </div>
            </div>

            <button class="btn-action btn-jobs" onclick="window.location.href='jobs.php'">
                <i class="fa-solid fa-briefcase"></i> 💼 Job Recruitments Portal
            </button>
            <button class="btn-action btn-bulk" onclick="openBulkModal()">
                <i class="fa-solid fa-file-import"></i> Bulk Import
            </button>
            <button class="btn-action" onclick="openAddQuestionModal()">
                <i class="fa-solid fa-plus"></i> Add Question
            </button>
            <button class="btn-action" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);" onclick="window.location.href='index.php?action=logout'">
                <i class="fa-solid fa-right-from-bracket"></i> Lock Dashboard
            </button>
        </div>
    </header>

    <!-- CONTAINER -->
    <div class="app-container" style="display: flex;">
        
        <!-- WORDPRESS LEFT SIDEBAR NAVIGATION -->
        <div class="wp-nav-bar">
            <div class="wp-nav-title">Navigation Menu</div>
            <div class="wp-nav-item active" id="nav-item-explorer" onclick="switchWpTab('explorer')">
                <i class="fa-solid fa-folder-tree" style="color: var(--primary);"></i>
                <span>Question Bank</span>
            </div>
            <div class="wp-nav-item" id="nav-item-test-series" onclick="switchWpTab('test-series')">
                <i class="fa-solid fa-list-check" style="color: var(--accent-emerald);"></i>
                <span>Test Series</span>
            </div>
            <div class="wp-nav-item" id="nav-item-exams" onclick="switchWpTab('exams')">
                <i class="fa-solid fa-graduation-cap" style="color: var(--accent-cyan);"></i>
                <span>Manage Exams</span>
            </div>
            <div class="wp-nav-item" id="nav-item-contacts" onclick="switchWpTab('contacts')">
                <i class="fa-solid fa-address-book" style="color: #ec4899;"></i>
                <span>User Contacts (1,702+)</span>
            </div>
            <div class="wp-nav-item" id="nav-item-users" onclick="switchWpTab('users')">
                <i class="fa-solid fa-users-gear" style="color: #8b5cf6;"></i>
                <span>App Users</span>
            </div>
            <div class="wp-nav-item" id="nav-item-notifications" onclick="switchWpTab('notifications')">
                <i class="fa-solid fa-bell" style="color: #f59e0b;"></i>
                <span>Push Notifications</span>
            </div>
            <div class="wp-nav-item" id="nav-item-jobs" onclick="openRecruitmentsModal()">
                <i class="fa-solid fa-briefcase" style="color: var(--accent-amber);"></i>
                <span>Job Recruitments</span>
            </div>

            <div class="wp-nav-title" style="margin-top: 16px;">Quick Actions</div>
            <div class="wp-nav-item" onclick="openCreateTestSeriesModal()">
                <i class="fa-solid fa-square-plus" style="color: var(--accent-emerald);"></i>
                <span>+ New Test Series</span>
            </div>
            <div class="wp-nav-item" onclick="openBulkModal()">
                <i class="fa-solid fa-file-import" style="color: var(--accent-cyan);"></i>
                <span>Bulk Import Qs</span>
            </div>
            <div class="wp-nav-item" onclick="openAddQuestionModal()">
                <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i>
                <span>Add Question</span>
            </div>
            <a class="wp-nav-item" href="db_diagnostic.php" target="_blank">
                <i class="fa-solid fa-database" style="color: var(--accent-rose);"></i>
                <span>DB Diagnostic</span>
            </a>
        </div>

        <!-- VIEW 1: QUESTION BANK EXPLORER -->
        <div id="wp-view-explorer" style="display: flex; flex: 1; overflow: hidden;">
            <!-- SIDEBAR WITH RECURSIVE MULTI-LEVEL HIERARCHY TREE -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="subject-search" class="search-input" placeholder="Search subjects & topics...">
                    </div>
                </div>

                <div class="subject-tree-container" id="subject-list-container">
                    <div class="loading-state">
                        <i class="fa-solid fa-circle-notch loading-spinner"></i>
                        <p>Loading Hierarchy Tree...</p>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="main-content">

                <!-- UNIFIED MODERN EXPLORER TOOLBAR CARD -->
                <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 16px; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                    
                    <!-- HEADER ROW: SUBJECT TITLE & SCOPE BADGE -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 36px; height: 36px; background: rgba(37, 99, 235, 0.15); border: 1px solid rgba(37, 99, 235, 0.3); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 16px;">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <h3 id="current-subject-title" style="font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: #f8fafc; margin: 0; line-height: 1.2;">All Subjects</h3>
                                <span id="topic-count-label" style="font-size: 11.5px; color: #94a3b8;">-- Topics</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <div id="search-scope-badge">
                                <span style="background: rgba(255,255,255,0.08); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 12px;">
                                    <i class="fa-solid fa-globe"></i> Scope: Global (All Subjects)
                                </span>
                            </div>
                            <button onclick="resetSearchScope()" style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <i class="fa-solid fa-rotate-left"></i> Reset Filters
                            </button>
                        </div>
                    </div>

                    <!-- DUAL DROPDOWN FILTER BAR -->
                    <div style="display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;">
                        <!-- SUBJECT DROPDOWN -->
                        <div style="flex: 1; min-width: 200px;">
                            <select id="filter-subject-select" onchange="handleSubjectFilterSelect(this.value)" style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 9px 12px; color: #f8fafc; font-size: 12.5px; outline: none; cursor: pointer; font-weight: 500;">
                                <option value="0">📚 All Subjects (Global Search)</option>
                            </select>
                        </div>

                        <!-- TOPIC DROPDOWN -->
                        <div style="flex: 1; min-width: 200px;">
                            <select id="filter-topic-select" onchange="handleTopicFilterSelect(this.value)" style="width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 9px 12px; color: #f8fafc; font-size: 12.5px; outline: none; cursor: pointer; font-weight: 500;">
                                <option value="0">🏷️ All Topics</option>
                            </select>
                        </div>
                    </div>

                    <!-- TOPIC CHIPS SCROLL BAR -->
                    <div class="topics-scroll" id="topics-scroll-container" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 12px;">
                        <div class="topic-chip active" onclick="selectTopic(0)">
                            <span>All Topics</span>
                        </div>
                    </div>

                    <!-- INTEGRATED SEARCH BAR & BUTTON -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="question-search-box" style="flex: 1; position: relative; width: 100%;">
                            <i class="fa-solid fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 13px;"></i>
                            <input type="text" id="question-search" class="question-search-input" placeholder="Search Marathi or English words, options, or Question ID..." oninput="handleSearchInput(this)" onkeydown="handleSearchKeyDown(event)" style="width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px 38px 10px 38px; color: #f8fafc; font-size: 13px; outline: none;">
                            <button id="btn-clear-search" onclick="clearQuestionSearch()" style="display: none; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; font-size: 16px;">&times;</button>
                        </div>
                        <button class="btn-action" onclick="triggerManualSearch()" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer; border: none; white-space: nowrap;">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>

                </div>

                <!-- QUESTIONS LIST -->
                <div class="questions-list" id="questions-container">
                    <div class="loading-state">
                        <i class="fa-solid fa-circle-notch loading-spinner"></i>
                        <p>Loading Questions...</p>
                    </div>
                </div>

                <!-- PAGINATION -->
                <div class="pagination-container">
                    <div class="page-info" id="pagination-info">
                        Showing 0 of 0 questions
                    </div>
                    <div class="page-buttons" id="pagination-buttons">
                        <!-- Dynamic Buttons -->
                    </div>
                </div>

            </div>
        </div>

        <!-- VIEW 2: TEST SERIES MANAGER -->
        <div id="wp-view-test-series" style="display: none; flex: 1; padding: 24px; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; color: white; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-list-check" style="color: var(--accent-emerald);"></i> Test Series Manager
                    </h2>
                    <p style="font-size: 13px; color: var(--text-dim); margin-top: 4px;">Create, edit, publish and link question bank IDs for mobile app test series</p>
                </div>
                <button class="btn-action" onclick="openCreateTestSeriesModal()" style="background: linear-gradient(135deg, #10b981, #059669); font-weight: 600; padding: 10px 18px; border-radius: 10px;">
                    <i class="fa-solid fa-plus"></i> Create New Test Series
                </button>
            </div>

            <div class="wp-table-container">
                <table class="wp-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Test Series Name</th>
                            <th>Price</th>
                            <th>Attempts Limit</th>
                            <th>Question Count</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="test-series-table-body">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 3: MANAGE EXAMS -->
        <div id="wp-view-exams" style="display: none; flex: 1; padding: 24px; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; color: white; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-graduation-cap" style="color: var(--accent-cyan);"></i> Exam Categories Manager
                    </h2>
                    <p style="font-size: 13px; color: var(--text-dim); margin-top: 4px;">Add, edit, or delete exam categories for question papers and test series</p>
                </div>
                <button class="btn-action" onclick="openCreateExamModal()" style="background: linear-gradient(135deg, #0284c7, #0369a1); font-weight: 600; padding: 10px 18px; border-radius: 10px;">
                    <i class="fa-solid fa-plus"></i> Add New Exam Category
                </button>
            </div>

            <div class="wp-table-container">
                <table class="wp-table">
                    <thead>
                        <tr>
                            <th>Exam ID</th>
                            <th>Exam Category Name</th>
                            <th>Exam Code</th>
                            <th>Question Count</th>
                            <th>App Visibility Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="exams-table-body">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 4: USER CONTACTS MANAGER -->
        <div id="wp-view-contacts" style="display: none; flex: 1; padding: 24px; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; color: white; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-address-book" style="color: #ec4899;"></i> User Contacts Manager
                    </h2>
                    <p style="font-size: 13px; color: var(--text-dim); margin-top: 4px;">View, search, filter, and export all 1,702+ user synced contacts</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-action" onclick="exportContactsCSV()" style="background: linear-gradient(135deg, #10b981, #059669); font-weight: 600; padding: 10px 18px; border-radius: 10px;">
                        <i class="fa-solid fa-file-csv"></i> Export Contacts CSV
                    </button>
                </div>
            </div>

            <!-- SEARCH & FILTER BAR -->
            <div style="display: flex; gap: 12px; margin-bottom: 16px; align-items: center; background: #1e293b; padding: 14px; border-radius: 12px; border: 1px solid #334155;">
                <div style="flex: 1; position: relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 12px; color: #64748b;"></i>
                    <input type="text" id="contacts-search-input" placeholder="Search contact name, phone (+91...), or user email..." onkeyup="handleContactsSearchKey(event)" style="width: 100%; padding: 10px 14px 10px 40px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 13px;">
                </div>
                <button class="btn-action" onclick="loadDashboardContacts(1)" style="background: #2563eb; padding: 10px 20px; border-radius: 8px;">
                    Search
                </button>
            </div>

            <!-- CONTACTS TABLE -->
            <div class="wp-table-container">
                <table class="wp-table">
                    <thead>
                        <tr>
                            <th># ID</th>
                            <th>Contact Name</th>
                            <th>Contact Phone Number</th>
                            <th>Synced By User Email</th>
                            <th>Sync Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="contacts-table-body">
                        <!-- Dynamic Contact Rows -->
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION BAR -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; color: var(--text-dim); font-size: 13px;" id="contacts-pagination-bar">
                <!-- Pagination Info -->
            </div>
        </div>

        <!-- VIEW 5: APP USERS & PRO PASS MANAGER -->
        <div id="wp-view-users" style="display: none; flex: 1; padding: 24px; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; color: white; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-users-gear" style="color: #8b5cf6;"></i> App Registered Users & Pass Access
                    </h2>
                    <p style="font-size: 13px; color: var(--text-dim); margin-top: 4px;">View registered students, phone numbers, and manage subscription access</p>
                </div>
            </div>

            <div class="wp-table-container">
                <table class="wp-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Phone Number</th>
                            <th>Registration Date</th>
                            <th>Subscription Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <!-- Dynamic User Rows -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 6: PUSH NOTIFICATIONS MANAGER -->
        <div id="wp-view-notifications" style="display: none; flex: 1; padding: 24px; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 22px; color: white; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-bell" style="color: #f59e0b;"></i> Push Notifications Broadcast
                    </h2>
                    <p style="font-size: 13px; color: var(--text-dim); margin-top: 4px;">Send and broadcast push announcements to all MPSC ABHYAS students</p>
                </div>
            </div>

            <!-- SEND NOTIFICATION FORM -->
            <div style="background: #1e293b; padding: 20px; border-radius: 12px; border: 1px solid #334155; margin-bottom: 24px; max-width: 650px;">
                <h3 style="color: white; font-size: 15px; margin-bottom: 14px;"><i class="fa-solid fa-paper-plane" style="color: #f59e0b;"></i> Publish New Notification</h3>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Title (शीर्षक)</label>
                    <input type="text" id="notif-title" placeholder="e.g. 📢 नवीन राज्यसेवा सराव चाचणी जोडली आहे!" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 13px;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Message (माहिती / संदेश)</label>
                    <textarea id="notif-message" rows="3" placeholder="उदा. आजच ५० नवीन प्रश्न सोडवा आणि आपली ऑल महाराष्ट्र रँक तपासा..." style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 13px;"></textarea>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; color: #94a3b8; font-size: 12px; margin-bottom: 4px;">Alert Type</label>
                    <select id="notif-type" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 13px;">
                        <option value="alert">🔔 Alert / Update</option>
                        <option value="exam">🏛️ Exam Notification</option>
                        <option value="test">📝 Test Series</option>
                    </select>
                </div>
                <button class="btn-action" onclick="sendDashboardNotification()" style="background: linear-gradient(135deg, #f59e0b, #d97706); font-weight: 600; padding: 10px 24px; border-radius: 8px;">
                    Broadcast Notification
                </button>
            </div>

            <!-- RECENT NOTIFICATIONS TABLE -->
            <div class="wp-table-container">
                <table class="wp-table">
                    <thead>
                        <tr>
                            <th># ID</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Published Date</th>
                        </tr>
                    </thead>
                    <tbody id="notifications-table-body">
                        <!-- Dynamic Notification Rows -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- CREATE / EDIT TEST SERIES MODAL -->
    <div class="modal-overlay" id="testSeriesModal">
        <div class="modal-card" style="max-width: 650px;">
            <div class="modal-header">
                <div class="modal-title" id="ts-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Create New Test Series</div>
                <button class="modal-close" onclick="closeTestSeriesModal()">&times;</button>
            </div>
            <form id="testSeriesForm" onsubmit="submitTestSeriesForm(event)">
                <input type="hidden" id="ts-id" value="0">
                <div class="form-group">
                    <label class="form-label">Test Series Name / Title *</label>
                    <input type="text" class="form-control" id="ts-title" placeholder="e.g. MPSC Rajyaseva Prelims 2026 Full Test Series" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-graduation-cap" style="color: var(--accent-cyan);"></i> Target Exam Category *</label>
                        <select class="form-control" id="ts-exam-name">
                            <option value="">-- Loading Exam Categories... --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="ts-price" placeholder="499" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Attempts Allowed</label>
                    <input type="number" class="form-control" id="ts-max-attempts" placeholder="10" value="10">
                </div>
                <div class="form-group" style="background: #1e293b; padding: 10px; border-radius: 6px; border: 1px solid #334155;">
                    <label class="form-label" style="color: #38bdf8;"><i class="fa-solid fa-wand-magic-sparkles"></i> Quick Question Auto-Linker</label>
                    <div style="display: flex; gap: 8px;">
                        <select class="form-control" id="ts-helper-subject" style="flex: 1;">
                            <option value="0">-- Select Subject to Append Questions --</option>
                            <option value="2">History (इतिहास)</option>
                            <option value="3">Polity (भारतीय राज्यघटना)</option>
                            <option value="4">Geography (भूगोल)</option>
                            <option value="5">Economics (अर्थव्यवस्था)</option>
                            <option value="6">General Science (सामान्य विज्ञान)</option>
                            <option value="12">Computers & IT (संगणक व माहिती तंत्रज्ञान)</option>
                            <option value="13">Laws & Acts (कायदे)</option>
                            <option value="14">Current Affairs (चालू घडामोडी)</option>
                        </select>
                        <button type="button" class="btn-action" onclick="autoLinkSubjectQuestions()" style="background: #0284c7; white-space: nowrap;">
                            <i class="fa-solid fa-link"></i> Append QIDs
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Question Bank IDs (Comma-separated)</label>
                    <textarea class="form-control" id="ts-question-ids" rows="4" placeholder="e.g. 1, 2, 3, 4, 5, 12, 15, 20..."></textarea>
                    <small style="color: var(--text-dim); font-size: 11px;">Enter question IDs separated by commas, or use the quick auto-linker above.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Publishing Status</label>
                    <select class="form-control" id="ts-published">
                        <option value="1">Published (Visible in Mobile App)</option>
                        <option value="0">Draft / Unpublished</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" class="btn-action" onclick="closeTestSeriesModal()" style="background: rgba(255,255,255,0.1);">Cancel</button>
                    <button type="submit" class="btn-action" style="background: var(--primary); font-weight: 600;">Save Test Series</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE / EDIT EXAM MODAL -->
    <div class="modal-overlay" id="examModal">
        <div class="modal-card" style="max-width: 500px;">
            <div class="modal-header">
                <div class="modal-title" id="exam-modal-title"><i class="fa-solid fa-graduation-cap" style="color: var(--accent-cyan);"></i> Add New Exam Category</div>
                <button class="modal-close" onclick="closeExamModal()">&times;</button>
            </div>
            <form id="examForm" onsubmit="submitExamForm(event)">
                <input type="hidden" id="exam-id" value="0">
                <div class="form-group">
                    <label class="form-label">Exam Category Name *</label>
                    <input type="text" class="form-control" id="exam-name-input" placeholder="e.g. Van Seva Prelims (वनसेवा पूर्व)" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Exam Short Code</label>
                    <input type="text" class="form-control" id="exam-code-input" placeholder="e.g. VAN_SEVA">
                    <small style="color: var(--text-dim); font-size: 11px;">Short code identifier used internally.</small>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-action" onclick="closeExamModal()" style="flex: 1; background: #334155;">Cancel</button>
                    <button type="submit" class="btn-action" style="flex: 1; background: var(--primary);">Save Exam Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RECRUITMENTS / JOB NOTIFICATIONS MODAL -->
    <div class="modal-overlay" id="recruitmentsModal">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title"><i class="fa-solid fa-bullhorn" style="color: var(--accent-amber);"></i> MPSC & Govt Job Recruitments (Bilingual Marathi & English)</div>
                <button class="modal-close" onclick="closeRecruitmentsModal()">&times;</button>
            </div>
            <div id="recruitments-list-container">
                <div class="loading-state">
                    <i class="fa-solid fa-circle-notch loading-spinner"></i>
                    <p>Loading Recruitment Postings...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- BULK IMPORT MODAL -->
    <div class="modal-overlay" id="bulkImportModal">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title"><i class="fa-solid fa-file-import" style="color: var(--accent-emerald);"></i> Bulk Import Questions (Bilingual JSON Array)</div>
                <button class="modal-close" onclick="closeBulkModal()">&times;</button>
            </div>
            <form id="bulkImportForm" onsubmit="submitBulkQuestions(event)">
                <div class="form-group">
                    <label class="form-label">Default Subject for Imported Questions</label>
                    <select class="form-control" id="bulk-default-subject">
                        <option value="2">History (इतिहास)</option>
                        <option value="3">Polity (भारतीय राज्यघटना)</option>
                        <option value="4">Geography (भूगोल)</option>
                        <option value="5">Economics (अर्थव्यवस्था)</option>
                        <option value="6">General Science (सामान्य विज्ञान)</option>
                        <option value="12">Computers & IT (संगणक व माहिती तंत्रज्ञान)</option>
                        <option value="13">Laws & Acts (कायदे)</option>
                        <option value="14">Current Affairs (चालू घडामोडी)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Paste JSON Array of Question Objects (Marathi & English Supported)</label>
                    <textarea class="form-control" id="bulk-json-input" rows="9" placeholder='[
  {
    "subjectID": 2,
    "topicID": 57,
    "questionName": "ब्रिटिश ईस्ट इंडिया कंपनीची स्थापना कोणत्या वर्षी झाली?",
    "questionNameE": "In which year was the British East India Company established?",
    "queOption1": "1600",
    "queOption2": "1757",
    "queOption3": "1857",
    "queOption4": "1947",
    "queOption1E": "1600",
    "queOption2E": "1757",
    "queOption3E": "1857",
    "queOption4E": "1947",
    "correctAnswer": "1",
    "solutionText": "31 डिसेंबर 1600 रोजी कंपनीला सनद मिळाली.",
    "solutionTextE": "The charter was granted on 31st December 1600."
  }
]' required></textarea>
                </div>
                <button type="submit" class="btn-action btn-bulk" style="width: 100%; justify-content: center; margin-top: 8px;">
                    <i class="fa-solid fa-upload"></i> Import Bilingual Questions in Bulk
                </button>
            </form>
        </div>
    </div>

    <!-- ADD BILINGUAL QUESTION MODAL -->
    <div class="modal-overlay" id="addQuestionModal">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title"><i class="fa-solid fa-circle-plus" style="color: var(--primary);"></i> Add Question (Marathi & English)</div>
                <button class="modal-close" onclick="closeAddQuestionModal()">&times;</button>
            </div>
            <form id="addQuestionForm" onsubmit="submitNewQuestion(event)" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-book" style="color: var(--primary);"></i> Subject</label>
                            <select class="form-control" id="modal-subject-id" name="subjectID" onchange="onAddModalSubjectChange()">
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-folder-tree" style="color: var(--accent-cyan);"></i> Topic / Subtopic</label>
                            <select class="form-control" id="modal-topic-id" name="topicID">
                                <option value="0">-- General Subject Question (No Topic) --</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-link" style="color:var(--primary);"></i> Assign Test Series</label>
                            <select class="form-control" id="modal-test-series" name="test_series_id">
                                <option value="0">-- General Database Question --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-graduation-cap" style="color: var(--accent-cyan);"></i> Exam Category</label>
                            <select class="form-control" id="modal-exam-category" name="examName">
                                <option value="Rajyaseva Prelims (राज्यसेवा पूर्व)">Rajyaseva Prelims (राज्यसेवा पूर्व)</option>
                                <option value="ASO (सहायक कक्ष अधिकारी)">ASO (सहायक कक्ष अधिकारी)</option>
                                <option value="Rajyaseva Mains (राज्यसेवा मुख्य)">Rajyaseva Mains (राज्यसेवा मुख्य)</option>
                                <option value="Combine Group B Prelims (गट ब पूर्व)">Combine Group B Prelims (गट ब पूर्व)</option>
                                <option value="Combine Group B Mains (गट ब मुख्य)">Combine Group B Mains (गट ब मुख्य)</option>
                                <option value="Combine Group C Prelims (गट क पूर्व)">Combine Group C Prelims (गट क पूर्व)</option>
                                <option value="Combine Group C Mains (गट क मुख्य)">Combine Group C Mains (गट क मुख्य)</option>
                                <option value="STI (राज्य कर निरीक्षक)">STI (राज्य कर निरीक्षक)</option>
                                <option value="PSI (पोलीस उपनिरीक्षक)">PSI (पोलीस उपनिरीक्षक)</option>
                                <option value="Clerk Typist (क्लार्क टायपिस्ट)">Clerk Typist (क्लार्क टायपिस्ट)</option>
                                <option value="Excise Sub Inspector (राज्य उत्पादन शुल्क)">Excise Sub Inspector (राज्य उत्पादन शुल्क)</option>
                                <option value="Tax Assistant (कर सहायक)">Tax Assistant (कर सहायक)</option>
                                <option value="MPSC Subordinate Services">MPSC Subordinate Services</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-calendar-days" style="color: var(--accent-amber);"></i> Exam Year</label>
                            <select class="form-control" id="modal-exam-year" name="examYear">
                                <option value="2026">2026</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                                <option value="2021">2021</option>
                                <option value="2020">2020</option>
                                <option value="2019">2019</option>
                                <option value="2018">2018</option>
                                <option value="2017">2017</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Question Text (Marathi)</label>
                            <textarea class="form-control" id="modal-qtext" name="questionName" rows="3" placeholder="मराठी प्रश्न..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Question Text (English Translation)</label>
                            <textarea class="form-control" id="modal-qtext-e" name="questionNameE" rows="3" placeholder="English Question Text..."></textarea>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group">
                            <label class="form-label">Option 1 (Marathi)</label>
                            <input type="text" class="form-control" id="modal-opt1" name="queOption1" placeholder="पर्याय १..." required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 1 (English)</label>
                            <input type="text" class="form-control" id="modal-opt1-e" name="queOption1E" placeholder="Option 1 English...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Option 2 (Marathi)</label>
                            <input type="text" class="form-control" id="modal-opt2" name="queOption2" placeholder="पर्याय २..." required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 2 (English)</label>
                            <input type="text" class="form-control" id="modal-opt2-e" name="queOption2E" placeholder="Option 2 English...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Option 3 (Marathi)</label>
                            <input type="text" class="form-control" id="modal-opt3" name="queOption3" placeholder="पर्याय ३..." required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 3 (English)</label>
                            <input type="text" class="form-control" id="modal-opt3-e" name="queOption3E" placeholder="Option 3 English...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Option 4 (Marathi)</label>
                            <input type="text" class="form-control" id="modal-opt4" name="queOption4" placeholder="पर्याय ४..." required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 4 (English)</label>
                            <input type="text" class="form-control" id="modal-opt4-e" name="queOption4E" placeholder="Option 4 English...">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Correct Answer</label>
                            <select class="form-control" id="modal-correct-ans" name="correctAnswer">
                                <option value="1">Option 1 (पर्याय १)</option>
                                <option value="2">Option 2 (पर्याय २)</option>
                                <option value="3">Option 3 (पर्याय ३)</option>
                                <option value="4">Option 4 (पर्याय ४)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Solution / Explanation (Marathi)</label>
                            <textarea class="form-control" id="modal-sol" name="solutionText" rows="2" placeholder="स्पष्टीकरण..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Solution / Explanation (English)</label>
                            <textarea class="form-control" id="modal-sol-e" name="solutionTextE" rows="2" placeholder="English Explanation..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeAddQuestionModal()" style="background: rgba(255,255,255,0.08);">Cancel</button>
                    <button type="submit" class="btn-action" style="background: #2563eb;">
                        <i class="fa-solid fa-plus"></i> Save Question
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT QUESTION MODAL -->
    <div class="modal-overlay" id="editQuestionModal">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--accent-amber);"></i> Edit Question & Explanations (Marathi & English)</div>
                <button class="modal-close" onclick="closeEditQuestionModal()">&times;</button>
            </div>
            <form id="editQuestionForm" onsubmit="saveQuestionEdit(event)" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                <input type="hidden" id="edit-q-id">

                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-book" style="color: var(--primary);"></i> Subject</label>
                            <select class="form-control" id="edit-subject-id" name="subjectID" onchange="onEditModalSubjectChange()">
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-folder-tree" style="color: var(--accent-cyan);"></i> Topic / Subtopic</label>
                            <select class="form-control" id="edit-topic-id" name="topicID">
                                <option value="0">-- General Subject Question (No Topic) --</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Question Text (Marathi)</label>
                            <textarea class="form-control" id="edit-q-name-m" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Question Text (English)</label>
                            <textarea class="form-control" id="edit-q-name-e" rows="3"></textarea>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Option 1 (Marathi)</label>
                            <input type="text" class="form-control" id="edit-opt1-m">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 1 (English)</label>
                            <input type="text" class="form-control" id="edit-opt1-e">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Option 2 (Marathi)</label>
                            <input type="text" class="form-control" id="edit-opt2-m">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 2 (English)</label>
                            <input type="text" class="form-control" id="edit-opt2-e">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Option 3 (Marathi)</label>
                            <input type="text" class="form-control" id="edit-opt3-m">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 3 (English)</label>
                            <input type="text" class="form-control" id="edit-opt3-e">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Option 4 (Marathi)</label>
                            <input type="text" class="form-control" id="edit-opt4-m">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option 4 (English)</label>
                            <input type="text" class="form-control" id="edit-opt4-e">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-link" style="color:var(--primary);"></i> Assign / Link to Test Series (Optional)</label>
                            <select class="form-control" id="edit-test-series">
                                <option value="0">-- Do Not Link to Additional Test Series --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Correct Option Answer</label>
                            <select class="form-control" id="edit-correct-ans">
                                <option value="1">Option 1</option>
                                <option value="2">Option 2</option>
                                <option value="3">Option 3</option>
                                <option value="4">Option 4</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-graduation-cap" style="color: var(--accent-cyan);"></i> Exam Category</label>
                            <select class="form-control" id="edit-exam-category">
                                <option value="">-- Select Exam Category --</option>
                                <option value="ASO (सहायक कक्ष अधिकारी)">ASO (सहायक कक्ष अधिकारी)</option>
                                <option value="Rajyaseva Prelims (राज्यसेवा पूर्व)">Rajyaseva Prelims (राज्यसेवा पूर्व)</option>
                                <option value="Rajyaseva Mains (राज्यसेवा मुख्य)">Rajyaseva Mains (राज्यसेवा मुख्य)</option>
                                <option value="Combine Group B Prelims (गट ब पूर्व)">Combine Group B Prelims (गट ब पूर्व)</option>
                                <option value="Combine Group B Mains (गट ब मुख्य)">Combine Group B Mains (गट ब मुख्य)</option>
                                <option value="Combine Group C Prelims (गट क पूर्व)">Combine Group C Prelims (गट क पूर्व)</option>
                                <option value="Combine Group C Mains (गट क मुख्य)">Combine Group C Mains (गट क मुख्य)</option>
                                <option value="STI (राज्य कर निरीक्षक)">STI (राज्य कर निरीक्षक)</option>
                                <option value="PSI (पोलीस उपनिरीक्षक)">PSI (पोलीस उपनिरीक्षक)</option>
                                <option value="Clerk Typist (क्लार्क टायपिस्ट)">Clerk Typist (क्लार्क टायपिस्ट)</option>
                                <option value="Excise Sub Inspector (राज्य उत्पादन शुल्क)">Excise Sub Inspector (राज्य उत्पादन शुल्क)</option>
                                <option value="Tax Assistant (कर सहायक)">Tax Assistant (कर सहायक)</option>
                                <option value="MPSC Subordinate Services">MPSC Subordinate Services</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-calendar-days" style="color: var(--accent-amber);"></i> Exam Year</label>
                            <select class="form-control" id="edit-exam-year">
                                <option value="2026">2026</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                                <option value="2021">2021</option>
                                <option value="2020">2020</option>
                                <option value="2019">2019</option>
                                <option value="2018">2018</option>
                                <option value="2017">2017</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Solution Explanation (Marathi)</label>
                            <textarea class="form-control" id="edit-sol-m" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Solution Explanation (English)</label>
                            <textarea class="form-control" id="edit-sol-e" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeEditQuestionModal()" style="background: rgba(255,255,255,0.08);">Cancel</button>
                    <button type="submit" class="btn-action" style="background: var(--primary);">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT ENGINE -->
    <script>
        let currentSubjectId = 0;
        let currentTopicId = 0;
        let currentPage = 1;
        let totalPages = 1;
        let currentSearch = '';
        let globalSubjects = [];
        let cardLangState = {}; // qID => 'en' | 'mr'
        let jobRecruits = [];

        document.addEventListener('DOMContentLoaded', () => {
            fetchStats();
            fetchSubjectsAndBuildTree();
            fetchQuestions();

            // Search inputs setup
            let searchTimeout = null;
            const searchInput = document.getElementById('question-search');
            const clearBtn = document.getElementById('btn-clear-search');

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const val = e.target.value;
                    if (clearBtn) clearBtn.style.display = val.length > 0 ? 'block' : 'none';

                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        currentSearch = val.trim();
                        currentPage = 1;
                        fetchQuestions();
                    }, 200);
                });
            }

            const subSearch = document.getElementById('subject-search');
            if (subSearch) {
                subSearch.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    filterSubjectTree(query);
                });
            }
        });

        function handleSearchKeyDown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerManualSearch();
            }
        }

        function triggerManualSearch() {
            const val = document.getElementById('question-search').value;
            currentSearch = val.trim();
            currentPage = 1;
            fetchQuestions();
        }

        function clearQuestionSearch() {
            const input = document.getElementById('question-search');
            const clearBtn = document.getElementById('btn-clear-search');
            if (input) input.value = '';
            if (clearBtn) clearBtn.style.display = 'none';
            currentSearch = '';
            currentPage = 1;
            fetchQuestions();
        }

        // 1. STATS
        async function fetchStats() {
            try {
                const res = await fetch('api.php?action=stats');
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('stat-subjects').innerText = json.data.total_subjects;
                    document.getElementById('stat-topics').innerText = json.data.total_topics;
                    document.getElementById('stat-questions').innerText = json.data.total_questions.toLocaleString();
                    document.getElementById('stat-jobs').innerText = json.data.total_recruitments || 0;
                }
            } catch (err) {
                console.error("Stats Error:", err);
            }
        }

        // 2. SUBJECT & TOPIC TREE ENGINE
        async function fetchSubjectsAndBuildTree() {
            try {
                const res = await fetch('api.php?action=tree');
                const json = await res.json();
                if (json.status === 'success') {
                    globalSubjects = json.data;
                    renderSubjectTree(globalSubjects);
                }
            } catch (err) {
                document.getElementById('subject-list-container').innerHTML = `<p style="color: var(--accent-rose);">Failed to load subject tree.</p>`;
            }
        }

        function renderSubjectTree(subjects) {
            const container = document.getElementById('subject-list-container');
            container.innerHTML = '';

            subjects.forEach(sub => {
                const card = document.createElement('div');
                card.className = 'tree-subject-card';
                card.id = `sub-card-${sub.subjectID}`;

                const header = document.createElement('div');
                header.className = `tree-subject-header ${currentSubjectId == sub.subjectID ? 'active' : ''}`;
                
                header.innerHTML = `
                    <div class="subject-info" onclick="selectSubject(${sub.subjectID}, '${escapeJsQuotes(sub.subjectNameE)}')">
                        <div class="sub-icon"><i class="fa-solid fa-book-bookmark"></i></div>
                        <div class="sub-names">
                            <div class="sub-title-e">${escapeHtml(sub.subjectNameE)}</div>
                            <div class="sub-title-m">${escapeHtml(sub.subjectNameM)}</div>
                        </div>
                    </div>
                    <div class="tree-badges">
                        <span class="badge-count">${sub.question_count.toLocaleString()} Qs</span>
                        <button class="btn-sub-sync" onclick="event.stopPropagation(); syncSingleSubject(${sub.subjectID}, this)">
                            <i class="fa-solid fa-rotate"></i> Sync
                        </button>
                        <i class="fa-solid fa-chevron-right toggle-arrow" id="arrow-${sub.subjectID}" onclick="toggleTreeBranch(${sub.subjectID}, event)"></i>
                    </div>
                `;

                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'tree-children';
                childrenContainer.id = `branch-${sub.subjectID}`;

                if (sub.topics_tree && sub.topics_tree.length > 0) {
                    renderRecursiveNodes(childrenContainer, sub.topics_tree);
                }

                card.appendChild(header);
                card.appendChild(childrenContainer);
                container.appendChild(card);
            });

            populateFilterSubjectDropdown();
        }

        function renderRecursiveNodes(container, nodes) {
            nodes.forEach(node => {
                const item = document.createElement('div');
                item.className = `tree-topic-item ${currentTopicId == node.topicID ? 'active' : ''}`;
                item.id = `topic-node-${node.topicID}`;
                
                const titleText = node.topicNameM || node.topicNameE;
                const hasChildren = node.children && node.children.length > 0;

                item.innerHTML = `
                    <div style="display:flex; align-items:center; gap:8px;" onclick="selectTopic(${node.topicID})">
                        <i class="fa-regular fa-folder" style="font-size:12px; color:var(--accent-amber);"></i>
                        <span>${escapeHtml(titleText)}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="font-size:11px; color:var(--text-dim);">${node.question_count} Qs</span>
                        ${hasChildren ? `<i class="fa-solid fa-chevron-right toggle-arrow" onclick="toggleSubNode(${node.topicID}, event)"></i>` : ''}
                    </div>
                `;

                container.appendChild(item);

                if (hasChildren) {
                    const childBox = document.createElement('div');
                    childBox.className = 'tree-children';
                    childBox.id = `branch-node-${node.topicID}`;
                    renderRecursiveNodes(childBox, node.children);
                    container.appendChild(childBox);
                }
            });
        }

        function populateFilterSubjectDropdown() {
            const sel = document.getElementById('filter-subject-select');
            if (!sel) return;
            let html = '<option value="0">📚 All Subjects (Global Search)</option>';
            globalSubjects.forEach(sub => {
                const name = sub.subjectNameE || sub.subjectNameM;
                const isSel = (currentSubjectId == sub.subjectID) ? 'selected' : '';
                html += `<option value="${sub.subjectID}" ${isSel}>${escapeHtml(name)}</option>`;
            });
            sel.innerHTML = html;
        }

        function populateFilterTopicDropdown(subjectId, selectedTopicId = 0) {
            const sel = document.getElementById('filter-topic-select');
            if (!sel) return;
            let html = '<option value="0">🏷️ All Topics</option>';
            if (subjectId && subjectId > 0) {
                const sub = globalSubjects.find(s => s.subjectID == subjectId);
                if (sub && sub.topics) {
                    sub.topics.forEach(top => {
                        const isSel = (selectedTopicId == top.topicID) ? 'selected' : '';
                        html += `<option value="${top.topicID}" ${isSel}>${escapeHtml(top.topicName)}</option>`;
                    });
                }
            }
            sel.innerHTML = html;
        }

        function handleSubjectFilterSelect(subId) {
            subId = parseInt(subId) || 0;
            if (subId === 0) {
                resetSearchScope();
            } else {
                const sub = globalSubjects.find(s => s.subjectID == subId);
                const name = sub ? (sub.subjectNameE || sub.subjectNameM) : 'Subject';
                selectSubject(subId, name);
            }
        }

        function handleTopicFilterSelect(topicId) {
            topicId = parseInt(topicId) || 0;
            selectTopic(topicId);
        }

        function toggleTreeBranch(subId, e) {
            if (e) e.stopPropagation();
            const body = document.getElementById(`sub-body-${subId}`);
            if (body) {
                body.classList.toggle('open');
            }
        }

        // 3. SELECT SUBJECT / TOPIC & SEARCH SCOPE ENGINE
        function updateSearchPlaceholder() {
            const input = document.getElementById('question-search');
            const scopeBadge = document.getElementById('search-scope-badge');
            if (!input) return;

            if (currentTopicId > 0) {
                const topicElem = document.getElementById(`topic-node-${currentTopicId}`);
                const topicName = topicElem ? topicElem.querySelector('span').innerText : 'Selected Topic';
                input.placeholder = `Search ONLY within Topic: "${topicName}"...`;
                if (scopeBadge) scopeBadge.innerHTML = `<span style="background: rgba(6,182,212,0.2); color: var(--accent-cyan); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa-solid fa-filter"></i> Topic: ${escapeHtml(topicName)} <i class="fa-solid fa-xmark" style="cursor:pointer; margin-left:6px;" onclick="resetSearchScope()" title="Reset to Global Search"></i></span>`;
            } else if (currentSubjectId > 0) {
                const subObj = globalSubjects.find(s => s.subjectID == currentSubjectId);
                const subName = subObj ? (subObj.subjectNameE || subObj.subjectNameM) : 'Selected Subject';
                input.placeholder = `Search ONLY within Subject: "${subName}"...`;
                if (scopeBadge) scopeBadge.innerHTML = `<span style="background: rgba(168,85,247,0.2); color: #c084fc; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa-solid fa-filter"></i> Subject: ${escapeHtml(subName)} <i class="fa-solid fa-xmark" style="cursor:pointer; margin-left:6px;" onclick="resetSearchScope()" title="Reset to Global Search"></i></span>`;
            } else {
                input.placeholder = `Search ANY question in database by entering any keyword or question ID (English or Marathi)...`;
                if (scopeBadge) scopeBadge.innerHTML = `<span style="background: rgba(255,255,255,0.08); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 12px;"><i class="fa-solid fa-globe"></i> Scope: Global (All Subjects)</span>`;
            }
        }

        function resetSearchScope() {
            currentSubjectId = 0;
            currentTopicId = 0;
            currentPage = 1;
            document.querySelectorAll('.tree-subject-header').forEach(h => h.classList.remove('active'));
            document.querySelectorAll('.tree-topic-item').forEach(i => i.classList.remove('active'));
            document.getElementById('current-subject-title').innerText = 'All Subjects';
            
            const subSel = document.getElementById('filter-subject-select');
            if (subSel) subSel.value = '0';
            populateFilterTopicDropdown(0, 0);

            updateSearchPlaceholder();
            fetchQuestions();
        }

        function selectSubject(subId, subName) {
            currentSubjectId = subId;
            currentTopicId = 0;
            currentPage = 1;

            document.querySelectorAll('.tree-subject-header').forEach(h => h.classList.remove('active'));
            const selHeader = document.querySelector(`#sub-card-${subId} .tree-subject-header`);
            if (selHeader) selHeader.classList.add('active');

            toggleTreeBranch(subId);

            document.getElementById('current-subject-title').innerText = subName;
            populateTopicStripForSubject(subId);
            updateSearchPlaceholder();
            fetchQuestions();
        }

        function selectTopic(topicId) {
            currentTopicId = topicId;
            currentPage = 1;

            document.querySelectorAll('.topic-chip').forEach(c => c.classList.remove('active'));
            const chip = document.getElementById(`chip-${topicId}`);
            if (chip) chip.classList.add('active');

            document.querySelectorAll('.tree-topic-item').forEach(i => i.classList.remove('active'));
            const node = document.getElementById(`topic-node-${topicId}`);
            if (node) node.classList.add('active');

            updateSearchPlaceholder();
            fetchQuestions();
        }

        function cleanOptionText(str) {
            if (!str) return '';
            let cleaned = str.trim();
            cleaned = cleaned.replace(/^<p[^>]*>/i, '').replace(/<\/p>$/i, '').trim();
            cleaned = cleaned.replace(/<\/?p[^>]*>/gi, '');
            return cleaned;
        }

        function setCorrectAnsSelectValue(rawVal) {
            const sel = document.getElementById('edit-correct-ans');
            if (!sel) return;
            const str = (rawVal || '1').toString().trim().toLowerCase();
            
            let targetVal = '1';
            if (['1', '2', '3', '4'].includes(str)) {
                targetVal = str;
            } else if (str.includes('1') || str.includes('a') || str.includes('option 1')) {
                targetVal = '1';
            } else if (str.includes('2') || str.includes('b') || str.includes('option 2')) {
                targetVal = '2';
            } else if (str.includes('3') || str.includes('c') || str.includes('option 3')) {
                targetVal = '3';
            } else if (str.includes('4') || str.includes('d') || str.includes('option 4')) {
                targetVal = '4';
            }
            
            sel.value = targetVal;
        }

        // 5. EDIT QUESTION MODAL ENGINE
        async function openEditQuestionModal(qid) {
            populateAllTestSeriesDropdowns();
            populateModalSubjectDropdowns();
            try {
                const res = await fetch(`api.php?action=get_question_by_id&question_id=${qid}`);
                const json = await res.json();
                if (json.status === 'success') {
                    const q = json.data;
                    document.getElementById('edit-q-id').value = q.questionID || '';
                    
                    if (q.subjectID && document.getElementById('edit-subject-id')) {
                        document.getElementById('edit-subject-id').value = q.subjectID;
                        populateTopicOptionsForSubject(q.subjectID, 'edit-topic-id', q.topicID || 0);
                    }

                    document.getElementById('edit-q-name-m').value = q.questionName || '';
                    document.getElementById('edit-q-name-e').value = q.questionNameE || '';
                    
                    document.getElementById('edit-opt1-m').value = cleanOptionText(q.queOption1 || '');
                    document.getElementById('edit-opt1-e').value = cleanOptionText(q.queOption1E || '');
                    document.getElementById('edit-opt2-m').value = cleanOptionText(q.queOption2 || '');
                    document.getElementById('edit-opt2-e').value = cleanOptionText(q.queOption2E || '');
                    document.getElementById('edit-opt3-m').value = cleanOptionText(q.queOption3 || '');
                    document.getElementById('edit-opt3-e').value = cleanOptionText(q.queOption3E || '');
                    document.getElementById('edit-opt4-m').value = cleanOptionText(q.queOption4 || '');
                    document.getElementById('edit-opt4-e').value = cleanOptionText(q.queOption4E || '');
                    
                    setCorrectAnsSelectValue(q.correctAnswer || q.queAnswer || q.correct_answer || q.answer);

                    const catSelect = document.getElementById('edit-exam-category');
                    if (catSelect) {
                        const targetCat = (q.examCategory || q.examName || '').trim();
                        if (targetCat) {
                            let found = false;
                            for (let i = 0; i < catSelect.options.length; i++) {
                                if (catSelect.options[i].value.toLowerCase().trim() === targetCat.toLowerCase()) {
                                    catSelect.selectedIndex = i;
                                    found = true;
                                    break;
                                }
                            }
                            if (!found) {
                                const opt = document.createElement('option');
                                opt.value = targetCat;
                                opt.innerText = targetCat;
                                catSelect.appendChild(opt);
                                catSelect.value = targetCat;
                            }
                        }
                    }

                    if (document.getElementById('edit-exam-year') && q.examYear) {
                        document.getElementById('edit-exam-year').value = q.examYear;
                    }

                    document.getElementById('edit-sol-m').value = q.solutionText || '';
                    document.getElementById('edit-sol-e').value = q.solutionTextE || '';

                    document.getElementById('editQuestionModal').classList.add('show');
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (err) {
                alert('Failed to load question details: ' + err);
            }
        }

        function closeEditQuestionModal() {
            document.getElementById('editQuestionModal').classList.remove('show');
        }

        async function saveQuestionEdit(e) {
            e.preventDefault();
            const data = {
                questionID: document.getElementById('edit-q-id').value,
                subjectID: document.getElementById('edit-subject-id') ? document.getElementById('edit-subject-id').value : 0,
                topicID: document.getElementById('edit-topic-id') ? document.getElementById('edit-topic-id').value : 0,
                questionName: document.getElementById('edit-q-name-m').value,
                questionNameE: document.getElementById('edit-q-name-e').value,
                queOption1: document.getElementById('edit-opt1-m').value,
                queOption1E: document.getElementById('edit-opt1-e').value,
                queOption2: document.getElementById('edit-opt2-m').value,
                queOption2E: document.getElementById('edit-opt2-e').value,
                queOption3: document.getElementById('edit-opt3-m').value,
                queOption3E: document.getElementById('edit-opt3-e').value,
                queOption4: document.getElementById('edit-opt4-m').value,
                queOption4E: document.getElementById('edit-opt4-e').value,
                correctAnswer: document.getElementById('edit-correct-ans').value,
                examName: document.getElementById('edit-exam-category') ? document.getElementById('edit-exam-category').value : '',
                examCategory: document.getElementById('edit-exam-category') ? document.getElementById('edit-exam-category').value : '',
                examYear: document.getElementById('edit-exam-year') ? document.getElementById('edit-exam-year').value : '',
                solutionText: document.getElementById('edit-sol-m').value,
                solutionTextE: document.getElementById('edit-sol-e').value,
                test_series_id: document.getElementById('edit-test-series') ? document.getElementById('edit-test-series').value : 0
            };

            try {
                const res = await fetch('api.php?action=update_question', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert('Question updated successfully!');
                    closeEditQuestionModal();
                    fetchQuestions();
                    loadDashboardTestSeries();
                } else {
                    alert('Failed to update: ' + json.message);
                }
            } catch (err) {
                alert('Error updating question: ' + err);
            }
        }

        function populateTopicStripForSubject(subId) {
            const sub = globalSubjects.find(s => s.subjectID == subId);
            const container = document.getElementById('topics-scroll-container');
            container.innerHTML = `
                <div class="topic-chip active" id="chip-0" onclick="selectTopic(0)">
                    <span>All Topics</span>
                </div>
            `;

            if (sub && sub.topics_tree) {
                flattenTopics(sub.topics_tree).forEach(t => {
                    const chip = document.createElement('div');
                    chip.className = 'topic-chip';
                    chip.id = `chip-${t.topicID}`;
                    chip.onclick = () => selectTopic(t.topicID);
                    chip.innerHTML = `<span>${escapeHtml(t.topicNameM || t.topicNameE)}</span> <span style="font-size:10px; opacity:0.7;">(${t.question_count})</span>`;
                    container.appendChild(chip);
                });
            }
        }

        function flattenTopics(nodes) {
            let list = [];
            nodes.forEach(n => {
                list.push(n);
                if (n.children && n.children.length > 0) {
                    list = list.concat(flattenTopics(n.children));
                }
            });
            return list;
        }

        // 4. QUESTIONS RENDERER WITH BILINGUAL TOGGLE
        async function fetchQuestions() {
            const container = document.getElementById('questions-container');
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fa-solid fa-circle-notch loading-spinner"></i>
                    <p>Loading Questions...</p>
                </div>
            `;

            try {
                let url = `api.php?action=questions&page=${currentPage}&limit=10`;
                if (currentSubjectId > 0) url += `&subject_id=${currentSubjectId}`;
                if (currentTopicId > 0) url += `&topic_id=${currentTopicId}`;
                if (currentSearch !== '') url += `&search=${encodeURIComponent(currentSearch)}`;

                const res = await fetch(url);
                const json = await res.json();

                if (json.status === 'success') {
                    const questionsList = Array.isArray(json.data) ? json.data : (json.data.questions || []);
                    const totalQs = json.total || (json.data ? json.data.total_questions : questionsList.length) || 0;
                    const limit = json.limit || 10;
                    const totalPg = Math.ceil(totalQs / limit) || 1;

                    renderQuestions(questionsList);
                    renderPagination(totalQs, currentPage, totalPg);
                } else {
                    container.innerHTML = `<div class="loading-state" style="color:var(--accent-rose);">Error: ${json.message}</div>`;
                }
            } catch (err) {
                console.error("fetchQuestions error:", err);
                container.innerHTML = `<div class="loading-state" style="color:var(--accent-rose);">Failed to load questions from database.</div>`;
            }
        }

        function renderQuestions(questions) {
            const container = document.getElementById('questions-container');
            if (!questions || questions.length === 0) {
                container.innerHTML = `
                    <div class="loading-state">
                        <i class="fa-solid fa-folder-open" style="font-size:36px; color:var(--text-dim); margin-bottom:12px;"></i>
                        <p>No questions found in this topic or search criteria.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            questions.forEach(q => {
                const card = document.createElement('div');
                card.className = 'question-card';

                const qid = q.id || q.questionID;
                card.id = `qcard-${qid}`;

                const qMr = q.question_mr || q.questionName || '';
                const qEn = q.question_en || q.questionNameE || '';
                const opt1Mr = q.opt1_mr || q.queOption1 || '';
                const opt2Mr = q.opt2_mr || q.queOption2 || '';
                const opt3Mr = q.opt3_mr || q.queOption3 || '';
                const opt4Mr = q.opt4_mr || q.queOption4 || '';
                const opt1En = q.opt1_en || q.queOption1E || '';
                const opt2En = q.opt2_en || q.queOption2E || '';
                const opt3En = q.opt3_en || q.queOption3E || '';
                const opt4En = q.opt4_en || q.queOption4E || '';
                const solMr = q.solution_mr || q.solutionText || '';
                const solEn = q.solution_en || q.solutionTextE || '';
                const ans = (q.correct_answer || q.correctAnswer || '1').toString().trim();

                const hasEnglish = qEn && qEn.trim() !== '';
                const activeLang = cardLangState[qid] || 'mr';

                const displayQ = (activeLang === 'en' && hasEnglish) ? qEn : qMr;
                const opt1 = (activeLang === 'en' && opt1En) ? opt1En : opt1Mr;
                const opt2 = (activeLang === 'en' && opt2En) ? opt2En : opt2Mr;
                const opt3 = (activeLang === 'en' && opt3En) ? opt3En : opt3Mr;
                const opt4 = (activeLang === 'en' && opt4En) ? opt4En : opt4Mr;
                const sol  = (activeLang === 'en' && solEn) ? solEn : solMr;

                card.innerHTML = `
                    <div class="q-header">
                        <div class="q-meta">
                            <span class="q-id-badge">Q #${qid}</span>
                            <span class="q-tag"><i class="fa-solid fa-book"></i> ${escapeHtml(q.subjectNameE || 'Subject')}</span>
                            ${q.topicNameE ? `<span class="q-tag" style="background:rgba(6,182,212,0.12); color:var(--accent-cyan); border-color:rgba(6,182,212,0.25);"><i class="fa-solid fa-tag"></i> ${escapeHtml(q.topicNameE)}</span>` : ''}
                            <span class="q-tag" style="background:rgba(168,85,247,0.18); color:#c084fc; border:1px solid rgba(168,85,247,0.35); font-weight:700;"><i class="fa-solid fa-graduation-cap"></i> ${escapeHtml(q.exam_name || q.examName || 'MPSC PRE')}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button class="btn-action" style="background: rgba(245, 158, 11, 0.18); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.35); font-weight: 600; padding: 4px 10px; font-size: 12px;" onclick="openEditQuestionModal(${qid})">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Question
                            </button>
                            ${hasEnglish ? `
                                <button class="lang-badge-btn" onclick="toggleCardLanguage(${qid})">
                                    <i class="fa-solid fa-language"></i> ${activeLang === 'en' ? 'Switch to Marathi' : 'Switch to English'}
                                </button>
                            ` : `<span style="font-size:11px; color:var(--text-dim);"><i class="fa-solid fa-check"></i> Marathi</span>`}
                        </div>
                    </div>

                    <div class="q-text">${displayQ}</div>

                    <div class="options-grid">
                        <div class="option-item ${ans == '1' ? 'correct' : ''}">
                            <div class="opt-num">1</div>
                            <div>${cleanOptionText(opt1 || '')}</div>
                        </div>
                        <div class="option-item ${ans == '2' ? 'correct' : ''}">
                            <div class="opt-num">2</div>
                            <div>${cleanOptionText(opt2 || '')}</div>
                        </div>
                        <div class="option-item ${ans == '3' ? 'correct' : ''}">
                            <div class="opt-num">3</div>
                            <div>${cleanOptionText(opt3 || '')}</div>
                        </div>
                        <div class="option-item ${ans == '4' ? 'correct' : ''}">
                            <div class="opt-num">4</div>
                            <div>${cleanOptionText(opt4 || '')}</div>
                        </div>
                    </div>

                    ${sol ? `
                        <div class="solution-box">
                            <strong style="color:var(--accent-cyan); display:block; margin-bottom:4px;"><i class="fa-solid fa-lightbulb"></i> Solution / Explanation:</strong>
                            ${sol}
                        </div>
                    ` : ''}
                `;

                container.appendChild(card);
            });
        }

        function toggleCardLanguage(qId) {
            cardLangState[qId] = cardLangState[qId] === 'en' ? 'mr' : 'en';
            fetchQuestions();
        }

        // 5. RECRUITMENT & JOB NOTIFICATIONS ENGINE
        async function openRecruitmentsModal() {
            document.getElementById('recruitmentsModal').classList.add('show');
            const container = document.getElementById('recruitments-list-container');
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fa-solid fa-circle-notch loading-spinner"></i>
                    <p>Loading Recruitment Postings...</p>
                </div>
            `;

            try {
                const res = await fetch('api.php?action=recruitments');
                const json = await res.json();
                if (json.status === 'success') {
                    jobRecruits = json.data;
                    renderRecruitmentCards(jobRecruits);
                } else {
                    container.innerHTML = `<div class="loading-state" style="color:var(--accent-rose);">Error: ${json.message}</div>`;
                }
            } catch (err) {
                container.innerHTML = `<div class="loading-state" style="color:var(--accent-rose);">Failed to load recruitments.</div>`;
            }
        }

        function renderRecruitmentCards(jobs) {
            const container = document.getElementById('recruitments-list-container');
            if (!jobs || jobs.length === 0) {
                container.innerHTML = `<p style="color:var(--text-muted); text-align:center; padding:20px;">No recruitment postings found.</p>`;
                return;
            }

            container.innerHTML = '';
            jobs.forEach(job => {
                const card = document.createElement('div');
                card.className = 'recruitment-card';

                card.innerHTML = `
                    <div class="rec-title-header">
                        <div>
                            <div class="rec-title">${escapeHtml(job.titleM)}</div>
                            <div style="font-size:13px; color:var(--accent-cyan); margin-top:2px;">${escapeHtml(job.titleE)}</div>
                            <div style="font-size:12px; color:var(--text-dim); margin-top:4px;">
                                <i class="fa-solid fa-hashtag"></i> Advt No: <strong>${job.advtNo || 'N/A'}</strong> | 
                                <i class="fa-solid fa-calendar"></i> Last Date: <strong style="color:var(--accent-amber);">${job.lastDateApplyTextM || job.lastDateApplyTextE}</strong> |
                                <i class="fa-solid fa-clock"></i> Exam Date: <strong style="color:var(--accent-emerald);">${job.examDateTextM || job.examDateTextE}</strong>
                            </div>
                        </div>
                        <div class="badge-vacancies"><i class="fa-solid fa-users"></i> ${job.totalVacanciesTextM}</div>
                    </div>

                    <button class="btn-action" style="align-self:flex-start; font-size:12px; padding:6px 12px;" onclick="loadJobDetailsTable(${job.jobID}, this)">
                        <i class="fa-solid fa-table-list"></i> View Post Breakdown Table & Qualifications
                    </button>

                    <div id="job-details-box-${job.jobID}" style="display:none; margin-top:10px;"></div>

                    <div class="link-buttons">
                        ${job.notificationPdfUrl ? `<a href="${job.notificationPdfUrl}" target="_blank" class="btn-rec-link"><i class="fa-solid fa-file-pdf" style="color:var(--accent-rose);"></i> Notification PDF</a>` : ''}
                        ${job.applyOnlineUrl ? `<a href="${job.applyOnlineUrl}" target="_blank" class="btn-rec-link btn-apply"><i class="fa-solid fa-paper-plane"></i> Apply Online</a>` : ''}
                        <a href="https://mpsc.gov.in" target="_blank" class="btn-rec-link"><i class="fa-solid fa-globe"></i> Official MPSC Site</a>
                    </div>
                `;

                container.appendChild(card);
            });
        }

        async function loadJobDetailsTable(jobId, btnElem) {
            const detailBox = document.getElementById(`job-details-box-${jobId}`);
            if (detailBox.style.display === 'block') {
                detailBox.style.display = 'none';
                btnElem.innerHTML = `<i class="fa-solid fa-table-list"></i> View Post Breakdown Table & Qualifications`;
                return;
            }

            btnElem.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Loading Details...`;
            try {
                const res = await fetch(`api.php?action=recruitment_details&job_id=${jobId}`);
                const json = await res.json();
                if (json.status === 'success') {
                    const data = json.data;
                    let tableHtml = `
                        <div style="background:rgba(0,0,0,0.3); padding:14px; border-radius:12px; border:1px solid var(--border-color);">
                            <h4 style="color:var(--accent-amber); margin-bottom:8px; font-size:14px;"><i class="fa-solid fa-list-check"></i> Post Name, Department & Qualification Details</h4>
                            <table class="rec-table">
                                <thead>
                                    <tr>
                                        <th>Post No.</th>
                                        <th>Post Name (पदाचे नाव)</th>
                                        <th>Department (विभाग)</th>
                                        <th>Vacancies</th>
                                        <th>Qualification & Age Limit</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    (data.posts_table || []).forEach(p => {
                        tableHtml += `
                            <tr>
                                <td><strong>#${p.postNo}</strong></td>
                                <td><strong>${escapeHtml(p.postNameM)}</strong><br><small style="color:var(--text-muted);">${escapeHtml(p.postNameE)}</small></td>
                                <td>${escapeHtml(p.departmentM)}</td>
                                <td><span style="color:var(--accent-emerald); font-weight:700;">${p.vacancyCount}</span></td>
                                <td><small><strong>Edu:</strong> ${escapeHtml(p.qualificationM)}<br><strong>Age:</strong> ${escapeHtml(p.ageLimitM)}</small></td>
                            </tr>
                        `;
                    });

                    tableHtml += `
                                </tbody>
                            </table>
                            <div style="margin-top:10px; font-size:12.5px; color:var(--text-muted);">
                                <strong>Exam Fee Details:</strong> ${data.feeDetailsM}<br>
                                <strong>Job Location:</strong> ${data.jobLocationM} (${data.jobLocationE})
                            </div>
                        </div>
                    `;

                    detailBox.innerHTML = tableHtml;
                    detailBox.style.display = 'block';
                    btnElem.innerHTML = `<i class="fa-solid fa-eye-slash"></i> Hide Post Breakdown Table`;
                }
            } catch (err) {
                alert("Failed to fetch recruitment breakdown details.");
                btnElem.innerHTML = `<i class="fa-solid fa-table-list"></i> View Post Breakdown Table & Qualifications`;
            }
        }

        function closeRecruitmentsModal() {
            document.getElementById('recruitmentsModal').classList.remove('show');
        }

        // 6. PAGINATION
        function renderPagination(totalCount, page, pages) {
            document.getElementById('pagination-info').innerText = `Showing ${totalCount === 0 ? 0 : (page - 1) * 10 + 1} - ${Math.min(page * 10, totalCount)} of ${totalCount.toLocaleString()} questions`;
            const btnContainer = document.getElementById('pagination-buttons');
            btnContainer.innerHTML = '';

            const prevBtn = document.createElement('button');
            prevBtn.className = 'btn-page';
            prevBtn.disabled = page <= 1;
            prevBtn.innerHTML = `<i class="fa-solid fa-chevron-left"></i>`;
            prevBtn.onclick = () => { currentPage--; fetchQuestions(); };
            btnContainer.appendChild(prevBtn);

            let start = Math.max(1, page - 2);
            let end = Math.min(pages, page + 2);

            for (let i = start; i <= end; i++) {
                const btn = document.createElement('button');
                btn.className = `btn-page ${i === page ? 'active' : ''}`;
                btn.innerText = i;
                btn.onclick = () => { currentPage = i; fetchQuestions(); };
                btnContainer.appendChild(btn);
            }

            const nextBtn = document.createElement('button');
            nextBtn.className = 'btn-page';
            nextBtn.disabled = page >= pages;
            nextBtn.innerHTML = `<i class="fa-solid fa-chevron-right"></i>`;
            nextBtn.onclick = () => { currentPage++; fetchQuestions(); };
            btnContainer.appendChild(nextBtn);
        }

        function populateAllTestSeriesDropdowns() {
            fetch('api.php?action=get_test_series')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentTestSeriesList = data.data;
                        const modalTs = document.getElementById('modal-test-series');
                        const editTs = document.getElementById('edit-test-series');

                        let optionsHtml = '<option value="0">-- General Database Question (No Test Series) --</option>';
                        data.data.forEach(ts => {
                            optionsHtml += `<option value="${ts.id}">${escapeHtml(ts.title)} (${ts.question_count} Qs - ₹${ts.price})</option>`;
                        });

                        if (modalTs) modalTs.innerHTML = optionsHtml;
                        if (editTs) editTs.innerHTML = '<option value="0">-- Do Not Link to Additional Test Series --</option>' + optionsHtml.replace('<option value="0">-- General Database Question (No Test Series) --</option>', '');
                    }
                }).catch(err => console.error("Dropdown error:", err));
        }

        function populateModalSubjectDropdowns() {
            const addSub = document.getElementById('modal-subject-id');
            const editSub = document.getElementById('edit-subject-id');

            if (!globalSubjects || globalSubjects.length === 0) return;

            let html = '';
            globalSubjects.forEach(s => {
                const titleE = s.subjectNameE || s.subjectNameM || 'Subject';
                const titleM = s.subjectNameM ? ` (${s.subjectNameM})` : '';
                html += `<option value="${s.subjectID}">${escapeHtml(titleE)}${escapeHtml(titleM)}</option>`;
            });

            if (addSub) addSub.innerHTML = html;
            if (editSub) editSub.innerHTML = html;
        }

        function populateTopicOptionsForSubject(subjectID, targetSelectId, selectedTopicID = 0) {
            const topicSelect = document.getElementById(targetSelectId);
            if (!topicSelect) return;

            const sub = globalSubjects.find(s => s.subjectID == subjectID);
            if (!sub || !sub.topics_tree || sub.topics_tree.length === 0) {
                topicSelect.innerHTML = '<option value="0">-- General Subject Question (No Topic) --</option>';
                return;
            }

            let optionsHtml = '<option value="0">-- General Subject Question (No Topic) --</option>';

            function recurseTopics(tree, level = 0) {
                tree.forEach(t => {
                    const indent = level > 0 ? '— '.repeat(level) : '';
                    const name = (t.topicNameE || t.topicName || 'Topic').trim();
                    const nameM = t.topicName && t.topicName !== name ? ` (${t.topicName.trim()})` : '';
                    optionsHtml += `<option value="${t.topicID}">${indent}${escapeHtml(name)}${escapeHtml(nameM)}</option>`;
                    if (t.children && t.children.length > 0) {
                        recurseTopics(t.children, level + 1);
                    }
                });
            }

            recurseTopics(sub.topics_tree, 0);
            topicSelect.innerHTML = optionsHtml;

            if (selectedTopicID > 0) {
                topicSelect.value = selectedTopicID;
            } else {
                topicSelect.value = 0;
            }
        }

        function onAddModalSubjectChange() {
            const subId = document.getElementById('modal-subject-id').value;
            populateTopicOptionsForSubject(subId, 'modal-topic-id', 0);
        }

        function onEditModalSubjectChange() {
            const subId = document.getElementById('edit-subject-id').value;
            populateTopicOptionsForSubject(subId, 'edit-topic-id', 0);
        }

        // MODAL CONTROLS
        function openAddQuestionModal() { 
            populateAllTestSeriesDropdowns();
            populateModalSubjectDropdowns();
            const addSub = document.getElementById('modal-subject-id');
            if (addSub && addSub.value > 0) {
                populateTopicOptionsForSubject(addSub.value, 'modal-topic-id', 0);
            }
            document.getElementById('addQuestionModal').classList.add('show'); 
        }
        function closeAddQuestionModal() { document.getElementById('addQuestionModal').classList.remove('show'); }
        function openBulkModal() { document.getElementById('bulkImportModal').classList.add('show'); }
        function closeBulkModal() { document.getElementById('bulkImportModal').classList.remove('show'); }

        async function autoLinkSubjectQuestions() {
            const subId = document.getElementById('ts-helper-subject').value;
            if (subId <= 0) {
                alert("Please select a subject first.");
                return;
            }
            try {
                const res = await fetch(`api.php?action=questions&subject_id=${subId}&limit=500`);
                const json = await res.json();
                if (json.status === 'success') {
                    const questions = Array.isArray(json.data) ? json.data : (json.data.questions || []);
                    const qids = questions.map(q => q.id || q.questionID).filter(Boolean);
                    if (qids.length === 0) {
                        alert("No questions found for this subject.");
                        return;
                    }
                    const area = document.getElementById('ts-question-ids');
                    const existing = area.value.trim();
                    let combined = existing ? existing.split(',').map(s => s.trim()).filter(Boolean) : [];
                    qids.forEach(id => {
                        if (!combined.includes(String(id))) combined.push(String(id));
                    });
                    area.value = combined.join(', ');
                    alert(`Successfully appended ${qids.length} Question IDs from selected subject!`);
                }
            } catch (err) {
                alert("Error fetching subject questions: " + err.message);
            }
        }

        async function submitNewQuestion(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('addQuestionForm'));
            formData.append('action', 'add_question');
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message);
                    closeAddQuestionModal();
                    fetchStats();
                    fetchQuestions();
                    loadDashboardTestSeries();
                } else {
                    alert("Error: " + json.message);
                }
            } catch (err) { alert("Failed to add question."); }
        }

        async function submitBulkQuestions(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('bulkImportForm'));
            formData.append('action', 'bulk_add_questions');
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message);
                    closeBulkModal();
                    fetchStats();
                    fetchQuestions();
                } else { alert("Error: " + json.message); }
            } catch (err) { alert("Failed to import bulk questions."); }
        }

        function filterSubjectTree(query) {
            if (!query) {
                renderSubjectTree(globalSubjects);
                return;
            }
            const filtered = globalSubjects.filter(s => 
                s.subjectNameE.toLowerCase().includes(query) || 
                s.subjectNameM.toLowerCase().includes(query)
            );
            renderSubjectTree(filtered);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
        function escapeJsQuotes(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'");
        }

        // --- WORDPRESS NAVIGATION & TEST SERIES MANAGER JS ---
        let currentTestSeriesList = [];
        let currentExamsList = [];

        function switchWpTab(tabName) {
            const itemExp = document.getElementById('nav-item-explorer');
            const itemTs = document.getElementById('nav-item-test-series');
            const itemExams = document.getElementById('nav-item-exams');
            const itemContacts = document.getElementById('nav-item-contacts');
            const itemUsers = document.getElementById('nav-item-users');
            const itemNotifs = document.getElementById('nav-item-notifications');

            if (itemExp) itemExp.classList.remove('active');
            if (itemTs) itemTs.classList.remove('active');
            if (itemExams) itemExams.classList.remove('active');
            if (itemContacts) itemContacts.classList.remove('active');
            if (itemUsers) itemUsers.classList.remove('active');
            if (itemNotifs) itemNotifs.classList.remove('active');

            const viewExp = document.getElementById('wp-view-explorer');
            const viewTs = document.getElementById('wp-view-test-series');
            const viewExams = document.getElementById('wp-view-exams');
            const viewContacts = document.getElementById('wp-view-contacts');
            const viewUsers = document.getElementById('wp-view-users');
            const viewNotifs = document.getElementById('wp-view-notifications');

            if (viewExp) viewExp.style.display = 'none';
            if (viewTs) viewTs.style.display = 'none';
            if (viewExams) viewExams.style.display = 'none';
            if (viewContacts) viewContacts.style.display = 'none';
            if (viewUsers) viewUsers.style.display = 'none';
            if (viewNotifs) viewNotifs.style.display = 'none';

            if (tabName === 'explorer') {
                if (itemExp) itemExp.classList.add('active');
                if (viewExp) viewExp.style.display = 'flex';
            } else if (tabName === 'test-series') {
                if (itemTs) itemTs.classList.add('active');
                if (viewTs) viewTs.style.display = 'block';
                loadDashboardTestSeries();
            } else if (tabName === 'exams') {
                if (itemExams) itemExams.classList.add('active');
                if (viewExams) viewExams.style.display = 'block';
                loadDashboardExams();
            } else if (tabName === 'contacts') {
                if (itemContacts) itemContacts.classList.add('active');
                if (viewContacts) viewContacts.style.display = 'block';
                loadDashboardContacts(1);
            } else if (tabName === 'users') {
                if (itemUsers) itemUsers.classList.add('active');
                if (viewUsers) viewUsers.style.display = 'block';
                loadDashboardUsers();
            } else if (tabName === 'notifications') {
                if (itemNotifs) itemNotifs.classList.add('active');
                if (viewNotifs) viewNotifs.style.display = 'block';
                loadDashboardNotifications();
            }
        }

        // --- USER CONTACTS ENGINE ---
        let currentContactsPage = 1;
        let currentContactsSearch = '';

        function loadDashboardContacts(page = 1) {
            currentContactsPage = page;
            const searchInput = document.getElementById('contacts-search-input');
            const search = searchInput ? searchInput.value.trim() : '';
            currentContactsSearch = search;

            const tbody = document.getElementById('contacts-table-body');
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading contacts...</td></tr>';

            fetch(`api.php?action=get_admin_contacts&page=${page}&limit=50&search=${encodeURIComponent(search)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        renderContactsTable(data.data);
                        renderContactsPagination(data.total, data.distinct_users, data.page, data.limit);
                    } else {
                        if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color: #ef4444;">No contacts found</td></tr>';
                    }
                })
                .catch(err => {
                    if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color: #ef4444;">Failed to load contacts: ' + err + '</td></tr>';
                });
        }

        function renderContactsTable(contacts) {
            const tbody = document.getElementById('contacts-table-body');
            if (!tbody) return;
            if (contacts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">No matching contacts found.</td></tr>';
                return;
            }
            tbody.innerHTML = contacts.map(c => `
                <tr>
                    <td>#${c.id}</td>
                    <td style="font-weight: 600; color: white;">${escapeHtml(c.contactName)}</td>
                    <td><span style="background: #0f172a; padding: 4px 8px; border-radius: 6px; color: #34d399; font-family: monospace;">${escapeHtml(c.contactPhone)}</span></td>
                    <td><span style="color: #60a5fa;">${escapeHtml(c.userEmail)}</span></td>
                    <td style="color: #94a3b8; font-size: 12px;">${c.createdDate}</td>
                </tr>
            `).join('');
        }

        function renderContactsPagination(total, distinctUsers, page, limit) {
            const bar = document.getElementById('contacts-pagination-bar');
            if (!bar) return;
            const totalPages = Math.ceil(total / limit) || 1;
            const start = (page - 1) * limit + 1;
            const end = Math.min(page * limit, total);

            bar.innerHTML = `
                <div>Showing <strong>${start}-${end}</strong> of <strong>${total.toLocaleString()}</strong> contacts (${distinctUsers} unique users)</div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-action" ${page <= 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} onclick="loadDashboardContacts(${page - 1})" style="padding: 6px 14px; font-size: 12px;">Prev</button>
                    <span style="padding: 6px 12px; background: #0f172a; border-radius: 6px; color: white;">Page ${page} of ${totalPages}</span>
                    <button class="btn-action" ${page >= totalPages ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} onclick="loadDashboardContacts(${page + 1})" style="padding: 6px 14px; font-size: 12px;">Next</button>
                </div>
            `;
        }

        function handleContactsSearchKey(e) {
            if (e.key === 'Enter') {
                loadDashboardContacts(1);
            }
        }

        function exportContactsCSV() {
            window.open(`api.php?action=get_admin_contacts&limit=10000&search=${encodeURIComponent(currentContactsSearch)}`, '_blank');
        }

        // --- APP USERS ENGINE ---
        function loadDashboardUsers() {
            const tbody = document.getElementById('users-table-body');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading users...</td></tr>';

            fetch('api.php?action=get_admin_users')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        tbody.innerHTML = data.data.map(u => `
                            <tr>
                                <td>#${u.userID}</td>
                                <td style="font-weight: 600; color: white;">${escapeHtml(u.fullName || 'Student')}</td>
                                <td><span style="color: #60a5fa;">${escapeHtml(u.email)}</span></td>
                                <td><span style="background: #0f172a; padding: 4px 8px; border-radius: 6px; color: #34d399; font-family: monospace;">${escapeHtml(u.phoneNumber || u.mobile || 'N/A')}</span></td>
                                <td style="color: #94a3b8; font-size: 12px;">${u.createdDate || '-'}</td>
                                <td><span style="background: #064e3b; color: #34d399; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: bold;">FREE PRO PASS</span></td>
                                <td><button class="btn-action" style="padding: 4px 10px; font-size: 11px; background: #2563eb;" onclick="alert('User has 100% Free Pro Pass Access!')">Manage</button></td>
                            </tr>
                        `).join('');
                    }
                });
        }

        // --- PUSH NOTIFICATIONS ENGINE ---
        function loadDashboardNotifications() {
            const tbody = document.getElementById('notifications-table-body');
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading notifications...</td></tr>';

            fetch('api.php?action=get_notifications')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        tbody.innerHTML = data.data.map(n => `
                            <tr>
                                <td>#${n.id}</td>
                                <td style="font-weight: 600; color: white;">${escapeHtml(n.title)}</td>
                                <td style="color: #cbd5e1;">${escapeHtml(n.message)}</td>
                                <td><span style="background: #334155; color: #f59e0b; padding: 4px 8px; border-radius: 6px; font-size: 11px;">${escapeHtml(n.type)}</span></td>
                                <td style="color: #94a3b8; font-size: 12px;">${n.createdDate}</td>
                            </tr>
                        `).join('');
                    }
                });
        }

        function sendDashboardNotification() {
            const title = document.getElementById('notif-title').value.trim();
            const message = document.getElementById('notif-message').value.trim();
            const type = document.getElementById('notif-type').value;

            if (!title || !message) {
                alert('Please enter both title and message');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send_admin_notification');
            formData.append('title', title);
            formData.append('message', message);
            formData.append('type', type);

            fetch('api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('notif-title').value = '';
                        document.getElementById('notif-message').value = '';
                        loadDashboardNotifications();
                        alert('📢 Notification broadcasted successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // --- EXAMS MANAGER ENGINE ---
        function loadDashboardExams() {
            const tbody = document.getElementById('exams-table-body');
            if (!tbody) return;
            tbody.innerHTML = `<tr><td colspan="5" class="loading-state"><i class="fa-solid fa-circle-notch loading-spinner"></i><p>Loading Exam Categories...</p></td></tr>`;

            fetch('api.php?action=get_all_exams')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentExamsList = data.data;
                        renderExamsTable(data.data);
                        populateExamDropdowns(data.data);
                    } else {
                        tbody.innerHTML = `<tr><td colspan="5" style="color: var(--accent-rose); text-align: center; padding: 20px;">Error: ${data.message}</td></tr>`;
                    }
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="5" style="color: var(--accent-rose); text-align: center; padding: 20px;">Failed to load exams: ${err.message}</td></tr>`;
                });
        }

        function renderExamsTable(list) {
            const tbody = document.getElementById('exams-table-body');
            if (!tbody) return;

            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-dim); padding: 30px;">No Exam Categories found. Click <strong>Add New Exam Category</strong> to create one.</td></tr>`;
                return;
            }

            let html = '';
            list.forEach(item => {
                const isVis = item.status == 1;
                const statusBadge = isVis 
                    ? `<span class="q-id-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3);"><i class="fa-solid fa-eye"></i> Visible in App</span>`
                    : `<span class="q-id-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);"><i class="fa-solid fa-eye-slash"></i> Hidden from App</span>`;

                html += `
                    <tr>
                        <td><strong>#${item.id}</strong></td>
                        <td>
                            <div style="font-weight: 600; color: white;">${escapeHtml(item.name)}</div>
                        </td>
                        <td><code style="background: rgba(255,255,255,0.08); padding: 2px 6px; border-radius: 4px; color: var(--accent-cyan);">${escapeHtml(item.code || '--')}</code></td>
                        <td><span style="background: rgba(99, 102, 241, 0.15); color: #818cf8; padding: 3px 10px; border-radius: 6px; font-weight: 600;">${item.question_count || 0} Qs</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button class="btn-action" onclick="toggleExamStatus(${item.id})" style="padding: 5px 10px; font-size: 11.5px; background: rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid ${isVis ? 'fa-eye-slash' : 'fa-eye'}"></i> ${isVis ? 'Hide from App' : 'Show in App'}
                                </button>
                                <button class="btn-action" onclick="editExam(${item.id})" style="padding: 5px 10px; font-size: 11.5px; background: rgba(99, 102, 241, 0.2); color: #818cf8; border-color: rgba(99, 102, 241, 0.4);">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <button class="btn-action" onclick="deleteExam(${item.id})" style="padding: 5px 10px; font-size: 11.5px; background: rgba(239, 68, 68, 0.2); color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        async function toggleExamStatus(id) {
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_exam_status', id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    loadDashboardExams();
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (err) {
                alert('Failed to toggle status: ' + err);
            }
        }

        function populateExamDropdowns(examsList) {
            if (!examsList || examsList.length === 0) return;
            let optionsHtml = '';
            examsList.forEach(e => {
                optionsHtml += `<option value="${escapeHtml(e.name)}">${escapeHtml(e.name)}</option>`;
            });

            ['ts-exam-name', 'edit-exam-category', 'modal-exam-category'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = optionsHtml;
            });
        }

        function openCreateExamModal() {
            document.getElementById('exam-modal-title').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: var(--accent-cyan);"></i> Add New Exam Category';
            document.getElementById('exam-id').value = '0';
            document.getElementById('exam-name-input').value = '';
            document.getElementById('exam-code-input').value = '';
            document.getElementById('examModal').classList.add('show');
        }

        function editExam(id) {
            const item = currentExamsList.find(e => e.id == id);
            if (!item) return;

            document.getElementById('exam-modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: var(--accent-cyan);"></i> Edit Exam Category #' + id;
            document.getElementById('exam-id').value = item.id;
            document.getElementById('exam-name-input').value = item.name;
            document.getElementById('exam-code-input').value = item.code;
            document.getElementById('examModal').classList.add('show');
        }

        function closeExamModal() {
            document.getElementById('examModal').classList.remove('show');
        }

        async function submitExamForm(e) {
            e.preventDefault();
            const id = document.getElementById('exam-id').value;
            const name = document.getElementById('exam-name-input').value;
            const code = document.getElementById('exam-code-input').value;

            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_exam', id: id, name: name, code: code })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message);
                    closeExamModal();
                    loadDashboardExams();
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (err) {
                alert('Failed to save exam category: ' + err);
            }
        }

        async function deleteExam(id) {
            if (!confirm(`Are you sure you want to delete Exam Category #${id}?`)) return;
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_exam', id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message);
                    loadDashboardExams();
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (err) {
                alert('Failed to delete exam category: ' + err);
            }
        }

        function loadDashboardTestSeries() {
            const tbody = document.getElementById('test-series-table-body');
            if (!tbody) return;
            tbody.innerHTML = `<tr><td colspan="7" class="loading-state"><i class="fa-solid fa-circle-notch loading-spinner"></i><p>Loading Test Series...</p></td></tr>`;

            fetch('api.php?action=get_test_series')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentTestSeriesList = data.data;
                        renderTestSeriesTable(data.data);
                    } else {
                        tbody.innerHTML = `<tr><td colspan="7" style="color: var(--accent-rose); text-align: center; padding: 20px;">Error: ${data.message}</td></tr>`;
                    }
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="7" style="color: var(--accent-rose); text-align: center; padding: 20px;">Failed to load test series: ${err.message}</td></tr>`;
                });
        }

        function renderTestSeriesTable(list) {
            const tbody = document.getElementById('test-series-table-body');
            if (!tbody) return;

            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-dim); padding: 30px;">No Test Series found. Click <strong>Create New Test Series</strong> to add one.</td></tr>`;
                return;
            }

            let html = '';
            list.forEach(item => {
                const isPub = item.is_published == 1;
                const statusBadge = isPub 
                    ? `<span class="q-id-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3);">Published</span>`
                    : `<span class="q-id-badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">Draft</span>`;

                html += `
                    <tr>
                        <td><strong>#${item.id}</strong></td>
                        <td>
                            <div style="font-weight: 600; color: white;">${escapeHtml(item.title)}</div>
                            <div style="font-size: 11px; color: var(--accent-cyan); margin-top: 2px;"><i class="fa-solid fa-graduation-cap"></i> ${escapeHtml(item.exam_name || 'Rajyaseva Prelims')} | QIDs: ${item.question_ids ? escapeHtml(item.question_ids.length > 35 ? item.question_ids.substring(0, 35) + '...' : item.question_ids) : 'Auto / None'}</div>
                        </td>
                        <td><strong>₹${item.price}</strong></td>
                        <td>${item.max_attempts} attempts</td>
                        <td><span style="background: rgba(99, 102, 241, 0.15); color: #818cf8; padding: 3px 10px; border-radius: 6px; font-weight: 600;">${item.question_count} Qs</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button class="btn-action" onclick="editTestSeries(${item.id})" style="padding: 5px 10px; font-size: 11.5px; background: rgba(99, 102, 241, 0.2); color: #818cf8; border-color: rgba(99, 102, 241, 0.4);">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <button class="btn-action" onclick="toggleTestSeriesPublish(${item.id})" style="padding: 5px 10px; font-size: 11.5px; background: rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-eye"></i> ${isPub ? 'Unpublish' : 'Publish'}
                                </button>
                                <button class="btn-action" onclick="deleteTestSeries(${item.id})" style="padding: 5px 10px; font-size: 11.5px; background: rgba(239, 68, 68, 0.2); color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        function openCreateTestSeriesModal() {
            loadDashboardExams();
            document.getElementById('ts-modal-title').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: var(--accent-emerald);"></i> Create New Test Series';
            document.getElementById('ts-id').value = '0';
            document.getElementById('ts-title').value = '';
            document.getElementById('ts-price').value = '0';
            document.getElementById('ts-max-attempts').value = '10';
            document.getElementById('ts-question-ids').value = '';
            document.getElementById('ts-published').value = '1';
            document.getElementById('testSeriesModal').classList.add('show');
        }

        function editTestSeries(id) {
            loadDashboardExams();
            const item = currentTestSeriesList.find(ts => ts.id == id);
            if (!item) return;

            document.getElementById('ts-modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Edit Test Series #' + id;
            document.getElementById('ts-id').value = item.id;
            document.getElementById('ts-title').value = item.title;
            setTimeout(() => {
                const tsDropdown = document.getElementById('ts-exam-name');
                if (tsDropdown && item.exam_name) tsDropdown.value = item.exam_name;
            }, 300);
            document.getElementById('ts-price').value = item.price;
            document.getElementById('ts-max-attempts').value = item.max_attempts;
            document.getElementById('ts-question-ids').value = item.question_ids;
            document.getElementById('ts-published').value = item.is_published;
            document.getElementById('testSeriesModal').classList.add('show');
        }

        function closeTestSeriesModal() {
            document.getElementById('testSeriesModal').classList.remove('show');
        }

        function submitTestSeriesForm(e) {
            e.preventDefault();
            const id = document.getElementById('ts-id').value;
            const title = document.getElementById('ts-title').value;
            const exam_name = document.getElementById('ts-exam-name').value;
            const price = document.getElementById('ts-price').value;
            const max_attempts = document.getElementById('ts-max-attempts').value;
            const question_ids = document.getElementById('ts-question-ids').value;
            const is_published = document.getElementById('ts-published').value;

            const formData = new FormData();
            formData.append('action', 'save_test_series');
            formData.append('id', id);
            formData.append('title', title);
            formData.append('exam_name', exam_name);
            formData.append('price', price);
            formData.append('max_attempts', max_attempts);
            formData.append('question_ids', question_ids);
            formData.append('is_published', is_published);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closeTestSeriesModal();
                    loadDashboardTestSeries();
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Failed to save test series: ' + err.message));
        }

        function toggleTestSeriesPublish(id) {
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_test_series_publish', id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    loadDashboardTestSeries();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => alert('Failed to toggle test series status: ' + err));
        }

        function deleteTestSeries(id) {
            if (!confirm('Are you sure you want to delete this test series?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_test_series');
            formData.append('id', id);

            fetch('api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        loadDashboardTestSeries();
                    } else {
                        alert(data.message);
                    }
                });
        }
    </script>
</body>
</html>
