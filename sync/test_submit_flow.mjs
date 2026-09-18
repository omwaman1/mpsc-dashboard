const NEW_AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function testSubmitFlow() {
    const testId = "6a3a4e0f08f442ee40b1d4c4"; // CT 11: English (Clauses)

    // 1. Check start test endpoint
    const startUrls = [
        { url: `https://api-new.testbook.com/api/v2/tests/${testId}/start`, method: 'POST', body: { language: 'en' } },
        { url: `https://api-new.testbook.com/api/v2/tests/${testId}/attempt/start`, method: 'POST', body: { language: 'en' } },
        { url: `https://api-new.testbook.com/api/v2/tests/${testId}/attempts`, method: 'POST', body: { language: 'en' } },
        { url: `https://api.testbook.com/api/v2/tests/${testId}/attempts`, method: 'POST', body: { language: 'en' } }
    ];

    for (const s of startUrls) {
        try {
            const res = await fetch(s.url, {
                method: s.method,
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "content-type": "application/json",
                    "accept": "application/json",
                    "authorization": `Bearer ${NEW_AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3"
                },
                body: JSON.stringify(s.body)
            });
            console.log(`Start Endpoint [${s.url}] -> Status: ${res.status}`);
            const text = await res.text();
            console.log(`  Response:`, text.slice(0, 300));
        } catch(e) {}
    }
}

testSubmitFlow();
