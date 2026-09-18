import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";
const SLUG = "mpsc-group-b";

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
};

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function categorizeTests() {
    console.log("=================================================================");
    console.log("🔍 AUDITING ALL 292 TESTS: RELEASED VS UPCOMING ON TESTBOOK");
    console.log("=================================================================\n");

    const conn = await mysql.createConnection(dbConfig);

    // Fetch tests list
    const proj = JSON.stringify({
        details: {
            id: 1, name: 1,
            sections: {
                id: 1, name: 1,
                subsections: { id: 1, name: 1, paidTestCount: 1, freeTestCount: 1 }
            }
        }
    });

    const seriesRes = await fetch(`https://api.testbook.com/api/v1/test-series/slug?__projection=${encodeURIComponent(proj)}&url=${SLUG}&branchId=&language=English`, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json",
            "authorization": `Bearer ${AUTH_TOKEN}`,
            "x-tb-client": "web,1.3"
        }
    });

    const json = await seriesRes.json();
    const details = json.data?.details;
    const seriesId = details?._id || details?.id;

    const sections = details?.sections || [];
    const allTests = [];

    for (const sec of sections) {
        const secId = sec._id || sec.id;
        for (const sub of sec.subsections || []) {
            const subId = sub._id || sub.id;
            const testProj = JSON.stringify({ tests: { id: 1, title: 1, questionCount: 1, hasAccess: 1, isFree: 1 } });
            const tRes = await fetch(`https://api.testbook.com/api/v2/test-series/${seriesId}/tests/details?__projection=${encodeURIComponent(testProj)}&testType=all&sectionId=${secId}&subSectionId=${subId}&skip=0&limit=200&branchId=&language=English`, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json",
                    "authorization": `Bearer ${AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3"
                }
            });
            const tJson = await tRes.json();
            for (const t of tJson.data?.tests || []) {
                allTests.push({ id: t._id || t.id, title: t.title, qCount: t.questionCount || 10, section: sec.name, sub: sub.name });
            }
        }
    }

    console.log(`Auditing ${allTests.length} tests against DB and Testbook Release Status...\n`);

    // Fetch existing DB test counts
    const [dbRows] = await conn.query("SELECT test_id, COUNT(*) as cnt, SUM(CASE WHEN correct_option > 0 THEN 1 ELSE 0 END) as valid_ans FROM tbl_questions WHERE test_series_slug = ? GROUP BY test_id", [SLUG]);
    const dbMap = new Map();
    for (const r of dbRows) {
        dbMap.set(r.test_id, { cnt: r.cnt, valid_ans: r.valid_ans });
    }

    let fullySynced = 0;
    let releasedNeedSync = 0;
    let upcomingOnTestbook = 0;

    const needSyncList = [];
    const upcomingList = [];

    const encAuth = encodeURIComponent(AUTH_TOKEN);

    for (let i = 0; i < allTests.length; i++) {
        const t = allTests[i];
        const db = dbMap.get(t.id);

        if (db && db.cnt >= t.qCount && db.valid_ans >= t.qCount) {
            fullySynced++;
            continue;
        }

        // Check if released on Testbook
        const qUrl = `https://api-new.testbook.com/api/v2/tests/${t.id}?auth_code=${encAuth}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`;
        try {
            const res = await fetch(qUrl, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json"
                }
            });

            if (res.ok) {
                const qJson = await res.json();
                const totalQ = (qJson.data?.sections || []).reduce((acc, s) => acc + (s.questions || []).length, 0);
                if (totalQ > 0) {
                    releasedNeedSync++;
                    needSyncList.push(t);
                    console.log(`[${i+1}/${allTests.length}] 🟢 RELEASED & READY TO SYNC: "${t.title}" (${totalQ} Qs)`);
                } else {
                    upcomingOnTestbook++;
                    upcomingList.push(t);
                }
            } else {
                upcomingOnTestbook++;
                upcomingList.push(t);
                console.log(`[${i+1}/${allTests.length}] ⏳ UPCOMING ON TESTBOOK: "${t.title}"`);
            }
        } catch(e) {
            upcomingOnTestbook++;
        }

        await sleep(350);
    }

    console.log("\n=================================================================");
    console.log("📊 COMPLETE BREAKDOWN OF MPSC GROUP-B 2026 (292 TESTS)");
    console.log("=================================================================");
    console.log(` 1. Fully Synced in Database:           ${fullySynced} tests`);
    console.log(` 2. Released on Testbook (Ready to Sync): ${releasedNeedSync} tests`);
    console.log(` 3. Upcoming (Not Yet Released by TB):   ${upcomingOnTestbook} tests`);
    console.log(` TOTAL IN TESTBOOK CALENDAR:             ${allTests.length} tests`);
    console.log("=================================================================\n");

    await conn.end();
}

categorizeTests();
