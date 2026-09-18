const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function checkAllAnswers() {
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
            const testProj = JSON.stringify({ tests: { id: 1, title: 1, questionCount: 1 } });
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
                allTests.push({ id: t._id || t.id, title: t.title, sub: sub.name });
            }
        }
    }

    console.log(`Checking answers endpoint for ${allTests.length} tests...`);

    let withAnswers = 0;
    let withoutAnswers = 0;

    // test a batch of 30 tests
    for (let i = 0; i < Math.min(30, allTests.length); i++) {
        const t = allTests[i];
        const u = `https://api-new.testbook.com/api/v2/tests/${t.id}/answers?auth_code=${encodeURIComponent(AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;
        try {
            const r = await fetch(u, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json"
                }
            });
            if (r.ok) {
                withAnswers++;
                console.log(` [${i+1}] ✓ [HAS ANSWERS] ${t.title}`);
            } else {
                withoutAnswers++;
                console.log(` [${i+1}] ✗ [NO ATTEMPT]  ${t.title}`);
            }
        } catch(e) {}
    }

    console.log(`\nSample of 30 tests: ${withAnswers} with answers, ${withoutAnswers} without answers.`);
}

checkAllAnswers();
