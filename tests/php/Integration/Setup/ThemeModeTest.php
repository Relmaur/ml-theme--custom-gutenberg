<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Integration\Setup;

use RigidHybrid\Setup\ThemeMode;
use WP_Block_Editor_Context;
use WP_Post;
use WP_Theme_JSON_Resolver;
use WP_UnitTestCase;

/**
 * What each mode actually does to the block editor (ADR 0005), checked
 * against real WordPress.
 *
 * WP_UnitTestCase restores all hooks after each test, so swapping the mode
 * inside a test can't leak into the next one.
 *
 * @covers \RigidHybrid\Setup\ThemeMode
 */
final class ThemeModeTest extends WP_UnitTestCase
{
    /**
     * Replace the ThemeMode the theme booted with (builder, since the test
     * install doesn't define RIGID_HYBRID_MODE) by one in the given mode.
     */
    private function switchToMode(string $mode): void
    {
        remove_all_filters('wp_theme_json_data_theme');
        remove_all_filters('allowed_block_types_all');
        remove_all_filters('block_editor_settings_all');

        add_filter('rigid_hybrid/mode', static fn (): string => $mode);
        (new ThemeMode())->register();

        // theme.json data is cached per request; recompute it for the new mode.
        WP_Theme_JSON_Resolver::clean_cached_data();
        wp_clean_theme_json_cache();
    }

    private function editorContextFor(WP_Post $post): WP_Block_Editor_Context
    {
        return new WP_Block_Editor_Context(['post' => $post]);
    }

    private function existingPage(): WP_Post
    {
        return self::factory()->post->create_and_get(['post_type' => 'page', 'post_status' => 'publish']);
    }

    private function newPage(): WP_Post
    {
        return self::factory()->post->create_and_get(['post_type' => 'page', 'post_status' => 'auto-draft']);
    }

    // --- Design settings (theme.json) ------------------------------------

    public function testBuilderModeTurnsOnAllDesignTools(): void
    {
        $this->switchToMode(ThemeMode::BUILDER);

        self::assertTrue(wp_get_global_settings(['border', 'color']));
        self::assertTrue(wp_get_global_settings(['spacing', 'padding']));
        self::assertTrue(wp_get_global_settings(['color', 'custom']));
    }

    public function testRigidModeLocksDesignToTheBrandTokens(): void
    {
        $this->switchToMode(ThemeMode::RIGID);

        self::assertFalse(wp_get_global_settings(['color', 'custom']));
        self::assertFalse(wp_get_global_settings(['typography', 'customFontSize']));
        self::assertFalse(wp_get_global_settings(['typography', 'dropCap']));
        self::assertNotTrue(wp_get_global_settings(['border', 'color']), 'appearanceTools must stay off');
    }

    public function testBothModesKeepTheSharedBrandPalette(): void
    {
        foreach ([ThemeMode::BUILDER, ThemeMode::RIGID] as $mode) {
            $this->switchToMode($mode);

            $palette = wp_get_global_settings(['color', 'palette', 'theme']);
            self::assertSame(['primary', 'accent'], array_column($palette, 'slug'), $mode);
        }
    }

    // --- Allowed blocks --------------------------------------------------

    public function testBuilderModeAllowsEveryBlock(): void
    {
        $this->switchToMode(ThemeMode::BUILDER);

        self::assertTrue(get_allowed_block_types($this->editorContextFor($this->existingPage())));
    }

    public function testRigidModeOnlyAllowsThemeBlocks(): void
    {
        $this->switchToMode(ThemeMode::RIGID);

        $allowed = get_allowed_block_types($this->editorContextFor($this->existingPage()));

        self::assertIsArray($allowed);
        self::assertContains('my-theme/hero', $allowed);
        self::assertNotContains('core/paragraph', $allowed);
        foreach ($allowed as $block_name) {
            self::assertStringStartsWith('my-theme/', $block_name);
        }
    }

    public function testRigidModeAppliesToEveryPostType(): void
    {
        $this->switchToMode(ThemeMode::RIGID);
        $post = self::factory()->post->create_and_get(['post_type' => 'post']);

        self::assertSame(['my-theme/hero'], get_allowed_block_types($this->editorContextFor($post)));
    }

    public function testRigidModeLeavesEditorsWithoutAPostAlone(): void
    {
        // e.g. the widgets editor: not part of the client editing flow.
        $this->switchToMode(ThemeMode::RIGID);

        self::assertTrue(get_allowed_block_types(new WP_Block_Editor_Context(['name' => 'core/edit-widgets'])));
    }

    // --- Layout lock -------------------------------------------------------

    public function testRigidModeLocksTheLayoutForEditors(): void
    {
        $this->switchToMode(ThemeMode::RIGID);
        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        $settings = get_block_editor_settings([], $this->editorContextFor($this->existingPage()));

        self::assertSame('all', $settings['templateLock']);
    }

    public function testAdministratorsCanStillArrangeLayouts(): void
    {
        $this->switchToMode(ThemeMode::RIGID);
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        $settings = get_block_editor_settings([], $this->editorContextFor($this->existingPage()));

        self::assertArrayNotHasKey('templateLock', $settings);
    }

    public function testNewPostsStartWithTheDefaultLayout(): void
    {
        $this->switchToMode(ThemeMode::RIGID);
        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        $settings = get_block_editor_settings([], $this->editorContextFor($this->newPage()));

        self::assertSame([['my-theme/hero']], $settings['template']);
    }

    public function testExistingPostsGetNoTemplateSoTheyAreNeverReset(): void
    {
        $this->switchToMode(ThemeMode::RIGID);
        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        $settings = get_block_editor_settings([], $this->editorContextFor($this->existingPage()));

        self::assertArrayNotHasKey('template', $settings);
    }

    public function testBuilderModeNeverLocksTheLayout(): void
    {
        $this->switchToMode(ThemeMode::BUILDER);
        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        $settings = get_block_editor_settings([], $this->editorContextFor($this->newPage()));

        self::assertArrayNotHasKey('templateLock', $settings);
        self::assertArrayNotHasKey('template', $settings);
    }
}
