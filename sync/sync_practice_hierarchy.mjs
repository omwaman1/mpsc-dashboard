import mysql from 'mysql2/promise';

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

const chapters = [
    { subject: 'History', chapter: 'Modern History', chapterId: '63bffd30d9588a40828c0458' },
    { subject: 'Geography', chapter: 'Indian Geography', chapterId: '63c69a8e3caf8ba1527134be' },
    { subject: 'Polity', chapter: 'Indian Constitution', chapterId: '63c69bf8625c085d258db7c4' },
    { subject: 'Economics', chapter: 'Indian Economy', chapterId: '63c005015d5b4b3e1b90b7b3' },
    { subject: 'Science & Technology', chapter: 'General Science', chapterId: '63c6989b6fee924631c1e45a' },
    { subject: 'Science & Technology', chapter: 'Computer & IT', chapterId: '63c699445bb83874b71a0254' },
    { subject: 'Marathi', chapter: 'Marathi Grammar', chapterId: '63c00b4f5d5b4b3e1b915081' },
    { subject: 'General Mental Ability', chapter: 'General Mental Ability', chapterId: '63c00afbd9588a40828d65f5' }
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
    return s.trim();
}

function extractSolution(q, lang) {
    // 1. Language-specific sol object
    if (q[lang]?.sol?.value) return decodeHtml(q[lang].sol.value);
    if (q[lang]?.sol && typeof q[lang].sol === 'string') return decodeHtml(q[lang].sol);

    // 2. Common sol object
    if (q.sol) {
        if (typeof q.sol === 'string') return decodeHtml(q.sol);
        if (Array.isArray(q.sol)) {
            for (const item of q.sol) {
                if (item?.lang === lang && item.value) return decodeHtml(item.value);
            }
            if (q.sol[0]?.value) return decodeHtml(q.sol[0].value);
        }
        if (q.sol[lang]?.value) return decodeHtml(q.sol[lang].value);
        if (typeof q.sol[lang] === 'string') return decodeHtml(q.sol[lang]);
    }

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
                    "referer": "https://testbook.com/mpsc-combined-group-c-2026-coaching",
                    "accept": "application/json, text/plain, */*",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3",
                    ...(options.headers || {})
                }
            });

            if (res.status === 429) {
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
    console.log("🚀 SYNC SUPERCOACHING PRACTICE HIERARCHY & QUESTIONS");
    console.log("=================================================================\n");

    const mappingRecords = [];
    const allUniqueQIds = new Set();
    const qDetailsMap = new Map(); // testbook_id -> { subject, chapter, topic, subtopic, practice_id, order }

    // 1. Fetch practice sections from API for all chapters
    console.log("Step 1: Fetching hierarchy for all 8 chapters...");
    for (const item of chapters) {
        const url = `https://api.testbook.com/api/v2/study-tab/chapters/${item.chapterId}/practice-sections?limit=-1&language=English`;
        const json = await fetchWithRetry(url);
        const sections = json?.data?.sections || [];

        console.log(`  • Subject: ${item.subject} | Chapter: "${item.chapter}" (${sections.length} topics)`);

        for (const sec of sections) {
            const topicTitle = sec.properties?.title || 'General';
            for (const p of sec.practices || []) {
                const subtopicTitle = p.title;
                const practiceId = p._id;
                const ques = p.ques || [];

                ques.forEach((qId, idx) => {
                    mappingRecords.push({
                        goal_slug: 'mpsc-combined-group-c-2026',
                        subject_name: item.subject,
                        chapter_name: item.chapter,
                        topic_name: topicTitle,
                        subtopic_name: subtopicTitle,
                        practice_id: practiceId,
                        testbook_id: qId,
                        question_order: idx + 1
                    });
                    allUniqueQIds.add(qId);
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
    }

    console.log(`\nTotal mapping entries: ${mappingRecords.length}`);
    console.log(`Unique questions to map: ${allUniqueQIds.size}`);

    // 2. Insert into tbl_coaching_practice_map
    console.log("\nStep 2: Saving hierarchy into 'tbl_coaching_practice_map'...");
    const mapInsertSql = `
        INSERT INTO tbl_coaching_practice_map (
            goal_slug, subject_name, chapter_name, topic_name, subtopic_name,
            practice_id, testbook_id, question_order
        ) VALUES ?
        ON DUPLICATE KEY UPDATE
            subject_name = VALUES(subject_name),
            chapter_name = VALUES(chapter_name),
            topic_name = VALUES(topic_name),
            subtopic_name = VALUES(subtopic_name),
            question_order = VALUES(question_order)
    `;

    // Batch insert mapping in chunks of 500
    const chunkSize = 500;
    for (let i = 0; i < mappingRecords.length; i += chunkSize) {
        const slice = mappingRecords.slice(i, i + chunkSize).map(r => [
            r.goal_slug, r.subject_name, r.chapter_name, r.topic_name, r.subtopic_name,
            r.practice_id, r.testbook_id, r.question_order
        ]);
        await pool.query(mapInsertSql, [slice]);
    }
    console.log(`✅ Saved ${mappingRecords.length} records into tbl_coaching_practice_map.`);

    // 3. Check which questions are already in tbl_questions
    console.log("\nStep 3: Checking question availability in tbl_questions...");
    const qIdArray = Array.from(allUniqueQIds);
    const existingQIds = new Set();

    for (let i = 0; i < qIdArray.length; i += 500) {
        const batch = qIdArray.slice(i, i + 500);
        const [rows] = await pool.query(
            `SELECT testbook_id FROM tbl_questions WHERE testbook_id IN (${batch.map(() => '?').join(',')})`,
            batch
        );
        rows.forEach(r => existingQIds.add(r.testbook_id));
    }

    const missingQIds = qIdArray.filter(id => !existingQIds.has(id));
    console.log(`• Already in DB: ${existingQIds.size}`);
    console.log(`• Missing from DB: ${missingQIds.length}`);

    // 4. Fetch and insert missing questions so 100% of questions are available
    if (missingQIds.length > 0) {
        console.log(`\nStep 4: Fetching ${missingQIds.length} missing questions from Testbook Question API...`);
        const qInsertSql = `
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
                subject_name = VALUES(subject_name),
                category_name = VALUES(category_name),
                topic_name = VALUES(topic_name),
                subtopic_name = VALUES(subtopic_name),
                solution_mr = COALESCE(NULLIF(solution_mr, ''), VALUES(solution_mr)),
                solution_en = COALESCE(NULLIF(solution_en, ''), VALUES(solution_en))
        `;

        const CONCURRENCY = 16;
        let fetchedCount = 0;

        for (let i = 0; i < missingQIds.length; i += CONCURRENCY) {
            const batch = missingQIds.slice(i, i + CONCURRENCY);
            await Promise.all(batch.map(async (qId) => {
                const info = qDetailsMap.get(qId);
                const qRes = await fetchWithRetry(`https://api.testbook.com/api/v2/questions/${qId}`);
                const q = qRes?.data || qRes;
                if (!q) return;

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

                let sol_mr = extractSolution(q, 'mr');
                let sol_en = extractSolution(q, 'en');
                if (!sol_mr && sol_en) sol_mr = sol_en;
                if (!sol_en && sol_mr) sol_en = sol_mr;

                const correctOpt = parseInt(q.correctOption || q.answer || 0);
                const posMarks = parseFloat(q.posMarks || 1.0);
                const negMarks = parseFloat(q.negMarks || 0.0);
                const tags = Array.isArray(q.tags) ? q.tags.join(', ') : null;

                if (q_mr || q_en || opt1_mr || opt1_en) {
                    try {
                        await pool.query(qInsertSql, [
                            qId,
                            'testbook-practice',
                            info?.practice_id || '',
                            info?.subtopic || 'Practice',
                            info?.subject || 'General',
                            'MPSC Combined Group C 2026 Coaching',
                            info?.topic || 'General',
                            info?.subtopic || 'General',
                            q_mr, q_en,
                            opt1_mr, opt1_en, opt2_mr, opt2_en,
                            opt3_mr, opt3_en, opt4_mr, opt4_en,
                            correctOpt, sol_mr, sol_en,
                            posMarks, negMarks, tags
                        ]);
                        fetchedCount++;
                    } catch (err) {
                        // ignore duplicate
                    }
                }
            }));

            if ((i + CONCURRENCY) % 200 === 0 || i + CONCURRENCY >= missingQIds.length) {
                console.log(`  Progress: ${Math.min(i + CONCURRENCY, missingQIds.length)} / ${missingQIds.length} fetched & saved (${fetchedCount} new questions added).`);
            }
            await sleep(150);
        }
        console.log(`✅ Successfully added ${fetchedCount} missing questions to tbl_questions.`);
    }

    // 5. Final summary
    const [finalCheck] = await pool.query(`
        SELECT COUNT(*) as total_mapped,
               COUNT(q.id) as with_question_data,
               COUNT(CASE WHEN (q.solution_mr IS NOT NULL AND TRIM(q.solution_mr) != '') OR (q.solution_en IS NOT NULL AND TRIM(q.solution_en) != '') THEN 1 END) as with_solutions
        FROM tbl_coaching_practice_map m
        LEFT JOIN tbl_questions q ON m.testbook_id = q.testbook_id
    `);

    console.log("\n=================================================================");
    console.log("🎉 SYNC COMPLETED SUCCESSFULLY!");
    console.log("=================================================================");
    console.log(`• Total Mapped Practice Questions: ${finalCheck[0].total_mapped}`);
    console.log(`• Available in Database: ${finalCheck[0].with_question_data} / ${finalCheck[0].total_mapped} (${((finalCheck[0].with_question_data / finalCheck[0].total_mapped) * 100).toFixed(1)}%)`);
    console.log(`• Questions with Full Solutions: ${finalCheck[0].with_solutions} / ${finalCheck[0].total_mapped}`);
    console.log("=================================================================\n");

    await pool.end();
}

main().catch(console.error);
