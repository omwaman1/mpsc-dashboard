import mysql from 'mysql2/promise';

const DB_CONFIG = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
};

async function createTable() {
    const conn = await mysql.createConnection(DB_CONFIG);
    console.log("Connected to MySQL. Creating tbl_coaching_practice_map...");

    await conn.query(`
        CREATE TABLE IF NOT EXISTS tbl_coaching_practice_map (
            id INT AUTO_INCREMENT PRIMARY KEY,
            goal_slug VARCHAR(128) NOT NULL DEFAULT 'mpsc-combined-group-c-2026',
            subject_name VARCHAR(128) NOT NULL,
            chapter_name VARCHAR(128) NOT NULL,
            topic_name VARCHAR(128) NOT NULL,
            subtopic_name VARCHAR(255) NOT NULL,
            practice_id VARCHAR(64) NOT NULL,
            testbook_id VARCHAR(64) NOT NULL,
            question_order INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_practice_q (practice_id, testbook_id),
            INDEX idx_subject_topic (subject_name, topic_name),
            INDEX idx_testbook_id (testbook_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    console.log("✅ Table tbl_coaching_practice_map created successfully.");
    await conn.end();
}

createTable().catch(console.error);
