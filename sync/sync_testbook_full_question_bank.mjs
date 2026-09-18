import mysql from 'mysql2/promise';

const dbConfig = {
    host: 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com',
    port: 4000,
    user: 'zscbg7NahcfLgxs.root',
    password: '9gqd36vEIR9HPZnq',
    database: 'BANK',
    ssl: { minVersion: 'TLSv1.2', rejectUnauthorized: false }
};

const sleep = (ms) => new Promise(r => setTimeout(r, ms));

function cleanText(text) {
    if (!text) return '';
    return text.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
}

async function fetchQuestion(qId) {
    const url = `https://api.testbook.com/api/v2/questions/${qId}`;
    for (let retry = 0; retry < 3; retry++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
                    "Accept": "application/json, text/plain, */*",
                    "Referer": "https://testbook.com/"
                }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            return json?.data || null;
        } catch {
            await sleep(200 * (retry + 1));
        }
    }
    return null;
}

// Seed Question IDs across subjects
const SEED_QUESTION_IDS = [
    "60013b49475094ea3e351fb2", // Biology (DNA/RNA)
    "66f53a8b84e527fb8d87a5ad", // Genetics
    "6470720c736addf37da82dd6", // Cell Biology
    "6077ee18f089720a14e3b93f", // Human Anatomy
    "62cc20135bcc422e44dd5420", // Plant Physiology
    "5ff6c3533e5548813e22b688", // Physics / Motion
    "67528751bb7dab75fa8de9ba", // Chemistry / Elements
    "66fa77b41ccf3a3f8f77d854", // Polity / Constitution
    "67a9986bf45053756fa39afe", // Economics / Banking
    "6603d564ce5dee9240576e64"  // History / Modern India
];

async function main() {
    console.log("=================================================================");
    console.log("🚀 TESTBOOK PUBLIC QUESTION BANK MASTER AUTOMATED CRAWLER");
    console.log("=================================================================\n");

    const pool = mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 15 });

    const queue = [...SEED_QUESTION_IDS];
    const visited = new Set();
    let totalImported = 0;
    let totalSkipped = 0;
    let targetGoal = 5000;

    while (queue.length > 0 && totalImported < targetGoal) {
        const qId = queue.shift();
        if (visited.has(qId)) continue;
        visited.add(qId);

        const qData = await fetchQuestion(qId);
        if (!qData) continue;

        // Push similar questions to crawl graph
        if (qData.similarQues && Array.isArray(qData.similarQues)) {
            for (const item of qData.similarQues) {
                if (item.qId && !visited.has(item.qId)) {
                    queue.push(item.qId);
                }
            }
        }

        const qMrData = qData.mr || qData.en;
        const qEnData = qData.en || qData.mr;

        if (!qMrData || !qMrData.value) continue;

        const qMr = cleanText(qMrData.value);
        const qEn = cleanText(qEnData?.value || qMr);

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

        const concepts = qData.globalConcepts?.[0] || {};
        const subjectName = concepts.s?.title || 'General Science';
        const categoryName = concepts.c?.title || 'Biology';
        const topicName = concepts.t?.title || 'General Topics';
        const subtopicName = concepts.st?.title || 'General Subtopics';

        const solMrRaw = qMrData.sol?.[0]?.value || '';
        const solEnRaw = qEnData?.sol?.[0]?.value || solMrRaw;

        const testbookId = `tb_pub_${qId}`;

        const [existing] = await pool.execute("SELECT id FROM tbl_questions WHERE testbook_id = ?", [testbookId]);

        if (existing.length === 0) {
            await pool.execute(`
                INSERT INTO tbl_questions
                (testbook_id, test_series_slug, test_id, test_title, subject_name, category_name, topic_name, subtopic_name, question_mr, question_en, opt1_mr, opt1_en, opt2_mr, opt2_en, opt3_mr, opt3_en, opt4_mr, opt4_en, correct_option, solution_mr, solution_en)
                VALUES (?, 'mpsc-sarthi', ?, 'Testbook Public Question Bank', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            `, [testbookId, `tb_q_${qId}`, subjectName, categoryName, topicName, subtopicName, qMr, qEn, opt1Mr, opt1En, opt2Mr, opt2En, opt3Mr, opt3En, opt4Mr, opt4En, corr, solMrRaw, solEnRaw]);
            
            totalImported++;
            if (totalImported % 10 === 0) {
                console.log(`  [Progress: ${totalImported}/${targetGoal}] Queue Size: ${queue.length} | Imported: "${qMr.substring(0, 50)}..."`);
            }
        } else {
            totalSkipped++;
        }

        await sleep(150);
    }

    console.log(`\n=================================================================`);
    console.log(`🎉 TESTBOOK MASTER CRAWLER FINISHED!`);
    console.log(`  Total New Imported: ${totalImported}`);
    console.log(`  Total Skipped: ${totalSkipped}`);

    const [countRows] = await pool.query("SELECT COUNT(*) as total FROM tbl_questions");
    console.log(`  Grand Total Questions in Database: ${countRows[0].total}`);

    await pool.end();
}

main().catch(console.error);
