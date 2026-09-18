const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function testSubmitTask() {
    const testId = "6a3a4e0f08f442ee40b1d4c4"; // CT 11: English (Clauses)

    console.log(`Submitting test ${testId} with task: "submit"...`);
    const u = `https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodeURIComponent(AUTH_TOKEN)}&X-Tb-Client=web,1.3`;

    const res = await fetch(u, {
        method: 'POST',
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "content-type": "application/json",
            "accept": "application/json",
            "authorization": `Bearer ${AUTH_TOKEN}`,
            "x-tb-client": "web,1.3"
        },
        body: JSON.stringify({
            task: "submit",
            responses: {}
        })
    });

    console.log("POST Status:", res.status);
    console.log("POST Body:", await res.text());

    // Now test if answers are available!
    const ansUrl = `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodeURIComponent(AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&attemptNo=1`;
    const ansRes = await fetch(ansUrl, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json"
        }
    });
    console.log("\nAnswers API Status after submit:", ansRes.status);
    if (ansRes.ok) {
        const json = await ansRes.json();
        console.log("🎉 SUCCESS! Answers unlocked! Count:", Object.keys(json.data || {}).length);
        const k = Object.keys(json.data || {})[0];
        console.log("Sample answer:", json.data[k]);
    } else {
        console.log("Answers error:", await ansRes.text());
    }
}

testSubmitTask();
