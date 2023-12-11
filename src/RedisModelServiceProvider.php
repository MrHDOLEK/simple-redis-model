<?php

declare(strict_types=1);

namespace MrHDOLEK\SimpleRedisModel;

use MrHDOLEK\SimpleRedisModel\Commands\RedisModelMakerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RedisModelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name("SimpleRedisModel")
            ->hasCommand(RedisModelMakerCommand::class);
    }
}
