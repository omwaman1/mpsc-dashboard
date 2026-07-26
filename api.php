<?php
// C:\xampp\htdocs\mpsc_dashboard\api.php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? ($_REQUEST['action'] ?? ''));

/**
 * High-Speed In-Memory Multi-Level Topic Tree Builder
 */
if (!function_exists('getFastSubjectTrees')) {
    function getFastSubjectTrees(PDO $pdo) {
        $subStmt = $pdo->query("SELECT s.subjectID, s.subjectNameE, s.subjectNameM, s.status,
                                       COUNT(DISTINCT t.topicID) as topic_count,
                                       COUNT(DISTINCT q.questionID) as question_count
                                FROM tbl_subjects s
                                LEFT JOIN tbl_topics t ON s.subjectID = t.subjectID
                                LEFT JOIN tbl_question_bank q ON s.subjectID = q.subjectID
                                GROUP BY s.subjectID, s.subjectNameE, s.subjectNameM, s.status
                                ORDER BY s.subjectID ASC");
        $subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);

        $topStmt = $pdo->query("SELECT t.topicID, t.subjectID, COALESCE(t.parentTopicID, 0) as parentTopicID, t.topicNameE, t.topicNameM, t.sortOrder, t.status,
                                       COUNT(q.questionID) as question_count
                                FROM tbl_topics t
                                LEFT JOIN tbl_question_bank q ON t.topicID = q.topicID
                                GROUP BY t.topicID, t.subjectID, t.parentTopicID, t.topicNameE, t.topicNameM, t.sortOrder, t.status
                                ORDER BY COALESCE(t.sortOrder, t.topicID) ASC, t.topicID ASC");
        $allTopics = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        $topicsBySubject = [];
        foreach ($allTopics as $t) {
            $sid = (int)$t['subjectID'];
            $topicsBySubject[$sid][] = $t;
        }

        foreach ($subjects as &$sub) {
            $sid = (int)$sub['subjectID'];
            $subTopics = $topicsBySubject[$sid] ?? [];
            $sub['topics_tree'] = buildTreeFromFlatList($subTopics, 0);
        }

        return $subjects;
    }
}

if (!function_exists('buildTreeFromFlatList')) {
    function buildTreeFromFlatList(array &$flatList, int $parentId = 0) {
        $branch = [];
        foreach ($flatList as $node) {
            if ((int)$node['parentTopicID'] === $parentId) {
                $children = buildTreeFromFlatList($flatList, (int)$node['topicID']);
                
                $childQCount = 0;
                foreach ($children as $c) {
                    $childQCount += (int)$c['question_count'];
                }
                $node['children'] = $children;
                $node['question_count'] = (int)$node['question_count'] + $childQCount;

                $branch[] = $node;
            }
        }
        return $branch;
    }
}

switch ($action) {
    case 'stats':
        try {
            $totalSubjects = $pdo->query("SELECT COUNT(*) FROM tbl_subjects")->fetchColumn();
            $totalTopics   = $pdo->query("SELECT COUNT(*) FROM tbl_topics")->fetchColumn();
            $totalQuestions= $pdo->query("SELECT COUNT(*) FROM tbl_question_bank")->fetchColumn();
            $totalJobs     = $pdo->query("SELECT COUNT(*) FROM tbl_recruitment WHERE status = 1")->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total_subjects'    => (int)$totalSubjects,
                    'total_topics'      => (int)$totalTopics,
                    'total_questions'   => (int)$totalQuestions,
                    'total_recruitments'=> (int)$totalJobs
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    // ANDROID APP API: List all recruitment drives in EXACT Majhi Naukri Post Order
    case 'recruitments':
        try {
            $stmt = $pdo->query("SELECT jobID, advtNo, titleM, titleE, examNameM, examNameE, totalVacancies, totalVacanciesTextM, totalVacanciesTextE, jobLocationM, jobLocationE, applicationMode, postDate, lastUpdateDate, lastDateApply, lastDateApplyTextM, lastDateApplyTextE, examDate, examDateTextM, examDateTextE, notificationPdfUrl, applyOnlineUrl, tags, dateAdded 
                                FROM tbl_recruitment 
                                WHERE status = 1 
                                ORDER BY jobID ASC");
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $jobs]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    // ANDROID APP API: Get full details & posts table + ALL HISTORICAL PAST ADVERTISEMENTS
    case 'recruitment_details':
        try {
            $jobID = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
            if ($jobID <= 0) {
                throw new Exception("Invalid Job ID.");
            }

            $stmt = $pdo->prepare("SELECT * FROM tbl_recruitment WHERE jobID = :jid");
            $stmt->execute([':jid' => $jobID]);
            $rec = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rec) {
                throw new Exception("Recruitment entry not found.");
            }

            // Fetch relational posts breakdown table
            $postStmt = $pdo->prepare("SELECT * FROM tbl_recruitment_posts WHERE jobID = :jid ORDER BY postID ASC");
            $postStmt->execute([':jid' => $jobID]);
            $posts = $postStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch related historical recruitments (e.g. Group C 2025, Group C 2024...)
            $catKey = '';
            if (stripos($rec['titleM'], 'Group C') !== false || stripos($rec['titleM'], 'गट-क') !== false) {
                $catKey = 'Group C';
            } else if (stripos($rec['titleM'], 'Talathi') !== false || stripos($rec['titleM'], 'तलाठी') !== false) {
                $catKey = 'Talathi';
            }

            $history = [];
            if ($catKey !== '') {
                $histStmt = $pdo->prepare("SELECT * FROM tbl_recruitment WHERE jobID != :jid AND (titleM LIKE :k OR titleE LIKE :k OR tags LIKE :k) ORDER BY jobID DESC");
                $histStmt->execute([':jid' => $jobID, ':k' => "%$catKey%"]);
                $history = $histStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($history as &$h) {
                    $hpStmt = $pdo->prepare("SELECT * FROM tbl_recruitment_posts WHERE jobID = :hjid ORDER BY postID ASC");
                    $hpStmt->execute([':hjid' => $h['jobID']]);
                    $h['posts_table'] = $hpStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            $rec['posts_table'] = $posts;
            $rec['history_recruitments'] = $history;
            $rec['postsJsonM'] = json_decode($rec['postsJsonM'], true);
            $rec['postsJsonE'] = json_decode($rec['postsJsonE'], true);
            $rec['faqsJson']   = json_decode($rec['faqsJson'], true);

            echo json_encode(['status' => 'success', 'data' => $rec]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'subjects':
        try {
            $subjects = getFastSubjectTrees($pdo);
            echo json_encode(['status' => 'success', 'data' => $subjects]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'topics':
        try {
            $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : (isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0);
            $sql = "SELECT t.topicID, t.subjectID, COALESCE(t.parentTopicID, 0) as parentTopicID, t.topicNameE, t.topicNameM, t.sortOrder, t.status,
                           COUNT(q.questionID) as question_count
                    FROM tbl_topics t
                    LEFT JOIN tbl_question_bank q ON t.topicID = q.topicID
                    " . ($subjectId > 0 ? "WHERE t.subjectID = $subjectId" : "") . "
                    GROUP BY t.topicID, t.subjectID, t.parentTopicID, t.topicNameE, t.topicNameM, t.sortOrder, t.status
                    ORDER BY COALESCE(t.sortOrder, t.topicID) ASC, t.topicID ASC";
            $stmt = $pdo->query($sql);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'questions':
        try {
            $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : (isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0);
            $topicId   = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : (isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0);
            $search    = isset($_GET['search']) ? trim($_GET['search']) : '';
            $page      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit     = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
            $offset    = ($page - 1) * $limit;

            $where = ["1=1"];
            $params = [];

            if ($subjectId > 0) {
                $where[] = "q.subjectID = :subject_id";
                $params[':subject_id'] = $subjectId;
            }
            if ($topicId > 0) {
                $childrenStmt = $pdo->prepare("SELECT topicID FROM tbl_topics WHERE COALESCE(parentTopicID, 0) = :tid");
                $childrenStmt->execute([':tid' => $topicId]);
                $childIDs = $childrenStmt->fetchAll(PDO::FETCH_COLUMN);
                $allIDs = array_merge([$topicId], array_map('intval', $childIDs));
                
                $inClause = implode(',', array_unique($allIDs));
                $where[] = "q.topicID IN ($inClause)";
            }
            if ($search !== '') {
                $where[] = "(
                    q.questionName LIKE :search 
                    OR q.questionNameE LIKE :search 
                    OR q.queOption1 LIKE :search OR q.queOption1E LIKE :search 
                    OR q.queOption2 LIKE :search OR q.queOption2E LIKE :search 
                    OR q.queOption3 LIKE :search OR q.queOption3E LIKE :search 
                    OR q.queOption4 LIKE :search OR q.queOption4E LIKE :search 
                    OR q.solutionText LIKE :search OR q.solutionTextE LIKE :search 
                    OR q.examName LIKE :search 
                    OR q.questionID = :exact_id
                )";
                $params[':search'] = "%$search%";
                $params[':exact_id'] = is_numeric($search) ? (int)$search : 0;
            }

            $whereClause = implode(' AND ', $where);

            $countSql = "SELECT COUNT(*) FROM tbl_question_bank q WHERE $whereClause";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalQuestions = (int)$countStmt->fetchColumn();

            $sql = "SELECT q.*, s.subjectNameE, s.subjectNameM, t.topicNameE, t.topicNameM
                    FROM tbl_question_bank q
                    LEFT JOIN tbl_subjects s ON q.subjectID = s.subjectID
                    LEFT JOIN tbl_topics t ON q.topicID = t.topicID
                    WHERE $whereClause
                    ORDER BY q.questionID DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalPages = ceil($totalQuestions / $limit);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'questions'       => $questions,
                    'total_questions' => $totalQuestions,
                    'current_page'    => $page,
                    'total_pages'     => $totalPages,
                    'limit'           => $limit
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'get_question_by_id':
        try {
            $qid = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;
            $stmt = $pdo->prepare("SELECT q.*, s.subjectNameE, s.subjectNameM, t.topicNameE, t.topicNameM 
                                   FROM tbl_question_bank q 
                                   LEFT JOIN tbl_subjects s ON q.subjectID = s.subjectID
                                   LEFT JOIN tbl_topics t ON q.topicID = t.topicID
                                   WHERE q.questionID = :qid");
            $stmt->execute([':qid' => $qid]);
            $q = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($q) {
                echo json_encode(['status' => 'success', 'data' => $q]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Question not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'update_question':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data) || empty($data['questionID'])) {
                $data = $_POST;
            }
            if (empty($data['questionID'])) {
                echo json_encode(['status' => 'error', 'message' => 'Question ID is required']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE tbl_question_bank SET 
                questionName = :qm,
                questionNameE = :qe,
                queOption1 = :o1m, queOption1E = :o1e,
                queOption2 = :o2m, queOption2E = :o2e,
                queOption3 = :o3m, queOption3E = :o3e,
                queOption4 = :o4m, queOption4E = :o4e,
                correctAnswer = :ans,
                solutionText = :solm,
                solutionTextE = :sole,
                examName = :exam,
                lastUpdateDate = NOW()
                WHERE questionID = :qid");

            $stmt->execute([
                ':qm'   => $data['questionName'] ?? '',
                ':qe'   => $data['questionNameE'] ?? '',
                ':o1m'  => $data['queOption1'] ?? '',
                ':o1e'  => $data['queOption1E'] ?? '',
                ':o2m'  => $data['queOption2'] ?? '',
                ':o2e'  => $data['queOption2E'] ?? '',
                ':o3m'  => $data['queOption3'] ?? '',
                ':o3e'  => $data['queOption3E'] ?? '',
                ':o4m'  => $data['queOption4'] ?? '',
                ':o4e'  => $data['queOption4E'] ?? '',
                ':ans'  => (string)($data['correctAnswer'] ?? '1'),
                ':solm' => $data['solutionText'] ?? '',
                ':sole' => $data['solutionTextE'] ?? '',
                ':exam' => $data['examName'] ?? 'MPSC',
                ':qid'  => (int)$data['questionID']
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Question updated successfully!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'sync_subject':
        try {
            $subID = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : (isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : (isset($_REQUEST['subject_id']) ? (int)$_REQUEST['subject_id'] : 0));
            if ($subID <= 0) {
                throw new Exception("Invalid Subject ID.");
            }

            $baseUrl = "http://innovagesolution.com/mpsc-sarthi/services/V1/index.php";
            $userID = "169923";
            $deviceID = "370b6616-0c9c-3dd8-b532-dc243b325b63";
            $appVersion = "2.6.1";

            $subNameStmt = $pdo->prepare("SELECT subjectNameE FROM tbl_subjects WHERE subjectID = :sid");
            $subNameStmt->execute([':sid' => $subID]);
            $subTitle = $subNameStmt->fetchColumn() ?: "Subject #$subID";

            $topicMap = [];
            $stmt = $pdo->prepare("SELECT topicID, topicNameE, topicNameM FROM tbl_topics WHERE subjectID = :sid");
            $stmt->execute([':sid' => $subID]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $topicMap[(int)$row['topicID']] = $row['topicNameE'] ?: $row['topicNameM'];
            }

            $topUrl = "$baseUrl?xAction=getSubjectTopics&subjectID=$subID&userID=$userID&deviceID=$deviceID&appVersion=$appVersion";
            $ch = curl_init($topUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            $res = curl_exec($ch);
            curl_close($ch);

            $jsonTop = json_decode($res, true);
            if ($jsonTop && isset($jsonTop['data']) && is_array($jsonTop['data']) && !empty($jsonTop['data'])) {
                foreach ($jsonTop['data'] as $top) {
                    $tID = (int)($top['topicID'] ?? $top['id'] ?? 0);
                    $tNameE = trim($top['topicNameE'] ?? $top['topicNameM'] ?? '');
                    if ($tID > 0) {
                        $topicMap[$tID] = $tNameE;
                        $insTop = $pdo->prepare("INSERT INTO tbl_topics (topicID, subjectID, parentTopicID, topicNameE, topicNameM, status) 
                                                 VALUES (:tid, :sid, 0, :tnameE, :tnameE, 1) 
                                                 ON DUPLICATE KEY UPDATE subjectID = :sid, topicNameE = :tnameE, status = 1");
                        $insTop->execute([':tid' => $tID, ':sid' => $subID, ':tnameE' => $tNameE]);
                    }
                }
            }

            if (empty($topicMap)) {
                for ($t = 1; $t <= 250; $t += 2) {
                    $topicMap[$t] = "Topic #$t";
                }
            }

            $topicList = [];
            foreach ($topicMap as $tID => $tName) {
                $topicList[] = ['topicID' => $tID, 'topicName' => $tName];
            }

            $maxQStmt = $pdo->query("SELECT MAX(questionID) FROM tbl_question_bank");
            $currentMaxID = (int)$maxQStmt->fetchColumn();

            $checkQStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_question_bank WHERE questionID = :qid OR questionName = :qname");
            $insQStmt = $pdo->prepare("INSERT INTO tbl_question_bank 
                (questionID, subjectID, topicID, questionName, questionPassage, queOption1, queOption2, queOption3, queOption4, correctAnswer, solutionText, examName, subjectNameE, topicNameE, topicNameM, status) 
                VALUES (:qid, :subID, :topID, :qName, :passage, :opt1, :opt2, :opt3, :opt4, :ans, :solText, :examName, :subName, :topName, :topName, 1)");

            $newAdded = 0;
            $skipped = 0;
            $totalRemoteFound = 0;

            $chunks = array_chunk($topicList, 15);
            foreach ($chunks as $chunk) {
                $mh = curl_multi_init();
                $handles = [];

                foreach ($chunk as $i => $t) {
                    $tID = (int)$t['topicID'];
                    $qUrl = "$baseUrl?xAction=getQuetionList&subjectID=$subID&topicID=$tID&userID=$userID&deviceID=$deviceID&appVersion=$appVersion";
                    $c = curl_init($qUrl);
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($c, CURLOPT_TIMEOUT, 4);
                    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
                    curl_multi_add_handle($mh, $c);
                    $handles[$i] = ['ch' => $c, 'topic' => $t];
                }

                $running = null;
                do {
                    curl_multi_exec($mh, $running);
                    curl_multi_select($mh, 0.05);
                } while ($running > 0);

                foreach ($handles as $item) {
                    $c = $item['ch'];
                    $t = $item['topic'];
                    $tID = (int)$t['topicID'];
                    $tName = $t['topicName'];

                    $res = curl_multi_getcontent($c);
                    curl_multi_remove_handle($mh, $c);
                    curl_close($c);

                    $json = json_decode($res, true);
                    if ($json && isset($json['data']) && is_array($json['data']) && !empty($json['data'])) {
                        $qList = $json['data'];
                        $totalRemoteFound += count($qList);
                        $sample = $qList[0];
                        $subName = trim($sample['subjectNameE'] ?? $sample['subjectNameM'] ?? $subTitle);

                        foreach ($qList as $q) {
                            $qID = (int)($q['questionID'] ?? 0);
                            $qName = trim($q['questionName'] ?? '');
                            if (empty($qName)) continue;

                            $checkQStmt->execute([':qid' => $qID, ':qname' => $qName]);
                            if ($checkQStmt->fetchColumn() == 0) {
                                $currentMaxID++;
                                $useID = ($qID > 0) ? $qID : $currentMaxID;

                                $insQStmt->execute([
                                    ':qid' => $useID,
                                    ':subID' => $subID,
                                    ':topID' => $tID,
                                    ':qName' => $qName,
                                    ':passage' => $q['questionPassage'] ?? '',
                                    ':opt1' => $q['queOption1'] ?? '',
                                    ':opt2' => $q['queOption2'] ?? '',
                                    ':opt3' => $q['queOption3'] ?? '',
                                    ':opt4' => $q['queOption4'] ?? '',
                                    ':ans' => (string)($q['correctAnswer'] ?? '1'),
                                    ':solText' => $q['solutionText'] ?? '',
                                    ':examName' => $q['examTypeID'] ?? 'MPSC',
                                    ':subName' => $subName,
                                    ':topName' => $tName
                                ]);
                                $newAdded++;
                            } else {
                                $skipped++;
                            }
                        }
                    }
                }
                curl_multi_close($mh);
            }

            if ($totalRemoteFound === 0) {
                $msg = "No remote stock questions are currently available on the remote server for $subTitle.";
            } else if ($newAdded === 0) {
                $msg = "$subTitle is already 100% fully synced! Verified $totalRemoteFound questions on remote server (all already exist in your database).";
            } else {
                $msg = "Successfully synced $subTitle! Imported $newAdded new questions ($skipped existing questions verified).";
            }

            echo json_encode([
                'status'        => 'success',
                'message'       => $msg,
                'added'         => $newAdded,
                'skipped'       => $skipped,
                'remote_total'  => $totalRemoteFound
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'add_question':
        try {
            $subID   = (int)($_POST['subject_id'] ?? 2);
            $topID   = (int)($_POST['topic_id'] ?? 0);
            $qText   = trim($_POST['question_name'] ?? '');
            $qTextE  = trim($_POST['question_name_e'] ?? '');
            $opt1    = trim($_POST['opt1'] ?? '');
            $opt2    = trim($_POST['opt2'] ?? '');
            $opt3    = trim($_POST['opt3'] ?? '');
            $opt4    = trim($_POST['opt4'] ?? '');
            $opt1E   = trim($_POST['opt1_e'] ?? '');
            $opt2E   = trim($_POST['opt2_e'] ?? '');
            $opt3E   = trim($_POST['opt3_e'] ?? '');
            $opt4E   = trim($_POST['opt4_e'] ?? '');
            $ans     = trim($_POST['correct_answer'] ?? '1');
            $solText = trim($_POST['solution_text'] ?? '');
            $solTextE= trim($_POST['solution_text_e'] ?? '');
            $examName= trim($_POST['exam_name'] ?? 'MPSC');

            if (empty($qText) && empty($qTextE)) {
                throw new Exception("Question text in Marathi or English is required.");
            }

            $formattedQText = strpos($qText, '<p>') !== false ? $qText : "<p><strong>$qText</strong></p>";
            $formattedQTextE = !empty($qTextE) ? (strpos($qTextE, '<p>') !== false ? $qTextE : "<p><strong>$qTextE</strong></p>") : '';

            $maxQStmt = $pdo->query("SELECT MAX(questionID) FROM tbl_question_bank");
            $maxQID = (int)$maxQStmt->fetchColumn() + 1;

            $ins = $pdo->prepare("INSERT INTO tbl_question_bank 
                (questionID, subjectID, topicID, questionName, questionNameE, queOption1, queOption2, queOption3, queOption4, queOption1E, queOption2E, queOption3E, queOption4E, correctAnswer, solutionText, solutionTextE, examName, status)
                VALUES (:qID, :subID, :topID, :qName, :qNameE, :opt1, :opt2, :opt3, :opt4, :opt1E, :opt2E, :opt3E, :opt4E, :ans, :solText, :solTextE, :examName, 1)");
            
            $ins->execute([
                ':qID' => $maxQID,
                ':subID' => $subID,
                ':topID' => $topID,
                ':qName' => $formattedQText,
                ':qNameE' => $formattedQTextE,
                ':opt1' => $opt1,
                ':opt2' => $opt2,
                ':opt3' => $opt3,
                ':opt4' => $opt4,
                ':opt1E' => $opt1E,
                ':opt2E' => $opt2E,
                ':opt3E' => $opt3E,
                ':opt4E' => $opt4E,
                ':ans' => $ans,
                ':solText' => $solText,
                ':solTextE' => $solTextE,
                ':examName' => $examName
            ]);

            echo json_encode(['status' => 'success', 'message' => "Bilingual Question #$maxQID added successfully!", 'question_id' => $maxQID]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_add_questions':
        try {
            $jsonInput = $_POST['questions_json'] ?? '';
            $subIDDefault = (int)($_POST['default_subject_id'] ?? 2);

            if (empty($jsonInput)) {
                throw new Exception("No JSON data provided for bulk import.");
            }

            $items = json_decode($jsonInput, true);
            if (!$items || !is_array($items)) {
                throw new Exception("Invalid JSON format.");
            }

            $maxQStmt = $pdo->query("SELECT MAX(questionID) FROM tbl_question_bank");
            $currentMaxID = (int)$maxQStmt->fetchColumn();

            $insertedCount = 0;

            $pdo->beginTransaction();

            $ins = $pdo->prepare("INSERT INTO tbl_question_bank 
                (questionID, subjectID, topicID, questionName, questionNameE, queOption1, queOption2, queOption3, queOption4, queOption1E, queOption2E, queOption3E, queOption4E, correctAnswer, solutionText, solutionTextE, examName, status)
                VALUES (:qID, :subID, :topID, :qName, :qNameE, :opt1, :opt2, :opt3, :opt4, :opt1E, :opt2E, :opt3E, :opt4E, :ans, :solText, :solTextE, :examName, 1)");

            foreach ($items as $q) {
                $subID   = (int)($q['subjectID'] ?? $subIDDefault);
                $topID   = (int)($q['topicID'] ?? 0);
                $qText   = trim($q['questionName'] ?? $q['question'] ?? '');
                $qTextE  = trim($q['questionNameE'] ?? $q['questionE'] ?? '');
                $opt1    = trim($q['queOption1'] ?? $q['option1'] ?? '');
                $opt2    = trim($q['queOption2'] ?? $q['option2'] ?? '');
                $opt3    = trim($q['queOption3'] ?? $q['option3'] ?? '');
                $opt4    = trim($q['queOption4'] ?? $q['option4'] ?? '');
                $opt1E   = trim($q['queOption1E'] ?? $q['option1E'] ?? '');
                $opt2E   = trim($q['queOption2E'] ?? $q['option2E'] ?? '');
                $opt3E   = trim($q['queOption3E'] ?? $q['option3E'] ?? '');
                $opt4E   = trim($q['queOption4E'] ?? $q['option4E'] ?? '');
                $ans     = (string)($q['correctAnswer'] ?? $q['answer'] ?? '1');
                $solText = trim($q['solutionText'] ?? $q['solution'] ?? '');
                $solTextE= trim($q['solutionTextE'] ?? $q['solutionE'] ?? '');
                $exam    = trim($q['examName'] ?? 'MPSC');
                $inputQID= (int)($q['questionID'] ?? 0);

                if (empty($qText) && empty($qTextE)) continue;

                $currentMaxID++;
                $useID = ($inputQID > 0 && $inputQID > $currentMaxID) ? $inputQID : $currentMaxID;

                $ins->execute([
                    ':qID' => $useID,
                    ':subID' => $subID,
                    ':topID' => $topID,
                    ':qName' => $qText,
                    ':qNameE' => $qTextE,
                    ':opt1' => $opt1,
                    ':opt2' => $opt2,
                    ':opt3' => $opt3,
                    ':opt4' => $opt4,
                    ':opt1E' => $opt1E,
                    ':opt2E' => $opt2E,
                    ':opt3E' => $opt3E,
                    ':opt4E' => $opt4E,
                    ':ans' => $ans,
                    ':solText' => $solText,
                    ':solTextE' => $solTextE,
                    ':examName' => $exam
                ]);

                $insertedCount++;
            }

            $pdo->commit();
            echo json_encode([
                'status'  => 'success',
                'message' => "Bilingual Bulk Import finished! Added: $insertedCount questions",
                'added_count'   => $insertedCount
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
