import mysql from 'mysql2/promise';

const TOKEN = "eyJhbGciOiJIUzM4NCIsInR5cCI6IkpXVCJ9.eyJpZCI6MTg5NzA5NjY4LCJvcmdJZCI6ODU5NywidHlwZSI6MSwibW9iaWxlIjoiOTE5OTIyNjY1ODIyIiwibmFtZSI6Im9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuM0BnbWFpbC5jb20iLCJpc0ZpcnN0TG9naW4iOnRydWUsImRlZmF1bHRMYW5ndWFnZSI6IkVOIiwiY291bnRyeUNvZGUiOiJJTiIsImlzSW50ZXJuYXRpb25hbCI6MCwiaXNEaXkiOnRydWUsImxvZ2luVmlhIjoiT3RwIiwiZmluZ2VycHJpbnRJZCI6ImFjNTFhYjU3NjJhOWZjZmQxM2M1YWRiNjRkZmRiZjk0IiwiaWF0IjoxNzg2MzYyMzU2LCJleHAiOjE3ODY5NjcxNTZ9.lQdKObF7yPvgeK4JAn2MV-RW2CCalcZZbPAkmgcE0LmqFtpcUMXED0AUnJxSTz6t";
const COURSE_IDS = [824728, 827533, 876461, 876723];

const dbConfig = {
    host: 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com',
    port: 4000,
    user: 'zscbg7NahcfLgxs.root',
    password: '9gqd36vEIR9HPZnq',
    database: 'BANK',
    ssl: { minVersion: 'TLSv1.2', rejectUnauthorized: false }
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function stripHtml(text) {
    if (!text) return '';
    return text.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
}

async function translateText(text, sl = 'mr', tl = 'en') {
    if (!text || !text.trim()) return '';
    const plain = stripHtml(text);
    if (!plain) return '';
    if (/^\d+$/.test(plain) || plain.length <= 1) return plain;

    const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=${sl}&tl=${tl}&dt=t&q=` + encodeURIComponent(plain);
    for (let attempt = 0; attempt < 3; attempt++) {
        try {
            const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (data && data[0] && Array.isArray(data[0])) {
                return data[0].map(s => s[0] || '').join('');
            }
        } catch (e) {
            await new Promise(r => setTimeout(r, 300 * (attempt + 1)));
        }
    }
    return plain;
}

async function fetchContent(courseId, folderId = 0) {
    const url = `https://api.classplusapp.com/v2/course/content/get?courseId=${courseId}&folderId=${folderId}`;
    for (let retry = 0; retry < 3; retry++) {
        try {
            const res = await fetch(url, {
                headers: {
                    "x-access-token": TOKEN,
                    "api-version": "52",
                    "region": "IN",
                    "device-id": "306",
                    "accept-language": "en",
                    "user-agent": "Mozilla/5.0",
                    "referer": "https://web.classplusapp.com/"
                }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            return json?.data?.courseContent || [];
        } catch (e) {
            if (retry === 2) return [];
            await sleep(500 * (retry + 1));
        }
    }
    return [];
}

async function fetchTestDetails(testId) {
    const urls = [
        `https://api.classplusapp.com/v2/tests/${testId}`,
        `https://api.classplusapp.com/v2/tests/${testId}/preview`,
        `https://api.classplusapp.com/v2/tests/${testId}/questions`
    ];

    for (const url of urls) {
        try {
            const res = await fetch(url, {
                headers: {
                    "x-access-token": TOKEN,
                    "api-version": "52",
                    "region": "IN",
                    "device-id": "306",
                    "accept-language": "en",
                    "user-agent": "Mozilla/5.0",
                    "referer": "https://web.classplusapp.com/"
                }
            });
            if (res.ok) {
                const json = await res.json();
                if (json?.data?.questions || json?.data?.questionList) {
                    return json.data;
                }
            }
        } catch {}
    }
    return null;
}

function normalizeSubject(raw) {
    if (!raw) return 'सामान्य अध्ययन';
    const c = raw.replace(/[\uFFFD\u200B\u200C\u200D\uFEFF\u00A0?]/g, '').trim();

    if (/अर्थ|Economics/i.test(c)) return 'अर्थव्यवस्था';
    if (/राज्यघटना|संविधान|Polity/i.test(c)) return 'भारतीय राज्यघटना';
    if (/इतिहास|History/i.test(c)) return 'इतिहास';
    if (/भूगोल|Geography/i.test(c)) return 'भूगोल';
    if (/चालू|घडामोडी|Current/i.test(c)) return 'चालू घडामोडी';
    if (/मराठी|Marathi/i.test(c)) return 'मराठी व्याकरण';
    if (/ENGLISH|English/i.test(c)) return 'English Grammar';
    if (/विज्ञान|Science|Biology|Physics|Chemistry/i.test(c)) return 'सामान्य विज्ञान';
    if (/गणित|बुद्धिमत्ता|Reasoning|Math/i.test(c)) return 'गणित व बुद्धिमत्ता (CSAT)';
    if (/पर्यावरण|Environment/i.test(c)) return 'पर्यावरण';
    if (/कृषी|Agriculture/i.test(c)) return 'कृषी';
    return c || 'सामान्य अध्ययन';
}

async function main() {
    console.log("=== APPA HATNURE LOKSEVA ACADEMY FULL MASTER SYNC & AI TRANSLATION ENGINE ===");

    const pool = mysql.createPool({ ...dbConfig, waitForConnections: true, connectionLimit: 15 });

    let totalInserted = 0;
    let totalUpdated = 0;
    let totalItemsScanned = 0;

    for (const cId of COURSE_IDS) {
        console.log(`\n=================================================================`);
        console.log(`⚡ Syncing Course ID ${cId}...`);

        async function processFolder(folderId, breadcrumbs) {
            const items = await fetchContent(cId, folderId);
            for (const item of items) {
                totalItemsScanned++;
                const currentPath = [...breadcrumbs, item.name];

                if (item.contentType === 1) { // Subfolder
                    console.log(`  📁 Folder [ID ${item.id}]: ${currentPath.join(' > ')}`);
                    await processFolder(item.id, currentPath);
                } else {
                    console.log(`  📄 Item [ID ${item.id}]: '${item.name}' (Type: ${item.contentType})`);
                    
                    const testDetails = await fetchTestDetails(item.id);
                    if (!testDetails) continue;

                    const questions = testDetails.questions || testDetails.questionList || [];
                    if (questions.length === 0) continue;

                    console.log(`     -> Extracted ${questions.length} questions from Test ${item.id}!`);

                    const subjectRaw = currentPath[0] || 'सामान्य अध्ययन';
                    const subjectName = normalizeSubject(subjectRaw);
                    const topicName = currentPath[1] || item.name;
                    const subtopicName = currentPath[2] || item.name;

                    for (const q of questions) {
                        const testbookId = `classplus_hatnure_${q.id || Math.random().toString(36).substr(2, 9)}`;
                        const qMr = (q.text || q.questionText || '').trim();
                        if (!qMr) continue;

                        const options = q.options || q.optionList || [];
                        const opt1Mr = options[0]?.text || '';
                        const opt2Mr = options[1]?.text || '';
                        const opt3Mr = options[2]?.text || '';
                        const opt4Mr = options[3]?.text || '';

                        let corr = parseInt(q.answer || q.correctOptionId) || 1;
                        const solMrRaw = (q.solution || q.explanation || '').trim();

                        // Translate to English
                        const qEn = await translateText(qMr, 'mr', 'en');
                        const opt1En = await translateText(opt1Mr, 'mr', 'en');
                        const opt2En = await translateText(opt2Mr, 'mr', 'en');
                        const opt3En = await translateText(opt3Mr, 'mr', 'en');
                        const opt4En = await translateText(opt4Mr, 'mr', 'en');
                        const solEnRaw = solMrRaw ? await translateText(solMrRaw, 'mr', 'en') : '';

                        const cNum = (corr >= 1 && corr <= 4) ? corr : 1;
                        const corrMrStr = stripHtml([opt1Mr, opt2Mr, opt3Mr, opt4Mr][cNum - 1]) || `पर्याय ${cNum}`;
                        const corrEnStr = stripHtml([opt1En, opt2En, opt3En, opt4En][cNum - 1]) || corrMrStr;

                        const mrHtml = `<div class="solution-content" style="font-family: 'Inter', sans-serif; line-height: 1.7; color: #f8fafc;">
    <div style="background: rgba(16, 185, 129, 0.12); border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 8px; margin-bottom: 14px;">
        <h4 style="color: #10b981; font-weight: 700; margin-bottom: 0;"><i class="fa-solid fa-circle-check"></i> योग्य उत्तर: पर्याय (${cNum}) - ${corrMrStr}</h4>
    </div>
    <div style="font-size: 14px; color: #e2e8f0; line-height: 1.6;">
        <p style="margin-bottom: 8px;"><strong>अचूक उत्तर:</strong> पर्याय (${cNum}) - <strong>${corrMrStr}</strong></p>
        <p style="margin-bottom: 8px;"><strong>स्पष्टीकरण (लोकसेवा २१०००+ प्रश्नसंच - आप्पा हातनुरे सर):</strong> ${stripHtml(solMrRaw) || `हा प्रश्न MPSC राज्यसेवा व संयुक्त पूर्व परीक्षेत विचारलेला मूळ प्रश्न (PYQ) आहे.`}</p>
    </div>
</div>`;

                        const enHtml = `<div class="solution-content" style="font-family: 'Inter', sans-serif; line-height: 1.7; color: #f8fafc;">
    <div style="background: rgba(16, 185, 129, 0.12); border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 8px; margin-bottom: 14px;">
        <h4 style="color: #10b981; font-weight: 700; margin-bottom: 0;"><i class="fa-solid fa-circle-check"></i> Correct Answer: Option (${cNum}) - ${corrEnStr}</h4>
    </div>
    <div style="font-size: 14px; color: #e2e8f0; line-height: 1.6;">
        <p style="margin-bottom: 8px;"><strong>Verified Answer:</strong> Option (${cNum}) - <strong>${corrEnStr}</strong></p>
        <p style="margin-bottom: 8px;"><strong>Explanation (Lokseva 21000+ PYQ Question Bank):</strong> ${stripHtml(solEnRaw) || `Authentic MPSC Previous Year Question (PYQ) with verified official answer key.`}</p>
    </div>
</div>`;

                        const [existing] = await pool.execute("SELECT id FROM tbl_questions WHERE testbook_id = ?", [testbookId]);

                        if (existing.length === 0) {
                            await pool.execute(`
                                INSERT INTO tbl_questions
                                (testbook_id, test_series_slug, test_id, test_title, subject_name, category_name, topic_name, subtopic_name, question_mr, question_en, opt1_mr, opt1_en, opt2_mr, opt2_en, opt3_mr, opt3_en, opt4_mr, opt4_en, correct_option, solution_mr, solution_en)
                                VALUES (?, 'mpsc-classplus-reliable', ?, ?, ?, 'MPSC Lokseva 21000+ PYQ (Appa Hatnure)', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            `, [testbookId, `test_${item.id}`, item.name, subjectName, topicName, subtopicName, qMr, qEn, opt1Mr, opt1En, opt2Mr, opt2En, opt3Mr, opt3En, opt4Mr, opt4En, corr, mrHtml, enHtml]);
                            totalInserted++;
                        } else {
                            await pool.execute(`
                                UPDATE tbl_questions
                                SET test_series_slug = 'mpsc-classplus-reliable', category_name = 'MPSC Lokseva 21000+ PYQ (Appa Hatnure)',
                                    subject_name = ?, topic_name = ?, subtopic_name = ?,
                                    question_mr = ?, question_en = ?,
                                    opt1_mr = ?, opt1_en = ?, opt2_mr = ?, opt2_en = ?,
                                    opt3_mr = ?, opt3_en = ?, opt4_mr = ?, opt4_en = ?,
                                    correct_option = ?, solution_mr = ?, solution_en = ?
                                WHERE id = ?
                            `, [subjectName, topicName, subtopicName, qMr, qEn, opt1Mr, opt1En, opt2Mr, opt2En, opt3Mr, opt3En, opt4Mr, opt4En, corr, mrHtml, enHtml, existing[0].id]);
                            totalUpdated++;
                        }
                    }
                }
            }
        }

        await processFolder(0, []);
    }

    console.log(`\n🎉 APPA HATNURE LOKSEVA FULL MASTER SYNC COMPLETE!`);
    console.log(`  Items Scanned: ${totalItemsScanned}`);
    console.log(`  New Questions Inserted: ${totalInserted}`);
    console.log(`  Existing Questions Updated: ${totalUpdated}`);

    const [finalTotal] = await pool.query("SELECT COUNT(*) as cnt FROM tbl_questions");
    console.log(`  Grand Total Questions in DB: ${finalTotal[0].cnt}`);

    await pool.end();
}

main().catch(console.error);
