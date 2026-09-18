import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";
const SLUG = "mpsc-group-b";

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
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

async function apiFetch(url, options = {}, retries = 6) {
    for (let i = 0; i < retries; i++) {
        try {
            const res = await fetch(url, {
                ...options,
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3",
                    ...(options.headers || {})
                }
            });

            if (res.status === 429) {
                console.log(`\n⏳ [RATE LIMIT 429] Waiting 40s before retry (attempt ${i + 1}/${retries})...`);
                await sleep(40000);
                continue;
            }

            if (res.status === 400 || res.status === 404) {
                return { ok: false, status: res.status, data: null };
            }

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const json = await res.json();
            return { ok: true, status: res.status, data: json.data || json };
        } catch (e) {
            if (i === retries - 1) return { ok: false, status: 500, error: e.message };
            await sleep(2000 * (i + 1));
        }
    }
}

async function fetchAll292Tests() {
    const proj = JSON.stringify({
        details: {
            id: 1, name: 1,
            sections: {
                id: 1, name: 1,
                subsections: { id: 1, name: 1, paidTestCount: 1, freeTestCount: 1 }
            }
        }
    });

    const seriesRes = await apiFetch(`https://api.testbook.com/api/v1/test-series/slug?__projection=${encodeURIComponent(proj)}&url=${SLUG}&branchId=&language=English`);
    const details = seriesRes.data?.details || seriesRes.data;
    const seriesId = details?._id || details?.id;
    console.log(`Testbook Series: ${details?.name} (ID: ${seriesId})\n`);

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

async function unlockAndGetAnswers(testId) {
    const encodedAuth = encodeURIComponent(AUTH_TOKEN);
    const ansUrl = `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodedAuth}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;

    let res = await apiFetch(ansUrl);
    if (res.ok && res.data && Object.keys(res.data).length > 0) {
        return res.data;
    }

    // Auto-submit to unlock solutions
    const submitUrl = `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodedAuth}&X-Tb-Client=web,1.3`;
    await apiFetch(submitUrl, {
        method: 'POST',
        headers: { "content-type": "application/json" },
        body: JSON.stringify({ task: "submit", responses: {} })
    });

    await sleep(400);

    // Re-fetch answers
    res = await apiFetch(ansUrl);
    return res.data || {};
}

async function syncSingleTest(testObj, index, total) {
    const { testId, testTitle, questionCount, sectionName, subSectionName } = testObj;
    const encodedAuth = encodeURIComponent(AUTH_TOKEN);

    // 1. Fetch questions
    const qUrl = `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodedAuth}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`;
    const qRes = await apiFetch(qUrl);

    if (!qRes.ok || !qRes.data || !qRes.data.sections) {
        console.log(`  [${index + 1}/${total}] ⚠️ Could not fetch questions for "${testTitle}"`);
        return 0;
    }

    await sleep(300);

    // 2. Fetch answers & solutions
    const answersMap = await unlockAndGetAnswers(testId);

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
            positive_marks, negative_marks, subject_tags
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            test_series_slug = VALUES(test_series_slug),
            test_id = VALUES(test_id),
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
            negative_marks = VALUES(negative_marks),
            subject_tags = VALUES(subject_tags);
    `;

    const db = getPool();
    let questionsSaved = 0;

    for (const sec of qRes.data.sections) {
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

            const sol_mr = decodeHtml(qAns.sol?.mr?.value || qAns.sol?.en?.value || "");
            const sol_en = decodeHtml(qAns.sol?.en?.value || qAns.sol?.mr?.value || "");

            const posMarks = parseFloat(q.posMarks || qAns.posMarks || 1.0);
            const negMarks = parseFloat(q.negMarks || qAns.negMarks || 0.0);
            const tags = (qAns.tags || []).join(', ') || null;

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
                        posMarks, negMarks, tags
                    ]);
                    questionsSaved++;
                } catch (err) {
                    console.error(`Error saving question ${qId}:`, err.message);
                }
            }
        }
    }

    console.log(`  [${index + 1}/${total}] ✓ Synced "${testTitle}" (${questionsSaved} Qs with solutions)`);
    return questionsSaved;
}

async function main() {
    console.log("=================================================================");
    console.log("🚀 MPSC GROUP-B SERVICES 2026 (292 TESTS) FULL RESILIENT SYNC");
    console.log("=================================================================\n");

    const db = getPool();

    console.log("Fetching all 292 tests from Testbook API...");
    const allTests = await fetchAll292Tests();
    console.log(`Found ${allTests.length} Total Tests in Test Series.\n`);

    console.log("Scanning local database for tests already 100% complete...");
    const [dbStatus] = await db.query(`
        SELECT test_id, COUNT(*) as cnt, SUM(CASE WHEN correct_option > 0 THEN 1 ELSE 0 END) as valid_ans
        FROM tbl_questions 
        WHERE test_series_slug = ? 
        GROUP BY test_id
    `, [SLUG]);

    const statusMap = new Map();
    for (const r of dbStatus) {
        statusMap.set(r.test_id, { cnt: r.cnt, valid_ans: r.valid_ans });
    }

    const testsToSync = [];
    let alreadySynced = 0;

    for (const t of allTests) {
        const s = statusMap.get(t.testId);
        // Only skip if ALL expected questions exist AND have valid answers (>0)
        if (s && s.cnt >= t.questionCount && s.valid_ans >= t.questionCount) {
            alreadySynced++;
        } else {
            testsToSync.push(t);
        }
    }

    console.log(`[STATUS CHECK]`);
    console.log(` • Already 100% Fully Synced: ${alreadySynced} tests (Skipping)`);
    console.log(` • Remaining to Sync:        ${testsToSync.length} tests\n`);

    if (testsToSync.length === 0) {
        console.log("🎉 All 292 Tests are already 100% synced with full questions, options, and solutions!");
        process.exit(0);
    }

    let totalSaved = 0;

    for (let i = 0; i < testsToSync.length; i++) {
        const test = testsToSync[i];
        const saved = await syncSingleTest(test, i, testsToSync.length);
        totalSaved += saved;

        if ((i + 1) % 10 === 0 || i === testsToSync.length - 1) {
            console.log(`\n---> PROGRESS: [${i + 1}/${testsToSync.length}] tests processed. Questions saved so far: ${totalSaved}\n`);
        }

        // Polite delay between tests to stay completely within rate limits
        await sleep(800);
    }

    console.log("\n=================================================================");
    console.log("✅ SYNC FINISHED! REBUILDING FINAL SUMMARY...");
    console.log("=================================================================");

    const [finalStats] = await db.query(`
        SELECT 
            COUNT(*) as total_q,
            COUNT(DISTINCT test_id) as total_tests,
            SUM(CASE WHEN correct_option > 0 THEN 1 ELSE 0 END) as valid_answers,
            SUM(CASE WHEN solution_mr != '' OR solution_en != '' THEN 1 ELSE 0 END) as with_sol
        FROM tbl_questions 
        WHERE test_series_slug = ? AND LENGTH(test_id) = 24
    `, [SLUG]);

    const s = finalStats[0];
    console.log(` Total Tests in Database:     ${s.total_tests} / 292`);
    console.log(` Total Questions:             ${s.total_q}`);
    console.log(` Valid Correct Answers:       ${s.valid_answers} (${Math.round(s.valid_answers / s.total_q * 100)}%)`);
    console.log(` With Detailed Solutions:     ${s.with_sol} (${Math.round(s.with_sol / s.total_q * 100)}%)`);
    console.log("=================================================================\n");

    process.exit(0);
}

main();
