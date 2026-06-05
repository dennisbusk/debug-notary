<?php

namespace Dennisbusk\DebugNotary\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void info( string $message, array $context = [] )
 * @method static void error( string $message, array $context = [] )
 * @method static void warning( string $message, array $context = [] )
 * @method static void critical( string $message, array $context = [] )
 * @method static void routes()
 *
 * @see \Dennisbusk\DebugNotary\DebugNotary
 */
class DebugNotary extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'debug-notary';
    }
}
