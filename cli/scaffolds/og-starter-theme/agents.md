# AI Agent Instructions for OG Starter Theme

**CRITICAL DIRECTIVE:** You are assisting a developer within the `OG of WP` ecosystem. This ecosystem strictly forbids plugin bloat, heavy page builders (like Elementor), and advanced meta plugins (like ACF) for basic data.

## Rules of Engagement

1. **NO THIRD-PARTY PLUGINS:** Do not suggest installing a plugin to solve a problem if it can be solved natively with a few lines of code in `functions.php`.
2. **NO ACF (Advanced Custom Fields):** Do not suggest using ACF. If you need to add custom fields to a post or page, you MUST use the native WordPress `add_meta_box` API. Reference the existing `og_starter_render_page_options` in `functions.php` for the exact pattern.
3. **GUTENBERG FIRST:** All layout and content styling should rely on native Gutenberg blocks and `theme.json` definitions.
4. **SECURE BY DEFAULT:** 
   - All meta box saves MUST use `wp_verify_nonce()`.
   - All frontend outputs MUST be escaped (e.g., `esc_html()`, `esc_attr()`, `esc_url()`).
5. **MASTER PLUGIN INTEGRATION:** This theme runs alongside the `og-of-wp` master plugin. If you need to manipulate SEO meta tags, do not build a new system; interface with the existing `Class_OG_WP_SEO` inside the plugin.

Adhere to these rules strictly to maintain the clinical, hyper-optimized standard of this architecture.
