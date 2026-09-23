# 0006. Upgrade Vite 4 → 8

- **Status:** Accepted (the owner chose "Vitest + upgrade Vite" on 2026-09-23)
- **Date:** 2026-09-23
- **Amends:** ADR 0001

## Context

Vite 4.5 was out of support and its dev server lacked later security fixes. Current Vitest (5.x) needs Vite ≥ 6.4. Vite 8 replaced esbuild with Oxc (transforms) and Rollup with Rolldown (bundling).

## Decision

Upgrade to **Vite 8.3** and adapt `vite.config.js`:

- **JSX:** the custom esbuild plugin is replaced by `oxc.jsx` with the classic runtime (`React.createElement`), which is still required because WordPress exposes React as a global.
- **Bundler options:** `build.rollupOptions` becomes `build.rolldownOptions`, and the Sass `api` option is dropped (the modern API is now the only one).
- **Externals:** the two identical dev/build WordPress-externals plugins are merged into **one** `wordpressExternals()` plugin, used by dev, build and Vitest alike. The export-name list is kept in one place, `wpExportNames`, which now also includes the rich-text and format names.
- **Dev server CORS:** `server.cors` is restricted to `localhost`, `127.0.0.1` and `*.local` instead of `true`. Otherwise any website could read source files from a running dev server.
- **ESM:** `package.json` gets `"type": "module"` and the config uses `import.meta.dirname`.

## Trade-offs

- Node **≥ 22.12** is now required by the whole toolchain (Vite 8 and Vitest 5).
- Rolldown/Oxc are newer than Rollup/esbuild, so ecosystem plugins may lag.
- A WordPress site on a domain other than `*.local` needs a change to the `cors.origin` pattern.

## Consequences

- Manifest keys are unchanged (source paths), so PHP needed no change to find assets. The manifest now lives only at `dist/.vite/manifest.json`, and `ViteService` still falls back to `dist/manifest.json`.
- Built file hashes use a new format (`main-DYe9SpKI.js`), which is harmless.
