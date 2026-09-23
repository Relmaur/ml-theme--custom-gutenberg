# 0008. Rigid mode per post type, then editing policies in taw/core

- **Status:** Accepted (owner approved on 2026-09-23). Part 1 is implemented here. Part 2 moved to taw/core for the new `taw-gutenberg` theme and **no longer applies to this theme** (see the update at the end).
- **Date:** 2026-09-23
- **Amends:** ADR 0005 (the "Everything is rigid" scope)

## Context

ADR 0005 applied rigid mode to **every post type** in the block editor. Then two things happened.

1. **This theme becomes a taw/core data consumer** (the TAW data-layer plan, Phase 1 Step 5). taw/core registers post types like `book` with `show_in_rest: true`, so they open in the block editor. Under ADR 0005, a book in rigid mode:
   - could only insert `my-theme/*` blocks
   - would start with a Hero
   - would be layout-locked for Editors
2. **taw/core's planned Block Bindings (Phase 4) bind *core* blocks** (heading, paragraph, image) to field values. Rigid mode's `my-theme/*` allow-list would make bindings unusable on rigid sites.

The owner wants all of this tooling (data, bindings, editor behavior) in **taw/core**, shared by every TAW site (taw-theme, and this theme as taw-gutenberg), and not re-implemented per theme.

## Decision

**Part 1: now, in this theme (stopgap).** Rigid mode only applies to a filterable list of post types:

```php
// Default: ['page']. Sites can widen or narrow it:
add_filter('rigid_hybrid/rigid_post_types', fn (array $types) => [...$types, 'post']);
```

- `ThemeMode::rigidPostTypes()` validates the filter output: non-strings are dropped, and a non-array falls back to `['page']` with a `_doing_it_wrong` notice.
- The inserter allow-list, the starting layout (`template`) and `templateLock` only apply on those post types. Posts and data post types keep the normal editor.
- **The design locks (theme.json) stay site-wide.** theme.json can't be scoped per post type, and brand consistency is a site-wide concern anyway.

**Part 2: later, in taw/core.** A per-post-type **editing policy**, declared next to the post type in taw/core's schema (PHP or `taw-schema/*.json`), covering:
- which blocks can be inserted (patterns, e.g. `taw-gutenberg/*`)
- the starting template
- the lock level, and which capability can change the layout
- (from Phase 4) whether bound core blocks are allowed

It's booted by **its own switch**, not `Boot::data()`, so a data-only site never gets editor locking by accident. Once it exists, this theme's rigid mode becomes a **preset** (`RIGID_HYBRID_MODE = 'rigid'` means "apply the curated policy to `page`"), and `ThemeMode` shrinks to applying it.

## Options considered

- **Leave out post types that come from taw/core.** *Rejected:* once every post type is declared in taw/core, that rule leaves out everything. It would also tie rigid mode to taw/core's internals.
- **Keep "everything", and add bound core blocks to the allow-list.** *Rejected on its own:* data post types would still start with a Hero and be layout-locked. It's kept as a future policy option (Part 2).
- **Build the full policy system in this theme.** *Rejected:* taw-theme couldn't use it, and it goes against the goal of keeping the tooling in taw/core.

## Trade-offs

- **Behavior change for existing rigid sites:** blog posts go back to the normal editor. This was intended: the ADR 0005 consequence "blog posts can only contain a Hero" was the problem.
- **The filter is code, not config,** so changing the scope needs a mu-plugin or child theme. That's acceptable for a stopgap, and Part 2 makes it declarative.
- **Part 2 widens taw/core beyond "data".** That's why it gets its own boot switch and its own taw/core ADR.

## Consequences

- Tests:
  - unit: default, filter, invalid values
  - integration: pages locked, posts open, filter adds a type
- taw-13 (the TAW umbrella session) has been asked to add "editing policies" to the taw/core roadmap, before or alongside Phase 4, with a taw/core ADR.
- When Part 2 lands, the `rigid_hybrid/rigid_post_types` filter keeps working (or is deprecated with a fallback), because sites may rely on it.

## Update (2026-09-23): plan change

The owner decided this theme will **not** become a taw/core consumer. The Gutenberg consumer is a new theme, `taw-gutenberg`. So:

- **Part 1 stays** as this theme's final design. Rigid mode covers the `rigid_hybrid/rigid_post_types` list (default `['page']`), and that fixes ADR 0005's "blog posts can only contain a Hero" problem regardless of taw/core. The filter is the permanent way to configure the scope here.
- **Part 2 is now taw/core roadmap item E** (its own ADR and boot switch, before or alongside Phase 4). It will be adopted by taw-gutenberg, not by this theme. `ThemeMode` will **not** become a preset of it. This theme's `ThemeMode.php` and tests remain the reference implementation.
- Mentions of taw/core's `book` post type above are historical context: they explain why the scoping was needed.
