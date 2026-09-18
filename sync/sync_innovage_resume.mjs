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

function normalizeSubjectName(raw) {
    if (!raw) return 'सामान्य अध्ययन';
    const clean = raw.replace(/[\uFFFD\u200B\u200C\u200D\uFEFF\u00A0?]/g, '').trim();

    if (/अर्थ|Economics/i.test(clean)) return 'अर्थव्यवस्था';
    if (/राज्यघटना|संविधान|Polity|Civics/i.test(clean)) return 'भारतीय राज्यघटना';
    if (/इतिहास|History/i.test(clean)) return 'इतिहास';
    if (/भूगोल|Geography/i.test(clean)) return 'भूगोल';
    if (/चालू|घडामोडी|Current/i.test(clean)) return 'चालू घडामोडी';
    if (/मराठी|Marathi/i.test(clean)) return 'मराठी व्याकरण';
    if (/ENGLISH|English/i.test(clean)) return 'English Grammar';
    if (/सामान्य विज्ञान|General Science/i.test(clean)) return 'सामान्य विज्ञान';
    if (/विज्ञान|तंत्रज्ञान|Science/i.test(clean)) return 'विज्ञान आणि तंत्रज्ञान';
    if (/कायदे|Law/i.test(clean)) return 'कायदे';
    if (/पंचायत|Panchayat/i.test(clean)) return 'पंचायत राज';
    if (/पर्यावरण|Environment/i.test(clean)) return 'पर्यावरण';
    if (/कृषी|Agriculture/i.test(clean)) return 'कृषी';
    if (/मानवी|मानव|Human Rights|HRD/i.test(clean)) return 'मानवी हक्क / मानव विकास संसाधन';
    if (/REASONING|Mathematics|Math|गणित|बुद्धिमत्ता|अंकगणित/i.test(clean)) return 'गणित व बुद्धिमत्ता (CSAT)';
    if (/संगणक|Computer/i.test(clean)) return 'संगणक व माहिती तंत्रज्ञान';

    return clean || 'सामान्य अध्ययन';
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
            pool = null;
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
            pool = null;
            await new Promise(r => setTimeout(r, 2000));
        }
    }
    throw new Error(`Failed execute after 5 attempts: ${sql}`);
}

async function resumeSync() {
    console.log("=== SMART RESUME INNOVAGE SARTHI API SYNC (ZERO API CALLS FOR ALREADY SYNCED TOPICS) ===");

    // Step 1: Pre-load all already synced topic IDs from BANK DB
    const [syncedRows] = await queryWithRetry(`
        SELECT test_id, COUNT(*) as cnt 
        FROM tbl_questions 
        WHERE testbook_id LIKE 'innovage_%' 
        GROUP BY test_id
    `);

    const syncedTopicMap = new Map();
    syncedRows.forEach(r => {
        // test_id is stored as 'topic_14' -> extract topicID 14
        const tIdStr = r.test_id ? r.test_id.replace('topic_', '') : '';
        if (tIdStr) {
            syncedTopicMap.set(parseInt(tIdStr), r.cnt);
        }
    });

    console.log(`Pre-loaded ${syncedTopicMap.size} ALREADY SYNCED topics from BANK DB.`);
    console.log(`Total questions already stored in DB: ${Array.from(syncedTopicMap.values()).reduce((a,b)=>a+b,0)}\n`);

    const baseUrl = "http://innovagesolution.com/mpsc-sarthi/services/V1/index.php";
    const userID = "169923";
    const deviceID = "370b6616-0c9c-3dd8-b532-dc243b325b63";
    const appVersion = "2.6.1";

    const subjects = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 23, 24];

    let totalSkippedTopics = 0;
    let totalNewTopicsProcessed = 0;
    let totalInserted = 0;
    let totalUpdated = 0;

    for (const sID of subjects) {
        console.log(`\nChecking Subject #${sID}...`);
        const topUrl = `${baseUrl}?xAction=getSubjectTopics&subjectID=${sID}&userID=${userID}&deviceID=${deviceID}&appVersion=${appVersion}`;
        const topData = await fetchInnovageUrl(topUrl, 10000);

        if (!topData || !topData.data || !Array.isArray(topData.data)) {
            console.log(`  [SKIP] Subject #${sID} returned no topics or timed out.`);
            continue;
        }

        console.log(`  Found ${topData.data.length} topics under Subject #${sID}.`);

        for (const topic of topData.data) {
            const tID = parseInt(topic.topicID);
            const tName = (topic.topicNameM || topic.topicNameE || `Topic #${tID}`).trim();

            // SMART RESUME CHECK: Skip API call completely if topic is already synced!
            if (syncedTopicMap.has(tID)) {
                totalSkippedTopics++;
                console.log(`  ⚡ [SKIP API CALL] Topic [ID ${tID}]: '${tName}' is ALREADY synced (${syncedTopicMap.get(tID)} questions in DB)`);
                continue;
            }

            // New Topic -> Fetch Questions from API
            console.log(`  🔄 [NEW TOPIC - FETCHING API] Topic [ID ${tID}]: '${tName}'...`);
            const qUrl = `${baseUrl}?xAction=getQuetionList&subjectID=${sID}&topicID=${tID}&userID=${userID}&deviceID=${deviceID}&appVersion=${appVersion}`;
            const qData = await fetchInnovageUrl(qUrl, 12000);

            if (!qData || !qData.data || !Array.isArray(qData.data) || qData.data.length === 0) {
                console.log(`     [NOTICE] Topic [ID ${tID}] returned 0 questions from API.`);
                continue;
            }

            console.log(`     -> Fetched ${qData.data.length} new questions from API. Saving to DB...`);
            totalNewTopicsProcessed++;

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
                const rawSubj = (q.subjectNameE || topic.subjectNameE || 'सामान्य अध्ययन').trim();
                const subj = normalizeSubjectName(rawSubj);
                const examTitle = (q.examName || q.examTypeID || 'MPSC Sarthi Question Bank').trim();

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

    console.log(`\n🎉 SMART RESUME SYNC COMPLETE!`);
    console.log(`  Topics Skipped (Already Synced - Zero API Calls): ${totalSkippedTopics}`);
    console.log(`  New Topics Processed from API: ${totalNewTopicsProcessed}`);
    console.log(`  New Questions Inserted into DB: ${totalInserted}`);
    console.log(`  Existing Questions Updated in DB: ${totalUpdated}`);

    const [totalBank] = await queryWithRetry("SELECT COUNT(*) as cnt FROM tbl_questions");
    console.log(`  Total Questions in BANK.tbl_questions DB: ${totalBank[0].cnt}`);

    if (pool) await pool.end();
}

resumeSync().catch(console.error);
