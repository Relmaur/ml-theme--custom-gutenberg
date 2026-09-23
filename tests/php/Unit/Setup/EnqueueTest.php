<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit\Setup;

use Brain\Monkey\Functions;
use RigidHybrid\Services\ViteService;
use RigidHybrid\Setup\Enqueue;
use RigidHybrid\Tests\Unit\UnitTestCase;

/**
 * @covers \RigidHybrid\Setup\Enqueue
 */
final class EnqueueTest extends UnitTestCase
{
    public function testRegistersItsHooks(): void
    {
        $enqueue = new Enqueue();
        $enqueue->register();

        self::assertNotFalse(has_action('wp_enqueue_scripts', [$enqueue, 'enqueueFrontendAssets']));
        self::assertNotFalse(has_filter('wp_script_attributes', [$enqueue, 'addModuleType']));
    }

    public function testEnqueuesTheGlobalThemeBundle(): void
    {
        $this->setDevServerRunning(false);
        Functions\when('wp_cache_get')->justReturn(['src/js/main.js' => ['file' => 'main-abc.js']]);
        Functions\when('get_theme_file_uri')->alias(fn (string $path): string => 'https://example.test' . $path);

        Functions\expect('wp_register_script')
            ->once()
            ->with('rigid-theme-main', 'https://example.test/dist/main-abc.js', [], null, true);
        Functions\expect('wp_enqueue_script')->once()->with('rigid-theme-main');

        (new Enqueue())->enqueueFrontendAssets();
    }

    public function testMarksViteScriptsAsModulesAndKeepsTheirOtherAttributes(): void
    {
        ViteService::$moduleHandles = ['rigid-theme-main'];

        $attributes = (new Enqueue())->addModuleType([
            'src' => 'https://example.test/dist/main.js',
            'id'  => 'rigid-theme-main-js',
            'defer' => true,
        ]);

        self::assertSame([
            'src' => 'https://example.test/dist/main.js',
            'id'  => 'rigid-theme-main-js',
            'defer' => true,
            'type' => 'module',
        ], $attributes);
    }

    public function testLeavesOtherScriptsAlone(): void
    {
        ViteService::$moduleHandles = ['rigid-theme-main'];
        $jquery = ['src' => '/wp-includes/js/jquery.js', 'id' => 'jquery-core-js'];

        self::assertSame($jquery, (new Enqueue())->addModuleType($jquery));
    }

    public function testHandlesThatThemselvesEndInJs(): void
    {
        // WordPress appends "-js" to the handle; only that final suffix is removed.
        ViteService::$moduleHandles = ['main-js'];

        $attributes = (new Enqueue())->addModuleType(['id' => 'main-js-js']);

        self::assertSame('module', $attributes['type']);
    }

    /**
     * @dataProvider attributesWithoutAUsableId
     * @param array<string, mixed> $attributes
     */
    public function testIgnoresTagsWithoutAWordPressScriptId(array $attributes): void
    {
        ViteService::$moduleHandles = ['rigid-theme-main'];

        self::assertSame($attributes, (new Enqueue())->addModuleType($attributes));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public function attributesWithoutAUsableId(): array
    {
        return [
            'no id'           => [['src' => 'x.js']],
            'id not a string' => [['id' => 42]],
            'no -js suffix'   => [['id' => 'rigid-theme-main']],
        ];
    }
}
