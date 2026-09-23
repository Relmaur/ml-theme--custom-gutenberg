# 0005. Rigid editing becomes an opt-in theme mode

- **Status:** Accepted (2026-09-23: implemented, checked with WP-CLI, and approved by the owner after testing as administrator and editor)
- **Date:** 2026-09-23
- **Supersedes (partially):** the "always rigid" assumption in `README.md` and ADR 0004's context

## Context

The theme must serve two kinds of client:

1. **Builder clients**, who want a block suite to compose their own pages.
2. **Rigid clients**, who only want to change text and images inside layouts the developer built, the way ACF works.

Until now, rigidness was hardcoded in `theme.json` (appearance tools off, custom colors and font sizes off, drop cap off), yet all core blocks could still be inserted. That means it was neither properly rigid nor properly open.

Verified against WordPress 7.1.2 (installed locally):

- `wp_theme_json_data_theme` + `WP_Theme_JSON_Data::update_with()` merge extra settings into the theme origin (available since 6.1).
- `appearanceTools: true` is expanded into individual settings **when the theme is loaded**, so merging `appearanceTools: false` later can't undo it. Mode-specific settings therefore can't live in `theme.json`.
- `edit-form-blocks.php` sets `templateLock` / `template` **before** `block_editor_settings_all` runs, so the filter can override both.

## Decision

A new Bootable service, `RigidHybrid\Setup\ThemeMode`, owns the mode.

- **Switch:** a wp-config constant, `define('RIGID_HYBRID_MODE', 'rigid');`, which the `rigid_hybrid/mode` filter can override. Allowed values are `builder` (the default) and `rigid`. Any other value falls back to `builder` and triggers `_doing_it_wrong()`.
- **`theme.json`** holds only the tokens both modes share (palette, layout sizes).
- **Builder mode:** merges `appearanceTools: true`. All core blocks and theme blocks are available.
- **Rigid mode, which applies to every post type in the block editor:**
  1. Design locks are merged in: `color.custom`, `typography.customFontSize` and `typography.dropCap` are all `false`.
  2. `allowed_block_types_all` limits the inserter to the theme's own blocks (the `my-theme/` namespace).
  3. `block_editor_settings_all`:
     - **Fixed layout per page.** Users without `edit_theme_options` (anyone below administrator) get `templateLock: 'all'`. They can edit block content and sidebar fields but can't insert, move or remove blocks. Administrators build each page's layout.
     - **New posts** (`auto-draft`) start from a default layout, `[my-theme/hero]`, so a locked editor never faces an empty page.

## Trade-offs

- **The layout is set per page by an administrator, not by per-page templates or patterns.** This is simple and uses only core APIs, but a rigid-mode client who needs a *new kind* of page has to ask an administrator to arrange it.
- **"Everything" is rigid, blog posts included.** Every post type gets theme blocks only, so writing a blog post needs theme blocks for paragraphs, images and so on. Those don't exist yet.
- **A wp-config constant, not an admin setting.** Clients can't flip the mode by accident, but changing it needs file access or a deploy.
- **Unknown values fall back to `builder` (fail-open).** A typo in wp-config shows a `_doing_it_wrong` notice (visible with `WP_DEBUG`) and doesn't take down the editor. The cost is that the site is briefly unlocked until someone notices.
- **Fixed default layout.** `DEFAULT_LAYOUT` is only applied to *new* posts, so existing pages never get the "content doesn't match template" reset prompt.

## Consequences

- Moving a site from builder to rigid leaves core blocks already in its content. They still render and edit, but can't be inserted again.
- A theme blocks-only setup means more theme blocks are needed before rigid mode can hold general content (text section, image, CTA…).
- `AGENTS.md` rule 5 ("Rigid editing") now applies only in rigid mode. Block code should still restrict `RichText` formats, because blocks behave the same in both modes.
- Later extensions (not built now, YAGNI): a per-post-type scope, a custom `rigid_hybrid_edit_layout` capability, per-page-template layouts.

## Updates

- **2026-09-23, ADR 0008:** rigid mode no longer applies to every post type. By default it covers only **`page`**, and the `rigid_hybrid/rigid_post_types` filter can widen or narrow that. Other post types (e.g. blog posts) keep the normal editor. The design locks (theme.json) stay site-wide.
