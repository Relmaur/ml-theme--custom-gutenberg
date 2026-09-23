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

1. ~~First CI run~~ **Done 2026-09-23:** all 6 jobs passed on PR #1 (`chore/test-suite-vite-8`), including unit and integration tests on PHP 7.4 and 8.4.
2. ~~Editor smoke test~~ **Done 2026-09-23:** the owner confirmed the Hero, the accent format and color support in the editor and on the frontend.
3. **`README.md` is stale.** It references `inc/`, `register_theme_block()`, `.jsx` and apiVersion 2.
4. **Placeholder metadata:**
   - `style.css` has "Your Name", example.com URIs and `Requires at least: 6.0` (6.6 is really needed).
   - `package.json` is named `my-hybrid-theme`.
   - `header.php` has a malformed profile link, and its `<main>`/`<footer>` nesting is off.
5. **`src/utils/wp-react*.js` are unused.** They're legacy shims, and one lint line is disabled for them. Deleting them needs the owner's OK.
6. **`npm audit`: 3 moderate advisories**, all in `@types/wordpress__block-editor`'s type-only dependency tree. That code never ships.
7. **`wp db …` doesn't work locally.** It shells out to the `mysql` client, which can't find Local's socket. Use `wp eval` with `$wpdb`.

## Next steps

1. Owner: review and merge PR #1 (https://github.com/Relmaur/ml-theme--custom-gutenberg/pull/1).
2. Human review of ADRs 0001–0004, 0006 and 0007.
3. Rewrite `README.md`, or reduce it to a pointer to `AGENTS.md`. Fix the placeholder metadata.
4. Decide whether to delete `src/utils/wp-react*.js`.
