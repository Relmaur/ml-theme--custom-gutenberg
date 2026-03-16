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
        if (file_exists("{$block_dir}/view.js")) {
            ViteService::enqueueAsset("{$slug}-view-script", "{$block_path}/view.js", [], false);
            $block_args['view_script'] = "{$slug}-view-script";
        }

        // 3. Handle Production Block Styles
        if (!ViteService::isDevServerRunning()) {
            $manifest = ViteService::getManifest();
            $view_key = ltrim($block_path, '/') . '/view.js';
            $index_key = ltrim($block_path, '/') . '/index.tsx';

            $manifest_key = isset($manifest[$view_key]) ? $view_key : (isset($manifest[$index_key]) ? $index_key : null);

            if ($manifest_key && !empty($manifest[$manifest_key]['css'])) {
                $style_handle = "{$slug}-block-style";
                foreach ($manifest[$manifest_key]['css'] as $index => $css_file) {
                    $handle = $index === 0 ? $style_handle : "{$style_handle}-{$index}";
                    wp_register_style($handle, get_theme_file_uri('/dist/' . $css_file));
                }
                $block_args['style'] = $style_handle;
            }
        }

        // 4. Register the block
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
