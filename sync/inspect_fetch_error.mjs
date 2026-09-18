const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwiaWF0IjoiMjAyNi0wOS0wMlQxNTowMTo1MS4xNjkzMDM5MTZaIiwibmFtZSI6Ik9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuMUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiNWY5MTYzYTQyZWM4MjdiMjE4ZGFjZDI5IiwiaXNQYWlkVXNlciI6dHJ1ZSwiaXNMTVNVc2VyIjpmYWxzZSwicm9sZXMiOiJzdHVkZW50In0.Ye4RF5MSMfOG5Hoxg_vjVGYy4FDDeTfv4C0CLtrBo9q0ViPGq1yqBCNbFWzFk_RibyU7il4uE_e3ImbOb-SoEaYTjpkkh4s1fn2jd_6fIBqCL7RndyxL3rreg5d32bqUSGQuGpUE7xA757fJu-M_TDyyUkJerxRoz4sfrXX2UCs";

async function inspectFetchError() {
    // Let's test "CT 8: Logical Reasoning (Ranking and Order)"
    // Or let's test any test that failed
    const proj = JSON.stringify({ tests: { id: 1, title: 1 } });
    const testsRes = await fetch("https://api.testbook.com/api/v2/test-series/69ddf6a8892e61f66da2ca64/tests/details?__projection=" + encodeURIComponent(proj) + "&testType=all&skip=0&limit=100&branchId=&language=English", {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json",
            "authorization": `Bearer ${AUTH_TOKEN}`
        }
    });

    const tests = (await testsRes.json()).data?.tests || [];
    const t8 = tests.find(t => t.title.includes("CT 8: Logical Reasoning")) || tests[50];
    console.log("Testing test:", t8.title, "ID:", t8._id);

    const qUrl = `https://api-new.testbook.com/api/v2/tests/${t8._id}?auth_code=${encodeURIComponent(AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`;
    const res = await fetch(qUrl, {
        headers: {
            "source": "testbook",
            "origin": "https://testbook.com",
            "referer": "https://testbook.com/",
            "accept": "application/json"
        }
    });

    console.log("Status:", res.status);
    console.log("Body:", await res.text());
}

inspectFetchError();
