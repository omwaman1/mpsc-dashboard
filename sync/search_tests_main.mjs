async function searchTestsInMain() {
    const sUrl = "https://testbook.com/angular-ssr/main.9089d700a9509869.js";
    const js = await (await fetch(sUrl)).text();

    const matches = ['/tests/', 'test-series/'];
    for (const m of matches) {
        let pos = 0;
        let count = 0;
        while ((pos = js.indexOf(m, pos)) !== -1 && count < 10) {
            console.log(`\n=== MATCH FOR [${m}] at ${pos} ===`);
            console.log(js.slice(Math.max(0, pos - 100), pos + 200));
            pos += m.length + 50;
            count++;
        }
    }
}

searchTestsInMain();
