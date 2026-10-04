import { getLocalEnv } from "../lib/env";
import { exportDb } from "../lib/db";
import { createZip } from "../lib/zip";
import { log } from "../lib/logger";
import { existsSync } from "fs";
import { mkdir } from "fs/promises";
import { join } from "path";

export default async function () {
    log.info("Starting local backup...");
    const env = await getLocalEnv();
    
    const backupsDir = join(process.cwd(), "backups");
    if (!existsSync(backupsDir)) {
        await mkdir(backupsDir, { recursive: true });
    }
    
    const date = new Date();
    const timestamp = date.toISOString().replace(/[:.]/g, "-").replace("T", "-").slice(0, 16);
    
    const sqlFile = join(backupsDir, `${env.PROJECT_SLUG}-db-${timestamp}.sql`);
    const zipFile = join(backupsDir, `${env.PROJECT_SLUG}-files-${timestamp}.zip`);
    
    await exportDb(env, sqlFile);
    await createZip(env.SEVENZIP_PATH, env.LOCAL_WP_PATH, zipFile);
    
    log.success("Backup completed successfully.");
    log.info(`Database: ${sqlFile}`);
    log.info(`Files: ${zipFile}`);
}
