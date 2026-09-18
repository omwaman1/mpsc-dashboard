import mysql from 'mysql2/promise';

const API_BASE = "https://ignite247api.classx.co.in";
const USER_ID = "149206";
const AUTH_TOKEN = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjE0OTIwNiIsInRpbWVzdGFtcCI6MTc3NzM3NjczNSwiaXZfdmVyIjo3LCJzZXNzaW9uIjoiZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKSVV6STFOaUo5LmV5SnBaQ0k2SWpFME9USXdOaUlzSW1WdFlXbHNJam9pYzJGdWRHOXphR0YyWTJoaGNqUXhOREZBWjIxaGFXd3VZMjl0SWl3aWJtRnRaU0k2SWxOaGJuUnZjMmdnUW1oaFozZGhiaUJCZG1Ob1lYSWlMQ0owWlc1aGJuUlVlWEJsSWpvaWRYTmxjaUlzSW5SbGJtRnVkRTVoYldVaU9pSnBaMjVwZEdVeU5EZGZaR0lpTENKMFpXNWhiblJKWkNJNklpSXNJbVJwYzNCdmMyRmliR1VpT21aaGJITmxmUS41TkpnTFl2YXdHWmFVNVdoLUprYmZBSGVWbTQzMjJkemFoVEtxZVc4ejAwIn0.jjZKaukYPQsjAK1sauz8ZaCOuh9EsCFaSjWT4YxBz8g";

const headers = {
    "user-id": USER_ID,
    "auth-key": "appxapi",
    "client-service": "Appx",
    "source": "windows",
    "authorization": AUTH_TOKEN,
    "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) ignite247/0.0.2 Chrome/108.0.5359.215 Electron/22.3.27 Safari/537.36",
    "origin": "https://igjhvhsdgavf.akamai.net.in",
    "referer": "https://igjhvhsdgavf.akamai.net.in/",
    "accept": "*/*"
};

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK',
    waitForConnections: true,
    connectionLimit: 15
};

function cleanHtml(str) {
    if (!str) return "";
    return str
        .replace(/<style[^>]*>.*?<\/style>/si, '')
        .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&nbsp;/g, " ")
        .trim();
}

function getSubjectTags(subjName, testTitle, qText) {
    const combined = `${subjName} ${testTitle} ${qText}`.toLowerCase();
    const tags = [];
    if (combined.includes('history') || combined.includes('इतिहास') || combined.includes('1857') || combined.includes('ब्रिटिश')) tags.push('history');
    if (combined.includes('polity') || combined.includes('राज्यघटना') || combined.includes('राज्यव्यवस्था') || combined.includes('राज्यशास्त्र') || combined.includes('संविधान')) tags.push('polity');
    if (combined.includes('geography') || combined.includes('भूगोल') || combined.includes('नदी') || combined.includes('पर्वत')) tags.push('geography');
    if (combined.includes('science') || combined.includes('विज्ञान') || combined.includes('जीवशास्त्र') || combined.includes('रसायन') || combined.includes('भौतिक')) {
        tags.push('science');
        if (combined.includes('जीवशास्त्र') || combined.includes('biology')) tags.push('biology');
        if (combined.includes('रसायन') || combined.includes('chemistry')) tags.push('chemistry');
        if (combined.includes('भौतिक') || combined.includes('physics')) tags.push('physics');
    }
    if (combined.includes('econom') || combined.includes('अर्थशास्त्र') || combined.includes('अर्थशास्र') || combined.includes('अर्थव्यवस्था')) tags.push('economics');
    if (combined.includes('aptitude') || combined.includes('अंकगणित') || combined.includes('गणित')) tags.push('maths');
    if (combined.includes('reasoning') || combined.includes('बुद्धिमत्ता')) tags.push('reasoning');
    if (combined.includes('current') || combined.includes('चालू घडामोडी')) tags.push('current-affairs');
    return tags.join(',');
}

async function syncIgnite() {
    console.log("=================================================================");
    console.log("🚀 IGNITE ACADEMY MASTER TEST SERIES SYNC");
    console.log("=================================================================\n");

    const pool = mysql.createPool(dbConfig);

    // 1. Fetch All Test Series
    let allSeries = [];
    let start = 0;
    while (true) {
        const res = await fetch(`${API_BASE}/get/test_series?start=${start}&search=&client_api_url=&exam_id=`, { headers });
        const json = await res.json();
        const list = json.data || [];
        if (list.length === 0) break;
        allSeries.push(...list);
        start += list.length;
        if (list.length < 10) break;
    }

    console.log(`Discovered ${allSeries.length} Total Test Series in Ignite Academy.\n`);

    // Priority MPSC and Maharashtra Series
    const mpscSeries = allSeries.filter(s => {
        const title = (s.title || '').toLowerCase();
        return title.includes('combine') || title.includes('mpsc') || title.includes('rajyaseva') ||
               title.includes('group b') || title.includes('group c') || title.includes('तलाठी') ||
               title.includes('पोलीस') || title.includes('rto') || title.includes('amvi') ||
               title.includes('nagarparishad') || title.includes('कृषी') || title.includes('civil');
    });

    console.log(`🎯 Target MPSC/State Series to Sync: ${mpscSeries.length} Series\n`);

    const upsertSql = `
        INSERT INTO \`tbl_questions\` (
            \`testbook_id\`, \`test_series_slug\`, \`test_id\`, \`test_title\`,
            \`subject_name\`, \`category_name\`, \`topic_name\`, \`subtopic_name\`,
            \`question_mr\`, \`question_en\`,
            \`opt1_mr\`, \`opt1_en\`, \`opt2_mr\`, \`opt2_en\`,
            \`opt3_mr\`, \`opt3_en\`, \`opt4_mr\`, \`opt4_en\`,
            \`correct_option\`, \`solution_mr\`, \`solution_en\`,
            \`positive_marks\`, \`negative_marks\`, \`subject_tags\`
        ) VALUES (?, 'ignite-academy', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            \`test_series_slug\` = 'ignite-academy',
            \`test_id\` = VALUES(\`test_id\`),
            \`test_title\` = VALUES(\`test_title\`),
            \`subject_name\` = VALUES(\`subject_name\`),
            \`category_name\` = VALUES(\`category_name\`),
            \`topic_name\` = VALUES(\`topic_name\`),
            \`subtopic_name\` = VALUES(\`subtopic_name\`),
            \`question_mr\` = VALUES(\`question_mr\`),
            \`opt1_mr\` = VALUES(\`opt1_mr\`),
            \`opt2_mr\` = VALUES(\`opt2_mr\`),
            \`opt3_mr\` = VALUES(\`opt3_mr\`),
            \`opt4_mr\` = VALUES(\`opt4_mr\`),
            \`correct_option\` = VALUES(\`correct_option\`),
            \`solution_mr\` = VALUES(\`solution_mr\`),
            \`positive_marks\` = VALUES(\`positive_marks\`),
            \`negative_marks\` = VALUES(\`negative_marks\`),
            \`subject_tags\` = VALUES(\`subject_tags\`);
    `;

    let totalTestsSynced = 0;
    let totalQuestionsSynced = 0;

    for (const ser of mpscSeries) {
        const seriesId = ser.id;
        const seriesTitle = ser.title;
        console.log(`\n📚 [Series ${seriesId}] ${seriesTitle}`);

        try {
            const sRes = await fetch(`${API_BASE}/get/testseries_subjects?testseries_id=${seriesId}`, { headers });
            const sJson = await sRes.json();
            const subjects = sJson.data || [];

            for (const subj of subjects) {
                const subjId = subj.subjectid;
                const subjName = subj.subject_name;

                const tRes = await fetch(`${API_BASE}/get/test_titlev2?testseriesid=${seriesId}&subject_id=${subjId}&userid=0&start=0`, { headers });
                const tJson = await tRes.json();
                const tests = tJson.test_titles || [];

                if (tests.length > 0) {
                    console.log(`   📁 ${subjName}: ${tests.length} tests`);
                }

                for (const t of tests) {
                    const testId = t.id;
                    const testTitle = t.title;
                    const qUrl = t.test_questions_url;

                    if (!qUrl) continue;

                    try {
                        const qRes = await fetch(qUrl);
                        if (!qRes.ok) continue;

                        const qList = await qRes.json();
                        if (!Array.isArray(qList)) continue;

                        for (const qItem of qList) {
                            const qId = `ignite_q_${qItem.id}`;
                            const qMr = cleanHtml(qItem.question);
                            const opt1 = cleanHtml(qItem.option_1);
                            const opt2 = cleanHtml(qItem.option_2);
                            const opt3 = cleanHtml(qItem.option_3);
                            const opt4 = cleanHtml(qItem.option_4);
                            const correctOpt = parseInt(qItem.answer) || 1;
                            
                            let solMr = cleanHtml(qItem.solution_text || "");
                            if (qItem.solution_image_1) {
                                solMr += `<p><img src="${qItem.solution_image_1}" style="max-width: 100%; border-radius: 8px;" /></p>`;
                            }
                            if (qItem.solution_image_2) {
                                solMr += `<p><img src="${qItem.solution_image_2}" style="max-width: 100%; border-radius: 8px;" /></p>`;
                            }

                            const posMarks = parseFloat(qItem.positive_marks) || 2.0;
                            const negMarks = parseFloat(qItem.negative_marks) || 0.5;
                            const tags = getSubjectTags(subjName, testTitle, qMr);

                            if (qMr) {
                                await pool.execute(upsertSql, [
                                    qId, `ignite_test_${testId}`, testTitle,
                                    subjName, seriesTitle, testTitle, subjName,
                                    qMr, '',
                                    opt1, '', opt2, '',
                                    opt3, '', opt4, '',
                                    correctOpt, solMr, '',
                                    posMarks, negMarks, tags
                                ]);
                                totalQuestionsSynced++;
                            }
                        }
                        totalTestsSynced++;
                    } catch(e) {
                        console.error(`      Error syncing test ${testId}:`, e.message);
                    }
                }
            }
        } catch(e) {
            console.error(`   Error for series ${seriesId}:`, e.message);
        }
    }

    console.log("\n=================================================================");
    console.log(`🎉 IGNITE ACADEMY SYNC COMPLETE!`);
    console.log(`  • Tests Synced: ${totalTestsSynced}`);
    console.log(`  • Questions Synced: ${totalQuestionsSynced}`);
    console.log("=================================================================\n");

    await pool.end();
}

syncIgnite().catch(err => {
    console.error("Fatal:", err);
});
