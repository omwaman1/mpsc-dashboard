const NEW_AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function testNewAuth() {
    const testId = "6a3a4e0f08f442ee40b1d4c4"; // Missing English test
    console.log(`Testing test fetch with NEW token for ${testId}...`);

    const qRes = await fetch(`https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json"
        }
    });
    console.log("Questions API status:", qRes.status);
    if (qRes.ok) {
        const qJson = await qRes.json();
        const totalQ = (qJson.data?.sections || []).reduce((acc, s) => acc + (s.questions || []).length, 0);
        console.log(`🎉 SUCCESS! Fetched ${totalQ} questions!`);
        if (totalQ > 0) {
            console.log("Sample question title:", qJson.data.sections[0].questions[0].title?.slice(0, 100));
        }
    } else {
        console.log("Error text:", await qRes.text());
    }

    const aRes = await fetch(`https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodeURIComponent(NEW_AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&attemptNo=1`, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json"
        }
    });
    console.log("Answers API status:", aRes.status);
    if (aRes.ok) {
        const aJson = await aRes.json();
        console.log("Answer keys count:", Object.keys(aJson.data || {}).length);
    }
}

testNewAuth();
