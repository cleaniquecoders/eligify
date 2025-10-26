<?php

namespace CleaniqueCoders\Eligify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\Eligify\Eligify
 */
class Eligify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CleaniqueCoders\Eligify\Eligify::class;
    }
}
