# 0004. Dynamic blocks rendered by render.php

- **Status:** Proposed (documents a decision already built; waiting for human approval)
- **Date:** 2026-09-23

## Context

Static blocks save their HTML into `post_content`. Any change to the block's markup then shows "This block contains unexpected or invalid content" on existing posts, unless a `deprecated` migration is written for every change. The "Rigid" philosophy expects markup to change often while the content editors enter stays stable, so it works like ACF or Carbon Fields but with native storage.

## Decision

- `save.tsx` returns `null`. Only the block comment and its JSON attributes are stored.
- `block.json` sets `"render": "file:./render.php"`, and PHP renders the frontend markup on every request.
- `edit.tsx` repeats the `render.php` structure (same wrapper and class names) so the editor preview matches the frontend.

## Trade-offs

- **Gained:** markup changes apply to all existing content with no validation errors or deprecations.
- **Gained:** output is escaped at render time in PHP, one place to enforce `esc_*` / `wp_kses_post`.
- **Cost:** markup exists in two places (`edit.tsx` and `render.php`) that have to be kept in sync by hand.
- **Cost:** a small PHP cost on every render, and the content can't be read if the theme is deactivated (no saved HTML to fall back to).
- **Cost:** attributes stored in the comment are editor-controlled input, so every one has to be treated as untrusted in `render.php`.

## Consequences

- Every block ships a `render.php` that escapes each attribute for its context. RichText values go through `wp_kses_post`, URLs through `esc_url`.
- Changes to the attribute schema still need care. Renaming or removing an attribute orphans stored data, so give attributes defaults and don't rename them.
- Content depends on this theme. Switching themes loses block output unless the blocks move to a plugin.
