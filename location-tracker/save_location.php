<?php
// save_location.php - Saves location readings grouped by click/session

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST method required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['latitude']) || !isset($input['longitude'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$sessionId = $input['session_id'] ?? uniqid('click_');
$readingNum = intval($input['reading_number'] ?? 1);

$jsonFile = __DIR__ . '/locations.json';

// Ensure file exists and is writable
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, '[]');
    chmod($jsonFile, 0666);
}

$data = [];
$raw = file_get_contents($jsonFile);
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        // Check if old format (flat array without session_id) — reset if so
        if (!empty($decoded) && !isset($decoded[0]['session_id'])) {
            $data = []; // Old format, start fresh
        } else {
            $data = $decoded;
        }
    }
}

// Find existing click group
$clickIndex = -1;
foreach ($data as $i => $click) {
    if (($click['session_id'] ?? '') === $sessionId) {
        $clickIndex = $i;
        break;
    }
}

$reading = [
    'reading'    => $readingNum,
    'latitude'   => floatval($input['latitude']),
    'longitude'  => floatval($input['longitude']),
    'accuracy'   => isset($input['accuracy']) ? round(floatval($input['accuracy']), 1) : null,
    'altitude'   => $input['altitude'] ?? null,
    'time'       => $input['timestamp'] ?? date('c'),
    'google_maps'=> 'https://www.google.com/maps?q=' . floatval($input['latitude']) . ',' . floatval($input['longitude'])
];

if ($clickIndex >= 0) {
    $data[$clickIndex]['readings'][] = $reading;
    $data[$clickIndex]['total_readings'] = count($data[$clickIndex]['readings']);

    // Update best reading
    $bestAcc = 999999;
    $bestReading = null;
    foreach ($data[$clickIndex]['readings'] as $r) {
        if ($r['accuracy'] !== null && $r['accuracy'] < $bestAcc) {
            $bestAcc = $r['accuracy'];
            $bestReading = $r;
        }
    }
    if ($bestReading) {
        $data[$clickIndex]['best_location'] = [
            'latitude'   => $bestReading['latitude'],
            'longitude'  => $bestReading['longitude'],
            'accuracy'   => $bestReading['accuracy'],
            'google_maps'=> $bestReading['google_maps']
        ];
    }
} else {
    $clickNumber = count($data) + 1;
    $data[] = [
        'click_number'   => $clickNumber,
        'session_id'     => $sessionId,
        'ip'             => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'userAgent'      => $input['userAgent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
        'screenRes'      => $input['screenRes'] ?? null,
        'language'        => $input['language'] ?? null,
        'started_at'     => date('Y-m-d H:i:s T'),
        'total_readings' => 1,
        'best_location'  => [
            'latitude'   => floatval($input['latitude']),
            'longitude'  => floatval($input['longitude']),
            'accuracy'   => isset($input['accuracy']) ? round(floatval($input['accuracy']), 1) : null,
            'google_maps'=> 'https://www.google.com/maps?q=' . floatval($input['latitude']) . ',' . floatval($input['longitude'])
        ],
        'readings'       => [$reading]
    ];
}

$result = file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if ($result !== false) {
    echo json_encode(['status' => 'success', 'reading' => $readingNum]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Write failed - check file permissions']);
}
