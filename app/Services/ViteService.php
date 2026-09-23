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
     *
     * @var list<string>
     */
    public static array $moduleHandles = [];

    /**
     * Result of the dev-server probe, cached for the rest of the request.
     * Null = not probed yet.
     */
    private static ?bool $devServerRunning = null;

    /**
     * Check if the Vite development server is running.
     *
     * @return bool
     */
    public static function isDevServerRunning(): bool
    {
        if (self::$devServerRunning !== null) {
            return self::$devServerRunning;
        }

        self::$devServerRunning = false;
        // Suppress warnings with @ to avoid cluttering logs if dev server is off.
        // This is a network socket, so WP_Filesystem doesn't apply.
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fsockopen
        $handle = @fsockopen('localhost', 3000, $errno, $errstr, 0.1);
        if ($handle) {
            fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            self::$devServerRunning = true;
        }

        return self::$devServerRunning;
    }

    /**
     * Get the Vite manifest using VIP-compliant Object Caching.
     *
     * The result is raw decoded JSON: use getManifestEntry() to read it safely.
     *
     * @return array<mixed>
     */
    public static function getManifest(): array
    {
        // 1. VIP Standard: Attempt to get from Object Cache first (RAM)
        $cached = wp_cache_get(self::MANIFEST_CACHE_KEY, self::MANIFEST_CACHE_GROUP);

        if (is_array($cached)) {
            return $cached;
        }

        // 2. Cache Miss: Read from the filesystem (Disk)
        $manifest_path = get_theme_file_path('/dist/.vite/manifest.json');
        if (!file_exists($manifest_path)) {
            $manifest_path = get_theme_file_path('/dist/manifest.json');
        }

        if (!file_exists($manifest_path)) {
            return [];
        }

        // A half-written or corrupt manifest (e.g. mid-deploy) must not fatal
        // every page. Treat it as "no assets" and don't cache it, so the next
        // request tries again once the file is complete.
        $contents = file_get_contents($manifest_path);
        $manifest = is_string($contents) ? json_decode($contents, true) : null;
        if (!is_array($manifest)) {
            return [];
        }

        // 3. Save to Object Cache for 24 hours
        wp_cache_set(self::MANIFEST_CACHE_KEY, $manifest, self::MANIFEST_CACHE_GROUP, HOUR_IN_SECONDS * 24);

        return $manifest;
    }

    /**
     * Look up one built asset by its source path (e.g. '/src/js/main.js').
     *
     * The manifest is JSON from disk, so every field is type-checked here once,
     * and callers get a clean, predictable shape.
     *
     * @param string $entry_point Source path, with or without the leading slash.
     * @return array{file: non-empty-string, css: list<non-empty-string>}|null Null if missing or malformed.
     */
    public static function getManifestEntry(string $entry_point): ?array
    {
        $manifest = self::getManifest();
        $entry = $manifest[ltrim($entry_point, '/')] ?? null;

        if (!is_array($entry) || !is_string($entry['file'] ?? null) || $entry['file'] === '') {
            return null;
        }

        // CSS files Vite extracted from this entry (e.g. SCSS imported by main.js).
        $css = [];
        $css_entries = $entry['css'] ?? [];
        if (is_array($css_entries)) {
            foreach ($css_entries as $css_file) {
                if (is_string($css_file) && $css_file !== '') {
                    $css[] = $css_file;
                }
            }
        }

        return ['file' => $entry['file'], 'css' => $css];
    }

    /**
     * Register or enqueue a Vite asset.
     *
     * @param non-empty-string       $handle       Script handle.
     * @param non-empty-string       $entry_point  Entry point path (e.g., '/src/js/main.js').
     * @param list<non-empty-string> $dependencies Script dependencies.
     * @param bool                   $enqueue      Whether to enqueue (true) or just register (false).
     * @return void
     */
    public static function enqueueAsset(
        string $handle,
        string $entry_point,
        array $dependencies = [],
        bool $enqueue = true
    ): void {
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
            $entry = self::getManifestEntry($entry_point);
            if ($entry === null) {
                return;
            }

            wp_register_script($handle, self::distUrl($entry['file']), $dependencies, null, true);

            foreach ($entry['css'] as $index => $css_file) {
                $style_handle = $index === 0 ? $handle . '-style' : $handle . '-style-' . $index;
                wp_register_style($style_handle, self::distUrl($css_file));
                if ($enqueue) {
                    wp_enqueue_style($style_handle);
                }
            }

            if ($enqueue) {
                wp_enqueue_script($handle);
            }
        }
    }

    /**
     * Register a standalone stylesheet entry (e.g. a block's style.scss).
     *
     * In dev, Vite compiles the SCSS on request; in production the hashed CSS
     * file is looked up in the manifest.
     *
     * @param non-empty-string $handle Style handle.
     * @param non-empty-string $source Source path (e.g. '/src/blocks/hero/style.scss').
     * @return bool Whether a style was registered (false if missing from the build).
     */
    public static function registerStyle(string $handle, string $source): bool
    {
        if (self::isDevServerRunning()) {
            return wp_register_style($handle, self::VITE_SERVER . $source);
        }

        $entry = self::getManifestEntry($source);
        if ($entry === null) {
            return false;
        }

        return wp_register_style($handle, self::distUrl($entry['file']));
    }

    /**
     * Public URL of a built file in dist/.
     *
     * @param non-empty-string $file File name from the manifest.
     * @return non-empty-string
     */
    private static function distUrl(string $file): string
    {
        $url = get_theme_file_uri('/dist/' . $file);

        // get_theme_file_uri() always returns a URL; this guard only exists to
        // prove to static analysis that it's never empty.
        return $url !== '' ? $url : '/dist/' . $file;
    }
}
