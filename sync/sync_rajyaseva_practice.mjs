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

const GOAL_SLUG = 'mpsc-rajyaseva-2027';

const chapters = [
    // GS Paper - I
    { subject: 'GS Paper - I', chapter: 'Physical Geography', chapterId: '691abed4e0b6e54fb0cd2125' },
    { subject: 'GS Paper - I', chapter: 'Indian Geography', chapterId: '691abed43d6bcdc2a6d83115' },
    { subject: 'GS Paper - I', chapter: 'World Geography', chapterId: '691abed5c711da358f862226' },
    { subject: 'GS Paper - I', chapter: 'Human Geography', chapterId: '691abed50e09197495c94c50' },
    { subject: 'GS Paper - I', chapter: 'Ancient History', chapterId: '691abed5d681c261e046e641' },
    { subject: 'GS Paper - I', chapter: 'Medieval India', chapterId: '691abed60497d08212a34126' },
    { subject: 'GS Paper - I', chapter: 'Modern India', chapterId: '691abed6687088e22753a375' },
    { subject: 'GS Paper - I', chapter: 'Post Independence', chapterId: '691abed66dee80ce69558c12' },
    { subject: 'GS Paper - I', chapter: 'World History', chapterId: '691abed760f6fb3c9a7dcbeb' },
    { subject: 'GS Paper - I', chapter: 'Art & Culture', chapterId: '691abed7c711da358f86222a' },

    // GS Paper - II
    { subject: 'GS Paper - II', chapter: 'Polity', chapterId: '694157fffe638e6fbf18f9ab' },
    { subject: 'GS Paper - II', chapter: 'International Relations', chapterId: '694158001078fd8fe964a2b7' },

    // GS Paper III
    { subject: 'GS Paper III', chapter: 'Economy', chapterId: '6945047e336e2abec23a9b23' },
    { subject: 'GS Paper III', chapter: 'Science & Technology', chapterId: '6945047ed6a55131a1cd5679' },
    { subject: 'GS Paper III', chapter: 'Environment & Ecology', chapterId: '6945047f39c2afeeacf843a7' },

    // Marathi
    { subject: 'Marathi', chapter: 'मराठी', chapterId: '63ad908103eca07c00498aec' },

    // English
    { subject: 'English', chapter: 'Parts of Speech', chapterId: '63ad9027aed832dae1161e90' },
    { subject: 'English', chapter: 'Grammar', chapterId: '63ad902701ea53acbb5f16b1' },
    { subject: 'English', chapter: 'Comprehension Based Chapters', chapterId: '63ad90286384ce6a07d881f1' },

    // CSAT
    { subject: 'CSAT', chapter: 'CSAT', chapterId: '63209eb5709b10212c13407d' }
];

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
    s = s.replace(/src="\/\//g, 'src="https://').replace(/src='\/\//g, "src='https://");
    return s.trim();
}

function extractSolution(q, lang) {
    if (Array.isArray(q[lang]?.sol) && q[lang].sol.length > 0) {
        return decodeHtml(q[lang].sol[0]?.value || "");
    }
    if (q[lang]?.sol?.value) return decodeHtml(q[lang].sol.value);
    if (typeof q[lang]?.sol === 'string') return decodeHtml(q[lang].sol);

    if (Array.isArray(q.sol)) {
        for (const item of q.sol) {
            if (item?.lang === lang && item.value) return decodeHtml(item.value);
        }
        if (q.sol[0]?.value) return decodeHtml(q.sol[0].value);
    }
    if (q.sol?.[lang]?.value) return decodeHtml(q.sol[lang].value);
    if (typeof q.sol?.[lang] === 'string') return decodeHtml(q.sol[lang]);

    return "";
}

async function fetchWithRetry(url, options = {}, retries = 4) {
    for (let i = 0; i < retries; i++) {
        try {
            const res = await fetch(url, {
                ...options,
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/mpsc-2027-coaching",
                    "accept": "application/json, text/plain, */*",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3",
                    ...(options.headers || {})
                }
            });

            if (res.status === 429) {
                console.log(`[HTTP 429 Rate Limit] Backing off ${3 * (i + 1)}s...`);
                await sleep(3000 * (i + 1));
                continue;
            }

            if (!res.ok) {
                if (res.status === 404) return null;
                throw new Error(`HTTP ${res.status}`);
            }

            return await res.json();
        } catch (e) {
            if (i === retries - 1) return null;
            await sleep(1000 * (i + 1));
        }
    }
    return null;
}

async function main() {
    const pool = mysql.createPool(DB_CONFIG);
    console.log("=================================================================");
    console.log("🚀 SYNC MPSC RAJYASEVA 2027 PRACTICE HIERARCHY & QUESTIONS");
    console.log("=================================================================\n");

    const mappingRecords = [];
    const allUniqueQIds = new Set();
    const qDetailsMap = new Map();

    // 1. Fetch hierarchy for all 20 Rajyaseva chapters
    console.log(`Step 1: Fetching hierarchy for all ${chapters.length} chapters...`);
    for (const item of chapters) {
        const url = `https://api.testbook.com/api/v2/study-tab/chapters/${item.chapterId}/practice-sections?limit=-1&language=English`;
        const json = await fetchWithRetry(url);
        const sections = json?.data?.sections || [];

        let chQuestionCount = 0;
        for (const sec of sections) {
            const topicTitle = sec.properties?.title || 'General';
            for (const p of sec.practices || []) {
                const subtopicTitle = p.title || 'Practice Set';
                const practiceId = p._id;
                const ques = p.ques || [];

                ques.forEach((qId, idx) => {
                    mappingRecords.push({
                        goal_slug: GOAL_SLUG,
                        subject_name: item.subject,
                        chapter_name: item.chapter,
                        topic_name: topicTitle,
                        subtopic_name: subtopicTitle,
                        practice_id: practiceId,
                        testbook_id: qId,
                        question_order: idx + 1
                    });
                    allUniqueQIds.add(qId);
                    chQuestionCount++;
                    if (!qDetailsMap.has(qId)) {
                        qDetailsMap.set(qId, {
                            subject: item.subject,
                            chapter: item.chapter,
                            topic: topicTitle,
                            subtopic: subtopicTitle,
                            practice_id: practiceId
                        });
                    }
                });
            }
        }
        console.log(`  • [${item.subject}] ${item.chapter} -> ${sections.length} topics, ${chQuestionCount} questions`);
        await sleep(100);
    }

    console.log(`\nTotal mapping rows: ${mappingRecords.length}`);
    console.log(`Unique questions to map: ${allUniqueQIds.size}`);

    // 2. Insert into tbl_coaching_practice_map
    console.log("\nStep 2: Saving hierarchy into 'tbl_coaching_practice_map'...");
    const mapInsertSql = `
        INSERT INTO tbl_coaching_practice_map (
            goal_slug, subject_name, chapter_name, topic_name, subtopic_name,
            practice_id, testbook_id, question_order
        ) VALUES ?
        ON DUPLICATE KEY UPDATE
            goal_slug = VALUES(goal_slug),
            subject_name = VALUES(subject_name),
            chapter_name = VALUES(chapter_name),
            topic_name = VALUES(topic_name),
            subtopic_name = VALUES(subtopic_name),
            question_order = VALUES(question_order)
    `;

    const chunkSize = 500;
    for (let i = 0; i < mappingRecords.length; i += chunkSize) {
        const slice = mappingRecords.slice(i, i + chunkSize).map(r => [
            r.goal_slug, r.subject_name, r.chapter_name, r.topic_name, r.subtopic_name,
            r.practice_id, r.testbook_id, r.question_order
        ]);
        await pool.query(mapInsertSql, [slice]);
    }
    console.log(`✅ Saved ${mappingRecords.length} records into tbl_coaching_practice_map.`);

    // 3. Fast copy from tbl_questions into tbl_coaching_practice_questions (READ-ONLY from tbl_questions!)
    console.log("\nStep 3: Fast copy of available questions from tbl_questions into tbl_coaching_practice_questions (read-only)...");
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
        WHERE m.goal_slug = ?
    `, [GOAL_SLUG]);
    console.log(`✓ Copied ${copyRes.affectedRows} existing questions directly from local storage.`);

    // 4. Check missing questions in tbl_coaching_practice_questions
    console.log("\nStep 4: Checking missing questions in tbl_coaching_practice_questions...");
    const qIdArray = Array.from(allUniqueQIds);
    const existingPracticeQ = new Set();

    for (let i = 0; i < qIdArray.length; i += 500) {
        const batch = qIdArray.slice(i, i + 500);
        const [rows] = await pool.query(
            `SELECT testbook_id FROM tbl_coaching_practice_questions WHERE testbook_id IN (${batch.map(() => '?').join(',')})`,
            batch
        );
        rows.forEach(r => existingPracticeQ.add(r.testbook_id));
    }

    const missingQIds = qIdArray.filter(id => !existingPracticeQ.has(id));
    console.log(`• Already in practice table: ${existingPracticeQ.size}`);
    console.log(`• Missing to fetch: ${missingQIds.length}`);

    // 5. Fetch missing questions from Testbook Question API
    if (missingQIds.length > 0) {
        console.log(`\nStep 5: Fetching ${missingQIds.length} missing questions from Testbook API...`);
        const qInsertSql = `
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

        const CONCURRENCY = 16;
        let fetchedCount = 0;

        for (let i = 0; i < missingQIds.length; i += CONCURRENCY) {
            const batch = missingQIds.slice(i, i + CONCURRENCY);
            await Promise.all(batch.map(async (qId) => {
                const qRes = await fetchWithRetry(`https://api.testbook.com/api/v2/questions/${qId}`);
                const q = qRes?.data || qRes;
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
                        await pool.query(qInsertSql, [
                            qId, q_mr, q_en,
                            opt1_mr, opt1_en, opt2_mr, opt2_en,
                            opt3_mr, opt3_en, opt4_mr, opt4_en,
                            correctOpt, sol_mr, sol_en,
                            posMarks, negMarks
                        ]);
                        fetchedCount++;
                    } catch (err) {
                        // ignore duplicate
                    }
                }
            }));

            if ((i + CONCURRENCY) % 160 === 0 || i + CONCURRENCY >= missingQIds.length) {
                console.log(`  Progress: ${Math.min(i + CONCURRENCY, missingQIds.length)} / ${missingQIds.length} processed (${fetchedCount} questions added)...`);
            }
            await sleep(80);
        }
        console.log(`✅ Successfully added ${fetchedCount} missing questions to tbl_coaching_practice_questions.`);
    }

    // 6. Final verification statistics
    const [finalCheck] = await pool.query(`
        SELECT 
            COUNT(m.id) as total_mapped,
            COUNT(DISTINCT m.testbook_id) as total_unique_mapped,
            COUNT(DISTINCT pq.testbook_id) as available_in_practice_table,
            COUNT(CASE WHEN (pq.solution_mr IS NOT NULL AND TRIM(pq.solution_mr) != '') OR (pq.solution_en IS NOT NULL AND TRIM(pq.solution_en) != '') THEN 1 END) as with_solutions
        FROM tbl_coaching_practice_map m
        LEFT JOIN tbl_coaching_practice_questions pq ON m.testbook_id = pq.testbook_id
        WHERE m.goal_slug = ?
    `, [GOAL_SLUG]);

    console.log("\n=================================================================");
    console.log("🎉 MPSC RAJYASEVA 2027 SYNC COMPLETE!");
    console.log("=================================================================");
    console.log(`• Total Mapped Practice Questions: ${finalCheck[0].total_mapped}`);
    console.log(`• Unique Practice Questions: ${finalCheck[0].total_unique_mapped}`);
    console.log(`• Available in Practice Table: ${finalCheck[0].available_in_practice_table} / ${finalCheck[0].total_unique_mapped} (${((finalCheck[0].available_in_practice_table / finalCheck[0].total_unique_mapped) * 100).toFixed(1)}%)`);
    console.log(`• Questions with Full Solutions: ${finalCheck[0].with_solutions} / ${finalCheck[0].total_mapped}`);
    console.log("• tbl_questions Status: UNTOUCHED & 100% PRESERVED.");
    console.log("=================================================================\n");

    await pool.end();
}

main().catch(console.error);
