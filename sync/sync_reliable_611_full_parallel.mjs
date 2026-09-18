import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";
const HASH = "eyJjb3Vyc2VJZCI6IjY4OTUwOSIsInR1dG9ySWQiOm51bGwsIm9yZ0lkIjo5MzQ3NTUsImNhdGVnb3J5SWQiOm51bGx9";
const CONCURRENCY = 8;

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

async function fetchClassplusFolderContent(folderId = 0) {
    const url = `https://api.classplusapp.com/v2/course/preview/content/list/${HASH}?folderId=${folderId}&limit=100&offset=0`;
    for (let retry = 0; retry < 3; retry++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "api-version": "22",
                    "region": "IN",
                    "accept-language": "EN",
                    "accept": "application/json, text/plain, */*",
                    "referer": "https://rslkgm.courses.store/",
                    "user-agent": "Mozilla/5.0"
                }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            return json.data || [];
        } catch (e) {
            if (retry === 2) return [];
            await sleep(500 * (retry + 1));
        }
    }
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

let testSeriesItems = [];

async function traverseHierarchy(folderId = 0, breadcrumbs = []) {
    const items = await fetchClassplusFolderContent(folderId);
    for (const item of items) {
        const currentBreadcrumbs = [...breadcrumbs, item.name];
        if (item.contentType === 1) { // Folder
            await traverseHierarchy(item.id, currentBreadcrumbs);
        } else if (item.testId) { // Test paper item
            testSeriesItems.push({
                item,
                breadcrumbs: currentBreadcrumbs
            });
        }
    }
}

async function processSingleTestItem(itemObj, index, total) {
    const { item, breadcrumbs } = itemObj;
    const tbTestId = item.testId;

    const qData = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${tbTestId}?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`);
    if (!qData || !qData.data || !qData.data.sections) {
        return 0;
    }

    const aData = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${tbTestId}/answers?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&attemptNo=1`);
    const answersMap = aData?.data || {};
    const sections = qData.data.sections;
    const testTitle = item.name || qData.data.title;

    const fullPath = breadcrumbs.join(" ") + " " + testTitle;

    let categoryName = "MPSC Combined Group B & C (Pre + Mains)";
    if (fullPath.includes("Rajyaseva") || fullPath.includes("State Service") || fullPath.includes("राज्यसेवा")) {
        categoryName = "MPSC State Service (Rajyaseva)";
    }
    if (fullPath.includes("Group B") || fullPath.includes("Group C") || fullPath.includes("Combine") || fullPath.includes("गट ब") || fullPath.includes("गट क") || fullPath.includes("कंबाइन")) {
        categoryName = "MPSC Combined Group B & C (Pre + Mains)";
    }

    const subjectName = breadcrumbs[1] || breadcrumbs[0] || "General Studies";
    const topicName = breadcrumbs[2] || subjectName;
    const subtopicName = breadcrumbs.slice(3, -1).join(" > ") || topicName;

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

            if (opt1_mr || opt1_en || q_mr || q_en) {
                try {
                    await db.execute(upsertSql, [
                        qId,
                        'mpsc-classplus-reliable',
                        tbTestId,
                        testTitle,
                        subjectName,
                        categoryName,
                        topicName,
                        subtopicName,
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

    console.log(`[Worker ${index}/${total}] Synced "${testTitle.substring(0, 35)}" (${questionsSaved} questions).`);
    return questionsSaved;
}

async function runReliableFullSync() {
    console.log("=================================================");
    console.log(`=== RELIABLE 611 TEST SERIES FULL SYNC (${CONCURRENCY} Workers) ===`);
    console.log("=================================================\n");

    console.log("Crawling Classplus hierarchy for Course 689509...");
    await traverseHierarchy(0, []);
    console.log(`Discovered ${testSeriesItems.length} test paper items.\n`);

    let totalQuestionsSaved = 0;
    let completedCount = 0;

    for (let i = 0; i < testSeriesItems.length; i += CONCURRENCY) {
        const chunk = testSeriesItems.slice(i, i + CONCURRENCY);
        const promises = chunk.map((item, idx) => processSingleTestItem(item, i + idx + 1, testSeriesItems.length));
        
        const results = await Promise.all(promises);
        const chunkSaved = results.reduce((sum, val) => sum + val, 0);
        totalQuestionsSaved += chunkSaved;
        completedCount += chunk.length;

        console.log(`\n---> Progress: [${completedCount}/${testSeriesItems.length}] test papers processed. Total questions & solutions saved: ${totalQuestionsSaved}\n`);
        await sleep(200);
    }

    console.log("=================================================");
    console.log("=== RELIABLE 611 FULL SYNC COMPLETE ===");
    console.log(`Total Test Papers Synced: ${completedCount}/${testSeriesItems.length}`);
    console.log(`Total Questions, Options, Keys & Solutions Saved: ${totalQuestionsSaved}`);
    console.log("=================================================");

    const pool = getPool();
    await pool.end();
}

runReliableFullSync().catch(err => {
    console.error("Reliable Full Sync Error:", err);
    process.exit(1);
});
