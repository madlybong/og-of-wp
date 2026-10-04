import { getLocalEnv } from "../lib/env";
import { importDb, searchReplaceSQL } from "../lib/db";
import { log } from "../lib/logger";
import { existsSync, readFileSync } from "fs";
import { cp, rm } from "fs/promises";

export default async function (args: string[]) {
    if (args.length === 0) {
        log.error("Please provide the path to the production SQL dump.");
        log.info("Usage: og-deploy db:pull <path/to/dump.sql>");
        return;
    }
    
    const inputSql = args[0];
    if (!existsSync(inputSql)) {
        log.error(`File not found: ${inputSql}`);
        return;
    }
    
    log.info("Starting production DB import...");
    const env = await getLocalEnv();
    
    let initialProdUrl = "";
    if (existsSync('.env.production')) {
        const prodEnvStr = readFileSync('.env.production', 'utf-8');
        const match = prodEnvStr.match(/^PROD_URL=(.*)$/m);
        if (match) initialProdUrl = match[1].trim();
    }

    const { prompt } = require('enquirer');
    const response: any = await prompt({
        type: 'input',
        name: 'PROD_URL',
        message: 'Enter the production URL to search and replace:',
        initial: initialProdUrl
    });
    const PROD_URL = response.PROD_URL;
    
    const tempSql = `temp-pull-${Date.now()}.sql`;
    log.info("Creating temporary copy...");
    await cp(inputSql, tempSql);
    
    log.info("Rewriting URLs for local environment...");
    await searchReplaceSQL(tempSql, PROD_URL, env.LOCAL_URL);
    
    await importDb(env, tempSql);
    
    log.info("Cleaning up temp file...");
    await rm(tempSql, { force: true });
    
    log.success(`Production database imported to local: ${env.LOCAL_DB_NAME}`);
    log.info(`All URLs replaced: ${PROD_URL} -> ${env.LOCAL_URL}`);
}
