<?php

/**
 * Bootstrap for integration tests: boots a real WordPress with this theme active.
 *
 * @package RigidHybrid\Tests
 */

declare(strict_types=1);

$rigid_hybrid_theme_dir = dirname(__DIR__, 2);

require_once $rigid_hybrid_theme_dir . '/vendor/autoload.php';

// 1. Load tests/php/.env.testing (if present) without overriding real env vars,
//    so CI can configure everything through its own environment.
$rigid_hybrid_env_file = __DIR__ . '/.env.testing';
if (is_readable($rigid_hybrid_env_file)) {
    $rigid_hybrid_settings = parse_ini_file($rigid_hybrid_env_file, false, INI_SCANNER_RAW);
    foreach ($rigid_hybrid_settings ?: [] as $rigid_hybrid_name => $rigid_hybrid_value) {
        if (getenv($rigid_hybrid_name) === false) {
            putenv("{$rigid_hybrid_name}={$rigid_hybrid_value}");
        }
    }
}

// 2. Tell the WordPress test library where its config file is.
putenv('WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php');
$rigid_hybrid_tests_dir = $rigid_hybrid_theme_dir . '/vendor/wp-phpunit/wp-phpunit';

require_once $rigid_hybrid_tests_dir . '/includes/functions.php';

// 3. Activate THIS theme before WordPress loads themes. The theme folder lives
//    outside the test WordPress install, so register its parent folder as a
//    theme root, then point the active theme options at it.
tests_add_filter('muplugins_loaded', static function () use ($rigid_hybrid_theme_dir): void {
    register_theme_directory(dirname($rigid_hybrid_theme_dir));

    $theme = basename($rigid_hybrid_theme_dir);
    add_filter('pre_option_template', static fn (): string => $theme);
    add_filter('pre_option_stylesheet', static fn (): string => $theme);
});

// 4. Install (on first run) and boot WordPress.
require $rigid_hybrid_tests_dir . '/includes/bootstrap.php';
