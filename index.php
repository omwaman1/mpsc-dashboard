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
            --bg-dark: #090d16;
            --bg-card: rgba(18, 25, 41, 0.75);
            --bg-card-hover: rgba(28, 38, 61, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(99, 102, 241, 0.4);
            
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.35);
            --accent-cyan: #06b6d4;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            
            --sidebar-width: 380px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(6, 182, 212, 0.06) 0%, transparent 40%);
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* HEADER */
        header {
            height: 70px;
            background: rgba(13, 19, 33, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Outfit', sans-serif;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--accent-cyan));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-badge {
            font-size: 11px;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-emerald);
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            background-color: var(--accent-emerald);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--accent-emerald);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
            100% { opacity: 1; transform: scale(1); }
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stats-summary {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.03);
            padding: 6px 12px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-size: 12.5px;
        }

        .stat-item i {
            color: var(--primary);
        }

        .stat-val {
            font-weight: 700;
            color: white;
            font-family: 'Outfit', sans-serif;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px var(--primary-glow);
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.5);
        }

        .btn-jobs {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-bulk {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* MAIN APP LAYOUT */
        .app-container {
            display: flex;
            flex: 1;
            height: calc(100vh - 70px);
        }

        /* SIDEBAR (380px) */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(13, 19, 33, 0.7);
            backdrop-filter: blur(12px);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 13px;
        }

        .search-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 9px 12px 9px 36px;
            color: white;
            font-size: 13px;
            outline: none;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .subject-tree-container {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        /* TREE ITEM STYLES */
        .tree-subject-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .tree-subject-header {
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s ease;
        }

        .tree-subject-header:hover {
            background: rgba(99, 102, 241, 0.08);
        }

        .tree-subject-header.active {
            background: rgba(99, 102, 241, 0.15);
            border-left: 3px solid var(--primary);
        }

        .subject-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }

        .sub-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--accent-cyan);
            flex-shrink: 0;
        }

        .sub-names {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sub-title-e {
            font-weight: 600;
            font-size: 13.5px;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sub-title-m {
            font-size: 11.5px;
            color: var(--text-muted);
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
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
        }

        .btn-sub-sync {
            background: rgba(6, 182, 212, 0.15);
            color: var(--accent-cyan);
            border: 1px solid rgba(6, 182, 212, 0.3);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .btn-sub-sync:hover {
            background: var(--accent-cyan);
            color: white;
        }

        .toggle-arrow {
            font-size: 11px;
            color: var(--text-dim);
            transition: transform 0.2s ease;
        }

        .toggle-arrow.open {
            transform: rotate(90deg);
        }

        /* RECURSIVE TREE NODES */
        .tree-children {
            display: none;
            padding-left: 14px;
            border-left: 1px dashed rgba(255, 255, 255, 0.1);
            margin-left: 20px;
            margin-bottom: 8px;
        }

        .tree-children.open {
            display: block;
        }

        .tree-topic-item {
            padding: 7px 10px;
            margin: 2px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-size: 12.5px;
            color: var(--text-muted);
            transition: all 0.15s ease;
        }

        .tree-topic-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .tree-topic-item.active {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary);
            font-weight: 600;
        }

        /* MAIN CONTENT AREA */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding: 24px;
            gap: 20px;
        }

        /* TOPICS HORIZONTAL STRIP */
        .topics-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 16px 20px;
            backdrop-filter: blur(12px);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topics-scroll {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 6px;
        }

        .topic-chip {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            color: var(--text-muted);
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .topic-chip:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border-color: var(--border-highlight);
        }

        .topic-chip.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 0 12px var(--primary-glow);
        }

        /* SEARCH BAR */
        .controls-bar {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .question-search-box {
            position: relative;
            flex: 1;
        }

        .question-search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
        }

        .question-search-input {
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 16px 12px 42px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .question-search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        /* QUESTION CARDS */
        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .question-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 24px;
            backdrop-filter: blur(12px);
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .question-card:hover {
            border-color: var(--border-highlight);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .q-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        .q-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .q-id-badge {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 13px;
            color: var(--primary);
            background: rgba(99, 102, 241, 0.12);
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.25);
        }

        .lang-badge-btn {
            font-size: 11px;
            font-weight: 600;
            background: rgba(6, 182, 212, 0.15);
            color: var(--accent-cyan);
            padding: 3px 10px;
            border-radius: 12px;
            border: 1px solid rgba(6, 182, 212, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .lang-badge-btn:hover {
            background: var(--accent-cyan);
            color: white;
        }

        .q-tag {
            font-size: 11px;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.12);
            color: var(--accent-amber);
            padding: 3px 10px;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .q-text {
            font-size: 15px;
            font-weight: 500;
            line-height: 1.6;
            color: #f8fafc;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .option-item {
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid var(--border-color);
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
        }

        .option-item.correct {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.35);
            color: #34d399;
            font-weight: 600;
        }

        .opt-num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .option-item.correct .opt-num {
            background: var(--accent-emerald);
            color: white;
        }

        .solution-box {
            background: rgba(6, 182, 212, 0.06);
            border-left: 3px solid var(--accent-cyan);
            padding: 12px 16px;
            border-radius: 0 10px 10px 0;
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        /* PAGINATION */
        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            backdrop-filter: blur(12px);
        }

        .page-info {
            font-size: 13px;
            color: var(--text-muted);
        }

        .page-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-page {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-page:hover:not(:disabled) {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-page:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-page.active {
            background: var(--primary);
            border-color: var(--primary);
            font-weight: 700;
        }

        /* LOADING SPINNER */
        .loading-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .loading-spinner {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 12px;
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
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        .modal-card {
            background: #111827;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            width: 100%;
            max-width: 850px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 14px;
        }

        .modal-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: var(--text-muted);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .modal-close:hover {
            background: var(--accent-rose);
            color: white;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .form-control {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 10px 14px;
            color: white;
            font-size: 13.5px;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        /* RECRUITMENT JOB STYLES */
        .recruitment-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .rec-title-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .rec-title {
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1.4;
        }

        .badge-vacancies {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-weight: 700;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .rec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            margin-top: 8px;
        }

        .rec-table th, .rec-table td {
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 8px 12px;
            text-align: left;
        }

        .rec-table th {
            background: rgba(99, 102, 241, 0.15);
            color: var(--accent-cyan);
            font-weight: 600;
        }

        .rec-table tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.015);
        }

        .link-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-rec-link {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-rec-link:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-apply {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        </div>
    </header>

    <!-- CONTAINER -->
    <div class="app-container">
        
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

            <!-- TOPICS STRIP -->
            <div class="topics-section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fa-solid fa-layer-group" style="color: var(--accent-cyan);"></i>
                        <span id="current-subject-title">All Subjects</span>
                    </div>
                    <span style="font-size: 12px; color: var(--text-dim);" id="topic-count-label">-- Topics</span>
                </div>
                <div class="topics-scroll" id="topics-scroll-container">
                    <div class="topic-chip active" onclick="selectTopic(0)">
                        <span>All Topics</span>
                    </div>
                </div>
            </div>

            <!-- CONTROLS & SEARCH -->
            <div class="controls-bar" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div id="search-scope-badge">
                        <span style="background: rgba(255,255,255,0.08); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 12px;">
                            <i class="fa-solid fa-globe"></i> Scope: Global (All Subjects)
                        </span>
                    </div>
                    <span style="font-size: 11.5px; color: var(--text-dim);"><i class="fa-solid fa-bolt" style="color: var(--accent-amber);"></i> Search as you type OR press Enter / click Search</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="question-search-box" style="flex: 1; position: relative;">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="question-search" class="question-search-input" placeholder="Search Marathi or English words, options, or Question ID..." onkeydown="handleSearchKeyDown(event)">
                        <button id="btn-clear-search" onclick="clearQuestionSearch()" style="display: none; position: absolute; right: 12px; background: transparent; border: none; color: var(--text-dim); cursor: pointer; font-size: 14px;">&times;</button>
                    </div>
                    <button class="btn-action" onclick="triggerManualSearch()" style="background: var(--primary); padding: 10px 18px; border-radius: 8px; font-weight: 600; white-space: nowrap; display: flex; align-items: center; gap: 6px;">
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
            <form id="addQuestionForm" onsubmit="submitNewQuestion(event)">
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select class="form-control" id="modal-subject-id">
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Question Text (Marathi)</label>
                        <textarea class="form-control" id="modal-qtext" rows="3" placeholder="मराठी प्रश्न..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Question Text (English Translation)</label>
                        <textarea class="form-control" id="modal-qtext-e" rows="3" placeholder="English Question Text..."></textarea>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label">Option 1 (Marathi)</label>
                        <input type="text" class="form-control" id="modal-opt1" placeholder="पर्याय १..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option 1 (English)</label>
                        <input type="text" class="form-control" id="modal-opt1-e" placeholder="Option 1 English...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Option 2 (Marathi)</label>
                        <input type="text" class="form-control" id="modal-opt2" placeholder="पर्याय २..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option 2 (English)</label>
                        <input type="text" class="form-control" id="modal-opt2-e" placeholder="Option 2 English...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Option 3 (Marathi)</label>
                        <input type="text" class="form-control" id="modal-opt3" placeholder="पर्याय ३..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option 3 (English)</label>
                        <input type="text" class="form-control" id="modal-opt3-e" placeholder="Option 3 English...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Option 4 (Marathi)</label>
                        <input type="text" class="form-control" id="modal-opt4" placeholder="पर्याय ४..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Option 4 (English)</label>
                        <input type="text" class="form-control" id="modal-opt4-e" placeholder="Option 4 English...">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Correct Answer</label>
                        <select class="form-control" id="modal-correct-ans">
                            <option value="1">Option 1 (पर्याय १)</option>
                            <option value="2">Option 2 (पर्याय २)</option>
                            <option value="3">Option 3 (पर्याय ३)</option>
                            <option value="4">Option 4 (पर्याय ४)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exam Name / Type</label>
                        <input type="text" class="form-control" id="modal-exam-name" value="MPSC" placeholder="e.g. MPSC Group C">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Solution / Explanation (Marathi)</label>
                        <textarea class="form-control" id="modal-sol" rows="2" placeholder="स्पष्टीकरण..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Solution / Explanation (English)</label>
                        <textarea class="form-control" id="modal-sol-e" rows="2" placeholder="English Explanation..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-action" style="width: 100%; justify-content: center; margin-top: 8px;">
                    <i class="fa-solid fa-save"></i> Save Question to Database
                </button>
            </form>
        </div>
    </div>

    <!-- EDIT QUESTION MODAL -->
    <div class="modal-overlay" id="editQuestionModal">
        <div class="modal-card" style="max-width: 880px; max-height: 92vh; overflow-y: auto;">
            <div class="modal-header">
                <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--accent-amber);"></i> Edit Question & Explanations (Marathi & English)</div>
                <button class="modal-close" onclick="closeEditQuestionModal()">&times;</button>
            </div>
            <form id="editQuestionForm" onsubmit="saveQuestionEdit(event)">
                <input type="hidden" id="edit-q-id">

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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px;">
                    <div class="form-group">
                        <label class="form-label">Correct Option Answer</label>
                        <select class="form-control" id="edit-correct-ans">
                            <option value="1">Option 1</option>
                            <option value="2">Option 2</option>
                            <option value="3">Option 3</option>
                            <option value="4">Option 4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exam Name (e.g. MPSC PRE 2025)</label>
                        <input type="text" class="form-control" id="edit-exam-name">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px;">
                    <div class="form-group">
                        <label class="form-label">Solution Explanation (Marathi)</label>
                        <textarea class="form-control" id="edit-sol-m" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Solution Explanation (English)</label>
                        <textarea class="form-control" id="edit-sol-e" rows="4"></textarea>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
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
                const res = await fetch('api.php?action=subjects');
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

        function toggleTreeBranch(subId, e) {
            if (e) e.stopPropagation();
            const branch = document.getElementById(`branch-${subId}`);
            const arrow = document.getElementById(`arrow-${subId}`);
            if (branch) {
                branch.classList.toggle('open');
                if (arrow) arrow.classList.toggle('open');
            }
        }

        function toggleSubNode(topicId, e) {
            if (e) e.stopPropagation();
            const branch = document.getElementById(`branch-node-${topicId}`);
            if (branch) {
                branch.classList.toggle('open');
            }
        }

        // Single Subject Sync
        async function syncSingleSubject(subId, btnElem) {
            const origHtml = btnElem.innerHTML;
            btnElem.disabled = true;
            btnElem.innerHTML = `<i class="fa-solid fa-rotate fa-spin"></i> Syncing...`;

            try {
                const res = await fetch(`api.php?action=sync_subject&subject_id=${subId}`);
                const json = await res.json();
                if (json.status === 'success') {
                    alert(json.message);
                    fetchStats();
                    fetchSubjectsAndBuildTree();
                    if (currentSubjectId === subId) {
                        fetchQuestions();
                    }
                } else {
                    alert("Sync Error: " + json.message);
                }
            } catch (err) {
                alert("Failed to sync subject. Please check server.");
            } finally {
                btnElem.disabled = false;
                btnElem.innerHTML = origHtml;
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

        // 5. EDIT QUESTION MODAL ENGINE
        async function openEditQuestionModal(qid) {
            try {
                const res = await fetch(`api.php?action=get_question_by_id&question_id=${qid}`);
                const json = await res.json();
                if (json.status === 'success') {
                    const q = json.data;
                    document.getElementById('edit-q-id').value = q.questionID;
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
                    document.getElementById('edit-correct-ans').value = (q.correctAnswer || '1').trim();
                    document.getElementById('edit-exam-name').value = q.examName || '';
                    document.getElementById('edit-sol-m').value = q.solutionText || '';
                    document.getElementById('edit-sol-e').value = q.solutionTextE || '';

                    document.getElementById('editQuestionModal').classList.add('active');
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (err) {
                alert('Failed to load question details: ' + err);
            }
        }

        function closeEditQuestionModal() {
            document.getElementById('editQuestionModal').classList.remove('active');
        }

        async function saveQuestionEdit(e) {
            e.preventDefault();
            const data = {
                questionID: document.getElementById('edit-q-id').value,
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
                examName: document.getElementById('edit-exam-name').value,
                solutionText: document.getElementById('edit-sol-m').value,
                solutionTextE: document.getElementById('edit-sol-e').value,
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
                    const data = json.data;
                    totalPages = data.total_pages || 1;
                    renderQuestions(data.questions);
                    renderPagination(data.total_questions, data.current_page, data.total_pages);
                } else {
                    container.innerHTML = `<div class="loading-state" style="color:var(--accent-rose);">Error: ${json.message}</div>`;
                }
            } catch (err) {
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
                card.id = `qcard-${q.questionID}`;

                const hasEnglish = q.questionNameE && q.questionNameE.trim() !== '';
                const activeLang = cardLangState[q.questionID] || 'mr';

                const displayQ = (activeLang === 'en' && hasEnglish) ? q.questionNameE : q.questionName;
                const opt1 = (activeLang === 'en' && q.queOption1E) ? q.queOption1E : q.queOption1;
                const opt2 = (activeLang === 'en' && q.queOption2E) ? q.queOption2E : q.queOption2;
                const opt3 = (activeLang === 'en' && q.queOption3E) ? q.queOption3E : q.queOption3;
                const opt4 = (activeLang === 'en' && q.queOption4E) ? q.queOption4E : q.queOption4;
                const sol  = (activeLang === 'en' && q.solutionTextE) ? q.solutionTextE : q.solutionText;
                const ans  = (q.correctAnswer || '1').trim();

                card.innerHTML = `
                    <div class="q-header">
                        <div class="q-meta">
                            <span class="q-id-badge">Q #${q.questionID}</span>
                            <span class="q-tag"><i class="fa-solid fa-book"></i> ${escapeHtml(q.subjectNameE || 'Subject')}</span>
                            ${q.topicNameE ? `<span class="q-tag" style="background:rgba(6,182,212,0.12); color:var(--accent-cyan); border-color:rgba(6,182,212,0.25);"><i class="fa-solid fa-tag"></i> ${escapeHtml(q.topicNameE)}</span>` : ''}
                            <span class="q-tag" style="background:rgba(168,85,247,0.18); color:#c084fc; border:1px solid rgba(168,85,247,0.35); font-weight:700;"><i class="fa-solid fa-graduation-cap"></i> ${escapeHtml(q.examName && q.examName !== '1' ? q.examName : 'MPSC PRE')}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button class="btn-action" style="background: rgba(245, 158, 11, 0.18); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.35); font-weight: 600; padding: 4px 10px; font-size: 12px;" onclick="openEditQuestionModal(${q.questionID})">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Question
                            </button>
                            ${hasEnglish ? `
                                <button class="lang-badge-btn" onclick="toggleCardLanguage(${q.questionID})">
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
                            <strong><i class="fa-solid fa-lightbulb" style="color:var(--accent-amber);"></i> Explanation:</strong> ${sol}
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

        // MODAL CONTROLS
        function openAddQuestionModal() { document.getElementById('addQuestionModal').classList.add('show'); }
        function closeAddQuestionModal() { document.getElementById('addQuestionModal').classList.remove('show'); }
        function openBulkModal() { document.getElementById('bulkImportModal').classList.add('show'); }
        function closeBulkModal() { document.getElementById('bulkImportModal').classList.remove('show'); }

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
    </script>
</body>
</html>
