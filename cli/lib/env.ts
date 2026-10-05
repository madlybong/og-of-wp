import { readFileSync, existsSync, writeFileSync, appendFileSync } from "fs";
const { prompt } = require('enquirer');
import { log } from "./logger";

export interface LocalEnvConfig {
    PROJECT_SLUG: string;
    LOCAL_WP_PATH: string;
    LOCAL_URL: string;
    LOCAL_DB_NAME: string;
    LOCAL_DB_USER: string;
    LOCAL_DB_PASS: string;
    LOCAL_DB_HOST: string;
    MYSQLDUMP_PATH: string;
    MYSQL_PATH: string;
    SEVENZIP_PATH: string;
}

export interface ProdEnvConfig {
    PROD_URL: string;
    PROD_DB_NAME: string;
    PROD_DB_USER: string;
    PROD_DB_PASS: string;
    PROD_DB_HOST: string;
    WP_TABLE_PREFIX: string;
    PROD_WP_ADMIN_USER: string;
    PROD_WP_ADMIN_PASS: string;
    PROD_WP_ADMIN_EMAIL: string;
}

export interface EnvConfig extends LocalEnvConfig, ProdEnvConfig {}

const LOCAL_KEYS = [
    "PROJECT_SLUG", "LOCAL_WP_PATH", "LOCAL_URL", "LOCAL_DB_NAME", "LOCAL_DB_USER", "LOCAL_DB_PASS",
    "LOCAL_DB_HOST", "MYSQLDUMP_PATH", "MYSQL_PATH", "SEVENZIP_PATH"
];

const PROD_KEYS = [
    "PROD_URL", "PROD_DB_NAME", "PROD_DB_USER", "PROD_DB_PASS",
    "PROD_DB_HOST", "WP_TABLE_PREFIX", "PROD_WP_ADMIN_USER", "PROD_WP_ADMIN_PASS", "PROD_WP_ADMIN_EMAIL"
];

const PASSWORD_KEYS = ["LOCAL_DB_PASS", "PROD_DB_PASS", "PROD_WP_ADMIN_PASS"];

const parseEnv = (path: string): Record<string, string> => {
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

async function promptKey(key: string, currentVal: string = ""): Promise<string> {
    const isPassword = PASSWORD_KEYS.includes(key);
    if (!isPassword) {
        const response: any = await prompt({
            type: 'input',
            name: key,
            message: `Enter value for ${key}:`,
            initial: currentVal
        });
        return response[key] ?? "";
    }

    while (true) {
        const response: any = await prompt({
            type: 'password',
            name: key,
            message: `Enter value for ${key}:`,
            initial: currentVal
        });
        const val = response[key] ?? "";

        // If user entered a new password or changed it from empty, confirm it
        if (val && val !== currentVal) {
            const confirmResponse: any = await prompt({
                type: 'password',
                name: 'confirm',
                message: `Confirm value for ${key}:`
            });
            if (confirmResponse.confirm !== val) {
                log.error(`Values for ${key} do not match. Please try again.`);
                continue;
            }
        }
        return val;
    }
}

export async function getLocalEnv(): Promise<LocalEnvConfig> {
    const localEnv = parseEnv(".env");

    const autoDetectExecutable = (name: string, commonPaths: string[]) => {
        const inPath = Bun.which(name);
        if (inPath) return inPath;
        for (const p of commonPaths) {
            if (existsSync(p)) return p;
        }
        return name; // Fallback to bare command
    };

    if (!localEnv["PROJECT_SLUG"]) {
        localEnv["PROJECT_SLUG"] = "og-of-wp";
    }

    if (!localEnv["MYSQLDUMP_PATH"]) {
        localEnv["MYSQLDUMP_PATH"] = autoDetectExecutable("mysqldump", [
            "C:\\xampp\\mysql\\bin\\mysqldump.exe",
            "/usr/bin/mysqldump",
            "/usr/local/bin/mysqldump"
        ]);
    }
    if (!localEnv["MYSQL_PATH"]) {
        localEnv["MYSQL_PATH"] = autoDetectExecutable("mysql", [
            "C:\\xampp\\mysql\\bin\\mysql.exe",
            "/usr/bin/mysql",
            "/usr/local/bin/mysql"
        ]);
    }
    if (!localEnv["SEVENZIP_PATH"]) {
        localEnv["SEVENZIP_PATH"] = autoDetectExecutable("7z", [
            "C:\\Program Files\\7-Zip\\7z.exe",
            "C:\\Program Files (x86)\\7-Zip\\7z.exe",
            "/usr/bin/7z",
            "/usr/local/bin/7z",
            "zip"
        ]);
    }

    if (!process.env.CI) {
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
                        
                        localEnv['LOCAL_DB_NAME'] = getDefine('DB_NAME') || localEnv['LOCAL_DB_NAME'] || "";
                        localEnv['LOCAL_DB_USER'] = getDefine('DB_USER') || localEnv['LOCAL_DB_USER'] || "";
                        localEnv['LOCAL_DB_PASS'] = getDefine('DB_PASSWORD') || localEnv['LOCAL_DB_PASS'] || "";
                        localEnv['LOCAL_DB_HOST'] = getDefine('DB_HOST') || localEnv['LOCAL_DB_HOST'] || "localhost";
                        localEnv['LOCAL_URL'] = getDefine('WP_HOME') || getDefine('WP_SITEURL') || localEnv['LOCAL_URL'] || "";

                        localEnv[key] = wpPath;
                        break;
                    } else {
                        log.error(`wp-config.php not found at ${configPath}. Please provide a valid WordPress path.`);
                    }
                }
            } else {
                localEnv[key] = await promptKey(key, localEnv[key] || "");
            }
        }
    } else {
        log.info("CI environment detected, skipping interactive prompts for local configuration.");
    }

    // Save .env completely
    writeFileSync(".env", Object.entries(localEnv).map(([k, v]) => `${k}=${v}`).join("\n") + "\n");

    return localEnv as unknown as LocalEnvConfig;
}

export async function getProdEnv(): Promise<ProdEnvConfig> {
    const prodEnv = parseEnv(".env.production");

    // Auto-generate missing admin credentials
    if (!prodEnv["PROD_WP_ADMIN_USER"]) prodEnv["PROD_WP_ADMIN_USER"] = "admin_zsec";
    if (!prodEnv["PROD_WP_ADMIN_PASS"]) {
        const crypto = require("crypto");
        prodEnv["PROD_WP_ADMIN_PASS"] = crypto.randomBytes(16).toString('base64').replace(/[^a-zA-Z0-9]/g, '').substring(0, 24);
        log.info("Generated new secure password for PROD_WP_ADMIN_PASS");
    }
    if (!prodEnv["PROD_WP_ADMIN_EMAIL"]) prodEnv["PROD_WP_ADMIN_EMAIL"] = "admin@example.com";

    if (!process.env.CI) {
        log.info("Please verify your production environment configuration (Press Enter to accept defaults):");
        for (const key of PROD_KEYS) {
            prodEnv[key] = await promptKey(key, prodEnv[key] || "");
        }
    } else {
        log.info("CI environment detected, skipping interactive prompts for production configuration.");
    }

    // Save .env.production completely
    writeFileSync(".env.production", Object.entries(prodEnv).map(([k, v]) => `${k}=${v}`).join("\n") + "\n");

    return prodEnv as unknown as ProdEnvConfig;
}

export async function getEnv(): Promise<EnvConfig> {
    const local = await getLocalEnv();
    const prod = await getProdEnv();
    return { ...local, ...prod };
}
