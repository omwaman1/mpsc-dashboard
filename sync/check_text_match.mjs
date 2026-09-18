import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlZTVlOTRjNDkyY2Q1MGQwZjczMjg4OSIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwiaWF0IjoiMjAyNi0wOS0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwibmFtZSI6Im9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuNUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiIiwiaXNQYWlkVXNlciI6ZmFsc2UsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.JI5HbF9uBEVOnfqGkiGIy-Jdri4vk79wMPjYeOPL2yDTw2fEJDqcM0HoqIWfDXZxFxiNqtnuU6kBcE3Ejhh2oFOfJHh7MJnFVxZvzGfC4NZiW6EZ_GH80TNze6TRyeQjDyp7VqK1sxOMznS_IgL6-rgQY9y-4gaNUNXnnJ8mIIA";

// Sample IDs from practice sets that were not among the 372
const sampleQIds = [
    '5fa8cf462a3ef8da23cc3ac0', // RTI
    '612e49ec94afaf8a524f9268', // RTI
    '5fbd01f9080bf366c97a36c5', // RTI
    '61badc8362b2b86145c52a90', // Maharashtra Lokseva Hakka
    '61bb22d27d75d4d198241e8f'  // Maharashtra Lokseva Hakka
];

async function checkTextMatch() {
    const conn = await mysql.createConnection({
        host: '127.0.0.1',
        port: 3306,
        user: 'root',
        password: '',
        database: 'BANK'
    });

    for (const qId of sampleQIds) {
        // Fetch question text from Testbook API
        const res = await fetch(`https://api.testbook.com/api/v2/questions/${qId}`, {
            headers: { "x-tb-client": "web,1.3" }
        });
        const json = await res.json();
        const q = json.data || json;
        const q_en = (q.en?.value || '').replace(/<[^>]+>/g, '').trim().substring(0, 50);
        const q_mr = (q.mr?.value || '').replace(/<[^>]+>/g, '').trim().substring(0, 50);

        console.log(`\nTesting ID: ${qId}`);
        console.log(`EN snippet: ${q_en}`);
        console.log(`MR snippet: ${q_mr}`);

        // Search by ID in tbl_questions
        const [byExactId] = await conn.query("SELECT id, test_series_slug, testbook_id FROM tbl_questions WHERE testbook_id = ?", [qId]);
        console.log(`Exact ID match:`, byExactId.length > 0 ? byExactId[0] : 'None');

        // Search by LIKE in question_en or question_mr
        if (q_en && q_en.length > 10) {
            const searchPattern = `%${q_en.substring(0, 30)}%`;
            const [byText] = await conn.query("SELECT id, test_series_slug, testbook_id, LEFT(question_en, 60) as q_en FROM tbl_questions WHERE question_en LIKE ? LIMIT 2", [searchPattern]);
            console.log(`Text match EN:`, byText.length > 0 ? byText : 'None');
        }
        if (q_mr && q_mr.length > 10) {
            const searchPatternMr = `%${q_mr.substring(0, 30)}%`;
            const [byTextMr] = await conn.query("SELECT id, test_series_slug, testbook_id, LEFT(question_mr, 60) as q_mr FROM tbl_questions WHERE question_mr LIKE ? LIMIT 2", [searchPatternMr]);
            console.log(`Text match MR:`, byTextMr.length > 0 ? byTextMr : 'None');
        }
    }

    await conn.end();
}

checkTextMatch().catch(console.error);
