import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlZTVlOTRjNDkyY2Q1MGQwZjczMjg4OSIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwiaWF0IjoiMjAyNi0wOS0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwibmFtZSI6Im9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuNUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiIiwiaXNQYWlkVXNlciI6ZmFsc2UsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.JI5HbF9uBEVOnfqGkiGIy-Jdri4vk79wMPjYeOPL2yDTw2fEJDqcM0HoqIWfDXZxFxiNqtnuU6kBcE3Ejhh2oFOfJHh7MJnFVxZvzGfC4NZiW6EZ_GH80TNze6TRyeQjDyp7VqK1sxOMznS_IgL6-rgQY9y-4gaNUNXnnJ8mIIA";

const chapters = [
    { subject: 'History', chapter: 'Modern History', chapterId: '63bffd30d9588a40828c0458' },
    { subject: 'Geography', chapter: 'Indian Geography', chapterId: '63c69a8e3caf8ba1527134be' },
    { subject: 'Polity', chapter: 'Indian Constitution', chapterId: '63c69bf8625c085d258db7c4' },
    { subject: 'Economics', chapter: 'Indian Economy', chapterId: '63c005015d5b4b3e1b90b7b3' },
    { subject: 'Science & Tech', chapter: 'General Science', chapterId: '63c6989b6fee924631c1e45a' },
    { subject: 'Science & Tech', chapter: 'Computer & IT', chapterId: '63c699445bb83874b71a0254' },
    { subject: 'Marathi', chapter: 'Marathi Grammar', chapterId: '63c00b4f5d5b4b3e1b915081' },
    { subject: 'General Mental Ability', chapter: 'General Mental Ability', chapterId: '63c00afbd9588a40828d65f5' }
];

async function checkPracticeQuestionsInDb() {
    const conn = await mysql.createConnection({
        host: '127.0.0.1',
        port: 3306,
        user: 'root',
        password: '',
        database: 'BANK'
    });

    console.log("Connecting to local DB 'BANK'...\n");

    let allPracticeQIds = [];
    const mappingData = [];

    for (const item of chapters) {
        const url = `https://api.testbook.com/api/v2/study-tab/chapters/${item.chapterId}/practice-sections?limit=-1&language=English`;
        const res = await fetch(url, {
            headers: {
                "source": "testbook",
                "origin": "https://testbook.com",
                "referer": "https://testbook.com/mpsc-combined-group-c-2026-coaching",
                "accept": "application/json, text/plain, */*",
                "authorization": `Bearer ${AUTH_TOKEN}`,
                "x-tb-client": "web,1.3"
            }
        });

        const json = await res.json();
        const sections = json?.data?.sections || [];

        for (const sec of sections) {
            const topicTitle = sec.properties?.title || 'General';
            for (const p of sec.practices || []) {
                const subtopicTitle = p.title;
                const ques = p.ques || [];
                for (const qId of ques) {
                    allPracticeQIds.push(qId);
                    mappingData.push({
                        subject: item.subject,
                        chapter: item.chapter,
                        topic: topicTitle,
                        subtopic: subtopicTitle,
                        testbook_id: qId
                    });
                }
            }
        }
    }

    const uniqueQIds = [...new Set(allPracticeQIds)];
    console.log(`Total question references collected: ${allPracticeQIds.length}`);
    console.log(`Unique question IDs: ${uniqueQIds.length}`);

    // Query DB for these question IDs in batches of 500
    let foundCount = 0;
    const foundBySlug = {};

    for (let i = 0; i < uniqueQIds.length; i += 500) {
        const batch = uniqueQIds.slice(i, i + 500);
        const placeholders = batch.map(() => '?').join(',');
        const [rows] = await conn.query(`
            SELECT testbook_id, test_series_slug, subject_name 
            FROM tbl_questions 
            WHERE testbook_id IN (${placeholders})
        `, batch);

        foundCount += rows.length;
        for (const r of rows) {
            foundBySlug[r.test_series_slug] = (foundBySlug[r.test_series_slug] || 0) + 1;
        }
    }

    console.log(`\n======================================================`);
    console.log(`Found in local DB: ${foundCount} / ${uniqueQIds.length} (${((foundCount / uniqueQIds.length) * 100).toFixed(1)}%)`);
    console.log(`Distribution by test_series_slug:`);
    console.dir(foundBySlug);
    console.log(`======================================================\n`);

    await conn.end();
}

checkPracticeQuestionsInDb().catch(console.error);
