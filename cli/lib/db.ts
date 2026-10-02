import { spawn } from "bun";
import { readFileSync, writeFileSync } from "fs";
import { EnvConfig } from "./env";
import { log } from "./logger";

export async function exportDb(config: EnvConfig, outputPath: string): Promise<string> {
    const args = [
        config.MYSQLDUMP_PATH,
        "-u", config.LOCAL_DB_USER,
        ...(config.LOCAL_DB_PASS ? [`-p${config.LOCAL_DB_PASS}`] : []),
        "-h", config.LOCAL_DB_HOST,
        config.LOCAL_DB_NAME
    ];

    log.info(`Exporting local database to ${outputPath}...`);
    const proc = spawn(args, { stdout: Bun.file(outputPath), stderr: "pipe" });
    const exitCode = await proc.exited;
    
    if (exitCode !== 0) {
        const stderr = await new Response(proc.stderr).text();
        throw new Error(`mysqldump failed: ${stderr}`);
    }
    
    return outputPath;
}

export async function importDb(config: EnvConfig, sqlPath: string): Promise<void> {
    const args = [
        config.MYSQL_PATH,
        "-u", config.LOCAL_DB_USER,
        ...(config.LOCAL_DB_PASS ? [`-p${config.LOCAL_DB_PASS}`] : []),
        "-h", config.LOCAL_DB_HOST,
        config.LOCAL_DB_NAME
    ];

    log.info(`Importing ${sqlPath} into local database...`);
    const proc = spawn(args, { stdin: Bun.file(sqlPath), stdout: "pipe", stderr: "pipe" });
    const exitCode = await proc.exited;
    
    if (exitCode !== 0) {
        const stderr = await new Response(proc.stderr).text();
        throw new Error(`mysql import failed: ${stderr}`);
    }
}

export function searchReplaceSQL(filePath: string, from: string, to: string) {
    let sql = readFileSync(filePath, "utf-8");
    
    // Pass 1: Replace serialized strings
    const regex = new RegExp(`s:\\d+:"${from.replace(/[.*+?^$\\{}()|[\\]\\\\]/g, '\\$&')}"`, "g");
    const toLength = Buffer.byteLength(to, 'utf8');
    const toStr = `s:${toLength}:"${to}"`;
    sql = sql.replace(regex, toStr);

    // Pass 2: Replace all other plain occurrences
    sql = sql.split(from).join(to);
    
    writeFileSync(filePath, sql, "utf-8");
}
