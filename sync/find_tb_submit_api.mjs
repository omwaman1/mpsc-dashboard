const headers = {
    "source": "testbook",
    "origin": "https://testbook.com",
    "referer": "https://testbook.com/",
    "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
};

async function findSubmitApi() {
    console.log("Fetching testbook test player page...");
    const res = await fetch("https://testbook.com/mpsc-group-b-services-mock-test/test-series", { headers });
    const html = await res.text();
    const scripts = Array.from(html.matchAll(/src=["']([^"']+\.js)["']/g)).map(m => m[1]);
    console.log(`Found ${scripts.length} scripts.`);

    for (const s of scripts) {
        if (!s.includes('chunk') && !s.includes('test') && !s.includes('main') && !s.includes('app')) continue;
        const sUrl = s.startsWith('http') ? s : `https://testbook.com${s}`;
        try {
            const js = await (await fetch(sUrl, { headers })).text();
            if (js.includes('/answers') || js.includes('attemptNo') || js.includes('can not serve solutions')) {
                console.log(`\nMatch in ${sUrl}:`);
                const idx = js.indexOf('attemptNo');
                console.log(js.slice(Math.max(0, idx - 150), idx + 250));
            }
        } catch(e) {}
    }
}

findSubmitApi();
