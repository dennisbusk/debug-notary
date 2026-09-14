<?php

namespace Dennisbusk\DebugNotary\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void info( string $message, array $context = [] )
 * @method static void error( string $message, array $context = [] )
 * @method static void warning( string $message, array $context = [] )
 * @method static void critical( string $message, array $context = [] )
 * @method static void report( string $message, array $options = [] )
 * @method static void sendToCentral( array $payload )
 * @method static array resolveUserContext()
 * @method static array maskData( array $data )
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
