<?php

/**
 * Register all custom blocks
 * 
 * Each block can have these optional assets in its folder:
 * - style.scss   → Frontend + Editor styles (visual appearance)
 * - editor.scss  → Editor-only styles (admin UI)
 * - view.ts      → Frontend-only JavaScript (interactions/animations)
 */

add_action('init', function () {

    // Register Hero Block
    register_theme_block('hero', 'my-theme/hero');

    // Register more blocks here...
    // register_theme_block('team-member', 'my-theme/team-member');
    // register_theme_block('testimonial', 'my-theme/testimonial');

});

/**
 * Helper function to register a block with all its assets
 * 
 * @param string $slug       Block folder name (e.g., 'hero')
 * @param string $block_name Full block name (e.g., 'my-theme/hero')
 */
function register_theme_block($slug, $block_name)
{
    $block_path = "/src/blocks/{$slug}";
    $block_dir = get_theme_file_path($block_path);
    $is_dev = vite_is_dev_server_running();

    // 1. Register the editor script (main block logic)
    vite_register_asset(
        "{$slug}-editor-script",
        "{$block_path}/index.tsx",
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components']
    );

    // 2. Register block styles (frontend + editor)
    $style_handle = null;
    $editor_style_handle = null;
    
    if ($is_dev) {
        // Dev mode: Register styles directly from Vite dev server.
        // Vite serves SCSS as raw CSS when fetched via URL (not as a JS module),
        // so WordPress can inject them as proper <link> tags into the API v3 iframe canvas.
        if (file_exists("{$block_dir}/style.scss")) {
            wp_register_style("{$slug}-style", VITE_SERVER . "{$block_path}/style.scss", [], null);
            $style_handle = "{$slug}-style";
        }
        if (file_exists("{$block_dir}/editor.scss")) {
            wp_register_style("{$slug}-editor-style", VITE_SERVER . "{$block_path}/editor.scss", [], null);
            $editor_style_handle = "{$slug}-editor-style";
        }
    } else {
        // Production: Load compiled CSS from manifest
        $style_handle = vite_register_style_from_manifest("{$slug}-style", "{$block_path}/style.scss");
        $editor_style_handle = vite_register_style_from_manifest("{$slug}-editor-style", "{$block_path}/editor.scss");
    }

    // 3. Register frontend-only view script (if exists)
    $view_script_handle = null;
    if (file_exists("{$block_dir}/view.js") || file_exists("{$block_dir}/view.ts")) {
        vite_register_asset(
            "{$slug}-view-script",
            "{$block_path}/view.js",
            []
        );
        $view_script_handle = "{$slug}-view-script";
    }

    // 4. Register the block
    $block_args = [
        'editor_script' => "{$slug}-editor-script",
    ];

    if ($view_script_handle) {
        $block_args['view_script'] = $view_script_handle;
    }
    
    if ($style_handle) {
        $block_args['style'] = $style_handle;
    }
    
    if ($editor_style_handle) {
        $block_args['editor_style'] = $editor_style_handle;
    }

    register_block_type($block_dir, $block_args);
}
