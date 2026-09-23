<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit\Setup;

use Brain\Monkey\Functions;
use RigidHybrid\Setup\BlockRegistry;
use RigidHybrid\Tests\Unit\UnitTestCase;

/**
 * @covers \RigidHybrid\Setup\BlockRegistry
 */
final class BlockRegistryTest extends UnitTestCase
{
    /** The real theme root, so the Hero block's real files are used. */
    private string $themeRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeRoot = dirname(__DIR__, 4);
        Functions\when('get_theme_file_path')->alias(fn (string $path): string => $this->themeRoot . $path);
        Functions\when('get_theme_file_uri')->alias(fn (string $path): string => 'https://example.test' . $path);
        $this->setDevServerRunning(false);
    }

    /** Accept any asset registration (for tests that check something else). */
    private function allowAssetRegistration(): void
    {
        Functions\when('wp_register_script')->justReturn(true);
        Functions\when('wp_register_style')->justReturn(true);
    }

    public function testRegistersItsHooks(): void
    {
        $registry = new BlockRegistry();
        $registry->register();

        self::assertNotFalse(has_action('init', [$registry, 'registerBlocks']));
        self::assertNotFalse(has_filter('block_categories_all', [$registry, 'registerCustomCategories']));
    }

    public function testRegistersTheHeroBlockWithAllItsAssets(): void
    {
        $this->allowAssetRegistration();
        Functions\when('wp_cache_get')->justReturn([
            'src/blocks/hero/index.tsx'   => ['file' => 'block-hero.js'],
            'src/blocks/hero/view.ts'     => ['file' => 'block-hero-view.js'],
            'src/blocks/hero/style.scss'  => ['file' => 'block-hero-style.css'],
            'src/blocks/hero/editor.scss' => ['file' => 'block-hero-editor.css'],
        ]);

        Functions\expect('register_block_type')
            ->once()
            ->with($this->themeRoot . '/src/blocks/hero', [
                'editor_script' => 'hero-editor-script',
                'view_script'   => 'hero-view-script',
                'style'         => 'hero-block-style',
                'editor_style'  => 'hero-editor-style',
            ]);

        (new BlockRegistry())->registerBlocks();
    }

    public function testTheEditorScriptDependsOnThePackagesBlocksImport(): void
    {
        Functions\when('wp_cache_get')->justReturn(['src/blocks/hero/index.tsx' => ['file' => 'block-hero.js']]);
        Functions\when('register_block_type')->justReturn(null);

        Functions\expect('wp_register_script')
            ->once()
            ->with(
                'hero-editor-script',
                'https://example.test/dist/block-hero.js',
                ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-rich-text'],
                null,
                true
            );

        (new BlockRegistry())->registerBlocks();
    }

    public function testDoesNotAttachStylesThatAreMissingFromTheBuild(): void
    {
        $this->allowAssetRegistration();
        // e.g. a new block whose SCSS wasn't added to vite.config.js yet:
        // better no style handle than one pointing at nothing.
        Functions\when('wp_cache_get')->justReturn(['src/blocks/hero/index.tsx' => ['file' => 'block-hero.js']]);

        Functions\expect('register_block_type')
            ->once()
            ->with($this->themeRoot . '/src/blocks/hero', [
                'editor_script' => 'hero-editor-script',
                'view_script'   => 'hero-view-script',
            ]);

        (new BlockRegistry())->registerBlocks();
    }

    public function testPutsTheThemeBlockCategoryFirst(): void
    {
        $core = [
            ['slug' => 'text', 'title' => 'Text', 'icon' => null],
            ['slug' => 'media', 'title' => 'Media', 'icon' => null],
        ];

        $categories = (new BlockRegistry())->registerCustomCategories($core);

        self::assertSame(['theme-blocks', 'text', 'media'], array_column($categories, 'slug'));
    }
}
