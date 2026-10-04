import { getEnv } from "../lib/env";
import { exportDb, searchReplaceSQL, hardenAdminCredentialsSQL } from "../lib/db";
import { log } from "../lib/logger";

export default async function () {
    const env = await getEnv();
    log.info(`Starting ${env.PROJECT_SLUG} local DB export...`);
    
    const dateStr = new Date().toISOString().split("T")[0];
    const outputFile = `${env.PROJECT_SLUG}-push-${dateStr}.sql`;
    
    await exportDb(env, outputFile);
    
    log.info("Rewriting URLs for production...");
    await searchReplaceSQL(outputFile, env.LOCAL_URL, env.PROD_URL);
    await hardenAdminCredentialsSQL(outputFile, env);
    
    log.success(`SQL ready: ${outputFile}`);
    log.info(`Import this file via cPanel > phpMyAdmin into database: ${env.PROD_DB_NAME}`);
}
