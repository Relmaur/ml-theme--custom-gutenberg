# 0001. Use Vite instead of @wordpress/scripts

- **Status:** Proposed (documents a decision already built; waiting for human approval)
- **Date:** 2026-09-23

## Context

The standard WordPress block toolchain is `@wordpress/scripts` (Webpack). It generates `*.asset.php` dependency files and treats `@wordpress/*` imports as externals automatically. Its rebuilds are slow, and it has no true HMR for PHP-rendered pages. The theme author wanted near-instant feedback while developing blocks and global styles.

## Decision

Build every theme asset (global JS/SCSS and each block's editor script, view script and styles) with **Vite 4** (`vite.config.js`):

- One Rollup input per asset, with hashed filenames and `manifest: true`.
- In PHP, `ViteService` detects the dev server on `localhost:3000` and serves assets from it. Otherwise it resolves hashed files through `dist/.vite/manifest.json`, cached in the WP object cache for 24h.
- `@wordpress/*` and `react` imports are turned into virtual modules that read from the `window.wp.*` / `window.React` globals, so WordPress's bundled copies are used and never duplicated.
- JSX/TSX is compiled with esbuild in classic mode (`React.createElement`).

## Trade-offs

- **Gained:** fast dev server and HMR for styles, one config for the whole theme, and native ES modules.
- **Lost:** the automatic dependency extraction and `*.asset.php` files that `wp-scripts` provides. Script dependencies are declared by hand in PHP.
- **Lost:** complete externals. The shim destructures a **hardcoded list** of named exports, so any name missing from it is silently `undefined` at runtime.
- **Lost:** the default toolchain. Upstream block tooling (`create-block`, `wp-scripts lint/test`) doesn't apply directly.

## Consequences

- Every new block needs matching Rollup inputs in `vite.config.js`. A missing input only fails in production.
- Every script served by Vite must be output as `type="module"`. `Enqueue::filterScriptTags()` does this for handles recorded in `ViteService::$moduleHandles`.
- A port collision on 3000 makes production-like environments act as if they're in dev. The dev URL and port are hardcoded constants.
- If Vite is upgraded past v4, check the `css.preprocessorOptions.scss.api` option and the plugin hook signatures again.

## Updates

- **2026-09-23, ADR 0006:**
  - Upgraded to Vite 8 (Oxc and Rolldown).
  - The dev and build externals plugins were merged into one `wordpressExternals()` with a single `wpExportNames` list. The "silently `undefined`" trade-off still applies.
  - Dev-server CORS is now restricted to local origins.
- **2026-09-23, ADR 0007:** `type="module"` is now added through the `wp_script_attributes` filter (`Enqueue::addModuleType`). The old `script_loader_tag` rewrite threw away the tag's `id`, inline scripts and translations.
