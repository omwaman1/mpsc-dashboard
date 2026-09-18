const NEW_AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function testAnswersApi() {
    const testId = "6a3a4e2b364391d6c1248c2b"; // CT 4: मराठी व्याकरण (वाक्यरचना)

    const u = `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;
    const res = await fetch(u, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json"
        }
    });

    console.log("Status:", res.status);
    if (res.ok) {
        const json = await res.json();
        console.log("Answers count:", Object.keys(json.data || {}).length);
        const firstKey = Object.keys(json.data || {})[0];
        console.log("Sample answer item:", JSON.stringify(json.data[firstKey], null, 2).slice(0, 500));
    } else {
        console.log("Error:", await res.text());
    }
}

testAnswersApi();
