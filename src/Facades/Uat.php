<?php

namespace CleaniqueCoders\Uat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\Uat\Uat
 */
class Uat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CleaniqueCoders\Uat\Uat::class;
    }
}
