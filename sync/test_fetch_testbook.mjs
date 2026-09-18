const AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlMzNlZTg2NGI2NTJjMGQxMDkzMjYyZCIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0wOC0yOVQxNjozNDoyOC4zNzEzMjcyNloiLCJpYXQiOiIyMDI2LTA3LTMwVDE2OjM0OjI4LjM3MTMyNzI2WiIsIm5hbWUiOiJPbSB3YW1hbiIsImVtYWlsIjoib213YW1hbjFAZ21haWwuY29tIiwib3JnSWQiOiIiLCJob21lU3RhdGVJZCI6IjVmOTE2M2E0MmVjODI3YjIxOGRhY2QyOSIsImlzUGFpZFVzZXIiOnRydWUsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.AvVvHEfriEjrjDeAM23igAFg5LqZBTbSObJb3iQpt9ZXrgmCGigAG-0Bj24q2mtvm3ooqRigSxsCOPWW-CaFgOn9w1euX03xvBCkwhluFXOSl9DlV1gsw8aG4gbFm7cnm89h-3FLtiMUr0rlnCDDlyG-Sgqz9flxwYyKP5C-nH4";

async function testFetchTest() {
    const testId = "6a3a4e0f08f442ee40b1d4c4"; // Missing English test
    console.log(`Testing test fetch for ${testId}...`);

    const qRes = await fetch(`https://api-new.testbook.com/api/v2/tests/${testId}?auth_code=${encodeURIComponent(AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&client=web&testLang=en&beforeServe=false`, {
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
        console.log(`Fetched ${totalQ} questions successfully!`);
        if (totalQ > 0) {
            console.log("Sample question title:", qJson.data.sections[0].questions[0].title?.slice(0, 100));
        }
    } else {
        console.log("Error text:", await qRes.text());
    }

    const aRes = await fetch(`https://api-new.testbook.com/api/v2/tests/${testId}/answers?auth_code=${encodeURIComponent(AUTH_TOKEN)}&X-Tb-Client=web,1.3&language=English&attemptNo=1`, {
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

testFetchTest();
