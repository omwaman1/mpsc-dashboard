const NEW_AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function testSubmitCandidates() {
    const testId = "6a3a4e0f08f442ee40b1d4c4";

    const endpoints = [
        `https://api-new.testbook.com/api/v2/tests/${testId}/submit`,
        `https://api.testbook.com/api/v2/tests/${testId}/submit`,
        `https://api-new.testbook.com/api/v2/tests/${testId}/user-attempts`,
        `https://api-new.testbook.com/api/v2/tests/${testId}/attempt`,
        `https://api-new.testbook.com/api/v1/tests/${testId}/attempt`,
        `https://api-new.testbook.com/api/v2/test-attempts`,
        `https://api.testbook.com/api/v1/test-attempts`,
        `https://api-new.testbook.com/api/v2/user-tests/${testId}`,
        `https://api.testbook.com/api/v2/user-tests/${testId}`
    ];

    for (const ep of endpoints) {
        try {
            const res = await fetch(ep, {
                method: 'POST',
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "content-type": "application/json",
                    "accept": "application/json",
                    "authorization": `Bearer ${NEW_AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3"
                },
                body: JSON.stringify({ testId, language: 'en' })
            });
            console.log(`Endpoint [${ep}] -> Status: ${res.status}`);
            if (res.status !== 404) {
                console.log(`  Output:`, (await res.text()).slice(0, 300));
            }
        } catch(e) {}
    }
}

testSubmitCandidates();
