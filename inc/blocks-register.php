<?php

/**
 * Register all custom blocks
 * 
 * Each block can have these optional assets in its folder:
 * - style.scss   → Frontend + Editor styles (visual appearance)
 * - editor.scss  → Editor-only styles (admin UI)
 * - view.js      → Frontend-only JavaScript (interactions/animations)
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
function register_theme_block($slug, $block_name) {
    $block_path = "/src/blocks/{$slug}";
    $block_dir = get_theme_file_path($block_path);
    
    // 1. Register the editor script (includes style.scss and editor.scss via imports)
    vite_register_asset(
        "{$slug}-editor-script",
        "{$block_path}/index.jsx",
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components']
    );
    
    // 2. Register frontend-only view script (if exists)
    $view_script_handle = null;
    if (file_exists("{$block_dir}/view.js")) {
        vite_register_asset(
            "{$slug}-view-script",
            "{$block_path}/view.js",
            [] // No dependencies needed for simple frontend JS
        );
        $view_script_handle = "{$slug}-view-script";
    }
    
    // 3. Register frontend styles separately for non-editor pages
    // In dev mode, styles are injected via JS. In production, we need to register CSS.
    $style_handle = null;
    if (!vite_is_dev_server_running()) {
        $style_handle = vite_register_block_style($slug, $block_path);
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
    
    register_block_type($block_dir, $block_args);
}

