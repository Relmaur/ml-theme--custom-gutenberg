<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit\Setup;

use Brain\Monkey\Functions;
use RigidHybrid\Setup\ThemeSetup;
use RigidHybrid\Tests\Unit\UnitTestCase;

/**
 * @covers \RigidHybrid\Setup\ThemeSetup
 */
final class ThemeSetupTest extends UnitTestCase
{
    public function testRegistersItsHook(): void
    {
        $setup = new ThemeSetup();
        $setup->register();

        self::assertNotFalse(has_action('after_setup_theme', [$setup, 'setupTheme']));
    }

    public function testDeclaresThemeSupportsAndMenus(): void
    {
        Functions\stubTranslationFunctions();

        Functions\expect('add_theme_support')->once()->with('title-tag');
        Functions\expect('add_theme_support')->once()->with('post-thumbnails');
        Functions\expect('add_theme_support')->once()->with('html5', \Mockery::type('array'));
        Functions\expect('register_nav_menus')->once()->with([
            'primary_menu' => 'Primary Menu',
            'footer_menu'  => 'Footer Menu',
        ]);

        (new ThemeSetup())->setupTheme();
    }
}
