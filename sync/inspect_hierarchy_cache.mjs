import fs from 'fs';

try {
    const raw = fs.readFileSync("hierarchy_cache.json", "utf-8");
    const json = JSON.parse(raw);
    console.log("Keys in hierarchy_cache.json:", Object.keys(json));
    console.log("Sample entry:");
    const firstKey = Object.keys(json)[0];
    console.log(firstKey, ":", json[firstKey]);
} catch (e) {
    console.error(e.message);
}
