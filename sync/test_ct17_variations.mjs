const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function testCt17Variations() {
    const testId = "6a3a4e1b6c6d2a5d94575354";
    const encAuth = encodeURIComponent(AUTH_TOKEN);

    const urls = [
        `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encAuth}&X-Tb-Client=web,1.3&language=English`,
        `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encAuth}&X-Tb-Client=web,1.3&beforeServe=true`,
        `https://api.testbook.com/api/v2/tests/${testId}?auth_code=${encAuth}&X-Tb-Client=web,1.3`,
        `https://api-new.testbook.com/api/v2/tests/${testId}/state?auth_code=${encAuth}&X-Tb-Client=web,1.3`
    ];

    for (const u of urls) {
        try {
            const res = await fetch(u, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json"
                }
            });
            console.log(`URL: ${u.split('?')[0].split('.com/')[1]} -> Status: ${res.status}`);
            const text = await res.text();
            console.log(`  Response:`, text.slice(0, 150));
        } catch(e) {}
    }
}

testCt17Variations();
