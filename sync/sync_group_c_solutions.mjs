import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";
const SLUG = "mpsc-group-c";

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function decodeHtml(str) {
    if (!str) return "";
    let s = str;
    // Repeatedly decode until no HTML entities remain (handles double-encoded entities like &amp;lt; or &lt;p&gt;)
    let prev;
    for (let i = 0; i < 3; i++) {
        prev = s;
        s = s
            .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
            .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&nbsp;/g, " ")
            .replace(/&ndash;/g, "–").replace(/&mdash;/g, "—")
            .replace(/&lsquo;/g, "\u2018").replace(/&rsquo;/g, "\u2019")
            .replace(/&ldquo;/g, "\u201C").replace(/&rdquo;/g, "\u201D")
            .replace(/&times;/g, "×").replace(/&zwj;/g, "\u200D").replace(/&zwnj;/g, "\u200C");
        if (s === prev) break;
    }
    return s.trim();
}

function extractSolution(solObj, lang) {
    if (!solObj) return "";
    if (typeof solObj === 'string') return decodeHtml(solObj);
    
    // Array format
    if (Array.isArray(solObj)) {
        for (const item of solObj) {
            if (item && item.lang === lang && item.value) return decodeHtml(item.value);
        }
        if (solObj[0]?.value) return decodeHtml(solObj[0].value);
    }
    
    // Object format with lang keys
    if (solObj[lang] && typeof solObj[lang] === 'object' && solObj[lang].value) {
        return decodeHtml(solObj[lang].value);
    }
    if (typeof solObj[lang] === 'string') {
        return decodeHtml(solObj[lang]);
    }
    
    return "";
}

async function apiFetch(url, options = {}, retries = 5) {
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
                console.log(`⏳ Rate limit (429). Waiting 35s... (attempt ${i + 1}/${retries})`);
                await sleep(35000);
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

async function unlockAndGetAnswers(testId) {
    const encodedAuth = encodeURIComponent(AUTH_TOKEN);
    const ansUrl = `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodedAuth}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;

    let res = await apiFetch(ansUrl);
    if (res.ok && res.data && Object.keys(res.data).length > 0) {
        return res.data;
    }

    // Auto-submit to unlock answers & solutions
    console.log(`  Submitting test ${testId} to unlock answers...`);
    const submitUrl = `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodedAuth}&X-Tb-Client=web,1.3`;
    await apiFetch(submitUrl, {
        method: 'POST',
        headers: { "content-type": "application/json" },
        body: JSON.stringify({ task: "submit", responses: {} })
    });

    await sleep(800);

    // Re-fetch answers
    res = await apiFetch(ansUrl);
    return res.data || {};
}

// Fallback: fetch directly from single question API
async function fetchDirectQuestion(qTestbookId) {
    const url = `https://api.testbook.com/api/v2/questions/${qTestbookId}`;
    const res = await apiFetch(url);
    if (!res.ok || !res.data) return null;
    return res.data;
}

async function main() {
    const pool = mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 10 });
    console.log("=== Syncing Solutions for MPSC Group C ===\n");

    // 1. Find all tests in mpsc-group-c that have missing solutions
    const [testsToFix] = await pool.query(`
        SELECT test_id, test_title, 
            COUNT(*) as total_in_test,
            SUM(CASE WHEN (solution_mr IS NULL OR TRIM(solution_mr) = '') AND (solution_en IS NULL OR TRIM(solution_en) = '') THEN 1 ELSE 0 END) as missing_cnt
        FROM tbl_questions 
        WHERE test_series_slug = ?
        GROUP BY test_id, test_title
        HAVING missing_cnt > 0
        ORDER BY missing_cnt DESC
    `, [SLUG]);

    console.log(`Found ${testsToFix.length} tests with missing solutions:\n`);
    for (const t of testsToFix) {
        console.log(`• [${t.test_id}] ${t.test_title}: ${t.missing_cnt} missing out of ${t.total_in_test}`);
    }
    console.log("\nStarting unlock and sync process...\n");

    let totalUpdated = 0;
    let totalDirectFallbacks = 0;

    for (let idx = 0; idx < testsToFix.length; idx++) {
        const test = testsToFix[idx];
        console.log(`\n------------------------------------------------------------`);
        console.log(`[${idx + 1}/${testsToFix.length}] Processing Test: "${test.test_title}" (${test.test_id})`);
        console.log(`------------------------------------------------------------`);

        // Get answers map
        const answersMap = await unlockAndGetAnswers(test.test_id);
        const ansKeys = Object.keys(answersMap);
        console.log(`  Fetched answers for ${ansKeys.length} questions from test answers API.`);

        // Get the missing questions for this test from the DB
        const [missingQs] = await pool.query(`
            SELECT id, testbook_id, correct_option
            FROM tbl_questions 
            WHERE test_id = ? 
              AND test_series_slug = ?
              AND (solution_mr IS NULL OR TRIM(solution_mr) = '') 
              AND (solution_en IS NULL OR TRIM(solution_en) = '')
        `, [test.test_id, SLUG]);

        console.log(`  Target questions to update in this test: ${missingQs.length}`);

        let testUpdatedCount = 0;

        for (const q of missingQs) {
            let qAns = answersMap[q.testbook_id];
            let sol_mr = "";
            let sol_en = "";
            let correctOpt = 0;
            let tags = null;

            if (qAns) {
                sol_mr = extractSolution(qAns.sol, 'mr');
                sol_en = extractSolution(qAns.sol, 'en');
                if (!sol_mr && sol_en) sol_mr = sol_en;
                if (!sol_en && sol_mr) sol_en = sol_mr;

                correctOpt = parseInt(qAns.correctOption || 0);
                if (Array.isArray(qAns.tags) && qAns.tags.length > 0) {
                    tags = qAns.tags.join(', ');
                }
            }

            // If test answers didn't yield a solution, fallback to direct Question API
            if (!sol_mr && !sol_en) {
                const directQ = await fetchDirectQuestion(q.testbook_id);
                if (directQ) {
                    totalDirectFallbacks++;
                    sol_mr = extractSolution(directQ.sol || directQ.mr?.sol, 'mr');
                    sol_en = extractSolution(directQ.sol || directQ.en?.sol, 'en');
                    if (!sol_mr && directQ.sol && Array.isArray(directQ.sol)) {
                        sol_mr = decodeHtml(directQ.sol[0]?.value || "");
                    }
                    if (!sol_en && directQ.sol && Array.isArray(directQ.sol)) {
                        sol_en = decodeHtml(directQ.sol[0]?.value || "");
                    }
                    if (!sol_mr && sol_en) sol_mr = sol_en;
                    if (!sol_en && sol_mr) sol_en = sol_mr;
                    if (!correctOpt && directQ.correctOption) {
                        correctOpt = parseInt(directQ.correctOption);
                    }
                }
                await sleep(150);
            }

            // Update in DB if we got solutions
            if (sol_mr || sol_en) {
                const updateFields = [];
                const updateParams = [];

                if (sol_mr) {
                    updateFields.push('solution_mr = ?');
                    updateParams.push(sol_mr);
                }
                if (sol_en) {
                    updateFields.push('solution_en = ?');
                    updateParams.push(sol_en);
                }
                if (correctOpt > 0) {
                    updateFields.push('correct_option = ?');
                    updateParams.push(correctOpt);
                }
                if (tags) {
                    updateFields.push('subject_tags = ?');
                    updateParams.push(tags);
                }

                if (updateFields.length > 0) {
                    updateParams.push(q.id);
                    await pool.query(`UPDATE tbl_questions SET ${updateFields.join(', ')} WHERE id = ?`, updateParams);
                    testUpdatedCount++;
                    totalUpdated++;
                }
            }
        }

        console.log(`  ✅ Successfully updated solutions for ${testUpdatedCount}/${missingQs.length} questions in "${test.test_title}".`);
        await sleep(500);
    }

    console.log(`\n============================================================`);
    console.log(`Sync Complete! Total questions updated: ${totalUpdated} (Direct fallbacks: ${totalDirectFallbacks})`);
    console.log(`============================================================\n`);

    // Final verification
    const [postCheck] = await pool.query(`
        SELECT 
            COALESCE(subject_name, 'NULL') as subject, 
            COUNT(*) as total_q,
            SUM(CASE WHEN (solution_mr IS NULL OR TRIM(solution_mr) = '') AND (solution_en IS NULL OR TRIM(solution_en) = '') THEN 1 ELSE 0 END) as missing_sol,
            SUM(CASE WHEN (solution_mr IS NOT NULL AND TRIM(solution_mr) != '') OR (solution_en IS NOT NULL AND TRIM(solution_en) != '') THEN 1 ELSE 0 END) as with_sol
        FROM tbl_questions 
        WHERE test_series_slug = ?
        GROUP BY subject_name
    `, [SLUG]);

    console.log('Final subject breakdown in mpsc-group-c:');
    for (const r of postCheck) {
        console.log(`Subject: ${r.subject.padEnd(35)} | Total: ${String(r.total_q).padStart(5)} | Missing Sol: ${String(r.missing_sol).padStart(5)} | With Sol: ${String(r.with_sol).padStart(5)}`);
    }

    const [totalRemaining] = await pool.query(`
        SELECT COUNT(*) as cnt 
        FROM tbl_questions 
        WHERE test_series_slug = ? 
          AND (solution_mr IS NULL OR TRIM(solution_mr) = '') 
          AND (solution_en IS NULL OR TRIM(solution_en) = '')
    `, [SLUG]);

    console.log(`\nTotal questions still missing solutions in ${SLUG}: ${totalRemaining[0].cnt}`);

    await pool.end();
}

main().catch(console.error);
