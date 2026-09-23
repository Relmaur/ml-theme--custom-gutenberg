# AGENTS.md — Rigid Hybrid Theme

Entry point for any AI agent or developer. Read this first, then only the docs relevant to your task.

## Documentation index

| File | Read when |
|------|-----------|
| `docs/STATE.md` | **Always, at session start.** Current progress, blockers, next steps. Update it before ending a session. |
| `docs/adr/` | Before changing build tooling, block architecture, or PHP service structure. |
| `README.md` | Human-facing overview: setup, modes, deploying. Agents: this file is the more detailed source. |

## Working mode: this is a learning project

The owner is learning by building this theme alongside a course. **Before starting each feature, ask the owner (with AskUserQuestion) which of these they want:**

- **Lesson (the owner types it):** present the code **in the conversation** as small, commented, step-by-step snippets (file path + where it goes), explaining *why* before *what*. Don't edit source files. Once the owner has typed it, review it and verify it (`npm run check`, `composer check`).
- **Implement (the agent writes it):** edit the files directly, still with teaching-quality *why* comments. Point out anything instructive, such as mistakes in partially typed code.

Either way, agents maintain `docs/` and this file directly.

## What this is

A WordPress theme (`mlizardo/rigid-hybrid`, text domain `rigid-hybrid`) with a **"Rigid Hybrid"** philosophy:

- **Two modes** (ADR 0005), set per install with `define('RIGID_HYBRID_MODE', 'rigid' | 'builder');` in `wp-config.php` (default `builder`) and handled by `Setup/ThemeMode.php`:
  - **builder** — clients compose pages from core blocks and theme blocks, with appearance tools on.
  - **rigid** (opt-in) — design settings are locked site-wide. On **rigid post types** (default `page`; `rigid_hybrid/rigid_post_types` filter, ADR 0008), the inserter offers only `my-theme/*` blocks and users below administrator get `templateLock: 'all'`. Administrators arrange each page's layout; editors change content and images. Other post types keep the normal editor.
- **Hybrid** — classic PHP templates (`header.php`, `footer.php`, `index.php`) render the page shell; `post_content` is made of custom blocks.
- Blocks are **dynamic**: `save` returns `null` and `render.php` renders the frontend (see ADR 0004).

## Stack and verified versions

Check the manifests before writing framework-specific code. Versions installed as of 2026-09-23:

| Tool | Version | Notes |
|------|---------|-------|
| Node | ≥ 22.12 | required by Vite 8 / Vitest 5 (`engines` in `package.json`) |
| Vite | 8.3 (Oxc + Rolldown) | ADR 0006 |
| TypeScript | 5.9 | `strict`, checked by `npm run typecheck` |
| Sass | 1.105 | modern API only |
| React (tests + types) | 18.3 | matches the React WordPress 7.1 ships |
| `@wordpress/blocks`, `components`, `rich-text`, `element` | pinned to installed versions | **dev-only, for types**; runtime comes from WP globals |
| `@types/wordpress__block-editor` | 15.x | block-editor ships no types of its own |
| Vitest / ESLint / Prettier | 5 / 9 / 3 | ESLint stays on 9: eslint-plugin-react doesn't support 10 |
| PHP | 8.4 locally; **7.4 minimum** | `config.platform.php` = 7.4, so the lock file installs on 7.4 |
| PHPUnit / PHPStan / PHPCS | 9.6 / 2.2 (level max) / 3.13 + WPCS 3.4 | ADR 0007 |
| WordPress | 7.1.2 locally; needs ≥ 6.6 | block.json **apiVersion 3**, theme.json **v3** |

`@wordpress/*` packages are never bundled. Imports are mapped to `window.wp.*` globals (see "Gotchas").

## Commands

```bash
composer install         # REQUIRED — functions.php wp_die()s without vendor/autoload.php
npm install

npm run dev              # Vite dev server on http://localhost:3000 (strictPort)
npm run build            # Production build → dist/ + dist/.vite/manifest.json

# JS quality (all run in CI)
npm run check            # = lint + format:check + typecheck + test + build
npm test                 # Vitest once   (npm run test:watch while developing)
npm run test:coverage    # fails under 90% coverage
npm run lint / lint:fix  # ESLint
npm run format           # Prettier --write
npm run typecheck        # tsc --noEmit

# PHP quality (all run in CI)
composer check           # = lint + analyse + test:unit + test:integration
composer lint / lint:fix # PHPCS / PHPCBF
composer analyse         # PHPStan (level max)
composer test:unit       # PHPUnit, no WordPress (Brain Monkey)
composer test:integration # PHPUnit on real WordPress; needs tests/php/.env.testing
```

**Integration-test database:** copy `tests/php/.env.testing.example` to `tests/php/.env.testing`. Locally it points at the **`wp_tests`** database in Local's MySQL. The test suite wipes that database on every run, so never point it at `local`.

**WP-CLI on this Local site:** plain `wp …` works from anywhere under `app/public`. `app/public/wp-cli.yml` loads `.wp-cli/local-db-socket.php`, which points PHP at Local's MySQL socket. `wp db …` subcommands shell out to the `mysql` client and still can't find the socket, so use `wp eval` with `$wpdb` instead.

## Layout

```
functions.php            Loads Composer autoload, boots RigidHybrid\Theme
app/                     PSR-4 root (namespace RigidHybrid)
  Theme.php              Service registry: instantiates services, calls register()
  Core/Bootable.php      Interface: register(): void — hooks only, no side effects
  Setup/ThemeSetup.php   theme supports + nav menus
  Setup/ThemeMode.php    builder/rigid mode: theme.json overlays, allowed blocks, layout lock (ADR 0005)
  Setup/Enqueue.php      global assets + type="module" via wp_script_attributes
  Setup/BlockRegistry.php  registers blocks + per-block assets, custom "theme-blocks" category
  Services/ViteService.php dev-server detection, manifest (object-cached, validated), script/style registration
src/
  js/main.js, scss/main.scss   global frontend entry
  blocks/<slug>/               one folder per block (see "Adding a block"); *.test.ts(x) live next to the code
theme.json               shared design tokens only (mode settings live in ThemeMode)
vite.config.js           entries, WP externals shim, JSX (Oxc), Vitest config
tests/js/                Vitest setup + fake window.wp (wp-globals.tsx)
tests/php/Unit/          PHPUnit + Brain Monkey (UnitTestCase base class)
tests/php/Integration/   PHPUnit on real WordPress (WP_UnitTestCase)
.github/workflows/ci.yml all checks on push/PR
```

## Architecture rules

1. **PHP services.** Each new hook-registering class implements `RigidHybrid\Core\Bootable`, is added to `Theme::$services`, and only calls `add_action` / `add_filter` inside `register()`. Put the work in public callback methods (ADR 0003).
2. **All asset URLs go through `ViteService`.** Don't hardcode `dist/` filenames; they're hashed. In dev, assets load from `ViteService::VITE_SERVER`. In production they're resolved from the manifest by their **source path** key (e.g. `src/blocks/hero/view.ts`).
3. **Blocks are dynamic.** `save.tsx` returns `null`; `render.php` owns the frontend markup. Keep `edit.tsx` markup in sync with `render.php` so the editor preview matches the frontend.
4. **Don't put `editorScript`, `viewScript`, `style` or `editorStyle` in `block.json`.** `BlockRegistry::registerThemeBlock()` registers those handles in PHP so Vite can serve them.
5. **Blocks must work in both modes.** Restrict `RichText` with `allowedFormats` and put structured data in `InspectorControls`. In rigid mode, editors can still use sidebar fields and media on locked blocks.
6. **Mode-specific editor settings go in `ThemeMode`, not `theme.json`.** `theme.json` holds only shared tokens. `appearanceTools: true` in `theme.json` can't be switched off again by a later merge.

## Adding a block

1. Create `src/blocks/<slug>/` with `block.json` (apiVersion 3, category `theme-blocks`, `"render": "file:./render.php"`), `index.tsx`, `edit.tsx`, `save.tsx` (returns `null`) and `render.php`. Optionally add `style.scss`, `editor.scss` and `view.ts`.
2. Add `$this->registerThemeBlock('<slug>');` in `BlockRegistry::registerBlocks()`.
3. Add every file that exists as an input in `vite.config.js` → `build.rolldownOptions.input` (`block-<slug>`, `-view`, `-style`, `-editor`). **A missing input only fails in production** because the manifest has no key for it.
4. Add tests:
   - `edit.test.tsx`, plus `view.test.ts` if the block has a view script, next to the block. Stub any new WordPress component in `tests/js/wp-globals.tsx`.
   - A render test in `tests/php/Integration/Blocks/` that includes hostile attribute values.

## Gotchas

- **WP externals shim (`vite.config.js`).** `@wordpress/*` and `react` imports become virtual modules that destructure a **hardcoded list**, `wpExportNames`, from `window.wp.*`. If you import a name that isn't on that list (e.g. `ToolbarButton`), it will be `undefined` at runtime with no build error. Add it to `wpExportNames`, then add a stub to `tests/js/wp-globals.tsx`.
- **Don't import `react` in test setup files.** It resolves to `window.React`, which is what the setup is creating. `tests/js/setup.ts` uses `createRequire` to get the real React instead.
- **JSX is classic** (`React.createElement`). Every `.tsx` file using JSX must `import React from 'react'`. ESLint enforces this.
- Enqueue WordPress scripts through `ViteService::enqueueAsset()`: the handle is recorded, and `Enqueue::addModuleType()` adds `type="module"` through `wp_script_attributes`. Don't use `script_loader_tag` for this, because it would discard inline scripts and translations.
- `wp_register_*` for Vite files omit `ver` on purpose: file names already contain a content hash.
- **Dev-server CORS** only allows `localhost`, `127.0.0.1` and `*.local` origins (`vite.config.js` → `server.cors`).
- **WordPress test library quirk:** `WP_UnitTestCase::tear_down()` removes `html5` theme support after every test (see `ThemeBootTest`).
- `vendor/` holds dev tools (and a copy of WordPress core for tests). Production deploys should run `composer install --no-dev`.
- `ViteService::isDevServerRunning()` probes `localhost:3000` once per request. If anything else is listening on port 3000, the site behaves as if it's in dev mode.
- The PHP-reload Vite plugin exists but is commented out in `plugins`.

## Coding standards

- PHP:
  - `declare(strict_types=1);`, typed params and returns, PHPDoc with generics (PHPStan level max).
  - Escape at output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for RichText attributes).
  - Never trust `$attributes` or JSON from disk: check types with `is_string()` / `is_array()` before use.
  - Keep PHP 7.4 syntax (no union types, no `str_starts_with`). PHPCS enforces this.
- TypeScript: `strict` mode. Type block attributes with a `type` (not an `interface`) exported from `edit.tsx`.
- Formatting: Prettier (JS/TS/SCSS/JSON) and PSR-12 (PHP). Run `npm run format` and `composer lint:fix`.
- Every behavior change comes with tests, including edge cases and failure modes. CI must stay green.
- Comments explain *why* (domain constraints and trade-offs), not *what*.
- Commits follow Conventional Commits: `feat(scope): …`, `fix(scope): …`, `refactor(scope): …`.
- Record structural or tooling decisions as an ADR in `docs/adr/NNNN-title.md` (Title, Status, Context, Decision, Trade-offs, Consequences).
