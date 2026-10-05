
import { searchReplaceSQL, renameDatabasePrefixSQL, hardenAdminCredentialsSQL } from "./cli/lib/db";
import { writeFileSync, readFileSync } from "fs";

async function test() {
    const sqlFile = "test3.sql";
    writeFileSync(sqlFile, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n-- Dump completed on 2026-10-05 17:05:53\n");
    
    await renameDatabasePrefixSQL(sqlFile, "wp_", "vh_");
    await searchReplaceSQL(sqlFile, "http://localhost/verbe-healthcare", "https://verbe.astrake.com");
    await hardenAdminCredentialsSQL(sqlFile, { PROD_WP_ADMIN_USER: "admin", PROD_WP_ADMIN_PASS: "pass", WP_TABLE_PREFIX: "vh_" } as any);
    
    console.log(readFileSync(sqlFile, "utf-8"));
}
test();

