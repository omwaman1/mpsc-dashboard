import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";
const CONCURRENCY = 8; // Process 8 test papers in parallel

const dbConfig = {
    host: 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com',
    port: 4000,
    user: 'zscbg7NahcfLgxs.root',
    password: '9gqd36vEIR9HPZnq',
    database: 'BANK',
    ssl: { rejectUnauthorized: false }
};

let pool = null;

function getPool() {
    if (!pool) {
        pool = mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 20 });
    }
    return pool;
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function decodeHtml(str) {
    if (!str) return "";
    return str
        .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&nbsp;/g, " ")
        .replace(/&ndash;/g, "–").replace(/&mdash;/g, "—");
}

async function apiFetch(url, retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3"
                }
            });
            if (!res.ok) {
                // If 400 or 404 on answers endpoint, return null safely without throwing
                if (url.includes('/answers') && (res.status === 400 || res.status === 404)) {
                    return null;
                }
                throw new Error(`HTTP ${res.status}`);
            }
            return await res.json();
        } catch (e) {
            if (i === retries - 1) return null;
            await sleep(500 * (i + 1));
        }
    }
}

async function processSingleTest(testId, testIndex, totalTests) {
    const qData = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`);
    if (!qData || !qData.data || !qData.data.sections) {
        return 0;
    }

    const aData = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&attemptNo=1`);
    const answersMap = aData?.data || {};
    const sections = qData.data.sections;
    const testTitle = qData.data.title || "Test Paper";

    const upsertSql = `
        INSERT INTO tbl_questions (
            testbook_id, test_series_slug, test_id, test_title,
            subject_name, category_name, topic_name, subtopic_name,
            question_mr, question_en,
            opt1_mr, opt1_en, opt2_mr, opt2_en,
            opt3_mr, opt3_en, opt4_mr, opt4_en,
            correct_option, solution_mr, solution_en,
            positive_marks, negative_marks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            question_mr = VALUES(question_mr),
            question_en = VALUES(question_en),
            opt1_mr = VALUES(opt1_mr), opt1_en = VALUES(opt1_en),
            opt2_mr = VALUES(opt2_mr), opt2_en = VALUES(opt2_en),
            opt3_mr = VALUES(opt3_mr), opt3_en = VALUES(opt3_en),
            opt4_mr = VALUES(opt4_mr), opt4_en = VALUES(opt4_en),
            correct_option = VALUES(correct_option),
            solution_mr = VALUES(solution_mr),
            solution_en = VALUES(solution_en);
    `;

    let questionsSaved = 0;
    const db = getPool();

    for (const sec of sections) {
        for (const q of sec.questions || []) {
            const qId = q._id;
            const qAns = answersMap[qId] || {};
            const correctOpt = parseInt(qAns.correctOption || 0);

            const mrOpts = q.mr?.options || q.options || [];
            const enOpts = q.en?.options || q.options || [];

            const opt1_mr = decodeHtml(mrOpts[0]?.value || enOpts[0]?.value || "");
            const opt1_en = decodeHtml(enOpts[0]?.value || mrOpts[0]?.value || "");
            const opt2_mr = decodeHtml(mrOpts[1]?.value || enOpts[1]?.value || "");
            const opt2_en = decodeHtml(enOpts[1]?.value || mrOpts[1]?.value || "");
            const opt3_mr = decodeHtml(mrOpts[2]?.value || enOpts[2]?.value || "");
            const opt3_en = decodeHtml(enOpts[2]?.value || mrOpts[2]?.value || "");
            const opt4_mr = decodeHtml(mrOpts[3]?.value || enOpts[3]?.value || "");
            const opt4_en = decodeHtml(enOpts[3]?.value || mrOpts[3]?.value || "");

            const q_mr = decodeHtml(q.mr?.value || q.en?.value || "");
            const q_en = decodeHtml(q.en?.value || q.mr?.value || "");

            const sol_mr = decodeHtml(qAns.sol?.mr?.value || "");
            const sol_en = decodeHtml(qAns.sol?.en?.value || "");

            if (opt1_mr || opt1_en) {
                try {
                    await db.execute(upsertSql, [
                        qId,
                        'mpsc-classplus-reliable',
                        testId,
                        testTitle,
                        sec.name || 'General Knowledge',
                        'MPSC Combined Group B & C (Pre + Mains)',
                        sec.name || 'General Knowledge',
                        sec.name || 'General Knowledge',
                        q_mr, q_en,
                        opt1_mr, opt1_en,
                        opt2_mr, opt2_en,
                        opt3_mr, opt3_en,
                        opt4_mr, opt4_en,
                        correctOpt,
                        sol_mr, sol_en,
                        qAns.posMarks || 1.0,
                        qAns.negMarks || 0.25
                    ]);
                    questionsSaved++;
                } catch(e) {}
            }
        }
    }

    console.log(`[Worker ${testIndex}/${totalTests}] Synced "${testTitle.substring(0, 35)}" (${questionsSaved} questions).`);
    return questionsSaved;
}

async function runParallelMasterSync() {
    console.log("=================================================");
    console.log(`=== PARALLEL MULTI-THREADED SYNC (${CONCURRENCY} Workers) ===`);
    console.log("=================================================\n");

    const db = getPool();
    const [testList] = await db.execute(`
        SELECT DISTINCT test_id 
        FROM tbl_questions 
        WHERE test_id IS NOT NULL AND test_id != '' 
          AND (opt1_mr IS NULL OR opt1_mr = '') 
          AND (opt1_en IS NULL OR opt1_en = '')
    `);

    console.log(`Found ${testList.length} test papers needing option fixes.\n`);
    let totalQuestionsSaved = 0;
    let completedCount = 0;

    // Process in batches of CONCURRENCY
    for (let i = 0; i < testList.length; i += CONCURRENCY) {
        const chunk = testList.slice(i, i + CONCURRENCY);
        const promises = chunk.map((t, idx) => processSingleTest(t.test_id, i + idx + 1, testList.length));
        
        const results = await Promise.all(promises);
        const chunkSaved = results.reduce((sum, val) => sum + val, 0);
        totalQuestionsSaved += chunkSaved;
        completedCount += chunk.length;

        console.log(`\n---> Batch Complete: [${completedCount}/${testList.length}] tests done. Total questions updated: ${totalQuestionsSaved}\n`);
        await sleep(300);
    }

    console.log("=================================================");
    console.log("=== PARALLEL MASTER SYNC COMPLETE ===");
    console.log(`Total Questions Updated with Full Options, Keys & Solutions: ${totalQuestionsSaved}`);
    console.log("=================================================");

    await pool.end();
}

runParallelMasterSync().catch(err => {
    console.error("Parallel Master Sync Error:", err);
    process.exit(1);
});
