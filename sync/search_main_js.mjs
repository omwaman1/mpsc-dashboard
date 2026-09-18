async function searchMainJs() {
    const sUrl = "https://testbook.com/angular-ssr/main.9089d700a9509869.js";
    const js = await (await fetch(sUrl)).text();

    const matches = ['/answers?', 'api/v2/tests', 'submitTest', 'submitAttempt', 'saveAttempt', 'createAttempt', 'postAttempt'];
    for (const m of matches) {
        let pos = 0;
        while ((pos = js.indexOf(m, pos)) !== -1) {
            console.log(`\n=== MATCH FOR [${m}] at ${pos} ===`);
            console.log(js.slice(Math.max(0, pos - 150), pos + 300));
            pos += m.length + 100;
            if (pos > 500000) break;
        }
    }
}

searchMainJs();
