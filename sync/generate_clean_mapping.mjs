import mysql from 'mysql2/promise';

async function generateCleanMapping() {
    const conn = await mysql.createConnection({
        host: '127.0.0.1',
        port: 3306,
        user: 'root',
        password: '',
        database: 'BANK'
    });

    const [rows] = await conn.query(`
        SELECT 
            COALESCE(subject_name, 'General') as subject,
            COALESCE(topic_name, 'General') as topic,
            COALESCE(subtopic_name, 'General') as subtopic,
            COUNT(*) as q_count
        FROM tbl_questions
        WHERE test_series_slug = 'mpsc-group-c'
        GROUP BY subject_name, topic_name, subtopic_name
        ORDER BY subject_name, topic_name, subtopic_name
    `);

    console.log(`Total groups: ${rows.length}`);

    const mapping = {};
    for (const r of rows) {
        const s = r.subject.trim();
        const t = r.topic.trim();
        const st = r.subtopic.trim();

        if (!mapping[s]) mapping[s] = { total: 0, topics: {} };
        mapping[s].total += r.q_count;

        if (!mapping[s].topics[t]) mapping[s].topics[t] = { total: 0, subtopics: {} };
        mapping[s].topics[t].total += r.q_count;

        mapping[s].topics[t].subtopics[st] = (mapping[s].topics[t].subtopics[st] || 0) + r.q_count;
    }

    for (const [sub, subData] of Object.entries(mapping)) {
        console.log(`\n============================================================`);
        console.log(`📚 SUBJECT: ${sub} (Total: ${subData.total} questions)`);
        console.log(`============================================================`);
        
        for (const [top, topData] of Object.entries(subData.topics)) {
            const subkeys = Object.keys(topData.subtopics);
            if (subkeys.length === 1 && (subkeys[0] === 'General' || subkeys[0] === top)) {
                console.log(`  ├─ 📌 Topic: ${top} [${topData.total} Qs]`);
            } else {
                console.log(`  ├─ 📌 Topic: ${top} [${topData.total} Qs]`);
                for (const [subtop, cnt] of Object.entries(topData.subtopics)) {
                    console.log(`  │    └─ • Subtopic: ${subtop} [${cnt} Qs]`);
                }
            }
        }
    }

    await conn.end();
}

generateCleanMapping().catch(console.error);
