import { getEnv } from "../lib/env";
import { exportDb, searchReplaceSQL } from "../lib/db";
import { log } from "../lib/logger";

export default async function () {
    log.info("Starting local DB export...");
    const env = await getEnv();
    
    const dateStr = new Date().toISOString().split("T")[0];
    const outputFile = `zerosugar-push-${dateStr}.sql`;
    
    await exportDb(env, outputFile);
    
    log.info("Rewriting URLs for production...");
    searchReplaceSQL(outputFile, env.LOCAL_URL, env.PROD_URL);
    
    log.success(`SQL ready: ${outputFile}`);
    log.info(`Import this file via cPanel > phpMyAdmin into database: ${env.PROD_DB_NAME}`);
}
