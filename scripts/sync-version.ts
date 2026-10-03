import { readFileSync, writeFileSync } from "fs";

const newVersion = process.argv[2];
if (!newVersion) {
    console.error("❌ Please provide a version (e.g. bun run scripts/sync-version.ts 1.0.1)");
    process.exit(1);
}

// 1. Update Root Package
let rootPkg = readFileSync("package.json", "utf8");
rootPkg = rootPkg.replace(/"version": ".*"/, `"version": "${newVersion}"`);
writeFileSync("package.json", rootPkg);

// 2. Update CLI Package
let cliPkg = readFileSync("cli/package.json", "utf8");
cliPkg = cliPkg.replace(/"version": ".*"/, `"version": "${newVersion}"`);
writeFileSync("cli/package.json", cliPkg);

// 3. Update Plugin PHP Headers
let pluginPhp = readFileSync("og-of-wp.php", "utf8");
pluginPhp = pluginPhp.replace(/define\(\s*'OG_WP_VERSION',\s*'.*'\s*\);/, `define( 'OG_WP_VERSION', '${newVersion}' );`);
pluginPhp = pluginPhp.replace(/\*\s*Version:\s*.*/, `* Version:     ${newVersion}`);
writeFileSync("og-of-wp.php", pluginPhp);

console.log(`✅ Successfully synced version ${newVersion} across all ecosystem files.`);
