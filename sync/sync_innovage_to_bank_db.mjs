import mysql from 'mysql2/promise';
import http from 'http';

function fetchInnovageUrl(url, timeoutMs = 10000) {
    return new Promise((resolve) => {
        const req = http.get(url, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                try { resolve(JSON.parse(body)); } catch(e) { resolve(null); }
            });
        });
        req.on('error', () => resolve(null));
        req.setTimeout(timeoutMs, () => {
            req.destroy();
            resolve(null);
        });
    });
}

let pool = null;

async function getResilientPool() {
    if (pool) {
        try {
            await pool.query("SELECT 1");
            return pool;
        } catch (e) {
            console.log("   [!] TiDB connection lost. Reconnecting...");
            try { await pool.end(); } catch (err) {}
        }
    }
    pool = mysql.createPool({
        host: 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com',
        port: 4000,
        user: 'zscbg7NahcfLgxs.root',
        password: '9gqd36vEIR9HPZnq',
        database: 'BANK',
        ssl: { rejectUnauthorized: false },
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0
    });
    return pool;
}

async function queryWithRetry(sql, params = []) {
    for (let attempt = 1; attempt <= 5; attempt++) {
        try {
            const currentPool = await getResilientPool();
            return await currentPool.query(sql, params);
        } catch (e) {
            console.log(`    [Retry ${attempt}/5] DB Error: ${e.message}. Retrying in 2s...`);
            pool = null; // force reconnect
            await new Promise(r => setTimeout(r, 2000));
        }
    }
    throw new Error(`Failed query after 5 attempts: ${sql}`);
}

async function executeWithRetry(sql, params = []) {
    for (let attempt = 1; attempt <= 5; attempt++) {
        try {
            const currentPool = await getResilientPool();
            return await currentPool.execute(sql, params);
        } catch (e) {
            console.log(`    [Retry ${attempt}/5] DB Error: ${e.message}. Retrying in 2s...`);
            pool = null; // force reconnect
            await new Promise(r => setTimeout(r, 2000));
        }
    }
    throw new Error(`Failed execute after 5 attempts: ${sql}`);
}

async function syncInnovageToBank() {
    console.log("=== AUTO-RESUMING INNOVAGE SARTHI API SYNC WITH TIDB AUTO-RECONNECT ===");

    const baseUrl = "http://innovagesolution.com/mpsc-sarthi/services/V1/index.php";
    const userID = "169923";
    const deviceID = "370b6616-0c9c-3dd8-b532-dc243b325b63";
    const appVersion = "2.6.1";

    const subjects = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 23, 24];

    let totalFetched = 0;
    let totalInserted = 0;
    let totalUpdated = 0;

    for (const sID of subjects) {
        console.log(`\nfetching topics for Subject #${sID}...`);
        const topUrl = `${baseUrl}?xAction=getSubjectTopics&subjectID=${sID}&userID=${userID}&deviceID=${deviceID}&appVersion=${appVersion}`;
        const topData = await fetchInnovageUrl(topUrl, 10000);

        if (!topData || !topData.data || !Array.isArray(topData.data)) {
            console.log(`  [SKIP] Subject #${sID} returned no topics or timed out.`);
            continue;
        }

        console.log(`  Found ${topData.data.length} topics under Subject #${sID}.`);

        for (const topic of topData.data) {
            const tID = topic.topicID;
            const tName = (topic.topicNameM || topic.topicNameE || `Topic #${tID}`).trim();

            const qUrl = `${baseUrl}?xAction=getQuetionList&subjectID=${sID}&topicID=${tID}&userID=${userID}&deviceID=${deviceID}&appVersion=${appVersion}`;
            const qData = await fetchInnovageUrl(qUrl, 12000);

            if (!qData || !qData.data || !Array.isArray(qData.data) || qData.data.length === 0) {
                continue;
            }

            console.log(`    Processing Topic [ID ${tID}]: '${tName}' -> ${qData.data.length} questions...`);
            totalFetched += qData.data.length;

            for (const q of qData.data) {
                const testbookId = `innovage_${q.questionID}`;
                const qText = (q.questionName || '').trim();
                if (!qText) continue;

                const opt1 = (q.queOption1 || '').trim();
                const opt2 = (q.queOption2 || '').trim();
                const opt3 = (q.queOption3 || '').trim();
                const opt4 = (q.queOption4 || '').trim();
                const corr = parseInt(q.correctAnswer) || 1;
                const sol = (q.correctAnswerDescription || '').trim();
                const subj = (q.subjectNameE || topic.subjectNameE || 'सामान्य अध्ययन').trim();
                const examTitle = (q.examName || q.examTypeID || 'MPSC Sarthi Question Bank').trim();

                // Check existing in DB with retry
                const [existing] = await queryWithRetry("SELECT id FROM tbl_questions WHERE testbook_id = ?", [testbookId]);

                if (existing.length === 0) {
                    await executeWithRetry(`
                        INSERT INTO tbl_questions 
                        (testbook_id, test_series_slug, test_id, test_title, subject_name, category_name, topic_name, subtopic_name, question_mr, opt1_mr, opt2_mr, opt3_mr, opt4_mr, correct_option, solution_mr)
                        VALUES (?, 'mpsc-sarthi', ?, ?, ?, 'MPSC Sarthi Bank', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    `, [testbookId, `topic_${tID}`, examTitle, subj, tName, tName, qText, opt1, opt2, opt3, opt4, corr, sol]);
                    totalInserted++;
                } else {
                    await executeWithRetry(`
                        UPDATE tbl_questions 
                        SET subject_name = ?, topic_name = ?, correct_option = ?, opt1_mr = ?, opt2_mr = ?, opt3_mr = ?, opt4_mr = ?, solution_mr = IF(solution_mr IS NULL OR solution_mr = '', ?, solution_mr)
                        WHERE id = ?
                    `, [subj, tName, corr, opt1, opt2, opt3, opt4, sol, existing[0].id]);
                    totalUpdated++;
                }
            }
        }
    }

    console.log(`\n🎉 FAST & RESILIENT SYNC COMPLETE!`);
    console.log(`  Total Innovage API Questions Processed: ${totalFetched}`);
    console.log(`  New Questions Inserted into BANK DB: ${totalInserted}`);
    console.log(`  Existing Questions Updated in BANK DB: ${totalUpdated}`);

    const [totalBank] = await queryWithRetry("SELECT COUNT(*) as cnt FROM tbl_questions");
    console.log(`  Total Questions in BANK.tbl_questions DB: ${totalBank[0].cnt}`);

    if (pool) await pool.end();
}

syncInnovageToBank().catch(console.error);
