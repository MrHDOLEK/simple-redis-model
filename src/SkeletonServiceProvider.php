<?php

declare(strict_types=1);

namespace MrHDOLEK\SimpleRedisModel;

use MrHDOLEK\SimpleRedisModel\Commands\SkeletonCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SkeletonServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name("skeleton")
            ->hasCommand(SkeletonCommand::class);
    }
}
