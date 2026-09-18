import mysql from './node_modules/mysql2/promise.js';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlZTVlOTRjNDkyY2Q1MGQwZjczMjg4OSIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwiaWF0IjoiMjAyNi0wOS0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwibmFtZSI6Im9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuNUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiIiwiaXNQYWlkVXNlciI6ZmFsc2UsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.JI5HbF9uBEVOnfqGkiGIy-Jdri4vk79wMPjYeOPL2yDTw2fEJDqcM0HoqIWfDXZxFxiNqtnuU6kBcE3Ejhh2oFOfJHh7MJnFVxZvzGfC4NZiW6EZ_GH80TNze6TRyeQjDyp7VqK1sxOMznS_IgL6-rgQY9y-4gaNUNXnnJ8mIIA";

const DB_CONFIG = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK',
    waitForConnections: true,
    connectionLimit: 20
};

const sleep = (ms) => new Promise(r => setTimeout(r, ms));

function decodeHtml(str) {
    if (!str) return "";
    let s = String(str);
    for (let i = 0; i < 3; i++) {
        let prev = s;
        s = s
            .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
            .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&#039;/g, "'")
            .replace(/&nbsp;/g, " ").replace(/&ndash;/g, "–").replace(/&mdash;/g, "—")
            .replace(/&lsquo;/g, "\u2018").replace(/&rsquo;/g, "\u2019")
            .replace(/&ldquo;/g, "\u201C").replace(/&rdquo;/g, "\u201D")
            .replace(/&times;/g, "×").replace(/&zwj;/g, "\u200D").replace(/&zwnj;/g, "\u200C");
        if (s === prev) break;
    }
    // Fix protocol-relative URLs in image tags
    s = s.replace(/src="\/\//g, 'src="https://').replace(/src='\/\//g, "src='https://");
    return s.trim();
}

function extractSolution(q, lang) {
    // 1. Language-specific sol array
    if (Array.isArray(q[lang]?.sol) && q[lang].sol.length > 0) {
        return decodeHtml(q[lang].sol[0]?.value || "");
    }
    if (q[lang]?.sol?.value) return decodeHtml(q[lang].sol.value);
    if (typeof q[lang]?.sol === 'string') return decodeHtml(q[lang].sol);

    // 2. Fallback to common sol
    if (Array.isArray(q.sol)) {
        for (const item of q.sol) {
            if (item?.lang === lang && item.value) return decodeHtml(item.value);
        }
        if (q.sol[0]?.value) return decodeHtml(q.sol[0].value);
    }
    if (q.sol?.[lang]?.value) return decodeHtml(q.sol[lang].value);

    return "";
}

async function fetchWithRetry(url, options = {}, retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            const res = await fetch(url, {
                ...options,
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/mpsc-combined-group-c-2026-coaching",
                    "accept": "application/json, text/plain, */*",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3",
                    ...(options.headers || {})
                }
            });

            if (res.status === 429) {
                await sleep(2000 * (i + 1));
                continue;
            }

            if (!res.ok) {
                if (res.status === 404) return null;
                throw new Error(`HTTP ${res.status}`);
            }

            return await res.json();
        } catch (e) {
            if (i === retries - 1) return null;
            await sleep(800 * (i + 1));
        }
    }
    return null;
}

async function main() {
    const pool = mysql.createPool(DB_CONFIG);
    console.log("=================================================================");
    console.log("🔒 SYNC SUPERCOACHING PRACTICE QUESTIONS (ISOLATED TABLE)");
    console.log("=================================================================\n");

    // Ensure table exists
    await pool.query(`
        CREATE TABLE IF NOT EXISTS tbl_coaching_practice_questions (
            testbook_id VARCHAR(64) PRIMARY KEY,
            question_mr LONGTEXT,
            question_en LONGTEXT,
            opt1_mr TEXT,
            opt1_en TEXT,
            opt2_mr TEXT,
            opt2_en TEXT,
            opt3_mr TEXT,
            opt3_en TEXT,
            opt4_mr TEXT,
            opt4_en TEXT,
            correct_option INT DEFAULT 0,
            solution_mr LONGTEXT,
            solution_en LONGTEXT,
            positive_marks DECIMAL(4,2) DEFAULT 1.0,
            negative_marks DECIMAL(4,2) DEFAULT 0.0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 1. Get all unique question IDs from practice map
    const [allMapped] = await pool.query(`
        SELECT DISTINCT testbook_id FROM tbl_coaching_practice_map
    `);
    const allQIds = allMapped.map(r => r.testbook_id);
    console.log(`Total unique practice question IDs: ${allQIds.length}`);

    // 2. Copy already available questions from tbl_questions (READ ONLY from tbl_questions!)
    console.log("\nStep 1: Reading matching questions from tbl_questions into tbl_coaching_practice_questions (read-only)...");
    const [copyRes] = await pool.query(`
        INSERT IGNORE INTO tbl_coaching_practice_questions (
            testbook_id, question_mr, question_en,
            opt1_mr, opt1_en, opt2_mr, opt2_en,
            opt3_mr, opt3_en, opt4_mr, opt4_en,
            correct_option, solution_mr, solution_en,
            positive_marks, negative_marks
        )
        SELECT 
            q.testbook_id, q.question_mr, q.question_en,
            q.opt1_mr, q.opt1_en, q.opt2_mr, q.opt2_en,
            q.opt3_mr, q.opt3_en, q.opt4_mr, q.opt4_en,
            q.correct_option, q.solution_mr, q.solution_en,
            q.positive_marks, q.negative_marks
        FROM tbl_questions q
        INNER JOIN tbl_coaching_practice_map m ON q.testbook_id = m.testbook_id
    `);
    console.log(`✓ Copied ${copyRes.affectedRows} existing questions into isolated practice table.`);

    // 3. Check which questions are still missing from tbl_coaching_practice_questions
    const [existingPracticeQ] = await pool.query(`
        SELECT testbook_id FROM tbl_coaching_practice_questions
    `);
    const existingSet = new Set(existingPracticeQ.map(r => r.testbook_id));
    const missingQIds = allQIds.filter(id => !existingSet.has(id));

    console.log(`\nStep 2: Missing from practice table: ${missingQIds.length}`);

    if (missingQIds.length > 0) {
        console.log(`Fetching ${missingQIds.length} questions from Testbook API...`);
        const insertSql = `
            INSERT INTO tbl_coaching_practice_questions (
                testbook_id, question_mr, question_en,
                opt1_mr, opt1_en, opt2_mr, opt2_en,
                opt3_mr, opt3_en, opt4_mr, opt4_en,
                correct_option, solution_mr, solution_en,
                positive_marks, negative_marks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                question_mr = VALUES(question_mr),
                question_en = VALUES(question_en),
                opt1_mr = VALUES(opt1_mr),
                opt1_en = VALUES(opt1_en),
                opt2_mr = VALUES(opt2_mr),
                opt2_en = VALUES(opt2_en),
                opt3_mr = VALUES(opt3_mr),
                opt3_en = VALUES(opt3_en),
                opt4_mr = VALUES(opt4_mr),
                opt4_en = VALUES(opt4_en),
                correct_option = VALUES(correct_option),
                solution_mr = VALUES(solution_mr),
                solution_en = VALUES(solution_en)
        `;

        const CONCURRENCY = 20;
        let saved = 0;

        for (let i = 0; i < missingQIds.length; i += CONCURRENCY) {
            const chunk = missingQIds.slice(i, i + CONCURRENCY);
            await Promise.all(chunk.map(async (qId) => {
                const res = await fetchWithRetry(`https://api.testbook.com/api/v2/questions/${qId}`);
                const q = res?.data || res;
                if (!q) return;

                const mrOpts = q.mr?.options || [];
                const enOpts = q.en?.options || [];

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

                let sol_mr = extractSolution(q, 'mr');
                let sol_en = extractSolution(q, 'en');
                if (!sol_mr && sol_en) sol_mr = sol_en;
                if (!sol_en && sol_mr) sol_en = sol_mr;

                const correctOpt = parseInt(q.mr?.co || q.en?.co || q.correctOption || q.answer || 0);
                const posMarks = parseFloat(q.posMarks || 1.0);
                const negMarks = parseFloat(q.negMarks || 0.0);

                if (q_mr || q_en || opt1_mr || opt1_en) {
                    try {
                        await pool.query(insertSql, [
                            qId, q_mr, q_en,
                            opt1_mr, opt1_en, opt2_mr, opt2_en,
                            opt3_mr, opt3_en, opt4_mr, opt4_en,
                            correctOpt, sol_mr, sol_en,
                            posMarks, negMarks
                        ]);
                        saved++;
                    } catch (err) {
                        // ignore
                    }
                }
            }));

            if ((i + CONCURRENCY) % 200 === 0 || i + CONCURRENCY >= missingQIds.length) {
                console.log(`  Fetched & Saved: ${Math.min(i + CONCURRENCY, missingQIds.length)} / ${missingQIds.length} (${saved} questions stored)...`);
            }
            await sleep(100);
        }
        console.log(`✓ Added ${saved} questions to tbl_coaching_practice_questions.`);
    }

    // Final check
    const [finalStats] = await pool.query(`
        SELECT 
            COUNT(m.id) as total_mapped_rows,
            COUNT(DISTINCT m.testbook_id) as total_unique_mapped,
            COUNT(DISTINCT pq.testbook_id) as available_in_practice_table,
            COUNT(CASE WHEN (pq.solution_mr IS NOT NULL AND TRIM(pq.solution_mr) != '') OR (pq.solution_en IS NOT NULL AND TRIM(pq.solution_en) != '') THEN 1 END) as with_solutions
        FROM tbl_coaching_practice_map m
        LEFT JOIN tbl_coaching_practice_questions pq ON m.testbook_id = pq.testbook_id
    `);

    console.log("\n=================================================================");
    console.log("🎉 PRACTICE REPOSITORY IS READY!");
    console.log("=================================================================");
    console.log(`• Total Mapped Rows in Hierarchy: ${finalStats[0].total_mapped_rows}`);
    console.log(`• Unique Practice Questions: ${finalStats[0].total_unique_mapped}`);
    console.log(`• Available in Practice Table: ${finalStats[0].available_in_practice_table} / ${finalStats[0].total_unique_mapped} (${((finalStats[0].available_in_practice_table / finalStats[0].total_unique_mapped) * 100).toFixed(1)}%)`);
    console.log(`• Questions with Full Solutions: ${finalStats[0].with_solutions} / ${finalStats[0].total_mapped_rows}`);
    console.log("• tbl_questions Status: UNTOUCHED & 100% PRESERVED.");
    console.log("=================================================================\n");

    await pool.end();
}

main().catch(console.error);
