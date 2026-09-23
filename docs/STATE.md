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

## Upcoming: taw/core data layer (announced 2026-09-23, NOT started)

The TAW umbrella session (taw-13) plans to make this theme the first "data-only" consumer of `taw/core`.

- Plan: `~/Documents/TAW/docs/plans/data-layer.md`
- Draft ADRs: `~/Documents/TAW/taw-core/docs/adr/0003-data-layer-and-boot-split.md` and `0004-schema-registry-php-and-json.md`

Nothing changes here until taw/core ships v1.42–v1.44. The owner confirms each step, and "Lesson or Implement?" applies to each.

**Phase 1, Step 5 (in this repo):**
1. Raise the PHP minimum from 7.4 to 8.2, with a new ADR here.
2. `composer require taw/core`, booted through a new `Bootable` that calls `\TAW\Core\Boot::data()`.
3. Add a sample `taw-schema/` (book, genre, book_details) as a test fixture.

**Track G (separate):** rename the theme to `taw-gutenberg`:
- namespace `RigidHybrid\` → `TAW\Gutenberg\`
- block prefix `my-theme/` → `taw-gutenberg/`, with a content migration
- add the theme to the TAW umbrella

After that come Block Bindings (`taw/field`) and a loop block.

### What each step touches here (checked 2026-09-23)

**PHP 8.2 minimum.** Update all of these together, and record the change in an ADR that amends ADR 0007:
- `composer.json` → `config.platform.php` (7.4.33), then regenerate `composer.lock`
- CI matrix `['7.4', '8.4']`, in both PHP jobs
- `phpstan.neon.dist` → `phpVersion.min`
- `phpcs.xml.dist` → `testVersion` "7.4-"
- the `style.css` header (`Requires PHP`)
- the README requirements, the AGENTS.md stack table, and ADR 0007

PHPUnit 9.6 was only chosen because of PHP 7.4. Consider moving to a newer PHPUnit then, but first check which versions the WordPress 7.1 test library (wp-phpunit) supports.

**Booting taw/core.** Two things to watch:
- `tests/php/Unit/ThemeTest` creates EVERY service in `Theme::$services` under Brain Monkey (no WordPress). A new `Bootable` that calls `Boot::data()` directly in `register()` would run real taw/core code in unit tests. Instead, hook the call to an action (e.g. `after_setup_theme`) so `register()` only adds hooks, as ADR 0003 in this repo requires.
- Production deploys use `composer install --no-dev` (README), so taw/core must be in `require`, not `require-dev`.

**Rigid mode vs. taw data: ✅ DECIDED 2026-09-23 (ADR 0008).**
- Part 1 is done: rigid mode only covers the `rigid_hybrid/rigid_post_types` list (default `['page']`), so a `book` post type gets the normal editor.
- Part 2 is planned in taw/core: per-post-type editing policies with their own boot switch, after which rigid mode becomes a preset. taw-13 was asked to add this to the roadmap.

Original analysis, kept for context:

ADR 0005 makes rigid mode apply to EVERY post type. Rigid mode currently:
- allows only `my-theme/*` blocks
- starts new posts with a Hero
- gives users below administrator `templateLock: 'all'`

That clashes with:
- the planned `book` post type and its fields (editing a book would be limited to theme blocks, and a new book would start with a Hero)
- Phase 4 Block Bindings, which bind **core** blocks (paragraph, heading, image) that rigid mode doesn't allow

Likely fix: make rigid mode's post types configurable (the "later extension" noted in ADR 0005), or leave data post types out.

**Track G rename: things that break or need a migration.**
- `my-theme/` is hardcoded in:
  - `ThemeMode::BLOCK_NAMESPACE` and `DEFAULT_LAYOUT`
  - the format names in `formats.tsx` and `edit.tsx` `allowedFormats`
  - `block.json`
  - JS and PHP tests (e.g. the `wp-block-my-theme-hero` class assertion)

  Saved posts contain `<!-- wp:my-theme/hero -->`, so they need migrating. Format names are NOT saved in content (only tags and classes), so formats need no migration.
- Renaming the theme folder deactivates the theme (the `template`/`stylesheet` options) and loses its saved settings, such as menu locations (`theme_mods_<folder>`). Plan: reactivate it and copy the settings over.
- Names that other code relies on:
  - the `RIGID_HYBRID_MODE` constant (in each client's wp-config)
  - the `rigid_hybrid/mode` filter

  Keep the old names working (or deprecate them) instead of removing them.
- Internal names that can change freely:
  - the `rigid_hybrid_vite_manifest` cache key
  - the `rigid-theme-main` handle
  - the `rigid-hybrid` text domain
  - the PHPCS `PrefixAllGlobals` prefixes
- `tests/php/bootstrap-integration.php` and the CI checkout already work out the theme folder name automatically, so the rename needs no change there.

## Next steps

1. Human review of ADRs 0001–0004, 0006 and 0007.
2. Replace the placeholder `header.php` / `footer.php` markup (the plain "Header" and "Footer" text) with real site navigation. It could use the registered `primary_menu` / `footer_menu`.
3. Owner: review and merge the `feat/rigid-mode-post-types` PR (ADR 0008 Part 1 + the STATE notes).
4. When taw-13 starts Step 5: the rigid-mode conflict is already solved (ADR 0008). Follow the checklist above.
