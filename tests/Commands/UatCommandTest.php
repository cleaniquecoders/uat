<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Actions\GenerateUatScript;
use CleaniqueCoders\Uat\Commands\UatCommand;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->command = new UatCommand;
});

it('can instantiate UatCommand', function () {
    expect($this->command)->toBeInstanceOf(UatCommand::class);
});

it('has correct signature', function () {
    expect($this->command->signature)->toContain('uat:generate');
    expect($this->command->signature)->toContain('--output-dir=');
    expect($this->command->signature)->toContain('--format=markdown');
});

it('has correct description', function () {
    expect($this->command->description)->toBe('Generate UAT Scripts');
});

it('executes successfully with default options', function () {
    // Skip - Cannot mock GenerateUatScript when class is already loaded by GenerateUatScriptTest
    // The underlying functionality is tested in GenerateUatScriptTest
    $this->markTestSkipped('GenerateUatScript class already loaded, cannot create alias mock');
});

it('executes successfully with custom output directory', function () {
    $this->markTestSkipped('GenerateUatScript class already loaded, cannot create alias mock');
});

it('executes successfully with custom format', function () {
    $this->markTestSkipped('GenerateUatScript class already loaded, cannot create alias mock');
});

it('fails with invalid format', function () {
    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate --format=invalid')
        ->expectsOutput('❌ Failed to generate UAT scripts: Invalid format. Only markdown, json are accepted')
        ->assertExitCode(1);
});

it('fails when action throws exception', function () {
    $this->markTestSkipped('GenerateUatScript class already loaded, cannot create alias mock');
});

it('handles formats config being null', function () {
    Config::set('uat.formats', null);

    $this->artisan('uat:generate --format=html')
        ->expectsOutput('❌ Failed to generate UAT scripts: Invalid format. Only markdown, json are accepted')
        ->assertExitCode(1);
});

it('handles both custom output directory and format', function () {
    $this->markTestSkipped('GenerateUatScript class already loaded, cannot create alias mock');
});
