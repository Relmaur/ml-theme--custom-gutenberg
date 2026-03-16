<?php

namespace RigidHybrid\Services;

/**
 * Class ViteService
 *
 * Handles Vite development server detection, manifest parsing,
 * and VIP-compliant caching of asset paths.
 *
 * @package RigidHybrid\Services
 */
class ViteService
{
    private const MANIFEST_CACHE_KEY = 'rigid_hybrid_vite_manifest';
    private const MANIFEST_CACHE_GROUP = 'rigid_hybrid_theme';
    public const VITE_SERVER = 'http://localhost:3000';

    /**
     * Array to keep track of script handles that require type="module".
     * * @var string[]
     */
    public static array $moduleHandles = [];

    /**
     * Check if the Vite development server is running.
     *
     * @return bool
     */
    public static function isDevServerRunning(): bool
    {
        static $is_dev = null;
        if ($is_dev !== null) {
            return $is_dev;
        }

        $is_dev = false;
        // Suppress warnings with @ to avoid cluttering logs if dev server is off
        $handle = @fsockopen('localhost', 3000, $errno, $errstr, 0.1);
        if ($handle) {
            fclose($handle);
            $is_dev = true;
        }

        return $is_dev;
    }

    /**
     * Get the Vite manifest using VIP-compliant Object Caching.
     *
     * @return array<string, mixed>
     */
    public static function getManifest(): array
    {
        // 1. VIP Standard: Attempt to get from Object Cache first (RAM)
        $manifest = wp_cache_get(self::MANIFEST_CACHE_KEY, self::MANIFEST_CACHE_GROUP);

        if (false !== $manifest) {
            return $manifest;
        }

        // 2. Cache Miss: Read from the filesystem (Disk)
        $manifest_path = get_theme_file_path('/dist/.vite/manifest.json');
        if (!file_exists($manifest_path)) {
            $manifest_path = get_theme_file_path('/dist/manifest.json');
        }

        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);

            // 3. Save to Object Cache for 24 hours
            wp_cache_set(self::MANIFEST_CACHE_KEY, $manifest, self::MANIFEST_CACHE_GROUP, HOUR_IN_SECONDS * 24);
        } else {
            $manifest = [];
        }

        return $manifest;
    }

    /**
     * Register or enqueue a Vite asset.
     *
     * @param string   $handle       Script handle.
     * @param string   $entry_point  Entry point path (e.g., '/src/js/main.js').
     * @param string[] $dependencies Script dependencies.
     * @param bool     $enqueue      Whether to enqueue (true) or just register (false).
     * @return void
     */
    public static function enqueueAsset(string $handle, string $entry_point, array $dependencies = [], bool $enqueue = true): void
    {
        self::$moduleHandles[] = $handle;

        if (self::isDevServerRunning()) {
            if (!wp_script_is('vite-client', 'registered')) {
                wp_register_script('vite-client', self::VITE_SERVER . '/@vite/client', [], null, true);
                self::$moduleHandles[] = 'vite-client';
            }

            $dev_dependencies = array_merge(['vite-client'], $dependencies);
            wp_register_script($handle, self::VITE_SERVER . $entry_point, $dev_dependencies, null, true);

            if ($enqueue) {
                wp_enqueue_script($handle);
            }
        } else {
            $manifest = self::getManifest();
            if (empty($manifest)) return;

            $manifest_key = ltrim($entry_point, '/');

            if (isset($manifest[$manifest_key])) {
                $file = $manifest[$manifest_key]['file'];
                wp_register_script($handle, get_theme_file_uri('/dist/' . $file), $dependencies, null, true);

                if (!empty($manifest[$manifest_key]['css'])) {
                    foreach ($manifest[$manifest_key]['css'] as $index => $css_file) {
                        $style_handle = $index === 0 ? $handle . '-style' : $handle . '-style-' . $index;
                        wp_register_style($style_handle, get_theme_file_uri('/dist/' . $css_file));
                        if ($enqueue) {
                            wp_enqueue_style($style_handle);
                        }
                    }
                }

                if ($enqueue) {
                    wp_enqueue_script($handle);
                }
            }
        }
    }
}
