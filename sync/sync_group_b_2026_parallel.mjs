import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";
const SLUG = "mpsc-group-b";
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
        .replace(/&ndash;/g, "–").replace(/&mdash;/g, "—")
        .replace(/&lsquo;/g, "\u2018").replace(/&rsquo;/g, "\u2019")
        .replace(/&ldquo;/g, "\u201C").replace(/&rdquo;/g, "\u201D")
        .replace(/&times;/g, "×").replace(/&zwj;/g, "\u200D").replace(/&zwnj;/g, "\u200C");
}

async function apiFetch(url, retries = 5) {
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
                if (res.status === 400 || res.status === 404) {
                    return null;
                }
                throw new Error(`HTTP ${res.status}`);
            }
            return await res.json();
        } catch (e) {
            if (i === retries - 1) throw e;
            await sleep(1000 * Math.pow(2, i));
        }
    }
}

async function fetchTestSeriesStructure() {
    const proj = JSON.stringify({
        details: {
            id: 1, name: 1,
            sections: {
                id: 1, name: 1,
                subsections: { id: 1, name: 1, paidTestCount: 1, freeTestCount: 1 },
                paidTestCount: 1, freeTestCount: 1
            }
        }
    });

    const seriesRes = await apiFetch(`https://api.testbook.com/api/v1/test-series/slug?__projection=${encodeURIComponent(proj)}&url=${SLUG}&branchId=&language=English`);
    const details = seriesRes.data?.details;
    const seriesId = details?._id || details?.id;
    console.log(`Series Found: ${details?.name} (ID: ${seriesId})`);

    const sections = details?.sections || [];
    const testList = [];

    for (const sec of sections) {
        const secId = sec._id || sec.id;
        const secName = sec.name;

        for (const sub of sec.subsections || []) {
            const subId = sub._id || sub.id;
            const subName = sub.name;

            const testProj = JSON.stringify({ tests: { id: 1, title: 1, questionCount: 1, hasAccess: 1, isFree: 1 } });
            const testsRes = await apiFetch(`https://api.testbook.com/api/v2/test-series/${seriesId}/tests/details?__projection=${encodeURIComponent(testProj)}&testType=all&sectionId=${secId}&subSectionId=${subId}&skip=0&limit=200&branchId=&language=English`);

            const tests = testsRes.data?.tests || [];
            for (const t of tests) {
                testList.push({
                    testId: t._id || t.id,
                    testTitle: t.title,
                    questionCount: t.questionCount || 10,
                    sectionName: secName,
                    subSectionName: subName
                });
            }
        }
    }

    return testList;
}

async function processSingleTest(testObj, index, total) {
    const { testId, testTitle, questionCount, sectionName, subSectionName } = testObj;
    const encodedAuthToken = encodeURIComponent(AUTH_TOKEN);

    const qData = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodedAuthToken}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`);
    if (!qData || !qData.data || !qData.data.sections) {
        console.log(`[Worker ${index + 1}/${total}] Skipped empty/invalid test: ${testTitle}`);
        return 0;
    }

    const aData = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodedAuthToken}&X-Tb-Client=web,1.3&language=English&attemptNo=1`);
    const answersMap = aData?.data || {};
    const sections = qData.data.sections;

    const categoryName = "MPSC Combined Group B & C (Pre + Mains)";
    const subjectName = subSectionName || "General Studies";
    const topicName = sectionName || "Mock Tests";
    const subtopicName = testTitle;

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
            test_series_slug = VALUES(test_series_slug),
            test_title = VALUES(test_title),
            subject_name = VALUES(subject_name),
            category_name = VALUES(category_name),
            topic_name = VALUES(topic_name),
            subtopic_name = VALUES(subtopic_name),
            question_mr = VALUES(question_mr),
            question_en = VALUES(question_en),
            opt1_mr = VALUES(opt1_mr), opt1_en = VALUES(opt1_en),
            opt2_mr = VALUES(opt2_mr), opt2_en = VALUES(opt2_en),
            opt3_mr = VALUES(opt3_mr), opt3_en = VALUES(opt3_en),
            opt4_mr = VALUES(opt4_mr), opt4_en = VALUES(opt4_en),
            correct_option = VALUES(correct_option),
            solution_mr = VALUES(solution_mr),
            solution_en = VALUES(solution_en),
            positive_marks = VALUES(positive_marks),
            negative_marks = VALUES(negative_marks);
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

            // Decode question text
            const q_mr = decodeHtml(q.mr?.value || q.en?.value || "");
            const q_en = decodeHtml(q.en?.value || q.mr?.value || "");

            // Solutions
            const sol_mr = decodeHtml(qAns.sol?.mr?.value || qAns.sol?.en?.value || "");
            const sol_en = decodeHtml(qAns.sol?.en?.value || qAns.sol?.mr?.value || "");

            const posMarks = parseFloat(q.posMarks || qAns.posMarks || 1.0);
            const negMarks = parseFloat(q.negMarks || qAns.negMarks || 0.0);

            if (opt1_mr || opt1_en || q_mr || q_en) {
                try {
                    await db.execute(upsertSql, [
                        qId,
                        SLUG,
                        testId,
                        testTitle,
                        subjectName,
                        categoryName,
                        topicName,
                        subtopicName,
                        q_mr, q_en,
                        opt1_mr, opt1_en, opt2_mr, opt2_en,
                        opt3_mr, opt3_en, opt4_mr, opt4_en,
                        correctOpt, sol_mr, sol_en,
                        posMarks, negMarks
                    ]);
                    questionsSaved++;
                } catch (err) {
                    console.error(`Error saving question ${qId}:`, err.message);
                }
            }
        }
    }

    console.log(`[Worker ${index + 1}/${total}] Synced "${testTitle}" (${questionsSaved} questions).`);
    return questionsSaved;
}

async function main() {
    console.log("=================================================");
    console.log("=== MPSC GROUP-B SERVICES 2026 PARALLEL SYNC ===");
    console.log("=================================================\n");

    const db = getPool();

    console.log("Fetching test structure for MPSC Group-B Services 2026...");
    const allTests = await fetchTestSeriesStructure();
    console.log(`Total test papers found in series: ${allTests.length}\n`);

    // Perform Skip Detection against TiDB BANK database
    console.log("Checking DB for already imported test papers (Skip Detection)...");
    const [existingRows] = await db.query(`
        SELECT test_id, COUNT(*) as cnt 
        FROM tbl_questions 
        WHERE test_series_slug = ? 
        GROUP BY test_id
    `, [SLUG]);

    const existingMap = new Map();
    for (const r of existingRows) {
        existingMap.set(r.test_id, r.cnt);
    }

    const testsToSync = [];
    let skippedCount = 0;

    for (const t of allTests) {
        const existingCount = existingMap.get(t.testId) || 0;
        if (existingCount > 0 && existingCount >= Math.min(t.questionCount, 10)) {
            skippedCount++;
        } else {
            testsToSync.push(t);
        }
    }

    console.log(`[SKIP DETECTION RESULT]`);
    console.log(` Already Synced (Skipped): ${skippedCount}`);
    console.log(` Remaining to Sync: ${testsToSync.length}\n`);

    if (testsToSync.length === 0) {
        console.log("All MPSC Group-B Services test papers are already fully synced!");
        process.exit(0);
    }

    let totalSaved = 0;
    let completedCount = 0;

    // Parallel queue worker execution
    async function worker(workerId, queue) {
        while (queue.length > 0) {
            const item = queue.shift();
            if (!item) break;
            const saved = await processSingleTest(item.test, item.index, allTests.length);
            totalSaved += saved;
            completedCount++;

            if (completedCount % 5 === 0 || queue.length === 0) {
                console.log(`\n---> Progress: [${completedCount + skippedCount}/${allTests.length}] test papers processed. Questions saved so far: ${totalSaved}\n`);
            }
            await sleep(200);
        }
    }

    const queue = testsToSync.map((t, idx) => ({ test: t, index: idx }));
    const workers = [];
    for (let i = 0; i < CONCURRENCY; i++) {
        workers.push(worker(i + 1, queue));
    }

    await Promise.all(workers);

    console.log("=================================================");
    console.log("=== MPSC GROUP-B SERVICES 2026 SYNC COMPLETE ===");
    console.log(`Total Test Papers Processed: ${completedCount}`);
    console.log(`Total Questions Saved to BANK DB: ${totalSaved}`);
    console.log("=================================================");

    await pool.end();
    process.exit(0);
}

main().catch((err) => {
    console.error("FATAL ERROR in Group-B sync:", err);
    process.exit(1);
});
