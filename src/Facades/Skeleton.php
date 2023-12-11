<?php

declare(strict_types=1);

namespace MrHDOLEK\SimpleRedisModel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MrHDOLEK\SimpleRedisModel\Skeleton
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MrHDOLEK\SimpleRedisModel\Skeleton::class;
    }
}
