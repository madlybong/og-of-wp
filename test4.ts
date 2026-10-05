
import { spawn } from "bun";
import { openSync, closeSync } from "fs";

async function run() {
    const fd = openSync("test4.txt", "w");
    const proc = spawn(["cmd.exe", "/c", "echo Hello World"], { stdout: fd });
    await proc.exited;
    closeSync(fd);
    
    const fs = require("fs");
    console.log(fs.readFileSync("test4.txt", "utf-8"));
}
run();

