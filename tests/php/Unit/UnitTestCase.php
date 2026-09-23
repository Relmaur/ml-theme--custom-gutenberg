<?php

declare(strict_types=1);

namespace RigidHybrid\Tests\Unit;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RigidHybrid\Services\ViteService;
use ReflectionProperty;

/**
 * Base class for unit tests.
 *
 * Brain Monkey replaces WordPress functions (add_action, apply_filters, ...)
 * with fakes we can inspect, so theme classes can be tested without loading
 * WordPress. setUp/tearDown must wrap every test, or fakes leak between tests.
 */
abstract class UnitTestCase extends TestCase
{
    // Turns Mockery expectations (e.g. ->once()) into PHPUnit assertions.
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->resetViteService();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * ViteService keeps per-request state in static properties. In PHPUnit
     * every test shares one PHP process, so reset it to a fresh "request".
     */
    private function resetViteService(): void
    {
        ViteService::$moduleHandles = [];

        $devServer = new ReflectionProperty(ViteService::class, 'devServerRunning');
        $devServer->setAccessible(true); // Required on PHP < 8.1.
        $devServer->setValue(null, null);
    }

    /**
     * Pretend the Vite dev server is (or isn't) running, without opening a socket.
     */
    protected function setDevServerRunning(bool $running): void
    {
        $devServer = new ReflectionProperty(ViteService::class, 'devServerRunning');
        $devServer->setAccessible(true);
        $devServer->setValue(null, $running);
    }
}
