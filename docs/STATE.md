# Project State

_Last updated: 2026-09-23_

## Completed

- **PHP architecture (OOP).**
  - Composer PSR-4 autoload (`RigidHybrid\` → `app/`).
  - `Theme` service registry and `Bootable` interface.
  - Services: `ThemeSetup`, `ThemeMode`, `Enqueue`, `BlockRegistry`, `ViteService`.
- **Vite integration.**
  - Vite 8 (ADR 0006).
  - Dev-server detection.
  - A validated manifest, cached in the object cache for 24h.
  - `type="module"` added through `wp_script_attributes`.
  - Per-block style registration through `ViteService::registerStyle()`.
- **Hero block** (`my-theme/hero`):
  - TypeScript, API v3, rendered by `render.php`.
  - RichText formats in `formats.tsx`, fully typed.
- **Opt-in rigid mode** (ADR 0005, Accepted). The owner tested it in the editor as an administrator and as an editor.
- **Test and quality suite** (ADR 0007), all green on 2026-09-23:

  | Area | Check | Result |
  |---|---|---|
  | JS | Vitest | 30 tests, ~98% coverage (90% floor) |
  | JS | ESLint | 0 problems |
  | JS | Prettier | clean |
  | JS | `tsc` | 0 errors |
  | JS | build | OK |
  | PHP | PHPStan (level max) | 0 errors |
  | PHP | PHPCS | clean |
  | PHP | PHPUnit unit | 42 tests |
  | PHP | PHPUnit integration | 32 tests, on WordPress 7.1.2 against the `wp_tests` database |
  | CI | `.github/workflows/ci.yml` | JS, PHP quality, and PHP unit + integration on 7.4 and 8.4 |

  Deliberately breaking the code made the matching tests fail, in both JS and PHP.
- **Bugs found by the new tooling and fixed:**
  - A corrupt manifest caused a fatal error on every page.
  - An `<img src="">` was printed for unsafe image URLs.
  - Inline scripts and translations were dropped for Vite scripts.
  - The editor script depended on the deprecated `wp-editor` handle.
  - `formats.tsx` read a ref during render.
- **Environment:**
  - The theme is active on the local site.
  - `wp` works from anywhere under `app/public`, via `app/public/wp-cli.yml` and `.wp-cli/local-db-socket.php`, which live outside this repo.
  - The integration-test DB is `wp_tests` in Local's MySQL. Local settings are in `tests/php/.env.testing` (git-ignored).

## Known issues and blockers

1. **`npm audit`: 3 moderate advisories**, all in `@types/wordpress__block-editor`'s type-only dependency tree. That code never ships.
2. **`wp db …` doesn't work locally.** It shells out to the `mysql` client, which can't find Local's socket. Use `wp eval` with `$wpdb`.

History: PR #1 (rigid mode, Vite 8, test suite, cleanup) was merged into `master` on 2026-09-23 with all 6 CI jobs green.

## taw/core: this theme is NOT a consumer (updated 2026-09-23)

The TAW plan changed. This theme stays a standalone learning project:
- no rename
- no PHP 8.2 bump
- no `taw/core`
- no block migration

The Gutenberg consumer of taw/core is a **new** theme, `taw-gutenberg` (repo `Relmaur/taw-gutenberg`, a TAW umbrella submodule, run on the `taw` Local site). It's built fresh and doesn't copy this theme.

What this theme contributed:
- the rigid-mode analysis
- the "editing policies" proposal, now item E on the taw/core roadmap (taw-gutenberg adopts it first)

This theme's `ThemeMode.php` and its tests are the reference implementation. The PHP 7.4 minimum, the `RigidHybrid\` namespace and the `my-theme/` block prefix all stay as they are.

## Next steps

1. Human review of ADRs 0001–0004, 0006 and 0007.
2. Replace the placeholder `header.php` / `footer.php` markup (the plain "Header" and "Footer" text) with real site navigation. It could use the registered `primary_menu` / `footer_menu`.
3. Owner: decide whether to push and merge `feat/rigid-mode-post-types` (ADR 0008). It's still worthwhile without taw/core: in rigid mode, blog posts get the normal editor again.
