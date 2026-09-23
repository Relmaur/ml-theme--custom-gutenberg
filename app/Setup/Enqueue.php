<?php

namespace RigidHybrid\Setup;

use RigidHybrid\Core\Bootable;
use RigidHybrid\Services\ViteService;

/**
 * Class Enqueue
 *
 * Handles enqueuing global theme scripts and styles,
 * and marking Vite scripts as ES modules.
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
        add_filter('wp_script_attributes', [$this, 'addModuleType']);
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
     * Vite outputs ES modules, which browsers only run with type="module".
     * We change just that attribute (instead of rewriting the whole tag in
     * `script_loader_tag`), so WordPress keeps the tag's id and any inline
     * scripts or translations attached to the handle.
     *
     * @param array<string, mixed> $attributes Attributes of the <script> tag being printed.
     * @return array<string, mixed>
     */
    public function addModuleType(array $attributes): array
    {
        // WordPress sets id="{$handle}-js" on enqueued scripts; that's the only
        // link back to the handle this filter gets.
        $id = $attributes['id'] ?? null;
        if (!is_string($id) || substr($id, -3) !== '-js') {
            return $attributes;
        }

        $handle = substr($id, 0, -3);
        if (in_array($handle, ViteService::$moduleHandles, true)) {
            $attributes['type'] = 'module';
        }

        return $attributes;
    }
}
