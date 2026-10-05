
import { createReadStream, createWriteStream } from "fs";
import { createInterface } from "readline";

async function run() {
    const fs = require("fs");
    fs.writeFileSync("test2.sql", "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r-- Dump completed on 2026-10-05 17:05:53\r\n");
    const rl = createInterface({ input: createReadStream("test2.sql", { encoding: "utf-8" }), crlfDelay: Infinity });
    const writeStream = createWriteStream("test2.sql.tmp", { encoding: "utf-8" });
    for await (const line of rl) { writeStream.write(line + "\n"); }
    await new Promise((resolve) => writeStream.end(resolve));
    console.log("OUT:", JSON.stringify(fs.readFileSync("test2.sql.tmp", "utf-8")));
}
run();

