import mysql from './node_modules/mysql2/promise.js';

const DB_CONFIG = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK',
    waitForConnections: true,
    connectionLimit: 10
};

const GOAL_SLUG = 'ignite-mpsc-group-c';

function mapSubjectAndChapter(rawSubject) {
    const s = (rawSubject || '').trim().replace(/\.$/, '');
    
    if (s.includes('इतिहास')) {
        return { subject: 'History', chapter: 'आधुनिक भारताचा इतिहास' };
    }
    if (s.includes('भूगोल')) {
        return { subject: 'Geography', chapter: 'महाराष्ट्राचा व भारताचा भूगोल' };
    }
    if (s.includes('राज्यव्यवस्था') || s.includes('राज्यशास्त्र')) {
        return { subject: 'Polity', chapter: s };
    }
    if (s.includes('अर्थशास्र') || s.includes('अर्थशास्त्र')) {
        return { subject: 'Economics', chapter: 'भारतीय अर्थव्यवस्था' };
    }
    if (s.includes('जीवशास्त्र') || s.includes('रसायनशास्त्र') || s.includes('भौतिकशास्त्र') || s.includes('विज्ञान')) {
        return { subject: 'Science & Technology', chapter: s };
    }
    if (s.includes('बुद्धिमत्ता')) {
        return { subject: 'General Mental Ability', chapter: 'तार्किक व मानसिक क्षमता' };
    }
    if (s.includes('चालू घडामोडी') || s.toUpperCase().includes('CURRENT AFFAIR')) {
        return { subject: 'Current Affairs', chapter: 'चालू घडामोडी' };
    }
    if (s.includes('Full Length Test')) {
        return { subject: 'Full Length Test', chapter: 'संयुक्त पूर्व परीक्षा गट-क सराव पेपर' };
    }
    return { subject: s || 'General', chapter: s || 'General' };
}

async function main() {
    const pool = mysql.createPool(DB_CONFIG);
    console.log("=================================================================");
    console.log("🚀 IMPORT IGNITE ACADEMY MPSC GROUP C INTO PRACTICE REPOSITORY");
    console.log("=================================================================\n");

    // 1. Fetch all Ignite Group C questions from tbl_questions
    console.log("Step 1: Reading Ignite Group C questions from tbl_questions...");
    const [qRows] = await pool.query(`
        SELECT 
            id,
            testbook_id,
            test_id,
            test_title,
            subject_name,
            topic_name,
            subtopic_name
        FROM tbl_questions
        WHERE test_series_slug = 'ignite-academy'
          AND category_name = 'COMBINE  Group C Pre Exam | ONLINE TEST SERIES'
        ORDER BY subject_name ASC, test_title ASC, id ASC
    `);

    console.log(`✓ Fetched ${qRows.length} questions from tbl_questions.`);

    // 2. Build practice hierarchy mapping
    console.log("\nStep 2: Preparing hierarchy mapping...");
    const mappingRecords = [];
    const testOrderMap = new Map(); // test_id -> count

    for (const r of qRows) {
        const { subject, chapter } = mapSubjectAndChapter(r.subject_name);
        const testId = r.test_id || 'ignite_test';
        const testTitle = (r.test_title || 'General Practice').trim();

        const curOrder = (testOrderMap.get(testId) || 0) + 1;
        testOrderMap.set(testId, curOrder);

        mappingRecords.push({
            goal_slug: GOAL_SLUG,
            subject_name: subject,
            chapter_name: chapter,
            topic_name: testTitle,
            subtopic_name: testTitle,
            practice_id: testId,
            testbook_id: r.testbook_id,
            question_order: curOrder
        });
    }

    console.log(`Total mapping records to save: ${mappingRecords.length}`);

    // 3. Clear existing goal mapping and insert fresh
    console.log(`\nStep 3: Populating 'tbl_coaching_practice_map' for ${GOAL_SLUG}...`);
    await pool.query(`DELETE FROM tbl_coaching_practice_map WHERE goal_slug = ?`, [GOAL_SLUG]);

    const mapInsertSql = `
        INSERT INTO tbl_coaching_practice_map (
            goal_slug, subject_name, chapter_name, topic_name, subtopic_name,
            practice_id, testbook_id, question_order
        ) VALUES ?
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

    // 4. Copy question records into tbl_coaching_practice_questions
    console.log(`\nStep 4: Copying question details into 'tbl_coaching_practice_questions'...`);
    const [copyRes] = await pool.query(`
        INSERT INTO tbl_coaching_practice_questions (
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
        WHERE q.test_series_slug = 'ignite-academy'
          AND q.category_name = 'COMBINE  Group C Pre Exam | ONLINE TEST SERIES'
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
    `);

    console.log(`✅ Questions updated/inserted in tbl_coaching_practice_questions.`);

    // 5. Verification
    const [finalStats] = await pool.query(`
        SELECT 
            m.subject_name,
            COUNT(DISTINCT m.topic_name) as total_topics,
            COUNT(DISTINCT m.practice_id) as total_sets,
            COUNT(m.id) as total_questions,
            COUNT(DISTINCT pq.testbook_id) as available_in_practice_table,
            COUNT(CASE WHEN pq.solution_mr IS NOT NULL AND TRIM(pq.solution_mr) != '' THEN 1 END) as with_solution
        FROM tbl_coaching_practice_map m
        LEFT JOIN tbl_coaching_practice_questions pq ON m.testbook_id = pq.testbook_id
        WHERE m.goal_slug = ?
        GROUP BY m.subject_name
        ORDER BY total_questions DESC
    `, [GOAL_SLUG]);

    console.log("\n=================================================================");
    console.log("🎉 IGNITE MPSC GROUP C IMPORT STATS BY SUBJECT:");
    console.log("=================================================================");
    console.table(finalStats);

    const [totalStats] = await pool.query(`
        SELECT 
            COUNT(m.id) as total_mapped,
            COUNT(DISTINCT m.testbook_id) as total_unique,
            COUNT(DISTINCT pq.testbook_id) as ready_in_practice_table
        FROM tbl_coaching_practice_map m
        LEFT JOIN tbl_coaching_practice_questions pq ON m.testbook_id = pq.testbook_id
        WHERE m.goal_slug = ?
    `, [GOAL_SLUG]);
    console.log("Overall Total:", totalStats[0]);
    console.log("=================================================================\n");

    await pool.end();
}

main().catch(console.error);
