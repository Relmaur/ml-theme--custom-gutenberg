<?php

declare(strict_types=1);

/**
 * Theme functions and definitions.
 * 
 * @package RigidHybrid
 */


if (! defined('ABSPATH')) {
    exit;
}

$composer_autoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
} else {
    wp_die('Composer dependencies not installed. Please run "composer dump-autoload" in the theme directory.');
}

// 1. Load the Vite Bridge (Development & Production Asset Loading)
require_once get_theme_file_path('/inc/vite-enqueue.php');

// 2. Load Block Registration Logic
require_once get_theme_file_path('/inc/blocks-register.php');

(new \RigidHybrid\Theme())->init();

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