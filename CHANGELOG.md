# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
