import { readFileSync, existsSync, writeFileSync, appendFileSync } from "fs";
const { prompt } = require('enquirer');
import { log } from "./logger";

export interface EnvConfig {
    LOCAL_WP_PATH: string;
    LOCAL_URL: string;
    LOCAL_DB_NAME: string;
    LOCAL_DB_USER: string;
    LOCAL_DB_PASS: string;
    LOCAL_DB_HOST: string;
    MYSQLDUMP_PATH: string;
    MYSQL_PATH: string;
    SEVENZIP_PATH: string;
    PROD_URL: string;
    PROD_DB_NAME: string;
    PROD_DB_USER: string;
    PROD_DB_PASS: string;
    PROD_DB_HOST: string;
    WP_TABLE_PREFIX: string;
    SMTP_HOST: string;
    SMTP_PORT: string;
    SMTP_USER: string;
    SMTP_PASS: string;
    PROD_WP_ADMIN_USER: string;
    PROD_WP_ADMIN_PASS: string;
    PROD_WP_ADMIN_EMAIL: string;
}

const LOCAL_KEYS = [
    "LOCAL_WP_PATH", "LOCAL_URL", "LOCAL_DB_NAME", "LOCAL_DB_USER", "LOCAL_DB_PASS",
    "LOCAL_DB_HOST", "MYSQLDUMP_PATH", "MYSQL_PATH", "SEVENZIP_PATH"
];

const PROD_KEYS = [
    "PROD_URL", "PROD_DB_NAME", "PROD_DB_USER", "PROD_DB_PASS",
    "PROD_DB_HOST", "WP_TABLE_PREFIX", "SMTP_HOST", "SMTP_PORT",
    "SMTP_USER", "SMTP_PASS", "PROD_WP_ADMIN_USER", "PROD_WP_ADMIN_PASS", "PROD_WP_ADMIN_EMAIL"
];

const PASSWORD_KEYS = ["LOCAL_DB_PASS", "PROD_DB_PASS", "SMTP_PASS", "PROD_WP_ADMIN_PASS"];

export async function getEnv(): Promise<EnvConfig> {
    const parseEnv = (path: string) => {
        if (!existsSync(path)) {
            appendFileSync(path, ""); // Ensure file exists
            return {};
        }
        const content = readFileSync(path, "utf-8");
        const lines = content.split("\n");
        const config: Record<string, string> = {};
        for (const line of lines) {
            const match = line.match(/^([^#=]+)=(.*)$/);
            if (match) {
                config[match[1].trim()] = match[2].trim();
            }
        }
        return config;
    };

    let localEnv = parseEnv(".env");
    let prodEnv = parseEnv(".env.production");

    log.info("Please verify your local environment configuration (Press Enter to accept defaults):");
    for (const key of LOCAL_KEYS) {
        if (key === "LOCAL_WP_PATH") {
            let wpPath = localEnv[key] || "../wordpress";
            while (true) {
                const choice: any = await prompt({
                    type: 'select',
                    name: 'pathType',
                    message: `Select WordPress installation source (Current: ${wpPath}):`,
                    choices: [`Default (${wpPath})`, 'Custom Path']
                });
                
                if (choice.pathType === 'Custom Path') {
                    const customPathResponse: any = await prompt({
                        type: 'input',
                        name: 'LOCAL_WP_PATH',
                        message: 'Enter custom path for LOCAL_WP_PATH:',
                        initial: wpPath
                    });
                    wpPath = customPathResponse.LOCAL_WP_PATH;
                }

                const configPath = require("path").join(process.cwd(), wpPath, "wp-config.php");
                if (existsSync(configPath)) {
                    const content = readFileSync(configPath, "utf-8");
                    const getDefine = (k: string) => {
                        const regex = new RegExp(`define\\s*\\(\\s*['"]${k}['"]\\s*,\\s*['"]([^'"]*)['"]\\s*\\)`, 'i');
                        const match = content.match(regex);
                        return match ? match[1] : "";
                    };
                    
                    localEnv['LOCAL_DB_NAME'] = getDefine('DB_NAME') || localEnv['LOCAL_DB_NAME'];
                    localEnv['LOCAL_DB_USER'] = getDefine('DB_USER') || localEnv['LOCAL_DB_USER'];
                    localEnv['LOCAL_DB_PASS'] = getDefine('DB_PASSWORD') || localEnv['LOCAL_DB_PASS'];
                    localEnv['LOCAL_DB_HOST'] = getDefine('DB_HOST') || localEnv['LOCAL_DB_HOST'];
                    localEnv['LOCAL_URL'] = getDefine('WP_HOME') || getDefine('WP_SITEURL') || localEnv['LOCAL_URL'];

                    localEnv[key] = wpPath;
                    break;
                } else {
                    log.error(`wp-config.php not found at ${configPath}. Please provide a valid WordPress path.`);
                }
            }
        } else {
            const isPassword = PASSWORD_KEYS.includes(key);
            const response: any = await prompt({
                type: isPassword ? 'password' : 'input',
                name: key,
                message: `Enter value for ${key}:`,
                initial: localEnv[key] || ""
            });
            localEnv[key] = response[key];
        }
    }
    
    // Save .env completely
    writeFileSync(".env", Object.entries(localEnv).map(([k, v]) => `${k}=${v}`).join("\n"));

    log.info("Please verify your production environment configuration (Press Enter to accept defaults):");
    
    // Auto-generate missing admin credentials
    if (!prodEnv["PROD_WP_ADMIN_USER"]) prodEnv["PROD_WP_ADMIN_USER"] = "admin_zsec";
    if (!prodEnv["PROD_WP_ADMIN_PASS"]) {
        const crypto = require("crypto");
        prodEnv["PROD_WP_ADMIN_PASS"] = crypto.randomBytes(16).toString('base64').replace(/[^a-zA-Z0-9]/g, '').substring(0, 24);
        log.info("Generated new secure password for PROD_WP_ADMIN_PASS");
    }
    if (!prodEnv["PROD_WP_ADMIN_EMAIL"]) prodEnv["PROD_WP_ADMIN_EMAIL"] = "admin@example.com";

    // If running in CI environment, skip prompting and use what we have (or what we just generated)
    if (!process.env.CI) {
        for (const key of PROD_KEYS) {
            const isPassword = PASSWORD_KEYS.includes(key);
            const response: any = await prompt({
                type: isPassword ? 'password' : 'input',
                name: key,
                message: `Enter value for ${key}:`,
                initial: prodEnv[key] || ""
            });
            prodEnv[key] = response[key];
        }
    } else {
        log.info("CI environment detected, skipping interactive prompts for production configuration.");
    }
    
    // Save .env.production completely
    writeFileSync(".env.production", Object.entries(prodEnv).map(([k, v]) => `${k}=${v}`).join("\n"));

    const combinedEnv = { ...localEnv, ...prodEnv };

    return combinedEnv as unknown as EnvConfig;
}
