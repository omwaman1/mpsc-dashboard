import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
};

async function scanAllTests() {
    console.log("Fetching 292 tests from Testbook...");

    const proj = JSON.stringify({
        details: {
            id: 1, name: 1,
            sections: {
                id: 1, name: 1,
                subsections: { id: 1, name: 1, paidTestCount: 1, freeTestCount: 1 }
            }
        }
    });

    const res = await fetch(`https://api.testbook.com/api/v1/test-series/slug?__projection=${encodeURIComponent(proj)}&url=mpsc-group-b&branchId=&language=English`, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json",
            "authorization": `Bearer ${AUTH_TOKEN}`,
            "x-tb-client": "web,1.3"
        }
    });

    const json = await res.json();
    const details = json.data?.details;
    const seriesId = details?._id || details?.id;

    const sections = details?.sections || [];
    const allTests = [];

    for (const sec of sections) {
        const secId = sec._id || sec.id;
        for (const sub of sec.subsections || []) {
            const subId = sub._id || sub.id;
            const testProj = JSON.stringify({ tests: { id: 1, title: 1, questionCount: 1, hasAccess: 1, isFree: 1, isAttempted: 1, attempts: 1, progress: 1 } });
            const tRes = await fetch(`https://api.testbook.com/api/v2/test-series/${seriesId}/tests/details?__projection=${encodeURIComponent(testProj)}&testType=all&sectionId=${secId}&subSectionId=${subId}&skip=0&limit=150&branchId=&language=English`, {
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
                allTests.push({
                    id: t._id || t.id,
                    title: t.title,
                    qCount: t.questionCount || 10,
                    section: sec.name,
                    subSection: sub.name,
                    progress: t.progress,
                    raw: t
                });
            }
        }
    }

    console.log(`Total tests fetched: ${allTests.length}`);

    // Print sample of test metadata to see progress and attempt fields
    console.log("\nSample test metadata 0:", JSON.stringify(allTests[0].raw, null, 2));
    console.log("\nSample test metadata 10 (CT 11 English):", JSON.stringify(allTests[10]?.raw, null, 2));

    // Count how many tests have progress or attempts
    const attempted = allTests.filter(t => t.raw.progress || t.raw.isAttempted || t.raw.hasAttempted);
    console.log(`\nTests with progress/attempted flag: ${attempted.length} / ${allTests.length}`);
}

scanAllTests();
