<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Commands\UatCommand;
use CleaniqueCoders\Uat\UatServiceProvider;
use Spatie\LaravelPackageTools\Package;

beforeEach(function () {
    $this->provider = new UatServiceProvider(app());
});

it('can instantiate service provider', function () {
    expect($this->provider)->toBeInstanceOf(UatServiceProvider::class);
});

it('configures package correctly', function () {
    $package = new Package;

    $this->provider->configurePackage($package);

    expect($package->shortName())->toBe('uat');
});

it('registers UatCommand', function () {
    $package = new Package;

    $this->provider->configurePackage($package);

    $commands = $package->commands;

    expect($commands)->toContain(UatCommand::class);
});

it('has config file', function () {
    $package = new Package;

    $this->provider->configurePackage($package);

    // Check if package was configured to have a config file by checking configFileNames array
    expect($package->configFileNames)->toContain('uat');
});
