<?php
// config.php - TiDB Cloud Database Credentials & API Configuration

define('DB_HOST', 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com');
define('DB_PORT', '4000');
define('DB_NAME', 'MPSC');
define('DB_USER', 'zscbg7NahcfLgxs.root');
define('DB_PASS', '9gqd36vEIR9HPZnq');
define('SSL_CA', 'C:/xampp/apache/bin/curl-ca-bundle.crt');

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];

        if (defined('SSL_CA') && file_exists(SSL_CA)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = SSL_CA;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        } else {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database Connection Failed: ' . $e->getMessage()]);
        exit;
    }
}
?>
