<?php

declare(strict_types=1);

namespace RigidHybrid\Core;

/**
 * Interface Bootable
 * 
 * Any class that needs to register WordPress hooks (actions/filters)
 * should implement this interface.
 * 
 * @package RigidHybrid\Core
 */

interface Bootable
{
    /**
     * Register the necessary WordPress hooks.
     * 
     * @return void
     */
    public function register(): void;
}
