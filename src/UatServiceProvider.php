<?php

namespace CleaniqueCoders\Uat;

use CleaniqueCoders\Uat\Commands\UatCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UatServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('uat')
            ->hasConfigFile()
            ->hasCommand(UatCommand::class);
    }
}
