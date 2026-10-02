#!/usr/bin/env bun
import { log } from "./lib/logger";

const command = process.argv[2];
const args = process.argv.slice(3);

async function main() {
    switch (command) {
        case "package":
            await (await import("./commands/package")).default(args);
            break;
        case "db:push":
            await (await import("./commands/db-push")).default(args);
            break;
        case "db:pull":
            await (await import("./commands/db-pull")).default(args);
            break;
        case "backup":
            await (await import("./commands/backup")).default(args);
            break;
        default:
            log.info("Usage: og-deploy <command> [options]");
            log.info("Commands:");
            log.info("  package  - Builds a ZIP for cPanel upload (includes DB & SMTP)");
            log.info("  db:push  - Exports local DB -> production-ready SQL");
            log.info("  db:pull  - Converts a provided prod SQL dump -> local-ready and imports");
            log.info("  backup   - Creates a timestamped local backup");
            break;
    }
}

main().catch((err) => {
    log.error(`Error: ${err.message}`);
    process.exit(1);
});
