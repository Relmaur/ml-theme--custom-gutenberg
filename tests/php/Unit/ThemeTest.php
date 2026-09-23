<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit;

use RigidHybrid\Theme;

/**
 * @covers \RigidHybrid\Theme
 */
final class ThemeTest extends UnitTestCase
{
    public function testBootsEveryService(): void
    {
        (new Theme())->init();

        // One hook per service proves each one was created and registered.
        self::assertNotFalse(has_action('after_setup_theme'), 'ThemeSetup');
        self::assertNotFalse(has_filter('wp_theme_json_data_theme'), 'ThemeMode');
        self::assertNotFalse(has_action('wp_enqueue_scripts'), 'Enqueue');
        self::assertNotFalse(has_action('init'), 'BlockRegistry');
    }
}
