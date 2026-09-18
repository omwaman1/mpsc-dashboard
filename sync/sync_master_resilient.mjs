import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";

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
        pool = mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 10 });
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

// Resilient API Fetch with 5 retries and exponential backoff
async function resilientFetch(url, headers = {}, retries = 5) {
    for (let attempt = 1; attempt <= retries; attempt++) {
        try {
            const res = await fetch(url, { headers });
            if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            return await res.json();
        } catch (err) {
            if (attempt === retries) {
                console.error(`[FETCH FAIL] After ${retries} attempts for ${url}: ${err.message}`);
                return null;
            }
            const backoff = Math.min(1000 * Math.pow(2, attempt - 1), 8000);
            await sleep(backoff);
        }
    }
}

// Fetch test questions from Testbook API
async function fetchTestQuestions(testId) {
    const qUrl = `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`;
    return await resilientFetch(qUrl, {
        "source": "testbook",
        "origin": "https://testbook.com",
        "referer": "https://testbook.com/",
        "accept": "application/json",
        "authorization": `Bearer ${AUTH_TOKEN}`,
        "x-tb-client": "web,1.3"
    });
}

// Fetch test answers from Testbook API
async function fetchTestAnswers(testId) {
    const aUrl = `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;
    const res = await resilientFetch(aUrl, {
        "source": "testbook",
        "origin": "https://testbook.com",
        "referer": "https://testbook.com/",
        "accept": "application/json",
        "authorization": `Bearer ${AUTH_TOKEN}`,
        "x-tb-client": "web,1.3"
    });
    return res || { data: {} };
}

// Safe DB query with retry
async function dbQueryWithRetry(sql, params = [], retries = 3) {
    const db = getPool();
    for (let attempt = 1; attempt <= retries; attempt++) {
        try {
            return await db.execute(sql, params);
        } catch (err) {
            if (attempt === retries) throw err;
            await sleep(500 * attempt);
        }
    }
}

async function runMasterSync() {
    console.log("=================================================");
    console.log("=== MASTER RESILIENT SYNC TO BANK DATABASE ===");
    console.log("=================================================\n");

    console.log("1. Finding all test papers in BANK database needing option fixes...");
    const [testList] = await dbQueryWithRetry(`
        SELECT DISTINCT test_id 
        FROM tbl_questions 
        WHERE test_id IS NOT NULL AND test_id != '' 
          AND (opt1_mr IS NULL OR opt1_mr = '') 
          AND (opt1_en IS NULL OR opt1_en = '')
    `);

    console.log(`Found ${testList.length} test papers with missing options.\n`);

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

    let totalFixedQuestions = 0;

    for (let i = 0; i < testList.length; i++) {
        const testId = testList[i].test_id;
        
        const qData = await fetchTestQuestions(testId);
        if (!qData || !qData.data || !qData.data.sections) {
            await sleep(200);
            continue;
        }

        const aData = await fetchTestAnswers(testId);
        const answersMap = aData?.data || {};
        const sections = qData.data.sections;
        const testTitle = qData.data.title || "Test Paper";

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
                const opt3_en = decodeHtml(enOpts[3]?.value || mrOpts[2]?.value || "");
                const opt4_mr = decodeHtml(mrOpts[3]?.value || enOpts[3]?.value || "");
                const opt4_en = decodeHtml(enOpts[3]?.value || mrOpts[3]?.value || "");

                const q_mr = decodeHtml(q.mr?.value || q.en?.value || "");
                const q_en = decodeHtml(q.en?.value || q.mr?.value || "");

                const sol_mr = decodeHtml(qAns.sol?.mr?.value || "");
                const sol_en = decodeHtml(qAns.sol?.en?.value || "");

                if (opt1_mr || opt1_en) {
                    await dbQueryWithRetry(upsertSql, [
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
                    totalFixedQuestions++;
                }
            }
        }

        if ((i + 1) % 10 === 0 || i + 1 === testList.length) {
            console.log(`[${i + 1}/${testList.length}] Test papers processed. Total questions & options updated: ${totalFixedQuestions}`);
        }

        await sleep(250); // Pause between requests to prevent network throttle
    }

    console.log("\n=================================================");
    console.log("=== MASTER RESILIENT SYNC COMPLETE ===");
    console.log(`Total Questions Updated with Full Options, Keys & Solutions: ${totalFixedQuestions}`);
    console.log("=================================================");

    if (pool) await pool.end();
}

runMasterSync().catch(err => {
    console.error("Master Sync Fatal Error:", err);
    process.exit(1);
});
