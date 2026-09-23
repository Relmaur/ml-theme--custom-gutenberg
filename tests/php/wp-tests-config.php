<?php

/**
 * Configuration for the WordPress PHPUnit test library (read by
 * vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php).
 *
 * Secrets and machine-specific values come from the environment
 * (see tests/php/.env.testing.example); nothing is hardcoded here.
 *
 * @package RigidHybrid\Tests
 */

declare(strict_types=1);

/**
 * Read a required setting, failing loudly instead of guessing.
 */
$rigid_hybrid_env = static function (string $name): string {
    $value = getenv($name);
    if (!is_string($value) || $value === '') {
        fwrite(STDERR, "Missing {$name}. Copy tests/php/.env.testing.example to tests/php/.env.testing.\n");
        exit(1);
    }
    return $value;
};

// The WordPress core the tests run against (installed by Composer).
define('ABSPATH', dirname(__DIR__, 2) . '/vendor/roots/wordpress-no-content/');

define('DB_NAME', $rigid_hybrid_env('WP_TESTS_DB_NAME'));
define('DB_USER', $rigid_hybrid_env('WP_TESTS_DB_USER'));
define('DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: '');
define('DB_HOST', $rigid_hybrid_env('WP_TESTS_DB_HOST'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// A prefix unlike any real site's, as a second guard against wiping real data.
$table_prefix = 'rhtests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Rigid Hybrid Tests');
// The test library runs its installer as a shell command built from this value
// without quoting it, so quote it here: PHP binaries often live in paths with
// spaces (e.g. Herd's ~/Library/Application Support/...).
define('WP_PHP_BINARY', escapeshellarg(PHP_BINARY));
define('WP_DEBUG', true);
