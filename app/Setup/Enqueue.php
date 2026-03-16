<?php

namespace RigidHybrid\Setup;

use RigidHybrid\Core\Bootable;
use RigidHybrid\Services\ViteService;

/**
 * Class Enqueue
 *
 * Handles enqueuing global theme scripts and styles,
 * and filtering script tags for ES modules.
 *
 * @package RigidHybrid\Setup
 */
class Enqueue implements Bootable
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        add_filter('script_loader_tag', [$this, 'filterScriptTags'], 10, 3);
    }

    /**
     * Enqueue global frontend assets.
     *
     * @return void
     */
    public function enqueueFrontendAssets(): void
    {
        ViteService::enqueueAsset('rigid-theme-main', '/src/js/main.js', []);
    }

    /**
     * Add type="module" to scripts loaded via Vite.
     *
     * @param string $tag    The `<script>` tag for the enqueued script.
     * @param string $handle The script's registered handle.
     * @param string $src    The script's source URL.
     * @return string
     */
    public function filterScriptTags(string $tag, string $handle, string $src): string
    {
        if (in_array($handle, ViteService::$moduleHandles, true)) {
            return '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }
}
