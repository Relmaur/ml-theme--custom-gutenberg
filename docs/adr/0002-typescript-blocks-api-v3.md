# 0002. Write blocks in TypeScript on block API v3

- **Status:** Proposed (documents a decision already built; waiting for human approval)
- **Date:** 2026-09-23

## Context

The Hero block started as `.jsx` on `block.json` apiVersion 2 (commit `20c39d6` moved it). Untyped attributes made it easy for `edit`, `render.php` and `block.json` to drift apart. WordPress is also moving the post editor to an iframe, and apiVersion 3 is the version that is iframe-compatible.

## Decision

- Block source files are TypeScript: `index.tsx`, `edit.tsx`, `save.tsx`, `formats.tsx` and `view.ts`. `tsconfig.json` has `strict: true` and `noEmit`. Vite/esbuild strips the types, and `tsc` only type-checks.
- The attribute shape is an exported interface in `edit.tsx` (e.g. `HeroAttributes`) that mirrors `block.json`.
- `block.json` uses `apiVersion: 3`. Scripts and styles are **not** declared there, because PHP registers them (see ADR 0001).
- Types come from the `@types/wordpress__*` and `@types/react` dev dependencies. Runtime still comes from WP globals.

## Trade-offs

- **Gained:** attribute and prop checking, editor autocomplete, and iframe-ready editor rendering.
- **Cost:** `@types/wordpress__*` lag behind Gutenberg and don't always match the WP version in use, so some casts (`as any` in `index.tsx`) are needed.
- **Cost:** esbuild doesn't type-check, so type errors don't break the build. Right now `formats.tsx` has 15 errors and nothing enforces a fix.
- **Cost:** apiVersion 3 needs WordPress 6.3+, which is newer than the `Requires at least: 6.0` in `style.css`.

## Consequences

- Add `tsc --noEmit` as an npm script and eventually to CI, or type errors will keep piling up.
- Keep `block.json` attributes and the TS interface in sync by hand; no code is generated from one to the other.
- Bump the `Requires at least` header to match what the theme actually needs (6.6 for theme.json v3).

## Updates

- **2026-09-23, ADR 0007:**
  - `npm run typecheck` now runs in CI, and the 15 `formats.tsx` errors are fixed (typed imports instead of the untyped `wp` global).
  - The deprecated stub packages `@types/wordpress__blocks` and `@types/wordpress__components` were replaced by the real `@wordpress/*` packages as type-only dev dependencies. `@types/wordpress__block-editor` stays, because the real package ships no types.
  - `@types/react` is pinned to 18.3 to match the React that WordPress ships.
  - `index.tsx` registers with `registerBlockType(metadata, { edit, save })` (no `as any`). `HeroAttributes` became a `type`, because interfaces aren't assignable to `Record<string, unknown>`.
