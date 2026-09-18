import zlib from 'zlib';
import mysql from 'mysql2/promise';

// Configuration
const CONCURRENCY = 32; // 32 Parallel Worker Threads
const DB_CONFIG = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK',
    waitForConnections: true,
    connectionLimit: 40
};

// Sitemaps to seed
const SITEMAP_URLS = [
    "https://testbook.com/sitemaps/question-bank/mr/question-bank-1.xml.gz",
    "https://testbook.com/sitemaps/question-bank/mr/question-bank-2.xml.gz",
    "https://testbook.com/sitemaps/question-bank/mr/question-bank-3.xml.gz"
];

// Helper: sleep
const sleep = (ms) => new Promise(r => setTimeout(r, ms));

// Helper: clean and decode HTML
function decodeAndCleanHtml(str) {
    if (!str) return '';
    let text = String(str);
    text = text
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/&#39;/g, "'")
        .replace(/&nbsp;/g, ' ')
        .replace(/&ndash;/g, '–')
        .replace(/&mdash;/g, '—')
        .replace(/src="\/\//gi, 'src="https://');
    
    // Strip local file paths if any
    text = text.replace(/<img[^>]*src="file:\/\/\/[^"]*"[^>]*>/gi, '');
    return text.trim();
}

// Helper: generate subject tags for fast filtering
function generateSubjectTags(subject, category, topic) {
    const combined = `${subject || ''} ${category || ''} ${topic || ''}`.toLowerCase();
    const tags = [];
    if (/hist|इतिहास|modern india|medieval|ancient/.test(combined)) tags.push('history');
    if (/polity|constitution|राज्यघटना|राज्यशास्त्र|judiciary|parliament/.test(combined)) tags.push('polity');
    if (/econom|अर्थशास्त्र|अर्थव्यवस्था|banking|budget|inflation/.test(combined)) tags.push('economics');
    if (/geog|भूगोल|climate|rivers|physiography/.test(combined)) tags.push('geography');
    if (/science|विज्ञान/.test(combined)) tags.push('science');
    if (/bio|जीवशास्त्र|botany|zoology|cell|genetics|disease|human body/.test(combined)) { tags.push('biology'); tags.push('science'); }
    if (/chem|रसायन|periodic|atomic|elements|compound/.test(combined)) { tags.push('chemistry'); tags.push('science'); }
    if (/phys|भौतिक|motion|optics|electricity|magnetism|force|energy/.test(combined)) { tags.push('physics'); tags.push('science'); }
    if (/reasoning|बुद्धिमत्ता|coding|puzzle|series|syllogism|analogy/.test(combined)) tags.push('reasoning');
    if (/math|गणित|quant|arithmetic|percentage|ratio|algebra|geometry/.test(combined)) tags.push('maths');
    if (/environ|ecology|पर्यावरण|biodiversity|pollution/.test(combined)) tags.push('environment');
    if (/current|चालू घडामोडी|awards|sports|summit|news/.test(combined)) tags.push('current-affairs');
    if (/marathi|मराठी/.test(combined)) tags.push('marathi');
    if (/english|इंग्रजी|grammar|vocabulary/.test(combined)) tags.push('english');
    return [...new Set(tags)].join(',');
}

// Fetch single question from Testbook Public API
async function fetchQuestion(qId) {
    const url = `https://api.testbook.com/api/v2/questions/${qId}`;
    for (let retry = 0; retry < 3; retry++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
                    "Accept": "application/json, text/plain, */*",
                    "Referer": "https://testbook.com/"
                }
            });
            if (res.status === 404) return null;
            if (res.status === 429) {
                // Rate limited, wait politely
                await sleep(1500 * (retry + 1));
                continue;
            }
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            return json?.data || null;
        } catch (e) {
            await sleep(250 * (retry + 1));
        }
    }
    return null;
}

async function main() {
    console.log("=================================================================");
    console.log(`🚀 TESTBOOK PUBLIC QUESTION BANK — 32 THREAD BILINGUAL CRAWLER`);
    console.log("=================================================================\n");

    const pool = mysql.createPool(DB_CONFIG);

    // 1. Preload all existing IDs for 0ms lookup
    console.log("⚡ [1/3] Preloading all existing testbook_id entries from database into RAM...");
    const visited = new Set();
    const [rows] = await pool.query("SELECT testbook_id FROM tbl_questions WHERE testbook_id IS NOT NULL");
    for (const r of rows) {
        if (r.testbook_id) {
            visited.add(r.testbook_id);
            const rawId = r.testbook_id.replace(/^tb_pub_/, '').replace(/^tb_q_/, '');
            visited.add(rawId);
        }
    }
    console.log(`✅ Loaded ${visited.size} existing ID entries into memory for instant duplicate skipping.\n`);

    // 2. Download and parse sitemaps
    console.log("📥 [2/3] Downloading and extracting question IDs from Marathi sitemaps...");
    const queue = [];
    const queuedSet = new Set();

    for (const sitemapUrl of SITEMAP_URLS) {
        try {
            console.log(`  Fetching ${sitemapUrl}...`);
            const res = await fetch(sitemapUrl);
            const buffer = await res.arrayBuffer();
            const xml = zlib.gunzipSync(Buffer.from(buffer)).toString('utf-8');
            const matches = [...xml.matchAll(/--([a-f0-9]{24})/g)].map(m => m[1]);

            let newAdded = 0;
            for (const qId of matches) {
                if (!visited.has(qId) && !visited.has(`tb_pub_${qId}`) && !queuedSet.has(qId)) {
                    queue.push(qId);
                    queuedSet.add(qId);
                    newAdded++;
                }
            }
            console.log(`   └─ Found ${matches.length} IDs -> ${newAdded} new un-fetched IDs added to queue.`);
        } catch (err) {
            console.error(`   ❌ Failed to load sitemap ${sitemapUrl}: ${err.message}`);
        }
    }

    console.log(`\n✅ Ready! Total Brand New Questions in Queue: ${queue.length}\n`);

    // 3. Start 32 worker threads
    console.log(`⚡ [3/3] Spawning ${CONCURRENCY} parallel worker threads...`);
    let totalImported = 0;
    let totalDuplicateSkipped = 0;
    let totalNonBilingualSkipped = 0;
    let totalNotFound = 0;
    const startTime = Date.now();

    async function worker(workerId) {
        while (queue.length > 0) {
            const qId = queue.shift();
            if (!qId) break;

            if (visited.has(qId) || visited.has(`tb_pub_${qId}`)) {
                totalDuplicateSkipped++;
                continue;
            }
            visited.add(qId);
            visited.add(`tb_pub_${qId}`);

            const qData = await fetchQuestion(qId);
            if (!qData) {
                totalNotFound++;
                continue;
            }

            // Expand graph: Push similar questions to queue
            if (qData.similarQues && Array.isArray(qData.similarQues)) {
                for (const item of qData.similarQues) {
                    if (item.qId && !visited.has(item.qId) && !visited.has(`tb_pub_${item.qId}`) && !queuedSet.has(item.qId)) {
                        queue.push(item.qId);
                        queuedSet.add(item.qId);
                    }
                }
            }

            // STRICT BILINGUAL CHECK: MUST HAVE BOTH MARATHI AND ENGLISH
            const rawMrVal = qData.mr?.value?.trim() || '';
            const rawEnVal = qData.en?.value?.trim() || '';

            if (!rawMrVal || !rawEnVal) {
                totalNonBilingualSkipped++;
                continue;
            }

            const qMr = decodeAndCleanHtml(rawMrVal);
            const qEn = decodeAndCleanHtml(rawEnVal);

            // Options parsing
            const optsMr = qData.mr?.options || [];
            const optsEn = qData.en?.options || [];

            const opt1Mr = decodeAndCleanHtml(optsMr[0]?.value || '');
            const opt2Mr = decodeAndCleanHtml(optsMr[1]?.value || '');
            const opt3Mr = decodeAndCleanHtml(optsMr[2]?.value || '');
            const opt4Mr = decodeAndCleanHtml(optsMr[3]?.value || '');

            const opt1En = decodeAndCleanHtml(optsEn[0]?.value || opt1Mr);
            const opt2En = decodeAndCleanHtml(optsEn[1]?.value || opt2Mr);
            const opt3En = decodeAndCleanHtml(optsEn[2]?.value || opt3Mr);
            const opt4En = decodeAndCleanHtml(optsEn[3]?.value || opt4Mr);

            const corr = parseInt(qData.mr?.co || qData.en?.co || 1) || 1;

            // Solutions parsing
            const solMr = decodeAndCleanHtml(qData.mr?.sol?.[0]?.value || '');
            const solEn = decodeAndCleanHtml(qData.en?.sol?.[0]?.value || solMr);

            // Hierarchical classification
            const concepts = qData.globalConcepts?.[0] || {};
            const subjectName = concepts.s?.title || 'General Studies';
            const categoryName = concepts.c?.title || concepts.s?.title || 'General Knowledge';
            const topicName = concepts.t?.title || 'General Topics';
            const subtopicName = concepts.st?.title || concepts.t?.title || 'General';

            const subjectTags = generateSubjectTags(subjectName, categoryName, topicName);

            const testbookId = `tb_pub_${qId}`;
            const testId = `tb_pub_qb`;
            const testTitle = `Testbook Public Question Bank`;
            const testSeriesSlug = `testbook-public`;

            try {
                await pool.execute(`
                    INSERT INTO tbl_questions
                    (testbook_id, test_series_slug, test_id, test_title, subject_name, category_name, topic_name, subtopic_name, 
                     question_mr, question_en, opt1_mr, opt1_en, opt2_mr, opt2_en, opt3_mr, opt3_en, opt4_mr, opt4_en, 
                     correct_option, solution_mr, solution_en, positive_marks, negative_marks, subject_tags, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1.0, 0.25, ?, NOW())
                `, [
                    testbookId, testSeriesSlug, testId, testTitle, subjectName, categoryName, topicName, subtopicName,
                    qMr, qEn, opt1Mr, opt1En, opt2Mr, opt2En, opt3Mr, opt3En, opt4Mr, opt4En,
                    corr, solMr, solEn, subjectTags
                ]);

                totalImported++;

                if (totalImported % 50 === 0) {
                    const elapsedSec = ((Date.now() - startTime) / 1000).toFixed(1);
                    const qps = (totalImported / elapsedSec).toFixed(1);
                    console.log(`🔥 [IMPORTED: ${totalImported}] Speed: ${qps} Q/s | Queue: ${queue.length} | Non-Bilingual Skipped: ${totalNonBilingualSkipped} | Last: [${subjectName} > ${topicName}]`);
                }
            } catch (err) {
                // Ignore unique constraint race conditions
            }
        }
    }

    const workers = [];
    for (let i = 0; i < CONCURRENCY; i++) {
        workers.push(worker(i + 1));
    }

    await Promise.all(workers);

    const totalTimeSec = ((Date.now() - startTime) / 1000).toFixed(1);
    console.log("\n=================================================================");
    console.log("🎉 CRAWL FINISHED!");
    console.log(`  Total New Bilingual Imported: ${totalImported}`);
    console.log(`  Non-Bilingual Skipped:        ${totalNonBilingualSkipped}`);
    console.log(`  Duplicates Skipped in RAM:    ${totalDuplicateSkipped}`);
    console.log(`  Total Execution Time:         ${totalTimeSec} seconds`);
    
    const [cnt] = await pool.query("SELECT COUNT(*) as total FROM tbl_questions");
    console.log(`  Grand Total Questions in DB:  ${cnt[0].total}`);
    console.log("=================================================================\n");

    await pool.end();
}

main().catch(console.error);
