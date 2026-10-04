import { spawn } from "bun";
import { createReadStream, createWriteStream } from "fs";
import { rename, appendFile } from "fs/promises";
import { createInterface } from "readline";
import { LocalEnvConfig, EnvConfig } from "./env";
import { log } from "./logger";

export async function exportDb(config: LocalEnvConfig | EnvConfig, outputPath: string): Promise<string> {
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

export async function importDb(config: LocalEnvConfig | EnvConfig, sqlPath: string): Promise<void> {
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

export async function searchReplaceSQL(filePath: string, from: string, to: string): Promise<void> {
    const tempPath = `${filePath}.tmp-${Date.now()}`;
    const readStream = createReadStream(filePath, { encoding: "utf-8" });
    const writeStream = createWriteStream(tempPath, { encoding: "utf-8" });

    const rl = createInterface({
        input: readStream,
        crlfDelay: Infinity
    });

    const regex = new RegExp(`s:\\d+:"${from.replace(/[.*+?^$\\{}()|[\\]\\\\]/g, '\\$&')}"`, "g");
    const toLength = Buffer.byteLength(to, 'utf8');
    const toStr = `s:${toLength}:"${to}"`;

    for await (const line of rl) {
        // Detect CREATE TABLE and inject DROP TABLE IF EXISTS to prevent import conflicts
        const createTableMatch = line.match(/^CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)`/i);
        if (createTableMatch) {
            writeStream.write(`DROP TABLE IF EXISTS \`${createTableMatch[1]}\`;\n`);
        }

        // Pass 1: Replace serialized strings
        let processed = line.replace(regex, toStr);
        // Pass 2: Replace all other plain occurrences
        processed = processed.split(from).join(to);

        writeStream.write(processed + "\n");
    }

    await new Promise<void>((resolve, reject) => {
        writeStream.end(() => resolve());
        writeStream.on("error", reject);
    });

    await rename(tempPath, filePath);
}

export async function hardenAdminCredentialsSQL(filePath: string, config: Partial<EnvConfig>): Promise<void> {
    const { PROD_WP_ADMIN_USER, PROD_WP_ADMIN_PASS, PROD_WP_ADMIN_EMAIL, WP_TABLE_PREFIX } = config;
    
    if (!PROD_WP_ADMIN_USER || !PROD_WP_ADMIN_PASS) {
        log.warn("Missing production admin credentials. Skipping credential hardening.");
        return;
    }

    const emailUpdate = PROD_WP_ADMIN_EMAIL ? `, \`user_email\` = '${PROD_WP_ADMIN_EMAIL}'` : "";

    const sqlAppend = `\n
-- Secure Admin Credentials Injection
UPDATE \`${WP_TABLE_PREFIX || 'wp_'}users\` 
SET \`user_login\` = '${PROD_WP_ADMIN_USER}', 
    \`user_pass\` = MD5('${PROD_WP_ADMIN_PASS}')${emailUpdate}
WHERE \`user_login\` = 'admin' OR \`ID\` = 1;
`;

    log.info(`Hardening admin credentials in ${filePath}...`);
    await appendFile(filePath, sqlAppend, "utf-8");
}
