<?php
if (! defined('ABSPATH')) {
    exit;
}

// 1. Load the Vite Bridge (Development & Production Asset Loading)
require_once get_theme_file_path('/inc/vite-enqueue.php');

// 2. Load Block Registration Logic
require_once get_theme_file_path('/inc/blocks-register.php');

// 3. Standard Theme Setup
add_action('after_setup_theme', function () {
    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable Featured Images
    add_theme_support('post-thumbnails');

    // Register Navigation Menus
    register_nav_menus(array(
        'primary_menu' => __('Primary Menu', 'rigid-hybrid'),
        'footer_menu'  => __('Footer Menu', 'rigid-hybrid'),
    ));

    // Enable HTML5 markup support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
});
// 4. Enqueue Global Frontend Assets (Main.js / Main.scss)
add_action('wp_enqueue_scripts', function () {
    // 'main' corresponds to the entry point in vite.config.js
    // CSS is automatically loaded when main.js imports it
    vite_enqueue_asset('rigid-theme-main', '/src/js/main.js', array());
});

// Register Custom Block Category
add_filter('block_categories_all', function ($categories) {
    $my_custom_category = array(
        array(
            'slug'  => 'theme-blocks',
            'title' => 'Theme Blocks',
            'icon'  => null, // Optional
        ),
    );

    // Merging $my_custom_category FIRST puts it at the top
    return array_merge($my_custom_category, $categories);
}, 10, 1);

// add_filter('register_block_type_args', function ($args, $block_name) {
//     $locked_namespaces = ['my-theme'];
    
//     foreach ($locked_namespaces as $namespace) {
//         if (strpos($block_name, $namespace . '/') === 0) {
//             // Set lock attribute
//             if (!isset($args['attributes'])) {
//                 $args['attributes'] = [];
//             }
            
//             $args['attributes']['lock'] = [
//                 'type'    => 'object',
//                 'default' => ['move' => true, 'remove' => true],
//             ];
            
//             // Disable UI options
//             if (!isset($args['supports'])) {
//                 $args['supports'] = [];
//             }
            
//             $args['supports']['lock'] = false;
//             $args['supports']['reusable'] = false;
//             $args['supports']['html'] = false;
            
//             break;
//         }
//     }
    
//     return $args;
// }, 10, 2);

// /**
//  * Remove block toolbar options (duplicate, remove) for locked custom blocks
//  */
// add_action('enqueue_block_editor_assets', function () {
//     wp_add_inline_script('wp-block-editor', "
//         wp.hooks.addFilter(
//             'editor.BlockEdit',
//             'my-theme/lock-blocks',
//             wp.compose.createHigherOrderComponent((BlockEdit) => {
//                 return (props) => {
//                     const { name, attributes, setAttributes, clientId } = props;
                    
//                     // Check if this is one of our custom blocks
//                     if (name && name.startsWith('my-theme/')) {
//                         // Ensure the block is locked
//                         if (!attributes.lock || !attributes.lock.move || !attributes.lock.remove) {
//                             setTimeout(() => {
//                                 setAttributes({
//                                     lock: { move: true, remove: true }
//                                 });
//                             }, 0);
//                         }
//                     }
                    
//                     return wp.element.createElement(BlockEdit, props);
//                 };
//             }, 'lockCustomBlocks')
//         );
//     ", 'after');
// });