import mysql from 'mysql2/promise';

const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";

const dbConfig = {
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    database: 'BANK'
};

async function verifyGroupBSync() {
    console.log("=================================================================");
    console.log("🔍 COMPARING TESTBOOK API (292 TESTS) VS DATABASE (tbl_questions)");
    console.log("=================================================================\n");

    const conn = await mysql.createConnection(dbConfig);

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
    console.log(`Testbook Series: ${details?.name} (ID: ${seriesId})\n`);

    const sections = details?.sections || [];
    let totalApiTests = 0;
    let totalSyncedTests = 0;
    let totalMissingTests = 0;
    let totalPartialTests = 0;
    let totalQuestionsInDb = 0;
    let totalQuestionsExpected = 0;

    const missingTestList = [];
    const partialTestList = [];

    for (const sec of sections) {
        console.log(`\n📂 [SECTION] ${sec.name}`);
        const secId = sec._id || sec.id;

        for (const sub of sec.subsections || []) {
            const subId = sub._id || sub.id;
            const testProj = JSON.stringify({ tests: { id: 1, title: 1, questionCount: 1, isFree: 1 } });
            
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
            const tests = tJson.data?.tests || [];
            totalApiTests += tests.length;

            let subMissing = 0;
            let subSynced = 0;

            for (const t of tests) {
                const testId = t._id || t.id;
                const expectedQ = t.questionCount || 0;
                totalQuestionsExpected += expectedQ;

                const [dbRows] = await conn.execute(
                    "SELECT COUNT(*) as cnt, SUM(CASE WHEN correct_option IS NOT NULL AND correct_option > 0 THEN 1 ELSE 0 END) as valid_ans, SUM(CASE WHEN solution_mr IS NOT NULL OR solution_en IS NOT NULL THEN 1 ELSE 0 END) as with_sol FROM tbl_questions WHERE test_id = ?",
                    [testId]
                );

                const dbCnt = dbRows[0]?.cnt || 0;
                totalQuestionsInDb += dbCnt;

                if (dbCnt === 0) {
                    totalMissingTests++;
                    subMissing++;
                    missingTestList.push({ id: testId, title: t.title, section: sec.name, sub: sub.name, expected: expectedQ });
                } else if (dbCnt < expectedQ) {
                    totalPartialTests++;
                    partialTestList.push({ id: testId, title: t.title, inDb: dbCnt, expected: expectedQ });
                } else {
                    totalSyncedTests++;
                    subSynced++;
                }
            }
            console.log(`   • ${sub.name}: ${tests.length} tests (Synced: ${subSynced}, Missing: ${subMissing})`);
        }
    }

    console.log("\n=================================================================");
    console.log("📊 SUMMARY FOR MPSC GROUP-B SERVICES TEST SERIES 2026 (292 TESTS)");
    console.log("=================================================================");
    console.log(` Total Tests on Testbook:         ${totalApiTests}`);
    console.log(` Fully Synced Tests in DB:        ${totalSyncedTests}`);
    console.log(` Partially Synced Tests:          ${totalPartialTests}`);
    console.log(` Completely Missing Tests:        ${totalMissingTests}`);
    console.log(` Total Questions Expected:        ${totalQuestionsExpected}`);
    console.log(` Total Questions in Database:     ${totalQuestionsInDb}`);

    if (missingTestList.length > 0) {
        console.log(`\nSample of Missing Tests (First 10 of ${missingTestList.length}):`);
        for (const m of missingTestList.slice(0, 10)) {
            console.log(` - [${m.id}] ${m.title} (${m.section} -> ${m.sub}, Qs: ${m.expected})`);
        }
    }

    if (partialTestList.length > 0) {
        console.log(`\nSample of Partial Tests (First 10 of ${partialTestList.length}):`);
        for (const p of partialTestList.slice(0, 10)) {
            console.log(` - [${p.id}] ${p.title} (${p.inDb}/${p.expected} Qs)`);
        }
    }

    await conn.end();
}

verifyGroupBSync();
