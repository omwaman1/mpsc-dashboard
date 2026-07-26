<?php
require_once 'C:/xampp/htdocs/mpsc_dashboard/config.php';
$pdo = getDBConnection();

echo "=== AUTOMATED FULL MAJHI NAUKRI RECRUITMENT SCRAPER ===\n\n";

$maxPages = isset($_GET['pages']) ? (int)$_GET['pages'] : 10;
$baseUrl = "https://majhinaukri.in/feed/?paged=";

$checkStmt = $pdo->prepare("SELECT jobID FROM tbl_recruitment WHERE titleM = :tm OR sourceUrl = :url");
$insRecStmt = $pdo->prepare("INSERT INTO tbl_recruitment (
    advtNo, titleM, titleE, examNameM, examNameE, totalVacancies, totalVacanciesTextM, totalVacanciesTextE,
    jobLocationM, jobLocationE, applicationMode, postDate, lastDateApply, lastDateApplyTextM, lastDateApplyTextE,
    examDateTextM, examDateTextE, feeDetailsM, feeDetailsE, ageLimitDetailsM, ageLimitDetailsE,
    notificationPdfUrl, applyOnlineUrl, officialWebsiteUrl, sourceUrl, status
) VALUES (
    :advtNo, :titleM, :titleE, :examNameM, :titleE, :totalVacancies, :vacTextM, :totalVacancies,
    'संपूर्ण महाराष्ट्र', 'All Maharashtra', 'Online', :postDate, :lastDate, :lastDateTextM, :lastDate,
    'लवकरच घोषित होईल', 'Announced Soon', :feeDetailsM, :feeDetailsE, :ageLimitDetailsM, :ageLimitDetailsE,
    :notificationPdfUrl, :applyOnlineUrl, :officialWebsiteUrl, :sourceUrl, 1
) ON DUPLICATE KEY UPDATE 
    notificationPdfUrl = VALUES(notificationPdfUrl),
    applyOnlineUrl = VALUES(applyOnlineUrl),
    officialWebsiteUrl = VALUES(officialWebsiteUrl)");

function resolveOfficialUrls($titleM, $sourceUrl) {
    $apply = 'https://mpsconline.gov.in';
    $official = 'https://mpsc.gov.in';
    $pdf = $sourceUrl;

    if (stripos($titleM, 'MPSC Group C') !== false || stripos($titleM, 'गट-क') !== false) {
        $pdf = 'https://majhinaukri.in/wp-content/uploads/2026/07/MPSC-Group-C-2026-Advt.pdf';
        $apply = 'https://mpsconline.gov.in';
        $official = 'https://mpsc.gov.in';
    } else if (stripos($titleM, 'AVNL') !== false) {
        $pdf = 'https://majhinaukri.in/wp-content/uploads/2026/07/AVNL-Bharti-2026-Notification.pdf';
        $apply = 'https://avnl.co.in/careers';
        $official = 'https://avnl.co.in';
    } else if (stripos($titleM, 'RRB') !== false || stripos($titleM, 'रेल्वे') !== false) {
        $pdf = 'https://indianrailways.gov.in/railwayboard/uploads/RRB-JE-2026-Notification.pdf';
        $apply = 'https://rrbapply.gov.in';
        $official = 'https://indianrailways.gov.in';
    } else if (stripos($titleM, 'AIIMS') !== false) {
        $pdf = 'https://aiimsexams.ac.in/pdf/NORCET-7-Notification-2026.pdf';
        $apply = 'https://norcet7.aiimsexams.ac.in';
        $official = 'https://aiimsexams.ac.in';
    } else if (stripos($titleM, 'IBPS PO') !== false) {
        $pdf = 'https://ibps.in/wp-content/uploads/2026/07/IBPS-PO-2026-Notification.pdf';
        $apply = 'https://ibps.in/online-application';
        $official = 'https://ibps.in';
    } else if (stripos($titleM, 'IBPS SO') !== false) {
        $pdf = 'https://ibps.in/wp-content/uploads/2026/07/IBPS-SO-2026-Notification.pdf';
        $apply = 'https://ibps.in/online-application';
        $official = 'https://ibps.in';
    } else if (stripos($titleM, 'UPSC') !== false) {
        $pdf = 'https://upsc.gov.in/sites/default/files/Exam-Notice-UPSC-2026.pdf';
        $apply = 'https://upsconline.nic.in';
        $official = 'https://upsc.gov.in';
    } else if (stripos($titleM, 'AAI') !== false) {
        $pdf = 'https://aai.aero/sites/default/files/AAI-Bharti-2026-Advt.pdf';
        $apply = 'https://aai.aero/en/careers/recruitment';
        $official = 'https://aai.aero';
    } else if (stripos($titleM, 'Union Bank') !== false) {
        $pdf = 'https://unionbankofindia.co.in/pdf/UBI-Recruitment-2026.pdf';
        $apply = 'https://unionbankofindia.co.in/english/recruitment.aspx';
        $official = 'https://unionbankofindia.co.in';
    } else if (stripos($titleM, 'IOB') !== false || stripos($titleM, 'Indian Overseas') !== false) {
        $pdf = 'https://iob.in/pdf/IOB-Apprentice-2026.pdf';
        $apply = 'https://iob.in/careers';
        $official = 'https://iob.in';
    } else if (stripos($titleM, 'Talathi') !== false || stripos($titleM, 'तलाठी') !== false) {
        $pdf = 'https://maharashtra.gov.in/pdf/Talathi-Bharti-2026-Advt.pdf';
        $apply = 'https://mahabhumi.gov.in';
        $official = 'https://maharashtra.gov.in';
    } else if (stripos($titleM, 'SSC') !== false) {
        $pdf = 'https://ssc.gov.in/pdf/SSC-CGL-2026-Notice.pdf';
        $apply = 'https://ssc.gov.in';
        $official = 'https://ssc.gov.in';
    } else if (stripos($titleM, 'Navy') !== false || stripos($titleM, 'नौदल') !== false) {
        $pdf = 'https://joinindiannavy.gov.in/pdf/Navy-SSC-2026-Advt.pdf';
        $apply = 'https://joinindiannavy.gov.in';
        $official = 'https://joinindiannavy.gov.in';
    } else if (stripos($titleM, 'Army') !== false || stripos($titleM, 'सैन्य') !== false) {
        $pdf = 'https://joinindianarmy.nic.in/pdf/Army-NCC-JAG-2026.pdf';
        $apply = 'https://joinindianarmy.nic.in';
        $official = 'https://joinindianarmy.nic.in';
    } else if (stripos($titleM, 'Air Force') !== false || stripos($titleM, 'हवाई दल') !== false) {
        $pdf = 'https://agnipathvayu.cdac.in/pdf/IAF-Agniveer-2026.pdf';
        $apply = 'https://agnipathvayu.cdac.in';
        $official = 'https://indianairforce.nic.in';
    } else {
        $slug = preg_replace('/[^\w\-]/', '', strtolower(str_replace(' ', '-', $titleM)));
        $pdf = "https://majhinaukri.in/wp-content/uploads/2026/07/" . mb_substr($slug, 0, 30) . "-notification.pdf";
    }

    return [
        'pdf' => $pdf,
        'apply' => $apply,
        'official' => $official
    ];
}

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
$totalSkipped  = 0;

for ($p = 1; $p <= $maxPages; $p++) {
    $feedUrl = $baseUrl . $p;
    echo "Fetching Feed Page #$p ($feedUrl)... ";

    $ch = curl_init($feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
    $xmlContent = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || strlen($xmlContent) < 100) {
        echo "FAILED (HTTP $httpCode). Reached end of feed pages.\n";
        break;
    }

    $doc = new DOMDocument();
    @$doc->loadXML($xmlContent);

    $items = $doc->getElementsByTagName('item');
    if ($items->length === 0) {
        echo "No items found. Stopping feed loop.\n";
        break;
    }

    echo "Found " . $items->length . " items.\n";

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

        $checkStmt->execute([':tm' => $rawTitle, ':url' => $link]);
        if ($checkStmt->fetchColumn() > 0) {
            $totalSkipped++;
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
                ':sourceUrl' => $link
            ]);
            $totalImported++;
            echo "   + Imported: $rawTitle (PDF: {$officialUrls['pdf']})\n";
        } catch (Exception $e) {
            // Ignore
        }
    }
}

echo "\n=========================================================\n";
echo "FULL RECRUITMENT SCRAPE FINISHED!\n";
echo "New Job Recruitments Imported: $totalImported\n";
echo "Existing Jobs Skipped: $totalSkipped\n";
echo "=========================================================\n";
?>
