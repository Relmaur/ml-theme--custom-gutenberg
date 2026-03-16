<?php

declare(strict_types=1);

namespace RigidHybrid\Setup;

use RigidHybrid\Core\Bootable;

/**
 * Class ThemeSetup
 * 
 * Handles the foundational WordPress theme support features and menu registrations.
 * 
 * @package RigidHybrid\Setup
 */

class ThemeSetup implements Bootable
{
    /**
     * Register theme setup actions.
     * 
     * @return void
     */
    public function register(): void
    {
        add_action('after_setup_theme', [$this, 'setupTheme']);
    }

    /**
     * Hook callback: Define theme supports and menus.
     * 
     * @return void 
     */
    public function setupTheme(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ]);

        register_nav_menus([
            'primary_menu' => __('Primary Menu', 'rigid-hybrid'),
            'footer_menu' => __('Footer Menu', 'rigid-hybrid')
        ]);
    }
}