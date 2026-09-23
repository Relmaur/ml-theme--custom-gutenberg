<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit\Setup;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use RigidHybrid\Setup\ThemeMode;
use RigidHybrid\Tests\Unit\UnitTestCase;

/**
 * Mode selection and hook wiring. What each mode actually DOES to the editor
 * (theme.json, allowed blocks, template lock) needs real WordPress classes,
 * so it's covered in tests/php/Integration/Setup/ThemeModeTest.php.
 *
 * @covers \RigidHybrid\Setup\ThemeMode
 */
final class ThemeModeTest extends UnitTestCase
{
    public function testDefaultsToBuilderModeWithNoRestrictions(): void
    {
        $mode = new ThemeMode();
        $mode->register();

        self::assertNotFalse(has_filter('wp_theme_json_data_theme', [$mode, 'applyDesignSettings']));
        self::assertFalse(has_filter('allowed_block_types_all', [$mode, 'limitToThemeBlocks']));
        self::assertFalse(has_filter('block_editor_settings_all', [$mode, 'lockLayout']));
    }

    public function testRigidModeAddsTheBlockAndLayoutRestrictions(): void
    {
        Filters\expectApplied('rigid_hybrid/mode')->once()->with('builder')->andReturn('rigid');

        $mode = new ThemeMode();
        $mode->register();

        self::assertNotFalse(has_filter('wp_theme_json_data_theme', [$mode, 'applyDesignSettings']));
        self::assertNotFalse(has_filter('allowed_block_types_all', [$mode, 'limitToThemeBlocks']));
        self::assertNotFalse(has_filter('block_editor_settings_all', [$mode, 'lockLayout']));
    }

    /**
     * Constants can't be undefined again, so this runs in its own PHP process.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReadsTheModeFromTheWpConfigConstant(): void
    {
        define('RIGID_HYBRID_MODE', 'rigid');
        // The filter receives the constant's value.
        Filters\expectApplied('rigid_hybrid/mode')->once()->with('rigid');

        $mode = new ThemeMode();
        $mode->register();

        self::assertNotFalse(has_filter('allowed_block_types_all', [$mode, 'limitToThemeBlocks']));
    }

    public function testAnUnknownModeFallsBackToBuilderAndWarnsDevelopers(): void
    {
        Filters\expectApplied('rigid_hybrid/mode')->andReturn('rigdi');
        Functions\stubEscapeFunctions();
        Functions\expect('_doing_it_wrong')
            ->once()
            ->with(
                ThemeMode::class . '::resolveMode',
                Mockery::pattern('/Unknown RIGID_HYBRID_MODE: rigdi\. Falling back to builder\./'),
                '1.0.0'
            );

        $mode = new ThemeMode();
        $mode->register();

        self::assertFalse(has_filter('allowed_block_types_all', [$mode, 'limitToThemeBlocks']));
    }

    public function testModeMatchingIsStrict(): void
    {
        // "Rigid" (capital R) or " rigid" must not silently count as rigid...
        // nor as anything else: the allow-list is exact.
        Filters\expectApplied('rigid_hybrid/mode')->andReturn('Rigid');
        Functions\stubEscapeFunctions();
        Functions\expect('_doing_it_wrong')->once();

        $mode = new ThemeMode();
        $mode->register();

        self::assertFalse(has_filter('allowed_block_types_all', [$mode, 'limitToThemeBlocks']));
    }

    public function testRigidModeCoversOnlyPagesByDefault(): void
    {
        self::assertSame(['page'], (new ThemeMode())->rigidPostTypes());
    }

    public function testSitesCanChooseWhichPostTypesAreRigid(): void
    {
        Filters\expectApplied('rigid_hybrid/rigid_post_types')
            ->once()
            ->with(['page'])
            ->andReturn(['page', 'post']);

        self::assertSame(['page', 'post'], (new ThemeMode())->rigidPostTypes());
    }

    public function testDropsInvalidPostTypesFromTheFilter(): void
    {
        Filters\expectApplied('rigid_hybrid/rigid_post_types')->andReturn(['page', '', 42, null, 'post']);

        self::assertSame(['page', 'post'], (new ThemeMode())->rigidPostTypes());
    }

    public function testAFilterThatDoesNotReturnAnArrayFallsBackToPages(): void
    {
        Filters\expectApplied('rigid_hybrid/rigid_post_types')->andReturn('page,post');
        Functions\stubEscapeFunctions();
        Functions\expect('_doing_it_wrong')
            ->once()
            ->with(Mockery::any(), Mockery::pattern('/must return an array, string given/'), '1.0.0');

        self::assertSame(['page'], (new ThemeMode())->rigidPostTypes());
    }

    public function testANonStringFromAFilterIsReportedByItsType(): void
    {
        // A badly written filter could return anything; printing an array
        // would itself raise a PHP warning, so only its type is reported.
        Filters\expectApplied('rigid_hybrid/mode')->andReturn(['rigid']);
        Functions\stubEscapeFunctions();
        Functions\expect('_doing_it_wrong')
            ->once()
            ->with(Mockery::any(), Mockery::pattern('/RIGID_HYBRID_MODE: array\./'), Mockery::any());

        (new ThemeMode())->register();
    }
}
