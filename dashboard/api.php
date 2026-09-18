<?php
// Unified Master API Bridge
if (file_exists(__DIR__ . '/../api/v1/api.php')) {
    require_once __DIR__ . '/../api/v1/api.php';
} elseif (file_exists(__DIR__ . '/api/v1/api.php')) {
    require_once __DIR__ . '/api/v1/api.php';
} elseif (file_exists(__DIR__ . '/MPSCAPI/api.php')) {
    require_once __DIR__ . '/MPSCAPI/api.php';
} else {
    require_once dirname(__DIR__) . '/MPSCAPI/api.php';
}
