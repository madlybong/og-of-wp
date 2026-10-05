import { spawn } from "bun";
import { openSync, closeSync } from "fs";
import { appendFile, readFile, writeFile } from "fs/promises";
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
    // Use native OS file descriptor to bypass Bun stream chunking bugs
    const fd = openSync(outputPath, "w");
    const proc = spawn(args, { stdout: fd, stderr: "pipe" });
    const exitCode = await proc.exited;
    closeSync(fd);
    
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
    const fd = openSync(sqlPath, "r");
    const proc = spawn(args, { stdin: fd, stdout: "pipe", stderr: "pipe" });
    const exitCode = await proc.exited;
    closeSync(fd);
    
    if (exitCode !== 0) {
        const stderr = await new Response(proc.stderr).text();
        throw new Error(`mysql import failed: ${stderr}`);
    }
}

export async function searchReplaceSQL(filePath: string, from: string, to: string): Promise<void> {
    // Read entirely into memory to avoid stream chunking boundary bugs
    let content = await readFile(filePath, "utf-8");
    
    // Inject DROP TABLE IF EXISTS for all CREATE TABLE statements to prevent import conflicts
    content = content.replace(/^CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)`/gim, "DROP TABLE IF EXISTS `$1`;\nCREATE TABLE `$1`");

    const regex = new RegExp(`s:\\d+:"${from.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}"`, "g");
    const toLength = Buffer.byteLength(to, "utf8");
    const toStr = `s:${toLength}:"${to}"`;

    // Pass 1: Replace serialized strings
    content = content.replace(regex, toStr);
    
    // Pass 2: Replace all other plain occurrences
    content = content.split(from).join(to);

    await writeFile(filePath, content, "utf-8");
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

export async function createDatabase(config: LocalEnvConfig | EnvConfig): Promise<void> {
	const args = [
		config.MYSQL_PATH,
		"-u", config.LOCAL_DB_USER,
		...(config.LOCAL_DB_PASS ? [`-p${config.LOCAL_DB_PASS}`] : []),
		"-h", config.LOCAL_DB_HOST,
		"-e", `CREATE DATABASE IF NOT EXISTS \`${config.LOCAL_DB_NAME}\`;`
	];

	log.info(`Ensuring database '${config.LOCAL_DB_NAME}' exists...`);
	const proc = spawn(args, { stdout: "pipe", stderr: "pipe" });
	const exitCode = await proc.exited;
	
	if (exitCode !== 0) {
		const stderr = await new Response(proc.stderr).text();
		throw new Error(`mysql database creation failed: ${stderr}`);
	}
}

export async function getLocalTablePrefix(wpPath: string): Promise<string> {
	const { join } = require("path");
	const wpConfigPath = join(wpPath, "wp-config.php");
	try {
		const content = await readFile(wpConfigPath, "utf-8");
		const match = content.match(/\$table_prefix\s*=\s*['"]([^'"]+)['"]/);
		return match ? match[1] : "wp_";
	} catch (err) {
		log.warn(`Could not read wp-config.php at ${wpConfigPath}. Defaulting local prefix to 'wp_'.`);
		return "wp_";
	}
}

export async function renameDatabasePrefixSQL(filePath: string, oldPrefix: string, newPrefix: string): Promise<void> {
	if (oldPrefix === newPrefix) return;
	
	log.info(`Migrating database prefixes from '${oldPrefix}' to '${newPrefix}'...`);
	
    let content = await readFile(filePath, "utf-8");

	// Safely replace table references wrapped in backticks: e.g. `wp_posts` -> `vh_posts`
	const prefixRegex = new RegExp(`\\\`${oldPrefix}`, "g");
    content = content.replace(prefixRegex, `\`${newPrefix}`);

	// Append native WordPress meta key migrations
	const sqlAppend = `\n
-- Meta Key Prefix Migrations
UPDATE \`${newPrefix}options\` SET option_name = REPLACE(option_name, '${oldPrefix}', '${newPrefix}') WHERE option_name LIKE '${oldPrefix}%';
UPDATE \`${newPrefix}usermeta\` SET meta_key = REPLACE(meta_key, '${oldPrefix}', '${newPrefix}') WHERE meta_key LIKE '${oldPrefix}%';
`;
    content += sqlAppend;

    await writeFile(filePath, content, "utf-8");
}
