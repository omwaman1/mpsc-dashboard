<?php
// config.php - Unified TiDB Cloud Database Credentials & API Configuration
error_reporting(0);
ini_set('display_errors', '0');

define('DB_HOST', 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com');
define('DB_PORT', '4000');
define('DB_NAME', 'MPSC');
define('DB_USER', 'zscbg7NahcfLgxs.root');
define('DB_PASS', '9gqd36vEIR9HPZnq');

function getDBConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];

    $possibleCAs = [
        'C:\xampp\php\extras\ssl\cacert.pem',
        'C:/xampp/apache/bin/curl-ca-bundle.crt',
        '/etc/ssl/certs/ca-certificates.crt',
        '/etc/pki/tls/certs/ca-bundle.crt'
    ];

    foreach ($possibleCAs as $ca) {
        if (file_exists($ca)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            break;
        }
    }

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}
