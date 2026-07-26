<?php
/**
 * MAJHI NAUKRI DAILY RECRUITMENT AUTOMATED SCRAPER & TIDB SYNC ENGINE
 * Scheduled to run every morning at 8:00 AM.
 */

define('CRON_SECURITY_TOKEN', 'MPSC_SAARTHI_CRON_2026');

if (php_sapi_name() !== 'cli') {
    $token = $_GET['cron_token'] ?? ($_POST['cron_token'] ?? '');
    if ($token !== CRON_SECURITY_TOKEN) {
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(['status' => 'error', 'message' => 'Unauthorized cron trigger token.']));
    }
    header('Content-Type: application/json');
}

require_once __DIR__ . '/config.php';

$logFile = __DIR__ . '/cron_execution.log';
$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');

function writeLog($msg, $logFile) {
    echo $msg . "\n";
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

function resolveOfficialUrls($titleM, $sourceUrl) {
    $t = strtolower($titleM);
    $apply = $sourceUrl;
    $official = 'https://majhinaukri.in';
    $c1 = '';
    $c2 = '';
    
    // CDN PDF Resolution
    if (stripos($titleM, 'MPSC Group C') !== false || stripos($titleM, 'गट-क') !== false) {
        $pdf = 'https://cdn.majhinaukri.net/news/07/mpsc-group-c-bharti-2026-pre-exam.pdf';
        $c1  = 'https://cdn.majhinaukri.net/news/07/corrigendum-1-mpsc-group-c-bharti-2026.pdf';
        $c2  = 'https://cdn.majhinaukri.net/news/07/new-corrigendum-2-mpsc-group-c-bharti-2026.pdf';
        $apply = 'https://mpsconline.gov.in';
        $official = 'https://mpsc.gov.in';
    } else {
        $slug = trim(parse_url($sourceUrl, PHP_URL_PATH), '/');
        if (empty($slug) || $slug === 'majhinaukri.in') {
            $slug = preg_replace('/[^\w\-]/', '', strtolower(str_replace(' ', '-', $titleM)));
            $slug = mb_substr($slug, 0, 30);
        }
        $pdf = "https://cdn.majhinaukri.net/news/07/$slug.pdf";

        if (strpos($titleM, '[मुदतवाढ]') !== false) {
            $c1 = "https://cdn.majhinaukri.net/news/07/corrigendum-1-$slug.pdf";
            $c2 = "https://cdn.majhinaukri.net/news/07/new-corrigendum-2-$slug.pdf";
        }

        if (strpos($t, 'mpsc') !== false) {
            $apply = 'https://mpsconline.gov.in'; $official = 'https://mpsc.gov.in';
        } else if (strpos($t, 'upsc') !== false) {
            $apply = 'https://upsconline.nic.in'; $official = 'https://upsc.gov.in';
        } else if (strpos($t, 'rrb') !== false || strpos($t, 'रेल्वे') !== false) {
            $apply = 'https://rrbapply.gov.in'; $official = 'https://indianrailways.gov.in';
        } else if (strpos($t, 'ssc') !== false) {
            $apply = 'https://ssc.gov.in'; $official = 'https://ssc.gov.in';
        } else if (strpos($t, 'ibps') !== false) {
            $apply = 'https://ibps.in/online-application'; $official = 'https://ibps.in';
        } else if (strpos($t, 'aiims') !== false) {
            $apply = 'https://norcet7.aiimsexams.ac.in'; $official = 'https://aiimsexams.ac.in';
        } else if (strpos($t, 'avnl') !== false) {
            $apply = 'https://avnl.co.in/careers'; $official = 'https://avnl.co.in';
        } else if (strpos($t, 'aai') !== false) {
            $apply = 'https://aai.aero/en/careers/recruitment'; $official = 'https://aai.aero';
        } else if (strpos($t, 'sbi') !== false) {
            $apply = 'https://sbi.co.in/careers'; $official = 'https://sbi.co.in';
        } else if (strpos($t, 'bank of india') !== false) {
            $apply = 'https://bankofindia.co.in/career'; $official = 'https://bankofindia.co.in';
        } else if (strpos($t, 'bank of baroda') !== false) {
            $apply = 'https://bankofbaroda.in/careers'; $official = 'https://bankofbaroda.in';
        } else if (strpos($t, 'union bank') !== false) {
            $apply = 'https://unionbankofindia.co.in/english/recruitment.aspx'; $official = 'https://unionbankofindia.co.in';
        } else if (strpos($t, 'iob') !== false) {
            $apply = 'https://iob.in/careers'; $official = 'https://iob.in';
        } else if (strpos($t, 'army') !== false || strpos($t, 'सैन्य') !== false) {
            $apply = 'https://joinindianarmy.nic.in'; $official = 'https://joinindianarmy.nic.in';
        } else if (strpos($t, 'navy') !== false || strpos($t, 'नौदल') !== false) {
            $apply = 'https://joinindiannavy.gov.in'; $official = 'https://joinindiannavy.gov.in';
        } else if (strpos($t, 'air force') !== false || strpos($t, 'हवाई दल') !== false) {
            $apply = 'https://agnipathvayu.cdac.in'; $official = 'https://indianairforce.nic.in';
        } else if (strpos($t, 'high court') !== false) {
            $apply = 'https://delhihighcourt.nic.in'; $official = 'https://delhihighcourt.nic.in';
        } else if (strpos($t, 'mahavitaran') !== false) {
            $apply = 'https://mahadiscom.in/careers'; $official = 'https://mahadiscom.in';
        } else if (strpos($t, 'talathi') !== false || strpos($t, 'तलाठी') !== false) {
            $apply = 'https://mahabhumi.gov.in'; $official = 'https://maharashtra.gov.in';
        }
    }

    return [
        'pdf' => $pdf,
        'apply' => $apply,
        'official' => $official,
        'c1' => $c1,
        'c2' => $c2
    ];
}

writeLog("=========================================================", $logFile);
writeLog("STARTING DAILY MAJHI NAUKRI RECRUITMENT CRON JOB at $timestamp", $logFile);

$pdo = getDBConnection();
$maxPages = 10;
$baseUrl = "https://majhinaukri.in/feed/?paged=";

$checkStmt = $pdo->prepare("SELECT jobID, lastDateApplyTextM FROM tbl_recruitment WHERE titleM = :tm OR sourceUrl = :url");

$insRecStmt = $pdo->prepare("INSERT INTO tbl_recruitment (
    advtNo, titleM, titleE, examNameM, examNameE, totalVacancies, totalVacanciesTextM, totalVacanciesTextE,
    jobLocationM, jobLocationE, applicationMode, postDate, lastDateApply, lastDateApplyTextM, lastDateApplyTextE,
    examDateTextM, examDateTextE, feeDetailsM, feeDetailsE, ageLimitDetailsM, ageLimitDetailsE,
    notificationPdfUrl, applyOnlineUrl, officialWebsiteUrl, corrigendumUrl1, corrigendumUrl2, sourceUrl, status
) VALUES (
    :advtNo, :titleM, :titleE, :examNameM, :titleE, :totalVacancies, :vacTextM, :totalVacancies,
    'संपूर्ण महाराष्ट्र', 'All Maharashtra', 'Online', :postDate, :lastDate, :lastDateTextM, :lastDate,
    'लवकरच घोषित होईल', 'Announced Soon', :feeDetailsM, :feeDetailsE, :ageLimitDetailsM, :ageLimitDetailsE,
    :notificationPdfUrl, :applyOnlineUrl, :officialWebsiteUrl, :corrigendumUrl1, :corrigendumUrl2, :sourceUrl, 1
) ON DUPLICATE KEY UPDATE 
    titleM = VALUES(titleM),
    lastDateApply = VALUES(lastDateApply),
    lastDateApplyTextM = VALUES(lastDateApplyTextM),
    notificationPdfUrl = VALUES(notificationPdfUrl),
    applyOnlineUrl = VALUES(applyOnlineUrl),
    officialWebsiteUrl = VALUES(officialWebsiteUrl),
    corrigendumUrl1 = VALUES(corrigendumUrl1),
    corrigendumUrl2 = VALUES(corrigendumUrl2),
    lastUpdateDate = NOW()");

function extractRealDateFromTitle($titleText) {
    if (preg_match('/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{4})/u', $titleText, $m)) {
        return sprintf('%02d/%02d/%04d', $m[1], $m[2], $m[3]);
    }
    if (preg_match('/(\d{1,2}\s+(?:जुलै|ऑगस्ट|सप्टेंबर|ऑक्टोबर|नोव्हेंबर|डिसेंबर|जानेवारी|फेब्रुवारी|मार्च|एप्रिल|मे|जून)\s+\d{4})/u', $titleText, $m)) {
        return $m[1];
    }
    return '05/08/2026';
}

$totalImported = 0;
$totalUpdated  = 0;

for ($p = 1; $p <= $maxPages; $p++) {
    $feedUrl = $baseUrl . $p;
    writeLog("Fetching Feed Page #$p ($feedUrl)...", $logFile);

    $ch = curl_init($feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
    $xmlContent = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || strlen($xmlContent) < 100) {
        writeLog("   -> Feed Page #$p HTTP $httpCode or empty. Reached end of RSS pages.", $logFile);
        break;
    }

    $doc = new DOMDocument();
    @$doc->loadXML($xmlContent);

    $items = $doc->getElementsByTagName('item');
    if ($items->length === 0) {
        writeLog("   -> No RSS items found on page #$p. Stopping loop.", $logFile);
        break;
    }

    foreach ($items as $item) {
        $titleNode = $item->getElementsByTagName('title')->item(0);
        $linkNode  = $item->getElementsByTagName('link')->item(0);
        $dateNode  = $item->getElementsByTagName('pubDate')->item(0);

        $rawTitle = $titleNode ? trim($titleNode->nodeValue) : '';
        $link     = $linkNode ? trim($linkNode->nodeValue) : '';
        $pubDate  = $dateNode ? date('Y-m-d', strtotime($dateNode->nodeValue)) : date('Y-m-d');

        if (empty($rawTitle)) continue;

        if (strpos($rawTitle, 'Current Affairs') !== false && strpos($rawTitle, 'Bharti') === false && strpos($rawTitle, 'भरती') === false) {
            continue;
        }

        preg_match('/(\d+)\s*(?:जागा|Posts|vacancies)/ui', $rawTitle, $vMatch);
        $vacCount = isset($vMatch[1]) ? (int)$vMatch[1] : 0;
        $vacText  = $vacCount > 0 ? "$vacCount जागा" : "विविध जागा";

        preg_match('/(?:जाहिरात क्र\.|Advt No\.?)\s*:?\s*([\w\/]+)/ui', $rawTitle, $advtMatch);
        $advtNo = isset($advtMatch[1]) ? $advtMatch[1] : '';

        $realDate = extractRealDateFromTitle($rawTitle);
        $formattedDateText = '<span style="color:#ef4444; font-weight:bold;">' . htmlspecialchars($realDate) . '</span>';

        $officialUrls = resolveOfficialUrls($rawTitle, $link);

        $checkStmt->execute([':tm' => $rawTitle, ':url' => $link]);
        $existingJobID = $checkStmt->fetchColumn();

        try {
            $insRecStmt->execute([
                ':advtNo' => $advtNo,
                ':titleM' => $rawTitle,
                ':titleE' => $rawTitle,
                ':examNameM' => $rawTitle,
                ':totalVacancies' => $vacCount,
                ':vacTextM' => $vacText,
                ':postDate' => $pubDate,
                ':lastDate' => $realDate,
                ':lastDateTextM' => $formattedDateText,
                ':feeDetailsM' => 'अधिकृत जाहिरात पहा [मागासवर्गीय: सूट]',
                ':feeDetailsE' => 'Check official notification',
                ':ageLimitDetailsM' => '18 ते 38 वर्षे [मागासवर्गीय: 05 वर्षे सूट]',
                ':ageLimitDetailsE' => '18 to 38 years',
                ':notificationPdfUrl' => $officialUrls['pdf'],
                ':applyOnlineUrl' => $officialUrls['apply'],
                ':officialWebsiteUrl' => $officialUrls['official'],
                ':corrigendumUrl1' => $officialUrls['c1'],
                ':corrigendumUrl2' => $officialUrls['c2'],
                ':sourceUrl' => $link
            ]);

            if ($existingJobID) {
                $totalUpdated++;
            } else {
                $totalImported++;
                writeLog("   [+] New Recruitment Added: $rawTitle", $logFile);
            }
        } catch (Exception $e) {
            writeLog("   [!] Error saving recruitment: " . $e->getMessage(), $logFile);
        }
    }
}

$executionTime = round(microtime(true) - $startTime, 2);

writeLog("DAILY CRON JOB COMPLETED IN $executionTime SECONDS!", $logFile);
writeLog("Summary: New Jobs Imported = $totalImported | Updated = $totalUpdated", $logFile);
writeLog("=========================================================\n", $logFile);

if (php_sapi_name() !== 'cli') {
    echo json_encode([
        'status'         => 'success',
        'execution_time' => $executionTime . ' seconds',
        'imported_count' => $totalImported,
        'updated_count'  => $totalUpdated,
        'message'        => "Daily 8:00 AM Cron Scraper completed successfully!"
    ]);
}
?>
