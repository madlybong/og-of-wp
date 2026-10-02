import { getEnv } from "../lib/env";
import { importDb, searchReplaceSQL } from "../lib/db";
import { log } from "../lib/logger";
import { existsSync, cpSync, rmSync } from "fs";

export default async function (args: string[]) {
    if (args.length === 0) {
        log.error("Please provide the path to the production SQL dump.");
        log.info("Usage: bun run deploy db:pull <path/to/dump.sql>");
        return;
    }
    
    const inputSql = args[0];
    if (!existsSync(inputSql)) {
        log.error(`File not found: ${inputSql}`);
        return;
    }
    
    log.info("Starting production DB import...");
    const env = await getEnv();
    
    const tempSql = `temp-pull-${Date.now()}.sql`;
    log.info("Creating temporary copy...");
    cpSync(inputSql, tempSql);
    
    log.info("Rewriting URLs for local environment...");
    searchReplaceSQL(tempSql, env.PROD_URL, env.LOCAL_URL);
    
    await importDb(env, tempSql);
    
    log.info("Cleaning up temp file...");
    rmSync(tempSql);
    
    log.success(`Production database imported to local: ${env.LOCAL_DB_NAME}`);
    log.info(`All URLs replaced: ${env.PROD_URL} -> ${env.LOCAL_URL}`);
}
