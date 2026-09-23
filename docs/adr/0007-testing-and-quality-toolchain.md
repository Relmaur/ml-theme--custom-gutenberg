# 0007. Testing and quality toolchain

- **Status:** Accepted (scope chosen by the owner on 2026-09-23)
- **Date:** 2026-09-23

## Context

The theme had no tests, no linting and no type checking. `tsc` reported 15 errors that no one saw, because esbuild strips types without checking them. The owner asked for an industry-standard suite for PHP and JS, run in CI.

## Decision

| Layer | Tool | Config | Command |
|---|---|---|---|
| JS unit/component tests | Vitest 5 + jsdom + Testing Library | `test` block in `vite.config.js`, `tests/js/` | `npm test` |
| JS coverage floor | @vitest/coverage-v8, **90%** on all metrics | `vite.config.js` | `npm run test:coverage` |
| JS lint | ESLint 9 flat config: typescript-eslint, react, react-hooks | `eslint.config.js` | `npm run lint` |
| Formatting | Prettier 3 (4 spaces, single quotes, 120 cols) | `.prettierrc.json` | `npm run format:check` |
| Types | `tsc --noEmit` (strict) | `tsconfig.json` | `npm run typecheck` |
| PHP static analysis | PHPStan 2 at level **max** + phpstan-wordpress | `phpstan.neon.dist` | `composer analyse` |
| PHP standards | PHPCS: **PSR-12** formatting + WordPress **security/API** sniffs + PHPCompatibilityWP (7.4+) | `phpcs.xml.dist` | `composer lint` |
| PHP unit | PHPUnit 9.6 + Brain Monkey (no WordPress) | `phpunit.xml.dist`, `tests/php/Unit` | `composer test:unit` |
| PHP integration | PHPUnit + wp-phpunit + real WordPress (`roots/wordpress-no-content`) + dedicated MySQL DB | `phpunit-integration.xml.dist`, `tests/php/Integration` | `composer test:integration` |
| CI | GitHub Actions: JS, PHP quality, PHP unit + integration on PHP 7.4 and 8.4 | `.github/workflows/ci.yml` | on push/PR |

Key choices:

- **Vitest over Jest.** It shares the Vite config, so tests compile code exactly as the build does. See ADR 0006.
- **Fake `window.wp` in JS tests** (`tests/js/wp-globals.tsx`). Blocks import `@wordpress/*` through the same externals plugin as production. The stubs are tiny HTML so tests check *our* wiring (attributes, handlers), not Gutenberg.
- **React 18.3** in tests and types: the version WordPress 7.1 ships.
- **Types from the real `@wordpress/*` packages** (dev-only; WordPress provides the runtime), except block-editor, which still needs `@types/wordpress__block-editor`.
- **PHPUnit 9.6**, because `config.platform.php` is pinned to 7.4 so the lock file installs on the theme's minimum PHP. The WordPress 7.1 test library supports it.
- **PSR-12 + selected WPCS rule groups**, not full WordPress-Core: the code uses spaces and PSR-4 names. Every exclusion is commented in `phpcs.xml.dist`.
- **Integration DB via environment variables** (`tests/php/.env.testing`, git-ignored; `.env.testing.example` committed), with an unusual table prefix (`rhtests_`) as a second guard against wiping a real database.

## Trade-offs

- There are two PHPUnit configs, because each needs its own bootstrap.
- The JS stubs can drift from Gutenberg's real components. They only test our side of the contract, and an editor smoke test is still manual.
- PHPUnit 9 is older than the latest (13), because of the PHP 7.4 support. Raising the theme's minimum PHP would allow newer versions.
- Integration tests need MySQL. Locally that means Local's socket; CI uses a MySQL service.

## Consequences

Adopting the tools uncovered, and led to fixing, several real bugs:
- A corrupt or half-written manifest caused a `TypeError` fatal error on every page.
- `render.php` printed `<img src="">` for `javascript:` or `data:` URLs.
- The `script_loader_tag` rewrite dropped inline scripts and translations for Vite scripts. It's now done with `wp_script_attributes`.
- The deprecated `wp-editor` dependency was replaced by the packages the block really imports.
- `formats.tsx` read a ref during render.

New code must keep every check green. Coverage may not drop below 90% (JS).
