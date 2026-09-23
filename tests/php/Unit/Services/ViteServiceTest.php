<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit\Services;

use Brain\Monkey\Functions;
use RigidHybrid\Services\ViteService;
use RigidHybrid\Tests\Unit\UnitTestCase;

/**
 * @covers \RigidHybrid\Services\ViteService
 */
final class ViteServiceTest extends UnitTestCase
{
    private const THEME_URL = 'https://example.test/wp-content/themes/rigid';

    /** A throwaway "theme folder" so tests can write manifest files. */
    private string $themeDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeDir = sys_get_temp_dir() . '/rigid-hybrid-' . uniqid();
        mkdir($this->themeDir . '/dist/.vite', 0777, true);

        Functions\when('get_theme_file_path')->alias(fn (string $path): string => $this->themeDir . $path);
        Functions\when('get_theme_file_uri')->alias(fn (string $path): string => self::THEME_URL . $path);
    }

    protected function tearDown(): void
    {
        foreach (['/dist/.vite/manifest.json', '/dist/manifest.json'] as $file) {
            if (file_exists($this->themeDir . $file)) {
                unlink($this->themeDir . $file);
            }
        }
        rmdir($this->themeDir . '/dist/.vite');
        rmdir($this->themeDir . '/dist');
        rmdir($this->themeDir);

        parent::tearDown();
    }

    private function writeManifest(string $contents, string $path = '/dist/.vite/manifest.json'): void
    {
        file_put_contents($this->themeDir . $path, $contents);
    }

    /** Simulate an empty object cache. */
    private function givenEmptyCache(): void
    {
        Functions\when('wp_cache_get')->justReturn(false);
    }

    // --- getManifest() ---------------------------------------------------

    public function testReadsTheViteManifestAndCachesItForADay(): void
    {
        $this->givenEmptyCache();
        $this->writeManifest('{"src/js/main.js":{"file":"main-abc.js"}}');

        Functions\expect('wp_cache_set')
            ->once()
            ->with(
                'rigid_hybrid_vite_manifest',
                ['src/js/main.js' => ['file' => 'main-abc.js']],
                'rigid_hybrid_theme',
                86400
            );

        self::assertSame(['src/js/main.js' => ['file' => 'main-abc.js']], ViteService::getManifest());
    }

    public function testFallsBackToTheLegacyManifestLocation(): void
    {
        // Vite 4 wrote dist/manifest.json; Vite 5+ writes dist/.vite/manifest.json.
        $this->givenEmptyCache();
        Functions\when('wp_cache_set')->justReturn(true);
        $this->writeManifest('{"legacy":{"file":"legacy.js"}}', '/dist/manifest.json');

        self::assertSame(['legacy' => ['file' => 'legacy.js']], ViteService::getManifest());
    }

    public function testUsesTheObjectCacheWithoutTouchingTheDisk(): void
    {
        Functions\when('wp_cache_get')->justReturn(['cached' => ['file' => 'cached.js']]);
        Functions\expect('wp_cache_set')->never();

        self::assertSame(['cached' => ['file' => 'cached.js']], ViteService::getManifest());
    }

    public function testReturnsAnEmptyManifestWhenNothingIsBuilt(): void
    {
        $this->givenEmptyCache();
        Functions\expect('wp_cache_set')->never();

        self::assertSame([], ViteService::getManifest());
    }

    public function testACorruptManifestDoesNotFatalAndIsNotCached(): void
    {
        // e.g. the file is read while a deploy is still writing it.
        $this->givenEmptyCache();
        $this->writeManifest('{"src/js/main.js": {"fi');
        Functions\expect('wp_cache_set')->never();

        self::assertSame([], ViteService::getManifest());
    }

    public function testIgnoresACacheEntryThatIsNotAnArray(): void
    {
        Functions\when('wp_cache_get')->justReturn('garbage');
        Functions\when('wp_cache_set')->justReturn(true);
        $this->writeManifest('{"fresh":{"file":"fresh.js"}}');

        self::assertSame(['fresh' => ['file' => 'fresh.js']], ViteService::getManifest());
    }

    // --- getManifestEntry() ----------------------------------------------

    public function testReturnsANormalisedEntryForASourcePath(): void
    {
        Functions\when('wp_cache_get')->justReturn([
            'src/js/main.js' => ['file' => 'main-abc.js', 'css' => ['main-abc.css'], 'isEntry' => true],
        ]);

        $expected = ['file' => 'main-abc.js', 'css' => ['main-abc.css']];
        self::assertSame($expected, ViteService::getManifestEntry('/src/js/main.js'));
        self::assertSame($expected, ViteService::getManifestEntry('src/js/main.js'));
    }

    public function testReturnsNullForAnAssetThatWasNotBuilt(): void
    {
        Functions\when('wp_cache_get')->justReturn(['src/js/main.js' => ['file' => 'main.js']]);

        self::assertNull(ViteService::getManifestEntry('/src/blocks/new-block/index.tsx'));
    }

    /**
     * @dataProvider malformedEntries
     * @param mixed $entry
     */
    public function testRejectsMalformedEntries($entry): void
    {
        Functions\when('wp_cache_get')->justReturn(['src/js/main.js' => $entry]);

        self::assertNull(ViteService::getManifestEntry('/src/js/main.js'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public function malformedEntries(): array
    {
        return [
            'not an object'   => ['main.js'],
            'no file'         => [['css' => []]],
            'file not string' => [['file' => 123]],
            'empty file'      => [['file' => '']],
        ];
    }

    public function testDropsInvalidCssItems(): void
    {
        Functions\when('wp_cache_get')->justReturn([
            'src/js/main.js' => ['file' => 'main.js', 'css' => ['ok.css', '', 42, null]],
        ]);

        self::assertSame(['file' => 'main.js', 'css' => ['ok.css']], ViteService::getManifestEntry('/src/js/main.js'));
    }

    // --- enqueueAsset() ----------------------------------------------------

    public function testProductionRegistersTheHashedFileAndItsCss(): void
    {
        $this->setDevServerRunning(false);
        Functions\when('wp_cache_get')->justReturn([
            'src/js/main.js' => ['file' => 'main-abc.js', 'css' => ['a.css', 'b.css']],
        ]);

        Functions\expect('wp_register_script')
            ->once()
            ->with('theme-main', self::THEME_URL . '/dist/main-abc.js', ['jquery'], null, true);
        Functions\expect('wp_register_style')->once()->with('theme-main-style', self::THEME_URL . '/dist/a.css');
        Functions\expect('wp_register_style')->once()->with('theme-main-style-1', self::THEME_URL . '/dist/b.css');
        Functions\expect('wp_enqueue_style')->twice();
        Functions\expect('wp_enqueue_script')->once()->with('theme-main');

        ViteService::enqueueAsset('theme-main', '/src/js/main.js', ['jquery']);

        self::assertSame(['theme-main'], ViteService::$moduleHandles);
    }

    public function testRegisterOnlyModeNeverEnqueues(): void
    {
        $this->setDevServerRunning(false);
        Functions\when('wp_cache_get')->justReturn(['src/js/main.js' => ['file' => 'main.js', 'css' => ['a.css']]]);
        Functions\when('wp_register_script')->justReturn(true);
        Functions\when('wp_register_style')->justReturn(true);

        Functions\expect('wp_enqueue_script')->never();
        Functions\expect('wp_enqueue_style')->never();

        ViteService::enqueueAsset('theme-main', '/src/js/main.js', [], false);
    }

    public function testProductionSkipsAssetsMissingFromTheBuild(): void
    {
        $this->setDevServerRunning(false);
        Functions\when('wp_cache_get')->justReturn([]);
        Functions\expect('wp_register_script')->never();

        ViteService::enqueueAsset('theme-main', '/src/js/main.js');
    }

    public function testDevServerServesSourceFilesThroughTheViteClient(): void
    {
        $this->setDevServerRunning(true);
        Functions\when('wp_script_is')->justReturn(false);

        Functions\expect('wp_register_script')
            ->once()
            ->with('vite-client', 'http://localhost:3000/@vite/client', [], null, true);
        Functions\expect('wp_register_script')
            ->once()
            ->with('theme-main', 'http://localhost:3000/src/js/main.js', ['vite-client', 'wp-i18n'], null, true);
        Functions\expect('wp_enqueue_script')->once()->with('theme-main');

        ViteService::enqueueAsset('theme-main', '/src/js/main.js', ['wp-i18n']);

        // Both must be printed as <script type="module">.
        self::assertSame(['theme-main', 'vite-client'], ViteService::$moduleHandles);
    }

    // --- registerStyle() ---------------------------------------------------

    public function testRegisterStyleUsesTheDevServerWhenRunning(): void
    {
        $this->setDevServerRunning(true);
        Functions\expect('wp_register_style')
            ->once()
            ->with('hero-block-style', 'http://localhost:3000/src/blocks/hero/style.scss')
            ->andReturn(true);

        self::assertTrue(ViteService::registerStyle('hero-block-style', '/src/blocks/hero/style.scss'));
    }

    public function testRegisterStyleUsesTheBuiltCssInProduction(): void
    {
        $this->setDevServerRunning(false);
        Functions\when('wp_cache_get')->justReturn(['src/blocks/hero/style.scss' => ['file' => 'hero-1.css']]);
        Functions\expect('wp_register_style')
            ->once()
            ->with('hero-block-style', self::THEME_URL . '/dist/hero-1.css')
            ->andReturn(true);

        self::assertTrue(ViteService::registerStyle('hero-block-style', '/src/blocks/hero/style.scss'));
    }

    public function testRegisterStyleReportsAStyleMissingFromTheBuild(): void
    {
        $this->setDevServerRunning(false);
        Functions\when('wp_cache_get')->justReturn([]);
        Functions\expect('wp_register_style')->never();

        self::assertFalse(ViteService::registerStyle('hero-block-style', '/src/blocks/hero/style.scss'));
    }
}
