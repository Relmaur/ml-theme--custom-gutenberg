# 0003. OOP PHP with a Bootable service registry

- **Status:** Proposed (documents a decision already built; waiting for human approval)
- **Date:** 2026-09-23

## Context

The theme's PHP started as procedural include files (`inc/vite-enqueue.php`, `inc/blocks-register.php`) loaded from `functions.php`. As more concerns were added (theme supports, asset loading, block registration, Vite integration), global functions and implicit load order made it hard to find where a hook was registered.

## Decision

- Composer PSR-4 autoloading maps `RigidHybrid\` → `app/`. `functions.php` only loads the autoloader and calls `(new \RigidHybrid\Theme())->init()`.
- `RigidHybrid\Core\Bootable` defines `register(): void`. Implementations register hooks there and do the work in public callback methods.
- `RigidHybrid\Theme::$services` is the one ordered list of services. `init()` creates each one and calls `register()`.
- Code shared by several services that has no hooks of its own (e.g. `ViteService`) is a stateless static class under `Services/`, not a Bootable.

## Trade-offs

- **Gained:** one place to see everything the theme hooks into, one class per concern, and classes that can be tested (hooks are separate from logic).
- **Cost:** `composer dump-autoload` becomes a required step. `vendor/` is gitignored, so without it the theme calls `wp_die()` on every request, including in wp-admin.
- **Cost:** `Theme::init()` checks `method_exists($instance, 'register')`, not `instanceof Bootable`, so the interface isn't actually enforced.
- **Cost:** `ViteService` holds static state (`$moduleHandles`), which is harder to isolate in tests than an injected instance.

## Consequences

- New PHP features become a Bootable class listed in `Theme::$services`. Nothing new goes into `functions.php`.
- Deploys must run `composer dump-autoload` (or `composer install`) or commit the generated autoloader.
- If services start needing dependencies on each other, bring in a small container instead of adding more static services. Record that in a new ADR.
