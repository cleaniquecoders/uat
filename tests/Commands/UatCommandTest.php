<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Actions\GenerateUatScript;
use CleaniqueCoders\Uat\Commands\UatCommand;
use Illuminate\Support\Facades\Config;
use Lorisleiva\Actions\Facades\Actions;

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
    // Mock the action
    Actions::mock(GenerateUatScript::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->with(null, 'markdown')
            ->andReturn([
                'directory' => '/test/directory',
                'generated_files' => [
                    '01-project-info.md',
                    '02-users.md',
                ],
                'date' => '2023-01-01',
            ]);
    });

    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate')
        ->expectsOutput('🚀 Starting UAT Script Generation...')
        ->expectsOutput('✅ UAT scripts generated successfully!')
        ->expectsOutput('📁 Directory: /test/directory')
        ->expectsOutput('📝 Generated Markdown files:')
        ->expectsOutput('   - 01-project-info.md')
        ->expectsOutput('   - 02-users.md')
        ->expectsOutput('📋 Generated UAT documentation for date: 2023-01-01')
        ->expectsOutput('🔍 Review the generated files before proceeding with UAT testing.')
        ->assertExitCode(0);
});

it('executes successfully with custom output directory', function () {
    Actions::mock(GenerateUatScript::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->with('/custom/output', 'markdown')
            ->andReturn([
                'directory' => '/custom/output',
                'generated_files' => ['test.md'],
                'date' => '2023-01-01',
            ]);
    });

    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate --output-dir=/custom/output')
        ->assertExitCode(0);
});

it('executes successfully with custom format', function () {
    Actions::mock(GenerateUatScript::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->with(null, 'json')
            ->andReturn([
                'directory' => '/test/directory',
                'generated_files' => ['test.json'],
                'date' => '2023-01-01',
            ]);
    });

    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate --format=json')
        ->assertExitCode(0);
});

it('fails with invalid format', function () {
    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate --format=invalid')
        ->expectsOutput('❌ Failed to generate UAT scripts: Invalid format. Only markdown, json are accepted')
        ->assertExitCode(1);
});

it('fails when action throws exception', function () {
    Actions::mock(GenerateUatScript::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->andThrow(new Exception('Test exception'));
    });

    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate')
        ->expectsOutput('❌ Failed to generate UAT scripts: Test exception')
        ->assertExitCode(1);
});

it('handles formats config being null', function () {
    Config::set('uat.formats', null);

    $this->artisan('uat:generate --format=markdown')
        ->expectsOutput('❌ Failed to generate UAT scripts: Invalid format. Only markdown, json are accepted')
        ->assertExitCode(1);
});

it('handles both custom output directory and format', function () {
    Actions::mock(GenerateUatScript::class, function ($mock) {
        $mock->shouldReceive('run')
            ->once()
            ->with('/custom/path', 'json')
            ->andReturn([
                'directory' => '/custom/path',
                'generated_files' => ['test.json'],
                'date' => '2023-01-01',
            ]);
    });

    Config::set('uat.formats', ['markdown', 'json']);

    $this->artisan('uat:generate --output-dir=/custom/path --format=json')
        ->assertExitCode(0);
});
