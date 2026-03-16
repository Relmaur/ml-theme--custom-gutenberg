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

(new \RigidHybrid\Theme())->init();