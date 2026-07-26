<?php
// smart_master_sync_discrepancies.php
require_once 'C:/xampp/htdocs/mpsc_dashboard/config.php';

set_time_limit(0);
ini_set('memory_limit', '1024M');

echo "=========================================================================\n";
echo "  MPSC SARTHI - AUTO-RECONNECTING DISCREPANCY SYNC ENGINE (TI-DB CLOUD) \n";
echo "=========================================================================\n\n";

$baseUrl = "http://innovagesolution.com/mpsc-sarthi/services/V1/index.php";
$userID = "169923";
$deviceID = "370b6616-0c9c-3dd8-b532-dc243b325b63";
$appVersion = "2.6.1";

// Helper function to maintain active TiDB Cloud connection
function getResilientPdo(?PDO $currentPdo = null): PDO {
    if ($currentPdo !== null) {
        try {
            $currentPdo->query("SELECT 1");
            return $currentPdo;
        } catch (Exception $e) {
            echo "   [!] TiDB connection lost. Reconnecting to database...\n";
        }
    }
    return getDBConnection();
}

$pdo = getResilientPdo();

// 1. Fetch Local Database Question Counts per Topic
echo "[1/3] Indexing local database question counts per topic...\n";
$localCounts = [];
$stmt = $pdo->query("SELECT topicID, COUNT(*) as cnt FROM tbl_question_bank GROUP BY topicID");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $localCounts[(int)$row['topicID']] = (int)$row['cnt'];
}

// 2. Fetch Remote Topics for All Subjects and Sort Lowest Local Count First
echo "[2/3] Building topic list and sorting by LOWEST local question count first...\n";

$subjects = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 23, 24];
$allTopicsList = [];

foreach ($subjects as $sID) {
    $topUrl = "$baseUrl?xAction=getSubjectTopics&subjectID=$sID&userID=$userID&deviceID=$deviceID&appVersion=$appVersion";
    $ch = curl_init($topUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $topRes = curl_exec($ch);
    curl_close($ch);

    $topArr = json_decode($topRes, true);
    $topics = $topArr['data'] ?? [];

    foreach ($topics as $t) {
        $tID = (int)$t['topicID'];
        $localCnt = $localCounts[$tID] ?? 0;
        $allTopicsList[] = [
            'subjectID' => $sID,
            'topicID' => $tID,
            'topicName' => trim($t['topicNameM'] ?? $t['topicName'] ?? "Topic #$tID"),
            'localCount' => $localCnt
        ];
    }
}

// Sort topics by lowest localCount first
usort($allTopicsList, function($a, $b) {
    return $a['localCount'] <=> $b['localCount'];
});

echo "Indexed " . count($allTopicsList) . " topics across all subjects.\n\n";

// 3. Compare & Sync Lowest-First Subtopics
echo "[3/3] Executing Resilient Discrepancy Check & Sync (Lowest Local Count First)...\n";
echo "-------------------------------------------------------------------------\n";

$totalTopics = count($allTopicsList);
$matchedCount = 0;
$syncedCount = 0;
$totalAdded = 0;

foreach ($allTopicsList as $idx => $tItem) {
    $sID = $tItem['subjectID'];
    $tID = $tItem['topicID'];
    $tName = $tItem['topicName'];
    $localCount = $tItem['localCount'];

    // Ensure active PDO connection before API call
    $pdo = getResilientPdo($pdo);

    // Fetch remote questions for this topic
    $qUrl = "$baseUrl?xAction=getQuetionList&subjectID=$sID&topicID=$tID&userID=$userID&deviceID=$deviceID&appVersion=$appVersion";
    $ch = curl_init($qUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    $qRes = curl_exec($ch);
    curl_close($ch);

    $qArr = json_decode($qRes, true);
    $remoteQuestions = $qArr['data'] ?? [];
    $remoteCount = count($remoteQuestions);

    if ($localCount >= $remoteCount && $remoteCount > 0) {
        echo sprintf("[%d/%d] ✓ Topic #%-4d (Sub #%-2d) | %-36s | Local: %-4d | Remote: %-4d -> MATCHED\n", 
            $idx+1, $totalTopics, $tID, $sID, mb_strimwidth($tName, 0, 34, ".."), $localCount, $remoteCount);
        $matchedCount++;
        continue;
    }

    $syncedCount++;
    $missing = max(0, $remoteCount - $localCount);
    echo sprintf("[%d/%d] ⚡ Topic #%-4d (Sub #%-2d) | %-36s | Local: %-4d | Remote: %-4d -> SYNCING (+%d Qs)...\n", 
        $idx+1, $totalTopics, $tID, $sID, mb_strimwidth($tName, 0, 34, ".."), $localCount, $remoteCount, $missing);

    $insertStmt = $pdo->prepare("INSERT INTO tbl_question_bank 
        (questionID, subjectID, topicID, questionName, questionNameE, queOption1, queOption1E, queOption2, queOption2E, queOption3, queOption3E, queOption4, queOption4E, correctAnswer, solutionText, solutionTextE, examName, status, dateAdded, dateModified)
        VALUES 
        (:qid, :sid, :tid, :qm, :qe, :o1m, :o1e, :o2m, :o2e, :o3m, :o3e, :o4m, :o4e, :ans, :solm, :sole, :exam, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        questionName = VALUES(questionName),
        questionNameE = VALUES(questionNameE),
        queOption1 = VALUES(queOption1),
        queOption2 = VALUES(queOption2),
        queOption3 = VALUES(queOption3),
        queOption4 = VALUES(queOption4),
        correctAnswer = VALUES(correctAnswer),
        solutionText = VALUES(solutionText),
        examName = VALUES(examName),
        dateModified = NOW()");

    $added = 0;
    foreach ($remoteQuestions as $q) {
        $qID = (int)($q['questionID'] ?? $q['id'] ?? 0);
        if ($qID <= 0) continue;

        try {
            $insertStmt->execute([
                ':qid'  => $qID,
                ':sid'  => $sID,
                ':tid'  => $tID,
                ':qm'   => $q['questionNameM'] ?? $q['questionName'] ?? '',
                ':qe'   => $q['questionNameE'] ?? '',
                ':o1m'  => $q['queOption1M'] ?? $q['queOption1'] ?? '',
                ':o1e'  => $q['queOption1E'] ?? '',
                ':o2m'  => $q['queOption2M'] ?? $q['queOption2'] ?? '',
                ':o2e'  => $q['queOption2E'] ?? '',
                ':o3m'  => $q['queOption3M'] ?? $q['queOption3'] ?? '',
                ':o3e'  => $q['queOption3E'] ?? '',
                ':o4m'  => $q['queOption4M'] ?? $q['queOption4'] ?? '',
                ':o4e'  => $q['queOption4E'] ?? '',
                ':ans'  => (string)($q['correctAnswer'] ?? '1'),
                ':solm' => $q['solutionTextM'] ?? $q['solutionText'] ?? '',
                ':sole' => $q['solutionTextE'] ?? '',
                ':exam' => $q['examName'] ?? 'MPSC'
            ]);
            $added++;
        } catch (Exception $e) {
            // If connection dropped mid-batch, reconnect and retry once
            $pdo = getResilientPdo($pdo);
            try {
                $insertStmt = $pdo->prepare("INSERT INTO tbl_question_bank 
                    (questionID, subjectID, topicID, questionName, questionNameE, queOption1, queOption1E, queOption2, queOption2E, queOption3, queOption3E, queOption4, queOption4E, correctAnswer, solutionText, solutionTextE, examName, status, dateAdded, dateModified)
                    VALUES 
                    (:qid, :sid, :tid, :qm, :qe, :o1m, :o1e, :o2m, :o2e, :o3m, :o3e, :o4m, :o4e, :ans, :solm, :sole, :exam, 1, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    questionName = VALUES(questionName),
                    questionNameE = VALUES(questionNameE),
                    queOption1 = VALUES(queOption1),
                    queOption2 = VALUES(queOption2),
                    queOption3 = VALUES(queOption3),
                    queOption4 = VALUES(queOption4),
                    correctAnswer = VALUES(correctAnswer),
                    solutionText = VALUES(solutionText),
                    examName = VALUES(examName),
                    dateModified = NOW()");

                $insertStmt->execute([
                    ':qid'  => $qID,
                    ':sid'  => $sID,
                    ':tid'  => $tID,
                    ':qm'   => $q['questionNameM'] ?? $q['questionName'] ?? '',
                    ':qe'   => $q['questionNameE'] ?? '',
                    ':o1m'  => $q['queOption1M'] ?? $q['queOption1'] ?? '',
                    ':o1e'  => $q['queOption1E'] ?? '',
                    ':o2m'  => $q['queOption2M'] ?? $q['queOption2'] ?? '',
                    ':o2e'  => $q['queOption2E'] ?? '',
                    ':o3m'  => $q['queOption3M'] ?? $q['queOption3'] ?? '',
                    ':o3e'  => $q['queOption3E'] ?? '',
                    ':o4m'  => $q['queOption4M'] ?? $q['queOption4'] ?? '',
                    ':o4e'  => $q['queOption4E'] ?? '',
                    ':ans'  => (string)($q['correctAnswer'] ?? '1'),
                    ':solm' => $q['solutionTextM'] ?? $q['solutionText'] ?? '',
                    ':sole' => $q['solutionTextE'] ?? '',
                    ':exam' => $q['examName'] ?? 'MPSC'
                ]);
                $added++;
            } catch (Exception $e2) {
                // Ignore permanent duplicates
            }
        }
    }
    $totalAdded += $added;
    echo "      -> Inserted/Updated $added questions.\n";
    usleep(50000); // 50ms pause
}

echo "\n=========================================================================\n";
echo "   MASTER SMART AUTO-RECONNECTING SYNC COMPLETE!                        \n";
echo "=========================================================================\n";
echo "   Total Topics Checked  : $totalTopics\n";
echo "   Topics 100% Matched   : $matchedCount\n";
echo "   Topics Synced         : $syncedCount\n";
echo "   New Questions Added   : $totalAdded\n";
echo "=========================================================================\n";
?>
