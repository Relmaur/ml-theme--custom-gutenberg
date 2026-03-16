<?php

declare(strict_types=1);

namespace RigidHybrid;

use RigidHybrid\Setup\ThemeSetup;
use RigidHybrid\Setup\Enqueue;
use RigidHybrid\Setup\BlockRegistry;

/**
 * Class Theme
 * 
 * The main bootstrapper for the Rigid Hybrid theme.
 * This class acts as a central registry to initialize all other theme components.
 * 
 * @package RigidHybrid
 */
final class Theme
{
    /**
     * Array of classes to instantiate and register.
     * 
     * @var class-string[]
     */
    private array $services = [
        ThemeSetup::class,
        Enqueue::class,
        BlockRegistry::class,
    ];

    /**
     * Initialize the theme by instantiating services and calling their register methods.
     * 
     * @return void
     */
    public function init(): void
    {
        foreach ($this->services as $service) {
            if (class_exists($service)) {
                $instance = new $service();

                // If class implements the Bootable interface, call register()
                if (method_exists($instance, 'register')) {
                    $instance->register();
                }
            }
        }
    }
}
