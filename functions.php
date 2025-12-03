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
