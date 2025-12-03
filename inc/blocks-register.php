<?php

add_action('init', function () {

    // 1. Register the Script Handle via Vite Bridge (register only, don't enqueue)
    // This ensures that in DEV, it points to localhost:3000, and in PROD, it uses the hashed filename.
    // 'block-hero' MUST match the key in vite.config.js input
    vite_register_asset('hero-script', '/src/blocks/hero/index.jsx', ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components']);

    // 2. Register the Block
    // We point to the folder containing block.json, but override the editor_script
    register_block_type(get_theme_file_path('/src/blocks/hero'), array(
        'editor_script' => 'hero-script', // Use the handle we just registered above
    ));

    // Repeat for other blocks...
    // vite_register_asset('flex-cols-script', '/src/blocks/flex-columns/index.jsx', [...]);
    // register_block_type( ..., ['editor_script' => 'flex-cols-script'] );
});
