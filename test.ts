
import { createReadStream, createWriteStream } from "fs";
import { createInterface } from "readline";

async function run() {
    const filePath = "test.sql";
    const tempPath = "test.sql.tmp";
    
    // Create dummy sql file
    const fs = require("fs");
    fs.writeFileSync(filePath, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n-- Dump completed on 2026-10-05 17:05:53\r\n");

    const readStream = createReadStream(filePath, { encoding: "utf-8" });
    const writeStream = createWriteStream(tempPath, { encoding: "utf-8" });

    const rl = createInterface({
        input: readStream,
        crlfDelay: Infinity
    });

    for await (const line of rl) {
        writeStream.write(line + "\n");
    }
    
    await new Promise((resolve) => writeStream.end(resolve));
    
    const out = fs.readFileSync(tempPath, "utf-8");
    console.log("OUT:", JSON.stringify(out));
}
run();

