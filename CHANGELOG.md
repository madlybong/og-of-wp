# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-10-04

### Added
- **Native Auto-Updater**: Built a lightweight, zero-dependency WordPress auto-updater that hooks into WordPress's native transient system to securely fetch and install updates directly from GitHub Releases.
- **Vue 3 UI Overhaul**: Replaced the legacy PHP/HTML settings page with a modern, reactive, buildless **Vue 3 + Tailwind CSS** Single Page Application (SPA). Ensured seamless offline local support and AJAX-based saving without page reloads.
- **Contact Form 7 Integration**: Added native Contact Form 7 integration, enabling a robust local database capture system for form submissions and easy toggle controls in settings.
- **Settings JSON Porter**: Re-introduced and improved the JSON Import/Export capability (Porter module) for effortlessly migrating plugin settings across environments.
- **GitHub Release Automation Support**: Automated version tagging and GitHub Action release pipelines are now natively integrated to feed the new updater.

### Fixed
- **Scanner Reliability**: Resolved PHP execution timeouts during local and cloud-based website scans by dynamically enforcing execution limits and forcing correct AJAX registration.
- **Local Dev URLs**: Fixed local proxy URL resolution bugs (`plugins_url()`) that broke asset paths when loaded from outside the standard `wp-content/plugins` directory.
- **CLI Enhancements**: Fixed automatic executable path detection (MySQL, 7-Zip) and improved environmental prompt handling to cleanly support CI pipelines.

## [1.0.1] - 2026-10-03

### Added
- **Premium Email Module**:
  - Full Asynchronous Queue architecture with 0ms background sending loopbacks.
  - Smart Multi-Routing conditional rules (route by subject, domain, sender).
  - High-availability Automated Failover Matrix (Primary API -> Backup SMTP).
  - Pure PHP Amazon SES integration via AWS SigV4 (no SDK bloat).
  - Deliverability Health Scorecard with live DNS SPF/DMARC checks.
- **Unified Ecosystem Testing & CLI Enterprise Refactor**:
  - Added a self-contained local testing architecture using a proxy loader pattern and Bun sync scripts to synchronize releases seamlessly.
  - **Memory Safety & Schema Conflicts**: Re-engineered SQL search-and-replace to use line-by-line asynchronous stream processing (`readline`), eliminating OOM crashes on large database dumps (>1GB). Also injected dynamic `DROP TABLE IF EXISTS` operations for every `CREATE TABLE` to reliably fix `ERROR 1050 (42S01)` import conflicts during `db:pull`.
  - **Context-Aware Prompting**: Isolated environment loading (`getLocalEnv` vs `getProdEnv`), eliminating redundant production credential prompts during local commands like `backup` and `db:pull`.
  - **Password Confirmation**: Added validation and confirmation loops for sensitive inputs to prevent lockout from accidental typos.
  - **Side-by-Side Exports**: Separated SQL database dump from the WordPress ZIP archive so both files output side-by-side in the working directory ready for File Manager and phpMyAdmin.
  - **Offline Security**: Replaced external WordPress.org salt API HTTP calls with zero-dependency native `crypto.randomBytes` salt generation.
  - **Dynamic White-Labeling**: Decoupled legacy hardcoded project names, establishing dynamic `PROJECT_SLUG` branding across all commands.

## [1.0.0] - 2026-10-03

### Added
- **Initial release of OG of WP** hybrid repository combining the WordPress core plugin and high-speed Deploy CLI.
- **15 Modular Security & Site Management Engines**:
  - `Auth`: Two-factor authentication (2FA), brute-force login lockdown, login logs, and captcha verification.
  - `WAF`: Advanced Web Application Firewall with IP blocking, query string inspection, and bad bot / scraper mitigation.
  - `Audit`: Real-time user event and security audit trail recorded into a custom dedicated SQL table.
  - `Hardening`: Instant disablement of XML-RPC, WordPress version disclosure removal, and REST API access controls.
  - `Headers`: Automated injection of strict HTTP security headers (HSTS, CSP, X-Frame-Options, Permissions-Policy).
  - `SSL`: Automatic HTTPS redirection and mixed-content enforcement.
  - `Files`: File change monitoring, filesystem integrity audits, and critical core file checks.
  - `Scanner`: Heuristic malware and back-door scanner across theme and plugin files.
  - `Spam`: Local anti-spam filter for comments and contact forms without third-party API dependencies.
  - `DB`: Database health analyzer, table optimization, defragmentation, and overhead cleaner.
  - `User`: User privilege audit, inactive account detection, and active session manager.
  - `Duplicator`: One-click deep cloner for posts, pages, and custom post types preserving full meta fields.
  - `Porter`: Portable JSON-based export and import system for plugin configurations across environments.
  - `Media Cleaner`: Scans and detects orphaned, unattached media assets to free disk space.
  - `Branding`: Complete white-label admin customization, custom login logo, styling, and dashboard branding.
- **Cross-Platform Deploy CLI**:
  - `og-deploy package`: Builds a cPanel-ready deployment ZIP archive with automated local database snapshot and SMTP configuration.
  - `og-deploy db:push`: Dumps local WordPress MySQL database, dynamically rewrites domain URLs, and preps for remote import.
  - `og-deploy db:pull`: Converts remote SQL dump to match local environment variables and auto-imports.
  - `og-deploy backup`: Creates full point-in-time timestamped backup archives of code and database.
  - Standalone binary compilation with Bun for Windows, macOS, and Linux without requiring runtime installs.
- **GitHub Actions Release Pipeline**:
  - Automated building of clean, production-ready WordPress plugin ZIP (`og-of-wp-v1.0.0.zip`) excluding developer and CLI files.
  - Multi-platform standalone binary compilation (`og-deploy.exe`, `og-deploy-linux`, `og-deploy-macos`).
  - Automatic GitHub Release creation and asset publishing on every git tag.
