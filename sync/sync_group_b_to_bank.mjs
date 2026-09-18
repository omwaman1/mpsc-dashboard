import mysql from "mysql2/promise";

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";
const TEST_SERIES_SLUG = "mpsc-group-b";
const TEST_SERIES_ID = "69ddf6a8892e61f66da2ca64";
const LANGUAGE = "English";
const CONCURRENCY_LIMIT = 5; // Process 5 test papers in parallel for fast bulk import

// TiDB DB Config
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
        .replace(/&ndash;/g, "–").replace(/&mdash;/g, "—")
        .replace(/&lsquo;/g, "\u2018").replace(/&rsquo;/g, "\u2019")
        .replace(/&ldquo;/g, "\u201C").replace(/&rdquo;/g, "\u201D")
        .replace(/&times;/g, "×").replace(/&zwj;/g, "\u200D").replace(/&zwnj;/g, "\u200C");
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
                const errText = await res.text();
                let errMsg = `HTTP ${res.status}: ${res.statusText}`;
                try {
                    const parsedErr = JSON.parse(errText);
                    if (parsedErr.message) errMsg += ` (${parsedErr.message})`;
                } catch(e) {}
                throw new Error(errMsg);
            }
            return await res.json();
        } catch (e) {
            if (i === retries - 1) throw e;
            await sleep(1000 * (i + 1));
        }
    }
}

async function syncSingleTest(test, secName, subName, encodedAuthToken) {
    const testId = test._id || test.id;
    const testTitle = test.title;
    const db = getPool();

    // 0 API Calls if test already imported in DB
    const [existing] = await db.execute("SELECT COUNT(*) as cnt FROM tbl_questions WHERE test_id = ?", [testId]);
    const countExisting = existing[0]?.cnt || 0;

    if (countExisting > 0 && countExisting >= (test.questionCount || 10)) {
        console.log(`   [SKIP] "${testTitle}" already in BANK DB (${countExisting} questions).`);
        return 0;
    }

    console.log(`   -> [BULK FETCH] "${testTitle}" (${testId})...`);

    try {
        const qRes = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodedAuthToken}&X-Tb-Client=web,1.3&language=${LANGUAGE}&client=web&testLang=en&beforeServe=false`);
        
        let aRes = { data: {} };
        try {
            aRes = await apiFetch(`https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodedAuthToken}&X-Tb-Client=web,1.3&language=${LANGUAGE}&attemptNo=1`);
        } catch (e) {
            // Unattempted test answers fallback
        }

        const qSections = qRes.data?.sections || [];
        const answersMap = aRes.data || {};
        const rowsToInsert = [];

        for (const qSec of qSections) {
            for (const qItem of qSec.questions || []) {
                const qId = qItem._id;
                const ansObj = answersMap[qId] || {};

                const globalConcept = ansObj?.globalConcept?.[0] || {};
                const subjectName = globalConcept?.s?.title || secName || "General Studies";
                const categoryName = globalConcept?.c?.title || subName || "";
                const topicName = globalConcept?.t?.title || "";
                const subtopicName = globalConcept?.st?.title || "";

                const qMr = decodeHtml(qItem.mr?.value || "");
                const qEn = decodeHtml(qItem.en?.value || "");

                const optsMr = qItem.mr?.options || [];
                const optsEn = qItem.en?.options || [];

                const opt1Mr = decodeHtml(optsMr[0]?.value || "");
                const opt1En = decodeHtml(optsEn[0]?.value || "");
                const opt2Mr = decodeHtml(optsMr[1]?.value || "");
                const opt2En = decodeHtml(optsEn[1]?.value || "");
                const opt3Mr = decodeHtml(optsMr[2]?.value || "");
                const opt3En = decodeHtml(optsEn[2]?.value || "");
                const opt4Mr = decodeHtml(optsMr[3]?.value || "");
                const opt4En = decodeHtml(optsEn[3]?.value || "");

                const correctOption = parseInt(ansObj.correctOption) || 1;
                const solMr = decodeHtml(ansObj.sol?.mr?.value || "");
                const solEn = decodeHtml(ansObj.sol?.en?.value || "");

                const posMarks = parseFloat(qItem.posMarks) || 1.0;
                const negMarks = parseFloat(qItem.negMarks) || 0.0;

                rowsToInsert.push([
                    qId, TEST_SERIES_SLUG, testId, testTitle,
                    subjectName, categoryName, topicName, subtopicName,
                    qMr, qEn,
                    opt1Mr, opt1En, opt2Mr, opt2En,
                    opt3Mr, opt3En, opt4Mr, opt4En,
                    correctOption, solMr, solEn,
                    posMarks, negMarks
                ]);
            }
        }

        if (rowsToInsert.length > 0) {
            const insertSql = `
                INSERT INTO \`tbl_questions\` (
                    \`testbook_id\`, \`test_series_slug\`, \`test_id\`, \`test_title\`,
                    \`subject_name\`, \`category_name\`, \`topic_name\`, \`subtopic_name\`,
                    \`question_mr\`, \`question_en\`,
                    \`opt1_mr\`, \`opt1_en\`, \`opt2_mr\`, \`opt2_en\`,
                    \`opt3_mr\`, \`opt3_en\`, \`opt4_mr\`, \`opt4_en\`,
                    \`correct_option\`, \`solution_mr\`, \`solution_en\`,
                    \`positive_marks\`, \`negative_marks\`
                ) VALUES ?
                ON DUPLICATE KEY UPDATE
                    \`question_mr\` = VALUES(\`question_mr\`),
                    \`question_en\` = VALUES(\`question_en\`),
                    \`correct_option\` = VALUES(\`correct_option\`),
                    \`solution_mr\` = VALUES(\`solution_mr\`),
                    \`solution_en\` = VALUES(\`solution_en\`);
            `;

            await db.query(insertSql, [rowsToInsert]);
            console.log(`      ✓ Bulk inserted ${rowsToInsert.length} questions for "${testTitle}".`);
            return rowsToInsert.length;
        }
    } catch (err) {
        console.error(`      ✗ Error syncing test "${testTitle}":`, err.message);
    }
    return 0;
}

async function main() {
    console.log("Connecting to TiDB BANK Database...");
    const db = getPool();
    console.log("Connected to BANK DB!");

    const encodedAuthToken = encodeURIComponent(AUTH_TOKEN);

    console.log(`\nFetching Test Series structure for ${TEST_SERIES_SLUG} (ID: ${TEST_SERIES_ID})...`);
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

    const seriesRes = await apiFetch(`https://api.testbook.com/api/v1/test-series/slug?__projection=${encodeURIComponent(proj)}&url=${TEST_SERIES_SLUG}&branchId=&language=${LANGUAGE}`);
    const details = seriesRes.data?.details;
    console.log(`Found Series: ${details?.name} (ID: ${TEST_SERIES_ID})`);

    const sections = details?.sections || [];
    let totalQuestionsInserted = 0;

    for (const sec of sections) {
        console.log(`\n==================================================`);
        console.log(`Section: ${sec.name}`);

        for (const sub of sec.subsections || []) {
            const secId = sec._id || sec.id;
            const subId = sub._id || sub.id;
            console.log(`\n  SubSection: ${sub.name} (ID: ${subId})...`);

            const testProj = JSON.stringify({ tests: { id: 1, title: 1, questionCount: 1, hasAccess: 1, isFree: 1, progress: 1 } });
            const testsRes = await apiFetch(`https://api.testbook.com/api/v2/test-series/${TEST_SERIES_ID}/tests/details?__projection=${encodeURIComponent(testProj)}&testType=all&sectionId=${secId}&subSectionId=${subId}&skip=0&limit=100&branchId=&language=${LANGUAGE}`);

            const tests = testsRes.data?.tests || [];
            console.log(`  Found ${tests.length} tests in "${sub.name}". Batch processing with concurrency = ${CONCURRENCY_LIMIT}...`);

            // Batch processing in groups of CONCURRENCY_LIMIT
            for (let i = 0; i < tests.length; i += CONCURRENCY_LIMIT) {
                const batch = tests.slice(i, i + CONCURRENCY_LIMIT);
                const results = await Promise.all(batch.map(t => syncSingleTest(t, sec.name, sub.name, encodedAuthToken)));
                totalQuestionsInserted += results.reduce((a, b) => a + b, 0);
            }
        }
    }

    console.log(`\n==================================================`);
    console.log(`🎉 SUCCESS! Completed MPSC Group B import into BANK Database! Total questions processed: ${totalQuestionsInserted}`);
    if (pool) await pool.end();
}

main().catch(err => {
    console.error("Bulk sync error:", err);
});
