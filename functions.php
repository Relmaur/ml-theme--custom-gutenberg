<?php

/**
 * Theme functions and definitions.
 *
 * @package RigidHybrid
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// Prefixed: code in functions.php runs in the global scope.
$rigid_hybrid_autoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($rigid_hybrid_autoload)) {
    require_once $rigid_hybrid_autoload;
} else {
    wp_die('Composer dependencies not installed. Please run "composer dump-autoload" in the theme directory.');
}

(new \RigidHybrid\Theme())->init();
