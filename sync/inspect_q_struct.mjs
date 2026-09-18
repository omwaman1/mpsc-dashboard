const NEW_AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function inspectQuestionStructure() {
    const testId = "6a3a4e0f08f442ee40b1d4c4";

    const qRes = await fetch(`https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json"
        }
    });

    const json = await qRes.json();
    const q = json.data.sections[0].questions[0];
    console.log("Question keys:", Object.keys(q));
    console.log("English value:", q.en?.value?.slice(0, 200));
    console.log("Marathi value:", q.mr?.value?.slice(0, 200));
    console.log("Options count:", (q.en?.options || []).length);
    console.log("Option 1:", q.en?.options?.[0]);
    console.log("Correct option on q:", q.correctOption, q.answer, q.key);

    // Let's also check how answers/solutions are fetched for unattempted tests in Testbook:
    // In Testbook, does /answers or /solutions work with attemptNo or without attemptNo?
    const urls = [
        `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3`,
        `https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3&attemptNo=0`,
        `https://api-new.testbook.com/api/v2/tests/${testId}/solutions?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English`,
        `https://api.testbook.com/api/v2/tests/${testId}/summary?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3`
    ];

    for (const u of urls) {
        try {
            const res = await fetch(u, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/",
                    "accept": "application/json",
                    "authorization": `Bearer ${NEW_AUTH_TOKEN}`
                }
            });
            console.log(`URL: ${u.split('/v2/tests/')[1]} -> Status: ${res.status}`);
            if (res.ok) {
                const aJson = await res.json();
                console.log("  Keys:", Object.keys(aJson.data || aJson));
            }
        } catch(e) {}
    }
}

inspectQuestionStructure();
