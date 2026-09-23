<?php

namespace RigidHybrid\Setup;

use RigidHybrid\Core\Bootable;
use RigidHybrid\Services\ViteService;

/**
 * Class BlockRegistry
 *
 * Handles the registration of native Gutenberg blocks and their specific assets.
 *
 * @package RigidHybrid\Setup
 */
class BlockRegistry implements Bootable
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        add_action('init', [$this, 'registerBlocks']);
        add_filter('block_categories_all', [$this, 'registerCustomCategories'], 10, 1);
    }

    /**
     * Register all theme blocks.
     *
     * @return void
     */
    public function registerBlocks(): void
    {
        $this->registerThemeBlock('hero');
        // $this->registerThemeBlock('team-member');
    }

    /**
     * Helper to register a block and its assets via Vite.
     *
     * @param string $slug The folder name of the block.
     * @return void
     */
    private function registerThemeBlock(string $slug): void
    {
        $block_path = "/src/blocks/{$slug}";
        $block_dir = get_theme_file_path($block_path);

        // 1. Register editor script (tsx/jsx). The dependencies are the WordPress
        // packages our blocks import (see wpExternals in vite.config.js), so
        // WordPress loads those globals before our script runs.
        ViteService::enqueueAsset(
            "{$slug}-editor-script",
            "{$block_path}/index.tsx",
            ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-rich-text'],
            false
        );

        $block_args = [
            'editor_script' => "{$slug}-editor-script",
        ];

        // 2. Register frontend-only view script (if it exists)
        if (file_exists("{$block_dir}/view.ts")) {
            ViteService::enqueueAsset("{$slug}-view-script", "{$block_path}/view.ts", [], false);
            $block_args['view_script'] = "{$slug}-view-script";
        }

        // 3. Register block style (frontend + editor)
        $style_source = "{$block_path}/style.scss";
        if (
            file_exists("{$block_dir}/style.scss")
            && ViteService::registerStyle("{$slug}-block-style", $style_source)
        ) {
            $block_args['style'] = "{$slug}-block-style";
        }

        // 4. Register editor-only style
        $editor_style_source = "{$block_path}/editor.scss";
        if (
            file_exists("{$block_dir}/editor.scss")
            && ViteService::registerStyle("{$slug}-editor-style", $editor_style_source)
        ) {
            $block_args['editor_style'] = "{$slug}-editor-style";
        }

        // 5. Register the block
        register_block_type($block_dir, $block_args);
    }

    /**
     * Register custom block categories.
     *
     * @param list<array<string, mixed>> $categories Array of block categories.
     * @return list<array<string, mixed>>
     */
    public function registerCustomCategories(array $categories): array
    {
        $custom_category = [
            [
                'slug'  => 'theme-blocks',
                'title' => 'Theme Blocks',
                'icon'  => null,
            ],
        ];

        return array_merge($custom_category, $categories);
    }
}
