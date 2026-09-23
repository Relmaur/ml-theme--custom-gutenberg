# Rigid Hybrid

A WordPress theme that pairs **classic PHP templates** for the page shell (`header.php`, `footer.php`) with **custom React blocks** for the content, built with Vite and written in TypeScript and OOP PHP.

One theme, two ways to hand it to a client:

| Mode | For clients who… | What editors get |
|------|------------------|------------------|
| **builder** (default) | want to build their own pages | Core blocks + theme blocks, full design tools |
| **rigid** (opt-in) | just want to update text and images | On pages: only theme blocks and a **locked layout** for everyone below Administrator. Site-wide: brand palette only |

In rigid mode, an Administrator arranges each page once. Editors can then change the content and images inside it, but can't add, move or remove blocks. Other post types (blog posts, data post types) keep the normal editor.

---

## Requirements

| | Version |
|---|---|
| WordPress | 6.6+ (developed on 7.1) |
| PHP | 7.4+ |
| Node.js | 22.12+ |
| Composer | 2 |

## Quick start

```bash
cd wp-content/themes/ml-theme--custom-gutenberg
composer install      # the theme won't load without vendor/autoload.php
npm install
npm run build         # or `npm run dev` while developing
```

Then activate **Rigid Hybrid** under *Appearance → Themes*.

### Choosing the mode

The mode is set per install in `wp-config.php`, so a client can't switch it by accident:

```php
define('RIGID_HYBRID_MODE', 'rigid');   // omit (or 'builder') for the open block builder
```

Any other value falls back to `builder` and shows a notice when `WP_DEBUG` is on. Child themes or mu-plugins can also override the mode through the `rigid_hybrid/mode` filter.

Rigid mode locks **pages** by default. To lock other post types too (or fewer), use a filter:

```php
add_filter('rigid_hybrid/rigid_post_types', fn (array $types) => [...$types, 'post']);
```

Details: [ADR 0005](docs/adr/0005-opt-in-rigid-mode.md) and [ADR 0008](docs/adr/0008-rigid-mode-per-post-type-and-taw-core-editing-policies.md).

## Development

```bash
npm run dev     # Vite dev server on http://localhost:3000, with hot reload
npm run build   # production build → dist/ (hashed files + manifest)
```

PHP detects the dev server automatically. If it's running, assets load from `localhost:3000`; otherwise they come from `dist/` through Vite's manifest. The dev server only accepts requests from local sites (`localhost`, `*.local`).

WordPress already ships React and the `@wordpress/*` packages. Imports of them are mapped to those globals (`window.wp.*`), not bundled. If you import a name that isn't in `wpExportNames` in `vite.config.js`, it will be `undefined` at runtime. See the Gotchas section in [AGENTS.md](AGENTS.md).

### Project structure

```
functions.php        loads Composer, boots RigidHybrid\Theme
app/                 PHP services (PSR-4, namespace RigidHybrid)
src/blocks/<slug>/   one folder per block: block.json, edit.tsx, render.php, tests…
src/js, src/scss     global frontend assets
tests/js, tests/php  test setup, PHP unit and integration tests
theme.json           shared design tokens (palette, layout sizes)
docs/                project state and architecture decisions (ADRs)
```

### Adding a block

1. Create `src/blocks/<slug>/` containing:
   - `block.json`: apiVersion 3, category `theme-blocks`, `"render": "file:./render.php"`
   - `index.tsx`, `edit.tsx`
   - `save.tsx`, which returns `null`
   - `render.php`
   - Optionally `style.scss`, `editor.scss` and `view.ts`.
2. Register it in `app/Setup/BlockRegistry.php` → `registerBlocks()`.
3. Add its files as build inputs in `vite.config.js`. A missing entry only fails in production.
4. Add tests next to the block (`edit.test.tsx`) and a render test in `tests/php/Integration/Blocks/`.

Blocks are **dynamic**: PHP renders the frontend, so changing the markup never breaks saved content. Use the `my-theme/` name prefix: in rigid mode, that prefix decides which blocks are allowed. The full checklist is in [AGENTS.md](AGENTS.md#adding-a-block).

## Quality checks

Everything below runs in CI (`.github/workflows/ci.yml`) on every push and pull request, including on PHP 7.4 and 8.4.

```bash
npm run check        # ESLint, Prettier, TypeScript, Vitest, build
composer check       # PHPCS, PHPStan (max), PHPUnit unit + integration
```

Other useful commands: `npm run test:watch`, `npm run test:coverage` (fails below 90%), `npm run format`, `composer lint:fix`, `composer test:unit`.

### Integration tests need a database

`composer test:integration` runs the PHP tests against a real WordPress. To set it up:

1. Create an **empty, dedicated** database. The test suite wipes it on every run.
2. Copy `tests/php/.env.testing.example` to `tests/php/.env.testing` (it's git-ignored).
3. Fill in the connection details. On *Local*, use the site's MySQL socket, as the example file explains.

## Deploying

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

Ship the theme folder without `node_modules/`, `tests/` or `src/**/*.test.*`. The browser needs `dist/`, and PHP needs `app/`, `src/blocks/**/render.php`, `src/blocks/**/block.json` and `vendor/`.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| "Composer dependencies not installed" | Run `composer install` in the theme folder. |
| Block missing from the editor in production | Its files aren't in `vite.config.js` inputs, or `npm run build` wasn't run. |
| A `@wordpress/*` component is `undefined` | Add its name to `wpExportNames` in `vite.config.js`. |
| Only the Hero block can be inserted | The site is in rigid mode (`RIGID_HYBRID_MODE`) and this post type is rigid (default: pages). |
| Editors can't add or move blocks | Rigid mode locks layouts below Administrator; that's intended. |

## Further reading

- [AGENTS.md](AGENTS.md): conventions, architecture rules, gotchas and exact tool versions
- [docs/adr/](docs/adr/): why things are the way they are (Vite, TypeScript, services, dynamic blocks, modes, tooling)
- [docs/STATE.md](docs/STATE.md): current progress and next steps

License: GPL-3.0-or-later.
