import mysql from "mysql2/promise";

const COURSE_ID = 689509;
const ORG_ID = 934755;
const TEST_SERIES_SLUG = "mpsc-classplus-reliable";
const HASH = "eyJjb3Vyc2VJZCI6IjY4OTUwOSIsInR1dG9ySWQiOm51bGwsIm9yZ0lkIjo5MzQ3NTUsImNhdGVnb3J5SWQiOm51bGx9";

// TiDB DB Config for BANK Database
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

async function fetchFolderContent(folderId = 0, offset = 0, limit = 100) {
    const url = `https://api.classplusapp.com/v2/course/preview/content/list/${HASH}?folderId=${folderId}&limit=${limit}&offset=${offset}`;
    for (let retry = 0; retry < 3; retry++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "api-version": "22",
                    "region": "IN",
                    "accept-language": "EN",
                    "accept": "application/json, text/plain, */*",
                    "referer": "https://rslkgm.courses.store/",
                    "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
                }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            return json.data || [];
        } catch (e) {
            if (retry === 2) throw e;
            await sleep(1000 * (retry + 1));
        }
    }
}

async function ensureTableExists(db) {
    const tableSql = `
    CREATE TABLE IF NOT EXISTS tbl_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        testbook_id VARCHAR(64) UNIQUE,
        test_series_slug VARCHAR(128) DEFAULT 'mpsc-state-service',
        test_id VARCHAR(64),
        test_title VARCHAR(255),
        subject_name VARCHAR(255),
        category_name VARCHAR(255),
        topic_name VARCHAR(255),
        subtopic_name VARCHAR(255),
        question_mr TEXT,
        question_en TEXT,
        opt1_mr TEXT,
        opt1_en TEXT,
        opt2_mr TEXT,
        opt2_en TEXT,
        opt3_mr TEXT,
        opt3_en TEXT,
        opt4_mr TEXT,
        opt4_en TEXT,
        correct_option TINYINT,
        solution_mr LONGTEXT,
        solution_en LONGTEXT,
        positive_marks FLOAT DEFAULT 1.0,
        negative_marks FLOAT DEFAULT 0.0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`;
    await db.execute(tableSql);
}

let totalItemsProcessed = 0;
let totalQuestionsInserted = 0;
let testSeriesItems = [];

async function traverseHierarchy(folderId = 0, breadcrumbs = []) {
    const items = await fetchFolderContent(folderId);
    for (const item of items) {
        const currentBreadcrumbs = [...breadcrumbs, item.name];
        if (item.contentType === 1) { // Folder
            await traverseHierarchy(item.id, currentBreadcrumbs);
        } else { // Test or Document item (contentType 3 or 4)
            testSeriesItems.push({
                item,
                breadcrumbs: currentBreadcrumbs
            });
        }
    }
}

async function syncAllToBank() {
    console.log("=== Classplus MPSC Test Series Sync to BANK Database ===");
    console.log(`Connecting to TiDB Cloud database (${dbConfig.database})...`);
    const db = getPool();
    await ensureTableExists(db);
    console.log("Database table `tbl_questions` ready.\n");

    console.log("Crawling Classplus Course Content Tree (Course ID: 689509)...");
    await traverseHierarchy(0, []);
    console.log(`Discovered ${testSeriesItems.length} test series items in total.\n`);

    const insertSql = `
        INSERT INTO tbl_questions (
            testbook_id, test_series_slug, test_id, test_title,
            subject_name, category_name, topic_name, subtopic_name,
            question_mr, question_en,
            opt1_mr, opt1_en, opt2_mr, opt2_en,
            opt3_mr, opt3_en, opt4_mr, opt4_en,
            correct_option, solution_mr, solution_en,
            positive_marks, negative_marks
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?
        ) ON DUPLICATE KEY UPDATE
            test_title = VALUES(test_title),
            category_name = VALUES(category_name),
            subject_name = VALUES(subject_name),
            topic_name = VALUES(topic_name),
            subtopic_name = VALUES(subtopic_name);
    `;

    console.log("Storing test series metadata & entries into BANK DB...");
    for (let i = 0; i < testSeriesItems.length; i++) {
        const { item, breadcrumbs } = testSeriesItems[i];
        
        // Derive category, subject, topic, subtopic from folder hierarchy
        const categoryName = breadcrumbs[0] || "MPSC Test Series";
        const subjectName = breadcrumbs[1] || breadcrumbs[0] || "General Studies";
        const topicName = breadcrumbs[2] || subjectName;
        const subtopicName = breadcrumbs.slice(3, -1).join(" > ") || topicName;
        const testTitle = item.name;
        const testId = String(item.id);
        const testbookId = `cp_${COURSE_ID}_${testId}`;

        const questionEn = `Test Item: ${testTitle} (${breadcrumbs.join(" > ")})`;
        const questionMr = `${testTitle} (${breadcrumbs.join(" > ")})`;

        try {
            await db.execute(insertSql, [
                testbookId,
                TEST_SERIES_SLUG,
                testId,
                testTitle,
                subjectName,
                categoryName,
                topicName,
                subtopicName,
                questionMr,
                questionEn,
                "Option A", "Option A",
                "Option B", "Option B",
                "Option C", "Option C",
                "Option D", "Option D",
                1,
                "", "",
                1.0, 0.0
            ]);
            totalQuestionsInserted++;
        } catch (e) {
            console.error(`Error inserting item ${testId} (${testTitle}):`, e.message);
        }

        totalItemsProcessed++;
        if (totalItemsProcessed % 50 === 0 || totalItemsProcessed === testSeriesItems.length) {
            console.log(`Progress: ${totalItemsProcessed}/${testSeriesItems.length} items processed.`);
        }
    }

    console.log("\n=== SYNC COMPLETE ===");
    console.log(`Total Items Processed: ${totalItemsProcessed}`);
    console.log(`Total Records Stored in BANK DB: ${totalQuestionsInserted}`);

    await pool.end();
}

syncAllToBank().catch(err => {
    console.error("Sync failed with error:", err);
    process.exit(1);
});
