# Rigid Hybrid Theme - Developer Documentation

**Version:** 1.0.0  
**Stack:** WordPress (PHP), React (Native Blocks), Vite (Build Tool), Tailwind CSS (via PostCSS/SASS)

---

## 1. Project Philosophy (The "Why")

This theme is built on a **"Rigid Hybrid"** architecture.

- **Rigid:** Unlike standard Gutenberg themes where users drag-and-drop layout blocks (Groups, Columns) to build pages, this theme restricts users to specific, pre-defined blocks. The layout is hard-coded; the content is variable. This emulates the "Meta Fields" (ACF/Carbon Fields) workflow but uses native Gutenberg storage.

- **Hybrid:** It uses classic PHP templates (`header.php`, `footer.php`) for the global shell, but relies entirely on React-based Gutenberg blocks for the `post_content`.

- **Modern Build:** It replaces `wp-scripts` (Webpack) with **Vite** for near-instant HMR (Hot Module Replacement) during development.

---

## 2. Prerequisites & Installation

### Requirements

| Requirement | Version |
|-------------|---------|
| Node.js | v18+ recommended |
| PHP | v7.4+ (Compatible with v8.0+) |
| WordPress | v6.0+ (Required for block.json API v2 support) |

### Setup

1. Navigate to the theme directory:
   ```bash
   cd wp-content/themes/my-hybrid-theme
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Activate the theme in WordPress.

---

## 3. Development Workflow

### Starting the Dev Server

```bash
npm run dev
```

- **What this does:** Starts Vite on `http://localhost:3000`.
- **The Bridge:** `inc/vite-enqueue.php` detects this port is open. It creates a script tag pointing to your local machine instead of the `dist/` folder.
- **HMR:** Styles and React components update instantly without a full page reload.

### Building for Production

```bash
npm run build
```

- **What this does:** Compiles SCSS to CSS and JSX to minified JS.
- **Output:** Files are saved to `dist/` with hashed filenames (e.g., `main-8a7d9.js`) for cache busting.
- **Manifest:** Generates `dist/manifest.json`. PHP reads this file to know which real file to load for a given handle.

---

## 4. Project Structure Overview

```
/
├── dist/                   # Compiled assets (Do not edit manually)
├── inc/
│   ├── blocks-register.php # PHP: Registers block types & assets
│   └── vite-enqueue.php    # PHP: The bridge between WP and Vite
├── src/
│   ├── blocks/             # YOUR CUSTOM BLOCKS
│   │   └── hero/           # Example Block
│   ├── scss/               # Global SCSS (Tailwind imports here)
│   ├── js/                 # Global JS (Mobile menu, interactions)
│   └── utils/              # React Shims (wp-react.js)
├── functions.php           # Theme bootstrapper
├── theme.json              # Global settings (Palette, Typography locks)
└── vite.config.js          # Build configuration
```

---

## 5. How to Create a New Block

This is the most common task. Follow these **4 steps** to add a new "Rigid" block.

### Step 1: Scaffold the Files

Create a folder in `src/blocks/{block-slug}/` (e.g., `src/blocks/team-member/`).

You need **4 files**:

| File | Purpose |
|------|---------|
| `block.json` | Block definition & attributes |
| `index.jsx` | Entry point (imports styling, registers block) |
| `edit.jsx` | The Editor UI (Inputs/Settings) |
| `save.jsx` | The Frontend HTML |

### Step 2: Configure `block.json`

Define your attributes (the data fields).

> **Important:** Do NOT include `"editorScript": "file:./index.jsx"` — we handle script registration via PHP.

```json
{
  "apiVersion": 2,
  "name": "my-theme/team-member",
  "title": "Team Member",
  "category": "design",
  "attributes": {
    "name": { "type": "string" },
    "role": { "type": "string" },
    "photoId": { "type": "number" },
    "photoUrl": { "type": "string" }
  }
}
```

### Step 3: Register in PHP

Open `inc/blocks-register.php`. You must manually register the script handle to ensure Vite loads it correctly.

```php
// 1. Register the Vite entry point (register only, don't enqueue)
vite_register_asset( 
    'team-member-script', 
    '/src/blocks/team-member/index.jsx', 
    ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'] 
);

// 2. Register the block type
register_block_type( get_theme_file_path( '/src/blocks/team-member' ), array(
    'editor_script' => 'team-member-script', 
));
```

### Step 4: Add to Vite Config

Open `vite.config.js`. Add the new block's entry point to the `input` object so Vite knows to bundle it.

```javascript
build: {
  rollupOptions: {
    input: {
      main: path.resolve(__dirname, 'src/js/main.js'),
      style: path.resolve(__dirname, 'src/scss/main.scss'),
      'block-hero': path.resolve(__dirname, 'src/blocks/hero/index.jsx'),
      // ADD THIS LINE:
      'block-team': path.resolve(__dirname, 'src/blocks/team-member/index.jsx'), 
    },
  },
}
```

---

## 6. The "Rigid" Implementation Guide

To maintain the "Carbon Fields" feel (strict layout, easy data entry), follow these rules in your React code:

### Rule A: Use `InspectorControls` for Data

Do not let users type directly into the design if it breaks the layout. Use the Sidebar.

```jsx
// edit.jsx
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
  return (
    <>
      <InspectorControls>
        <PanelBody title="Settings">
           <TextControl 
             label="Button Link"
             value={attributes.url}
             onChange={(val) => setAttributes({ url: val })}
           />
        </PanelBody>
      </InspectorControls>
      
      {/* Visual Preview */}
      <div className="my-block">...</div>
    </>
  );
}
```

### Rule B: Restrict `RichText`

If you use `RichText` in the canvas, strip the toolbars to prevent clients from adding H1s inside paragraphs or weird colors.

```jsx
<RichText
  tagName="h2"
  value={attributes.title}
  allowedFormats={[]} // REMOVES ALL BOLD/ITALIC/LINK options
  placeholder="Type title here..."
/>
```

### Rule C: Use `theme.json` to Lock Global Styles

Ensure `theme.json` has `appearanceTools: false` to prevent clients from changing margins, padding, or colors on a per-block basis unless you explicitly allow it.

---

## 7. Utilities & Helpers

### `src/utils/wp-react.js`

Since we are not using `wp-scripts`, we cannot just `import { useState } from 'react'`. React is provided globally by WordPress.

Use this helper to import React hooks cleanly:

```javascript
import { useState, useEffect } from '../../utils/wp-react';
```

### `src/scss/main.scss`

This is your global stylesheet. It is imported by `src/js/main.js`.

- **Tailwind:** If you install Tailwind, imports go here.
- **Fonts:** Load local fonts here.

---

## 8. Troubleshooting

### "Module not found" in Console

| | |
|---|---|
| **Cause** | You likely created a new block but forgot to add it to `vite.config.js` inputs. |
| **Fix** | Add the path to `vite.config.js` and restart `npm run dev`. |

### Styles not updating

| | |
|---|---|
| **Cause** | Vite bridge might not be connecting. |
| **Fix** | Check `inc/vite-enqueue.php`. Ensure `VITE_SERVER` matches your localhost port. Check browser console for connection errors. |

### "React is not defined"

| | |
|---|---|
| **Cause** | You tried to import React from a node_module instead of the WP global. |
| **Fix** | Check your imports. Use the `src/utils/wp-react.js` shim or `const { useState } = wp.element;`. |

### Block not appearing in editor (production)

| | |
|---|---|
| **Cause** | The production build wasn't run, or the manifest is outdated. |
| **Fix** | Run `npm run build` to generate fresh production assets. |