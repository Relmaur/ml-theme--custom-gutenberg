<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Integration\Setup;

use RigidHybrid\Setup\ThemeSetup;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * The theme boots inside real WordPress: supports, menus, blocks, categories.
 *
 * @covers \RigidHybrid\Theme
 * @covers \RigidHybrid\Setup\ThemeSetup
 * @covers \RigidHybrid\Setup\BlockRegistry
 */
final class ThemeBootTest extends WP_UnitTestCase
{
    public function testThisThemeIsActive(): void
    {
        self::assertSame(basename(dirname(__DIR__, 4)), get_template());
    }

    public function testDeclaresThemeSupports(): void
    {
        self::assertTrue(current_theme_supports('title-tag'));
        self::assertTrue(current_theme_supports('post-thumbnails'));
    }

    public function testDeclaresHtml5Markup(): void
    {
        // The WordPress test library removes 'html5' support after every test,
        // so re-run our after_setup_theme callback before checking it. Doing
        // that after boot makes WordPress (rightly) complain about title-tag
        // being added late, which is expected here and only here.
        $this->setExpectedIncorrectUsage("add_theme_support( 'title-tag' )");
        (new ThemeSetup())->setupTheme();

        self::assertTrue(current_theme_supports('html5', 'script'));
        self::assertTrue(current_theme_supports('html5', 'style'));
    }

    public function testRegistersTheNavMenus(): void
    {
        self::assertSame(['primary_menu', 'footer_menu'], array_keys(get_registered_nav_menus()));
    }

    public function testRegistersTheHeroBlockAsADynamicBlock(): void
    {
        $hero = WP_Block_Type_Registry::get_instance()->get_registered('my-theme/hero');

        self::assertNotNull($hero);
        self::assertSame('theme-blocks', $hero->category);
        self::assertTrue($hero->is_dynamic(), 'rendered by render.php (ADR 0004)');
    }

    public function testTheThemeBlockCategoryComesFirst(): void
    {
        $post = self::factory()->post->create_and_get();

        self::assertSame('theme-blocks', get_block_categories($post)[0]['slug']);
    }
}
