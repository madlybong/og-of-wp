import { getLocalEnv } from "../lib/env";
import { createDatabase } from "../lib/db";
import { extractZip } from "../lib/zip";
import { log } from "../lib/logger";
import { existsSync } from "fs";
import { mkdir, writeFile, cp, rm } from "fs/promises";
import { join } from "path";
import { randomBytes } from "crypto";

export default async function (args: string[]) {
    log.info("Starting OG WP Ecosystem Initialization...");
    
    // 1. Get Environment (will prompt if .env.local doesn't exist)
    const env = await getLocalEnv();
    
    const wpDir = resolvePath(env.LOCAL_WP_PATH);
    if (!existsSync(wpDir)) {
        await mkdir(wpDir, { recursive: true });
    }

    // 2. Database Auto-Creation
    await createDatabase(env);

    // 3. Download and Extract Core
    const tempDir = join(process.cwd(), ".wp-temp-" + Date.now());
    await mkdir(tempDir, { recursive: true });
    const coreZip = join(tempDir, "latest.zip");

    log.info("Downloading latest WordPress core...");
    const wpRes = await fetch("https://wordpress.org/latest.zip");
    if (!wpRes.ok) throw new Error("Failed to download WordPress core");
    await Bun.write(coreZip, await wpRes.arrayBuffer());

    await extractZip(env.SEVENZIP_PATH, coreZip, tempDir);

    log.info("Scaffolding core into target directory...");
    // 7z extracts `latest.zip` into a `wordpress` folder
    const extractedWpDir = join(tempDir, "wordpress");
    await cp(extractedWpDir, wpDir, { recursive: true, force: true });

    // 4. Download and Extract CF7
    const cf7Zip = join(tempDir, "cf7.zip");
    log.info("Downloading Contact Form 7...");
    const cf7Res = await fetch("https://downloads.wordpress.org/plugin/contact-form-7.latest-stable.zip");
    if (cf7Res.ok) {
        await Bun.write(cf7Zip, await cf7Res.arrayBuffer());
        const pluginsDir = join(wpDir, "wp-content", "plugins");
        await extractZip(env.SEVENZIP_PATH, cf7Zip, pluginsDir);
    } else {
        log.warn("Failed to download Contact Form 7. Skipping.");
    }

    // 5. Scaffold og-of-wp plugin
    const myPluginSource = join(process.cwd());
    const myPluginDest = join(wpDir, "wp-content", "plugins", "og-of-wp");
    log.info("Linking og-of-wp master plugin...");
    if (myPluginSource !== myPluginDest) {
        // We will just copy the necessary plugin files over (excluding node_modules, cli, etc)
        await mkdir(myPluginDest, { recursive: true });
        const includeDirs = ['admin', 'includes', 'languages', 'og-of-wp.php', 'index.php'];
        for (const item of includeDirs) {
            const src = join(myPluginSource, item);
            if (existsSync(src)) {
                await cp(src, join(myPluginDest, item), { recursive: true, force: true });
            }
        }
    }

    // 6. Scaffold OG Starter Theme
    log.info("Scaffolding OG Starter Theme...");
    const themeSource = join(process.cwd(), "cli", "scaffolds", "og-starter-theme");
    const themeDest = join(wpDir, "wp-content", "themes", "og-starter-theme");
    if (existsSync(themeSource)) {
        await cp(themeSource, themeDest, { recursive: true, force: true });
    }

    // 7. Auto-generate wp-config.php
    log.info("Generating secure wp-config.php...");
    
    // Generate native crypto salts
    const saltKeys = [
        'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
        'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'
    ];
    let salts = '';
    for (const key of saltKeys) {
        const salt = randomBytes(48).toString('base64');
        salts += `define( '${key}', '${salt}' );\n`;
    }

    const wpConfigContent = `<?php
define( 'DB_NAME', '${env.LOCAL_DB_NAME}' );
define( 'DB_USER', '${env.LOCAL_DB_USER}' );
define( 'DB_PASSWORD', '${env.LOCAL_DB_PASS}' );
define( 'DB_HOST', '${env.LOCAL_DB_HOST}' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

${salts}

$table_prefix = 'wp_';

define( 'WP_HOME', '${env.LOCAL_URL}' );
define( 'WP_SITEURL', '${env.LOCAL_URL}' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
`;
    await writeFile(join(wpDir, "wp-config.php"), wpConfigContent, "utf-8");

    // Clean up
    log.info("Cleaning up temporary files...");
    await rm(tempDir, { recursive: true, force: true });

    log.success("OG WP Ecosystem Initialized Successfully!");
    log.info(`Core Path: ${wpDir}`);
    log.info(`Database: ${env.LOCAL_DB_NAME} (Created via MySQL)`);
    log.info(`Active Theme: og-starter-theme`);
    log.info(`You can now log into your local WordPress at ${env.LOCAL_URL}/wp-admin`);
}

function resolvePath(p: string): string {
    const { resolve, isAbsolute } = require("path");
    return isAbsolute(p) ? p : resolve(process.cwd(), p);
}
