<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Integration\Setup;

use RigidHybrid\Services\ViteService;
use ReflectionProperty;
use WP_UnitTestCase;

/**
 * The <script> tags WordPress actually prints for Vite bundles.
 *
 * @covers \RigidHybrid\Setup\Enqueue
 * @covers \RigidHybrid\Services\ViteService
 */
final class ScriptTagTest extends WP_UnitTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        // Fresh "request" state, production mode, and a known manifest, so the
        // test doesn't depend on a local build or a running Vite dev server.
        ViteService::$moduleHandles = [];
        $dev_server = new ReflectionProperty(ViteService::class, 'devServerRunning');
        $dev_server->setAccessible(true);
        $dev_server->setValue(null, false);

        wp_cache_set(
            'rigid_hybrid_vite_manifest',
            ['src/js/main.js' => ['file' => 'main-abc123.js']],
            'rigid_hybrid_theme'
        );

        // Start from an empty script queue.
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
    }

    private function printedTag(): string
    {
        do_action('wp_enqueue_scripts');
        return get_echo('wp_print_scripts', ['rigid-theme-main']);
    }

    public function testPrintsTheThemeBundleAsAModule(): void
    {
        $tag = $this->printedTag();

        self::assertStringContainsString('type="module"', $tag);
        self::assertStringContainsString('dist/main-abc123.js', $tag);
    }

    public function testKeepsTheIdWordPressGivesTheTag(): void
    {
        self::assertStringContainsString('id="rigid-theme-main-js"', $this->printedTag());
    }

    public function testKeepsInlineScriptsAttachedToTheHandle(): void
    {
        // Regression: rewriting the whole tag in `script_loader_tag` used to
        // throw away inline scripts and translations for Vite handles.
        add_action('wp_enqueue_scripts', static function (): void {
            wp_add_inline_script('rigid-theme-main', 'window.rigidHybridConfig = {};', 'before');
        }, 20);

        self::assertStringContainsString('window.rigidHybridConfig = {};', $this->printedTag());
    }

    public function testLeavesOtherScriptsAsClassicScripts(): void
    {
        wp_enqueue_script('some-plugin', 'https://example.org/plugin.js', [], '1.0', true);

        $tag = get_echo('wp_print_scripts', ['some-plugin']);

        self::assertStringNotContainsString('type="module"', $tag);
    }
}
