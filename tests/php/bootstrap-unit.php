<?php

/**
 * Bootstrap for unit tests (no WordPress loaded).
 *
 * @package RigidHybrid\Tests
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// WordPress constants the code under test reads. Real WordPress defines these;
// in unit tests nothing does, so give them their real values.
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
