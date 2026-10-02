import { spawn } from "bun";
import { log } from "./logger";
import { resolve } from "path";

export async function createZip(sevenZipPath: string, sourceDir: string, outputZip: string): Promise<void> {
    log.info(`Creating ZIP archive: ${outputZip}...`);
    
    const absOutput = resolve(outputZip);
    const absSource = resolve(sourceDir, "*");

    const args = [sevenZipPath, "a", "-tzip", "-mx=6", absOutput, absSource];
    const proc = spawn(args, { stdout: "pipe", stderr: "pipe" });
    const exitCode = await proc.exited;
    
    if (exitCode !== 0) {
        const stderr = await new Response(proc.stderr).text();
        throw new Error(`7-Zip failed: ${stderr}`);
    }
}
