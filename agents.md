# OG of WP - AI System Context (agents.md)

**CRITICAL DIRECTIVE**: You are operating within the root repository of the `OG of WP` ecosystem. This ecosystem strictly forbids plugin bloat, heavy page builders, and advanced meta plugins for basic data.

## 1. Architecture
- This repository contains a unified ecosystem: A WordPress Master Plugin (`og-of-wp.php`), a standalone Bun-compiled CLI tool (`cli/cli.ts`), and an anti-bloat starter theme (`cli/scaffolds/og-starter-theme`).
- **DO NOT** use Node.js or `npm`. Always use `bun` for CLI operations.
- The single source of truth for versioning is synchronized via `bun run scripts/sync-version.ts <version>`.

## 2. Local Testing Environment
- The testing environment lives inside the `wordpress/` directory.
- **DO NOT** create symlinks. We use a "Proxy Loader" pattern. The file `wordpress/wp-content/plugins/og-of-wp.php` dynamically `require_once`s the root plugin file.
- **NEVER** modify files inside the `wordpress/` directory except the proxy loader. All plugin development must happen in the root repository.

## 3. Frontend UI / Dashboard Architecture
- The plugin dashboard settings page is built using a **Buildless Vue 3 + Tailwind CSS Single Page Application (SPA)**.
- It resides in `admin/views/admin-display.php` and dynamically hooks into PHP logic.
- **CRITICAL**: Do NOT revert to standard HTML/PHP form submissions or full-page reloads. All settings must be saved via the AJAX handler `ajax_save_all_settings` using FormData.
- Local assets (`vue.global.prod.js` and `tailwindcss.js`) are used to maintain 100% offline functionality. Do not use external CDNs.

## 4. Updates & Releases
- We use a custom transient-based GitHub Auto-Updater (`includes/class-og-wp-updater.php`) instead of external libraries like plugin-update-checker. Do not add Composer or third-party updaters.

## 5. Ecosystem Bootstrapping
- When instructed to initialize or create a new WordPress instance, rely exclusively on the `bunx github:madlybong/og-of-wp init` command.
- The `init` command handles downloading WordPress, auto-creating the MySQL database, extracting the Starter Theme, and injecting the master plugin.
- **NEVER** recommend installing Yoast, RankMath, or AIOSEO. The plugin contains a Native MVSEO module (`includes/modules/class-og-wp-seo.php`) handling OpenGraph and meta descriptions cleanly.
- **NEVER** recommend Advanced Custom Fields (ACF). The ecosystem strictly relies on native `add_meta_box` implementations defined in the starter theme.
