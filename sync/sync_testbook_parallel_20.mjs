import mysql from 'mysql2/promise';

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
};

const CONCURRENCY = 20; // 20 Parallel Worker Threads
const TARGET_IMPORT_GOAL = 200000; // Goal: 200,000 New Questions

// Broad multi-subject seed question IDs from Testbook
const SEED_QUESTION_IDS = [
    "60013b49475094ea3e351fb2", "66f53a8b84e527fb8d87a5ad", "6470720c736addf37da82dd6",
    "6077ee18f089720a14e3b93f", "62cc20135bcc422e44dd5420", "5ff6c3533e5548813e22b688",
    "67528751bb7dab75fa8de9ba", "66fa77b41ccf3a3f8f77d854", "67a9986bf45053756fa39afe",
    "6603d564ce5dee9240576e64", "6232eea46c215d3d588e598c", "626006c1c6b8cd2fd525603a",
    "63c53f314d7804286c20b97b", "637cff6c0084687246fd1bb0", "650056a1e465f8bb5ca1ec5a",
    "68e61789e3d5ce18b03db2a3", "61eb04104d30ec64439953ef", "637482e3d95b38ba31f6b9d5",
    "636fbf0efbc9927f48f9b96d", "5eb915cbf60d5d3458cd3a6f", "60067e963072adcc65fc2b62",
    "63b2b1c5d745fea0fe185c9d", "665768f9f3679a25e91502e7", "635a0c237344cde18d23b5a2"
];

function cleanText(text) {
    if (!text) return '';
    let str = String(text);
    // Decode HTML entities
    str = str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&');
    str = str.replace(/src="\/\//gi, 'src="https://');
    str = str.replace(/\s*style="[^"]*"/gi, '');
    str = str.replace(/<span[^>]*>/gi, '').replace(/<\/span>/gi, '');
    str = str.replace(/^<p>(.*?)<\/p>$/i, '$1');
    return str.trim();
}

function isRelevantMpscSubject(subjectName, categoryName) {
    const combined = `${subjectName || ''} ${categoryName || ''}`.toLowerCase();
    
    // MPSC / General Competitive Exam Relevant Keywords
    const allowed = [
        'science', 'biology', 'physics', 'chemistry', 'polity', 'constitution', 
        'economy', 'economics', 'history', 'geography', 'environment', 'ecology',
        'reasoning', 'aptitude', 'mathematics', 'math', 'marathi', 'english',
        'general studies', 'general awareness', 'general knowledge', 'current affairs',
        'botany', 'zoology', 'cell', 'genetics', 'anatomy', 'physiology'
    ];

    return allowed.some(kw => combined.includes(kw));
}

async function fetchQuestionData(qId) {
    const url = `https://api.testbook.com/api/v2/questions/${qId}`;
    for (let retry = 0; retry < 2; retry++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
                    "Accept": "application/json, text/plain, */*",
                    "Referer": "https://testbook.com/"
                }
            });
            if (!res.ok) return null;
            const json = await res.json();
            return json?.data || null;
        } catch {
            await new Promise(r => setTimeout(r, 100 * (retry + 1)));
        }
    }
    return null;
}

async function main() {
    console.log("=================================================================");
    console.log(`🚀 TESTBOOK 20-THREAD MASTER CRAWLER WITH IN-MEMORY DUPLICATE BYPASS`);
    console.log("=================================================================\n");

    const pool = mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 35 });

    console.log("⚡ [1/2] Pre-loading all existing 277k DB testbook_ids into memory set...");
    const visited = new Set();
    const [rows] = await pool.query("SELECT testbook_id FROM tbl_questions WHERE testbook_id IS NOT NULL");
    
    rows.forEach(r => {
        if (r.testbook_id) {
            visited.add(r.testbook_id);
            const rawId = r.testbook_id.replace(/^tb_pub_/, '').replace(/^tb_q_/, '');
            visited.add(rawId);
        }
    });

    console.log(`✅ Fully preloaded ${visited.size} question ID entries into RAM in 0ms lookup!\n`);

    const queue = [...SEED_QUESTION_IDS];
    let totalImported = 0;
    let totalSkipped = 0;
    let totalIrrelevantSkipped = 0;
    const startTime = Date.now();

    async function worker(workerId) {
        while (totalImported < TARGET_IMPORT_GOAL) {
            let qId = null;
            if (queue.length > 0) {
                qId = queue.shift();
            }

            if (!qId) {
                await new Promise(r => setTimeout(r, 150));
                if (queue.length === 0) break;
                continue;
            }

            // INSTANT MEMORY DUPLICATE BYPASS (0ms)
            if (visited.has(qId) || visited.has(`tb_pub_${qId}`)) {
                totalSkipped++;
                continue;
            }

            // Mark as visited in RAM before fetching network
            visited.add(qId);
            visited.add(`tb_pub_${qId}`);

            const qData = await fetchQuestionData(qId);
            if (!qData) continue;

            // Expand graph: Push similar question IDs to queue
            if (qData.similarQues && Array.isArray(qData.similarQues)) {
                for (const item of qData.similarQues) {
                    if (item.qId && !visited.has(item.qId) && !visited.has(`tb_pub_${item.qId}`)) {
                        queue.push(item.qId);
                    }
                }
            }

            const concepts = qData.globalConcepts?.[0] || {};
            const rawSubject = concepts.s?.title || 'General Science';
            const rawCategory = concepts.c?.title || 'Biology';

            // Filter out irrelevant non-MPSC subjects
            if (!isRelevantMpscSubject(rawSubject, rawCategory)) {
                totalIrrelevantSkipped++;
                continue;
            }

            const qMrData = qData.mr || qData.en;
            const qEnData = qData.en || qData.mr;

            if (!qMrData || !qMrData.value) continue;

            const qMr = cleanText(qMrData.value);
            const qEn = cleanText(qEnData?.value || qMr);
            if (!qMr && !qEn) continue;

            const optsMr = qMrData.options || [];
            const optsEn = qEnData?.options || optsMr;

            const opt1Mr = cleanText(optsMr[0]?.value || '');
            const opt2Mr = cleanText(optsMr[1]?.value || '');
            const opt3Mr = cleanText(optsMr[2]?.value || '');
            const opt4Mr = cleanText(optsMr[3]?.value || '');

            const opt1En = cleanText(optsEn[0]?.value || opt1Mr);
            const opt2En = cleanText(optsEn[1]?.value || opt2Mr);
            const opt3En = cleanText(optsEn[2]?.value || opt3Mr);
            const opt4En = cleanText(optsEn[3]?.value || opt4Mr);

            const corr = parseInt(qMrData.co || qData.en?.co || 1) || 1;

            const subjectName = rawSubject;
            const categoryName = rawCategory;
            const topicName = concepts.t?.title || 'General Topics';
            const subtopicName = concepts.st?.title || 'General Subtopics';

            const solMrRaw = qMrData.sol?.[0]?.value || '';
            const solEnRaw = qEnData?.sol?.[0]?.value || solMrRaw;

            const testbookId = `tb_pub_${qId}`;

            try {
                await pool.execute(`
                    INSERT INTO tbl_questions
                    (testbook_id, test_series_slug, test_id, test_title, subject_name, category_name, topic_name, subtopic_name, question_mr, question_en, opt1_mr, opt1_en, opt2_mr, opt2_en, opt3_mr, opt3_en, opt4_mr, opt4_en, correct_option, solution_mr, solution_en)
                    VALUES (?, 'testbook-public', ?, 'Testbook Public Question Bank', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                `, [testbookId, `tb_q_${qId}`, subjectName, categoryName, topicName, subtopicName, qMr, qEn, opt1Mr, opt1En, opt2Mr, opt2En, opt3Mr, opt3En, opt4Mr, opt4En, corr, solMrRaw, solEnRaw]);

                totalImported++;

                if (totalImported % 50 === 0) {
                    const elapsedSec = ((Date.now() - startTime) / 1000).toFixed(1);
                    const qps = (totalImported / elapsedSec).toFixed(1);
                    console.log(`⚡ [NEW IMPORTED: ${totalImported}] Queue: ${queue.length} | Dupes Skipped: ${totalSkipped} | Speed: ${qps} Q/s | Last: "${qMr.substring(0, 35)}..."`);
                }
            } catch (err) {
                // Ignore DB duplicate key errors if any race condition occurs
            }
        }
    }

    console.log(`⚡ [2/2] Spawning ${CONCURRENCY} parallel crawler threads...`);
    const workers = [];
    for (let i = 0; i < CONCURRENCY; i++) {
        workers.push(worker(i + 1));
    }

    await Promise.all(workers);

    const totalTimeSec = ((Date.now() - startTime) / 1000).toFixed(1);
    console.log(`\n=================================================================`);
    console.log(`🎉 TESTBOOK CRAWLER COMPLETED!`);
    console.log(`  Total NEW Imported: ${totalImported}`);
    console.log(`  Duplicates Skipped in RAM: ${totalSkipped}`);
    console.log(`  Non-MPSC Skipped: ${totalIrrelevantSkipped}`);
    console.log(`  Total Time: ${totalTimeSec} seconds`);

    const [countRows] = await pool.query("SELECT COUNT(*) as total FROM tbl_questions");
    console.log(`  Grand Total Questions in DB: ${countRows[0].total}`);

    await pool.end();
}

main().catch(console.error);
