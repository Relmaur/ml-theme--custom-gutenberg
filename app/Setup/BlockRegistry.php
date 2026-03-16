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

        // 1. Register editor script (tsx/jsx)
        ViteService::enqueueAsset("{$slug}-editor-script", "{$block_path}/index.tsx", ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'], false);

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
        if (file_exists(get_theme_file_path($style_source))) {
            if (ViteService::isDevServerRunning()) {
                wp_register_style("{$slug}-block-style", ViteService::VITE_SERVER . $style_source);
            } else {
                $manifest = ViteService::getManifest();
                $style_key = ltrim($style_source, '/');
                if (isset($manifest[$style_key])) {
                    wp_register_style("{$slug}-block-style", get_theme_file_uri('/dist/' . $manifest[$style_key]['file']));
                }
            }
            $block_args['style'] = "{$slug}-block-style";
        }

        // 4. Register editor-only style
        $editor_style_source = "{$block_path}/editor.scss";
        if (file_exists(get_theme_file_path($editor_style_source))) {
            if (ViteService::isDevServerRunning()) {
                wp_register_style("{$slug}-editor-style", ViteService::VITE_SERVER . $editor_style_source);
            } else {
                $manifest = ViteService::getManifest();
                $editor_key = ltrim($editor_style_source, '/');
                if (isset($manifest[$editor_key])) {
                    wp_register_style("{$slug}-editor-style", get_theme_file_uri('/dist/' . $manifest[$editor_key]['file']));
                }
            }
            $block_args['editor_style'] = "{$slug}-editor-style";
        }

        // 5. Register the block
        register_block_type($block_dir, $block_args);
    }

    /**
     * Register custom block categories.
     *
     * @param array[] $categories Array of block categories.
     * @return array[]
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