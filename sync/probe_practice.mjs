const NEW_AUTH_TOKEN = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3Rlc3Rib29rLmNvbSIsInN1YiI6IjVlZTVlOTRjNDkyY2Q1MGQwZjczMjg4OSIsImF1ZCI6IlRCIiwiZXhwIjoiMjAyNi0xMC0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwiaWF0IjoiMjAyNi0wOS0xN1QxMDowMjoxMy4xNTE5NDMyNDdaIiwibmFtZSI6Im9tIHdhbWFuIiwiZW1haWwiOiJvbXdhbWFuNUBnbWFpbC5jb20iLCJvcmdJZCI6IiIsImhvbWVTdGF0ZUlkIjoiIiwiaXNQYWlkVXNlciI6ZmFsc2UsImlzTE1TVXNlciI6ZmFsc2UsInJvbGVzIjoic3R1ZGVudCJ9.JI5HbF9uBEVOnfqGkiGIy-Jdri4vk79wMPjYeOPL2yDTw2fEJDqcM0HoqIWfDXZxFxiNqtnuU6kBcE3Ejhh2oFOfJHh7MJnFVxZvzGfC4NZiW6EZ_GH80TNze6TRyeQjDyp7VqK1sxOMznS_IgL6-rgQY9y-4gaNUNXnnJ8mIIA";

const goalId = "6a3d1362f429fa9d1b0fe7a1";
const slug = "mpsc-combined-group-c-2026";
const targetId = "6045d9d0d4e6fea92394f4f3";

async function probePracticeEndpoints() {
    const candidateUrls = [
        // Goals Practice endpoints
        `https://api.testbook.com/api/v1/goals/${goalId}/practice`,
        `https://api.testbook.com/api/v2/goals/${goalId}/practice`,
        `https://api.testbook.com/api/v1/goals/${slug}/practice`,
        `https://api.testbook.com/api/v2/goals/${slug}/practice`,
        `https://api.testbook.com/api/v1/goals/${goalId}/practice-subjects`,
        `https://api.testbook.com/api/v1/goals/${goalId}/practice-entities`,
        `https://api.testbook.com/api/v2/goals/${goalId}/practice-entities`,

        // Practice API endpoints
        `https://api.testbook.com/api/v1/practice/goals/${goalId}`,
        `https://api.testbook.com/api/v2/practice/goals/${goalId}`,
        `https://api.testbook.com/api/v1/practice/targets/${targetId}`,
        `https://api.testbook.com/api/v2/practice/targets/${targetId}`,
        `https://api.testbook.com/api/v1/practice/subjects?goalId=${goalId}`,
        `https://api.testbook.com/api/v2/practice/subjects?goalId=${goalId}`,
        `https://api.testbook.com/api/v1/practice/subjects?targetId=${targetId}`,
        `https://api.testbook.com/api/v2/practice/subjects?targetId=${targetId}`,

        // Practice Entities
        `https://api.testbook.com/api/v1/practice-entities?goalId=${goalId}`,
        `https://api.testbook.com/api/v2/practice-entities?goalId=${goalId}`,
        `https://api.testbook.com/api/v1/practice-entities/goal/${goalId}`,
        `https://api.testbook.com/api/v2/practice-entities/goal/${goalId}`,
        `https://api.testbook.com/api/v1/practice-entities?targetId=${targetId}`,
        `https://api.testbook.com/api/v2/practice-entities?targetId=${targetId}`,

        // Subjects from goal
        `https://api.testbook.com/api/v1/goals/${goalId}/subjects`,
        `https://api.testbook.com/api/v2/goals/${goalId}/subjects`,
        `https://api.testbook.com/api/v1/goals/${goalId}/tabs/practice`,
        `https://api.testbook.com/api/v2/goals/${goalId}/tabs/practice`,
        `https://api.testbook.com/api/v1/coaching/${slug}/practice`,

        // Supercoaching endpoints
        `https://api.testbook.com/api/v1/supercoaching/goals/${goalId}/practice`,
        `https://api.testbook.com/api/v2/supercoaching/goals/${goalId}/practice`
    ];

    console.log(`Probing ${candidateUrls.length} candidate practice endpoints...\n`);

    for (const url of candidateUrls) {
        try {
            const res = await fetch(url, {
                headers: {
                    "source": "testbook",
                    "origin": "https://testbook.com",
                    "referer": "https://testbook.com/mpsc-combined-group-c-2026-coaching",
                    "accept": "application/json, text/plain, */*",
                    "authorization": `Bearer ${NEW_AUTH_TOKEN}`,
                    "x-tb-client": "web,1.3"
                }
            });

            if (res.status !== 404 && res.status !== 502 && res.status !== 500) {
                const text = await res.text();
                let jsonPreview = "";
                try {
                    const parsed = JSON.parse(text);
                    jsonPreview = JSON.stringify(parsed).substring(0, 150);
                } catch {
                    jsonPreview = text.substring(0, 100);
                }
                console.log(`[HTTP ${res.status}] ${url}`);
                console.log(`   Preview: ${jsonPreview}\n`);
            }
        } catch (e) {
            // ignore network error
        }
    }
}

probePracticeEndpoints().catch(console.error);
