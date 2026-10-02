import { getEnv } from "../lib/env";
import { exportDb } from "../lib/db";
import { createZip } from "../lib/zip";
import { log } from "../lib/logger";
import { existsSync, mkdirSync } from "fs";
import { join } from "path";

export default async function () {
    log.info("Starting local backup...");
    const env = await getEnv();
    
    const backupsDir = join(process.cwd(), "backups");
    if (!existsSync(backupsDir)) {
        mkdirSync(backupsDir);
    }
    
    const date = new Date();
    const timestamp = date.toISOString().replace(/[:.]/g, "-").replace("T", "-").slice(0, 16);
    
    const sqlFile = join(backupsDir, `zerosugar-db-${timestamp}.sql`);
    const zipFile = join(backupsDir, `zerosugar-files-${timestamp}.zip`);
    
    await exportDb(env, sqlFile);
    await createZip(env.SEVENZIP_PATH, env.LOCAL_WP_PATH, zipFile);
    
    log.success("Backup completed successfully.");
    log.info(`Database: ${sqlFile}`);
    log.info(`Files: ${zipFile}`);
}
