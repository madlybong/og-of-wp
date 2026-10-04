<div align="center">

# 👑 OG of WP
### The Original Gangster of WordPress

**The unified WordPress powerhouse: 15 Modular Security & Utility Engines + High-Speed Cross-Platform Deploy CLI.**

[![GitHub Release](https://img.shields.io/github/v/release/madlybong/og-of-wp?style=for-the-badge&color=00d2ff)](https://github.com/madlybong/og-of-wp/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-brightgreen.svg?style=for-the-badge)](LICENSE)
[![WordPress Compatibility](https://img.shields.io/badge/WordPress-6.0+-21759b.svg?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP Compatibility](https://img.shields.io/badge/PHP-7.4+-777bb4.svg?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Runtime: Bun](https://img.shields.io/badge/Bun-1.0+-000000.svg?style=for-the-badge&logo=bun&logoColor=white)](https://bun.sh)
[![CI/CD Pipeline](https://img.shields.io/badge/Release-Automated-success?style=for-the-badge&logo=githubactions&logoColor=white)](https://github.com/madlybong/og-of-wp/actions)

</div>

---

## 📖 Overview

**OG of WP** is an all-in-one WordPress toolkit engineered for professional developers, agencies, and site owners. Instead of juggling dozens of bulky, bloated third-party plugins that degrade page performance, **OG of WP** consolidates complete enterprise-grade security, optimization, and administration utilities into a single, clean, modular codebase.

Additionally, this repository introduces the **Hybrid Architecture**: alongside the WordPress plugin, it includes a lightning-fast **Deploy CLI** powered by [Bun](https://bun.sh). The CLI allows instant zero-downtime packaging, database sanitization, push/pull migrations, and backups without needing external dependencies.

---

## ⚡ Key Highlights

- 🛡️ **15 Modular Engines** — Activate only what you need. Zero bloat, zero unnecessary queries.
- 🚀 **Hybrid Architecture** — WordPress plugin and Deploy CLI live harmoniously in one single repository.
- 📦 **Automated Release Pipeline** — Pure plugin `.zip` builds and standalone cross-platform CLI executables automatically published on every release.
- 💻 **Zero-Install CLI** — Run deployment scripts across macOS, Windows, and Linux via `bunx github:madlybong/og-of-wp`.
- 🪟 **Standalone Native Binaries** — Download single-file `.exe` or Linux/macOS binaries with no Node.js or Bun installation required.

---

## 🧩 The 15 Plugin Modules

| # | Module | Category | Description |
|---|---|---|---|
| 1 | **Auth** | Security | Two-factor authentication (2FA), brute-force login lockdown, login logs, and captcha verification. |
| 2 | **WAF** | Security | Web Application Firewall protecting against SQL injection, XSS, malicious queries, and bad bot traffic. |
| 3 | **Audit** | Security | Complete event and user action audit trail stored securely in a dedicated database table (`wp_og_wp_audit_log`). |
| 4 | **Hardening** | Security | Disables XML-RPC, strips WordPress generator meta tags, and enforces REST API authentication requirements. |
| 5 | **Headers** | Security | Injects strict HTTP security headers: HSTS, Content-Security-Policy (CSP), X-Frame-Options, and Permissions-Policy. |
| 6 | **SSL** | Security | Enforces sitewide HTTPS redirection, fixes insecure resources, and prevents mixed-content warnings. |
| 7 | **Files** | Monitoring | Deep file system integrity checker detecting unauthorized modifications to WordPress core files. |
| 8 | **Scanner** | Monitoring | Heuristic malware scanner auditing active themes and plugins for backdoors, shells, and obfuscated code. |
| 9 | **Spam** | Utility | Native comment and form spam mitigation engine without relying on slow external API lookups. |
| 10 | **DB** | Performance | Interactive database health analyzer, overhead table defragmenter, and transient cleaner. |
| 11 | **User** | Management | User privilege auditor, dormant account detection, and instantaneous active session invalidation. |
| 12 | **Duplicator** | Utility | Deep 1-click cloner for pages, posts, and custom post types while preserving complete taxonomies and custom fields. |
| 13 | **Porter** | Utility | Complete JSON-based configuration export and import engine to synchronize plugin settings across staging and production. |
| 14 | **Media Cleaner** | Performance | Analyzes the media library, identifying unattached and orphaned media files to reclaim server storage. |
| 15 | **Branding** | White-label | Complete custom admin dashboard white-labeling: custom login logo, custom styling, and dashboard footer customization. |

---

## 🚀 Installation & Usage

### Option A: Install the WordPress Plugin

#### 1. Via Pre-built Release ZIP (Recommended)
1. Go to the [Releases](https://github.com/madlybong/og-of-wp/releases) page.
2. Download the latest `og-of-wp-v*.zip` asset.
3. In your WordPress Admin panel, navigate to **Plugins > Add New > Upload Plugin**.
4. Choose the downloaded ZIP file and click **Install Now**, then **Activate Plugin**.

#### 2. Via Git Clone (For Developers)
From your WordPress installation directory:
```bash
cd wp-content/plugins
git clone https://github.com/madlybong/og-of-wp.git
```
Activate **OG of WP** inside your WordPress Admin dashboard under **Plugins**.

---

### Option B: Using the Deploy CLI

The Deploy CLI allows seamless packaging and database synchronization between your local development environment and live cPanel/VPS servers.

#### 1. Run Instantly Without Installing (via Bun)
```bash
bunx github:madlybong/og-of-wp <command>
```
*Example:*
```bash
bunx github:madlybong/og-of-wp package
```

#### 2. Standalone Executable (Zero Runtime Needed)
Download the pre-compiled binary for your operating system from the latest [Release](https://github.com/madlybong/og-of-wp/releases):
- **Windows**: `og-deploy.exe`
- **Linux**: `og-deploy-linux`
- **macOS**: `og-deploy-macos`

Run it directly from your terminal:
```bash
./og-deploy package
```

#### 3. Local Development in this Repository
```bash
# Clone the repository
git clone https://github.com/madlybong/og-of-wp.git
cd og-of-wp

# Install dependencies
bun install

# Run CLI directly
bun start <command>
```

---

## 🛠️ Deploy CLI Commands

| Command | Description |
|---|---|
| `package` | Interactively scans local WordPress directory, prompts for database export, compresses clean code (excluding git/staging), and creates a production-ready ZIP archive. |
| `db:push` | Exports the local MySQL database, automatically performs domain search-and-replace, and generates an optimized production SQL file ready for server import. |
| `db:pull` | Ingests a production SQL dump, replaces production domains with your local development URLs, and imports it directly into your local database. |
| `backup` | Creates a full timestamped snapshot archive of your WordPress files and current database state. |

### Environment Configuration
The CLI reads configuration automatically from your environment. You can create a `.env` file in the project directory:
```env
PROJECT_SLUG=og-of-wp
LOCAL_WP_PATH=../wordpress
LOCAL_DB_NAME=og_of_wp_db
LOCAL_DB_USER=root
LOCAL_DB_PASS=
LOCAL_URL=http://localhost/og-of-wp/wordpress

PROD_DB_NAME=cpanel_wpdb
PROD_DB_USER=cpanel_user
PROD_DB_PASS=secret
PROD_URL=https://example.com
```

> **Interactive Fallback:** If any variable is missing or you wish to override it, the CLI will prompt you in the terminal with dynamic defaults pre-populated!

---

## 🔨 Building Standalone Executables Locally

You can compile standalone single-file executables for any platform using [Bun](https://bun.sh):

```bash
# Build for your current OS
bun run build:exe

# Cross-compile for Windows (creates og-deploy.exe)
bun run build:exe:win

# Cross-compile for Linux (creates og-deploy-linux)
bun run build:exe:linux

# Cross-compile for macOS Apple Silicon (creates og-deploy-macos)
bun run build:exe:macos
```

---

## 🤖 CI/CD & Automated Releases

Releases are fully automated using GitHub Actions (`.github/workflows/release.yml`).

To trigger a new production release with both the WordPress plugin ZIP and cross-platform CLI binaries:
```bash
# Create and push a semver tag
git tag v1.0.0
git push origin v1.0.0
```

The GitHub Actions pipeline will:
1. Filter and package only pure WordPress plugin files into `og-of-wp-{version}.zip`.
2. Cross-compile standalone native CLI binaries for Linux, macOS, and Windows.
3. Automatically publish a new GitHub Release with release notes extracted from `CHANGELOG.md` and attach all 4 production assets.

---

## 🤝 Contributor Setup Guide

We have bundled a local testing architecture to help you develop the plugin instantly without complex symlinks.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/madlybong/og-of-wp.git
   cd og-of-wp
   ```
2. **Install CLI Dependencies:**
   ```bash
   bun install
   ```
3. **Initialize the local WordPress Testing Environment:**
   The repository safely tracks a local `wordpress/` core directory but ignores your local credentials.
   ```bash
   # Create a MySQL database locally (e.g. 'og_of_wp_test')
   # Navigate into the local WordPress directory
   cd wordpress
   
   # Use WP-CLI to generate your local config
   wp config create --dbname=og_of_wp_test --dbuser=root --dbpass=root
   
   # Install WordPress core
   wp core install --url="http://localhost/og-of-wp/wordpress" --title="OG of WP Test" --admin_user=admin --admin_password=admin --admin_email=test@test.com
   
   # Activate our custom proxy loader
   wp plugin activate og-of-wp
   ```
   **Note:** The proxy loader (`wordpress/wp-content/plugins/og-of-wp.php`) dynamically executes the plugin code directly from the root repository, meaning your IDE changes are reflected instantly!

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues page](https://github.com/madlybong/og-of-wp/issues).

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0 or later** — see the [LICENSE](LICENSE) file for details.

---

<div align="center">
Developed with ❤️ by Astrake & Agentic AI
</div>
