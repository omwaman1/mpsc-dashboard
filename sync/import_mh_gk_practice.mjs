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

const GOAL_SLUG = 'maharashtra-gk';

function categorizeTest(testTitle, subjectsRaw) {
    const t = (testTitle || '').trim();

    // 1. Previous Year Papers
    if (stripos(t, 'Talathi')) {
        return {
            subject: 'Previous Year Papers',
            chapter: 'तलाठी भरती',
            topic: 'Maharashtra Talathi Papers 2019 (तलाठी भरती अधिकृत पेपर्स)',
            subtopic: t
        };
    }
    if (stripos(t, 'Police')) {
        return {
            subject: 'Previous Year Papers',
            chapter: 'पोलीस भरती',
            topic: 'Maharashtra Police Papers (पोलीस भरती अधिकृत पेपर्स)',
            subtopic: t
        };
    }
    if (stripos(t, 'MPSC Subordinate') || stripos(t, 'ASO')) {
        return {
            subject: 'Previous Year Papers',
            chapter: 'MPSC मुख्य परीक्षा',
            topic: 'MPSC Official Papers (MPSC दुय्यम सेवा मुख्य)',
            subtopic: t
        };
    }

    // 2. Full Length Tests
    if (stripos(t, 'Full Test')) {
        return {
            subject: 'Full Length Tests',
            chapter: 'संपूर्ण सराव चाचण्या',
            topic: 'General Studies Full Mock Tests (संपूर्ण सराव चाचण्या)',
            subtopic: t
        };
    }

    // 3. Maharashtra Special (स्थापना दिवस)
    if (stripos(t, 'स्थापना दिवस') || stripos(t, 'Special')) {
        return {
            subject: 'Maharashtra Special',
            chapter: 'महाराष्ट्र विशेष',
            topic: 'Maharashtra Special - स्थापना दिवस (किल्ले, संत, पेशवे व संस्कृती)',
            subtopic: t
        };
    }

    // 4. History Chapter & Sectional Tests
    if (stripos(t, 'Ancient History')) {
        return { subject: 'History', chapter: 'प्राचीन इतिहास', topic: 'Ancient History (प्राचीन भारताचा इतिहास)', subtopic: t };
    }
    if (stripos(t, 'Medieval History')) {
        return { subject: 'History', chapter: 'मध्ययुगीन इतिहास', topic: 'Medieval History (मध्ययुगीन भारताचा इतिहास)', subtopic: t };
    }
    if (stripos(t, 'Modern History')) {
        return { subject: 'History', chapter: 'आधुनिक इतिहास', topic: 'Modern History (आधुनिक भारताचा इतिहास)', subtopic: t };
    }
    if (stripos(t, 'Maharashtra History')) {
        return { subject: 'History', chapter: 'महाराष्ट्र इतिहास', topic: 'Maharashtra History (महाराष्ट्राचा इतिहास)', subtopic: t };
    }

    // 5. Geography Chapter & Sectional Tests
    if (stripos(t, 'Maharashtra Geography')) {
        return { subject: 'Geography', chapter: 'महाराष्ट्र भूगोल', topic: 'Maharashtra Geography (महाराष्ट्राचा भूगोल)', subtopic: t };
    }
    if (stripos(t, 'Indian Geography')) {
        return { subject: 'Geography', chapter: 'भारत भूगोल', topic: 'Indian Geography (भारताचा भूगोल)', subtopic: t };
    }
    if (stripos(t, 'World Geography')) {
        return { subject: 'Geography', chapter: 'जग व प्राकृतिक भूगोल', topic: 'World & Physical Geography (जगाचा व प्राकृतिक भूगोल)', subtopic: t };
    }
    if (stripos(t, 'Geography')) {
        return { subject: 'Geography', chapter: 'सामान्य भूगोल', topic: 'General Geography (सामान्य भूगोल)', subtopic: t };
    }

    // 6. Polity Chapter & Sectional Tests
    if (stripos(t, 'Polity') || stripos(t, 'Constitution')) {
        return { subject: 'Polity', chapter: 'भारतीय राज्यघटना', topic: 'Indian Polity & Governance (भारतीय राज्यघटना व प्रशासन)', subtopic: t };
    }

    // 7. Science Chapter & Sectional Tests
    if (stripos(t, 'General Science') || stripos(t, 'Science')) {
        return { subject: 'Science & Technology', chapter: 'सामान्य विज्ञान', topic: 'General Science (सामान्य विज्ञान व तंत्रज्ञान)', subtopic: t };
    }

    // 8. Economy Chapter & Sectional Tests
    if (stripos(t, 'Economy') || stripos(t, 'Economics')) {
        return { subject: 'Economics', chapter: 'अर्थव्यवस्था', topic: 'Economy & Banking (भारतीय अर्थव्यवस्था व बँकिंग)', subtopic: t };
    }

    // 9. Marathi Chapter & Sectional Tests
    if (stripos(t, 'Marathi')) {
        return { subject: 'Marathi', chapter: 'मराठी व्याकरण', topic: 'Marathi Grammar & Vocabulary (मराठी व्याकरण व शब्दसंग्रह)', subtopic: t };
    }

    // 10. Current Affairs Chapter & Sectional Tests
    if (stripos(t, 'Current Affairs') || stripos(t, 'CA')) {
        if (stripos(t, 'Maharashtra Specific')) {
            return { subject: 'Current Affairs', chapter: 'महाराष्ट्र घडामोडी', topic: 'Maharashtra Specific Current Affairs (महाराष्ट्र विशेष घडामोडी)', subtopic: t };
        }
        if (stripos(t, '2023') || stripos(t, '2024') || /\(July|\(August|\(September|\(October|\(November|\(December|\(January|\(February|\(March|\(April|\(May|\(June/i.test(t)) {
            return { subject: 'Current Affairs', chapter: 'मासिक घडामोडी', topic: 'Monthly Current Affairs (मासिक चालू घडामोडी संच)', subtopic: t };
        }
        return { subject: 'Current Affairs', chapter: 'राष्ट्रीय घडामोडी', topic: 'National & International Affairs (राष्ट्रीय व आंतरराष्ट्रीय घडामोडी)', subtopic: t };
    }

    // 11. Maharashtra GK / General Awareness
    if (stripos(t, 'Maharashtra GK')) {
        return { subject: 'General Knowledge', chapter: 'महाराष्ट्र GK', topic: 'Maharashtra GK (महाराष्ट्र सामान्य ज्ञान घटक)', subtopic: t };
    }
    if (stripos(t, 'General Awareness')) {
        return { subject: 'General Knowledge', chapter: 'सामान्य ज्ञान', topic: 'General Awareness Sectional Tests (सामान्य ज्ञान सराव)', subtopic: t };
    }

    // 12. Fallbacks based on subjects
    if (stripos(subjectsRaw, 'मराठी')) {
        return { subject: 'Marathi', chapter: 'मराठी व्याकरण', topic: 'Marathi Grammar (मराठी व्याकरण)', subtopic: t };
    }
    if (stripos(subjectsRaw, 'English')) {
        return { subject: 'English', chapter: 'इंग्रजी व्याकरण', topic: 'English Grammar (इंग्रजी व्याकरण)', subtopic: t };
    }
    if (stripos(subjectsRaw, 'गणित') || stripos(subjectsRaw, 'CSAT')) {
        return { subject: 'General Mental Ability', chapter: 'अंकगणित व बुद्धिमत्ता', topic: 'Quantitative & Reasoning (अंकगणित व बुद्धिमत्ता)', subtopic: t };
    }

    return { subject: 'General Knowledge', chapter: 'सामान्य ज्ञान', topic: 'General Studies Practice (सामान्य अध्ययन सराव)', subtopic: t };
}

function stripos(str, needle) {
    if (!str || !needle) return false;
    return str.toLowerCase().includes(needle.toLowerCase());
}

async function main() {
    const pool = mysql.createPool(DB_CONFIG);
    console.log("=================================================================");
    console.log("🚀 CLEAN HIERARCHY INGESTION FOR MAHARASHTRA GK");
    console.log("   Goal Slug: " + GOAL_SLUG);
    console.log("=================================================================\n");

    // 1. Fetch questions grouped by test
    console.log("Step 1: Reading questions from tbl_questions...");
    const [qRows] = await pool.query(`
        SELECT 
            id,
            testbook_id,
            test_id,
            test_title,
            subject_name
        FROM tbl_questions
        WHERE test_series_slug = 'maharashtra-general-knowledge'
        ORDER BY test_title ASC, id ASC
    `);

    console.log(`✓ Fetched ${qRows.length} questions.`);

    // 2. Build practice hierarchy mapping
    console.log("\nStep 2: Mapping into clean Subject -> Topic -> Subtopic hierarchy...");
    const mappingRecords = [];
    const testOrderMap = new Map(); // test_id -> count

    for (const r of qRows) {
        const cat = categorizeTest(r.test_title, r.subject_name);
        const testId = r.test_id || 'mh_gk_test';
        
        const curOrder = (testOrderMap.get(testId) || 0) + 1;
        testOrderMap.set(testId, curOrder);

        mappingRecords.push({
            goal_slug: GOAL_SLUG,
            subject_name: cat.subject,
            chapter_name: cat.chapter,
            topic_name: cat.topic,
            subtopic_name: cat.subtopic,
            practice_id: testId,
            testbook_id: r.testbook_id,
            question_order: curOrder
        });
    }

    console.log(`✓ Total mapping records created: ${mappingRecords.length}`);

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

    // 4. Verification
    const [stats] = await pool.query(`
        SELECT 
            m.subject_name,
            COUNT(DISTINCT m.topic_name) as total_topics,
            COUNT(DISTINCT m.practice_id) as total_sets,
            COUNT(m.id) as total_questions,
            COUNT(DISTINCT pq.testbook_id) as available_in_practice_table,
            COUNT(CASE WHEN pq.solution_mr IS NOT NULL AND TRIM(pq.solution_mr) != '' THEN 1 END) as with_solution_mr
        FROM tbl_coaching_practice_map m
        LEFT JOIN tbl_coaching_practice_questions pq ON m.testbook_id = pq.testbook_id
        WHERE m.goal_slug = ?
        GROUP BY m.subject_name
        ORDER BY total_questions DESC
    `, [GOAL_SLUG]);

    console.log("\n=================================================================");
    console.log("🎉 CLEAN HIERARCHY SUMMARY BY SUBJECT (NO DUPLICATE TOPICS):");
    console.log("=================================================================");
    console.table(stats);

    const [topicStats] = await pool.query(`
        SELECT 
            subject_name,
            topic_name,
            COUNT(DISTINCT practice_id) as sets,
            COUNT(id) as questions
        FROM tbl_coaching_practice_map
        WHERE goal_slug = ?
        GROUP BY subject_name, topic_name
        ORDER BY subject_name ASC, questions DESC
    `, [GOAL_SLUG]);

    console.log("\n--- COMPLETE TOPIC LIST ---");
    console.table(topicStats);

    await pool.end();
}

main().catch(console.error);
