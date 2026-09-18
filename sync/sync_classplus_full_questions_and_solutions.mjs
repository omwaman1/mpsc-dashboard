import mysql from "mysql2/promise";

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";
const COURSE_ID = 689509;
const HASH = "eyJjb3Vyc2VJZCI6IjY4OTUwOSIsInR1dG9ySWQiOm51bGwsIm9yZ0lkIjo5MzQ3NTUsImNhdGVnb3J5SWQiOm51bGx9";

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

async function fetchTestQuestions(testId) {
    const qUrl = `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`;
    for (let retry = 0; retry < 3; retry++) {
        try {
            const res = await fetch(qUrl, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3"
                }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return await res.json();
        } catch (e) {
            if (retry === 2) return null;
            await sleep(500 * (retry + 1));
        }
    }
}

async function fetchTestAnswers(testId) {
    const aUrl = `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${AUTH_TOKEN}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;
    try {
        const res = await fetch(aUrl, {
            headers: {
                "source": "testbook",
                "origin": "https://testbook.com",
                "referer": "https://testbook.com/",
                "accept": "application/json",
                "authorization": `Bearer ${AUTH_TOKEN}`,
                "x-tb-client": "web,1.3"
            }
        });
        if (!res.ok) return { data: {} };
        return await res.json();
    } catch {
        return { data: {} };
    }
}

let testSeriesItems = [];

async function traverseHierarchy(folderId = 0, breadcrumbs = []) {
    const items = await fetchClassplusFolderContent(folderId);
    for (const item of items) {
        const currentBreadcrumbs = [...breadcrumbs, item.name];
        if (item.contentType === 1) { // Folder
            await traverseHierarchy(item.id, currentBreadcrumbs);
        } else if (item.testId) { // Test paper with underlying testId
            testSeriesItems.push({
                item,
                breadcrumbs: currentBreadcrumbs
            });
        }
    }
}

async function main() {
    console.log("=== Syncing Full Questions, Options, Keys & Solutions to BANK DB ===");
    const db = getPool();

    console.log("Crawling Classplus hierarchy for test papers...");
    await traverseHierarchy(0, []);
    console.log(`Discovered ${testSeriesItems.length} test papers with testId.\n`);

    const stmtSql = `
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

    let totalQuestionsSaved = 0;
    let testsProcessed = 0;

    for (let i = 0; i < testSeriesItems.length; i++) {
        const { item, breadcrumbs } = testSeriesItems[i];
        const tbTestId = item.testId;
        const categoryName = breadcrumbs[0] || "MPSC Test Series";
        const subjectName = breadcrumbs[1] || breadcrumbs[0] || "General Studies";
        const topicName = breadcrumbs[2] || subjectName;
        const subtopicName = breadcrumbs.slice(3, -1).join(" > ") || topicName;

        try {
            const qData = await fetchTestQuestions(tbTestId);
            if (!qData || !qData.data) {
                continue;
            }

            const aData = await fetchTestAnswers(tbTestId);
            const sections = qData.data.sections || [];
            const answersMap = aData?.data || {};
            const testTitle = item.name || qData.data.title;

            for (const sec of sections) {
                for (const q of sec.questions || []) {
                    const qId = q._id;
                    const qAns = answersMap[qId] || {};
                    const correctOpt = parseInt(qAns.correctOption || 0);

                    const opt1_mr = decodeHtml(q.options?.[0]?.mr?.value || "");
                    const opt1_en = decodeHtml(q.options?.[0]?.en?.value || "");
                    const opt2_mr = decodeHtml(q.options?.[1]?.mr?.value || "");
                    const opt2_en = decodeHtml(q.options?.[1]?.en?.value || "");
                    const opt3_mr = decodeHtml(q.options?.[2]?.mr?.value || "");
                    const opt3_en = decodeHtml(q.options?.[2]?.en?.value || "");
                    const opt4_mr = decodeHtml(q.options?.[3]?.mr?.value || "");
                    const opt4_en = decodeHtml(q.options?.[3]?.en?.value || "");

                    const sol_mr = decodeHtml(qAns.sol?.mr?.value || "");
                    const sol_en = decodeHtml(qAns.sol?.en?.value || "");

                    await db.execute(stmtSql, [
                        qId,
                        'mpsc-classplus-reliable',
                        tbTestId,
                        testTitle,
                        subjectName,
                        categoryName,
                        topicName,
                        subtopicName,
                        decodeHtml(q.mr?.value || ""),
                        decodeHtml(q.en?.value || ""),
                        opt1_mr, opt1_en,
                        opt2_mr, opt2_en,
                        opt3_mr, opt3_en,
                        opt4_mr, opt4_en,
                        correctOpt,
                        sol_mr,
                        sol_en,
                        qAns.posMarks || 1.0,
                        qAns.negMarks || 0.25
                    ]);
                    totalQuestionsSaved++;
                }
            }

            testsProcessed++;
            if (testsProcessed % 10 === 0 || testsProcessed === testSeriesItems.length) {
                console.log(`[${testsProcessed}/${testSeriesItems.length}] Test papers fetched. Total questions & solutions saved: ${totalQuestionsSaved}`);
            }
        } catch (err) {
            console.error(`Error processing test ${tbTestId} (${item.name}):`, err.message);
        }
    }

    console.log("\n=== FULL QUESTION & SOLUTION INGESTION COMPLETE ===");
    console.log(`Tests Processed: ${testsProcessed}/${testSeriesItems.length}`);
    console.log(`Total Questions, Answer Keys & Solutions Saved: ${totalQuestionsSaved}`);

    await pool.end();
}

main().catch(console.error);
