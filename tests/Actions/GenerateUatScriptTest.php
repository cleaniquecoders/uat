<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Actions\GenerateUatScript;
use CleaniqueCoders\Uat\Contracts\Data;
use CleaniqueCoders\Uat\Contracts\Presentation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mockery as m;

beforeEach(function () {
    $this->action = new GenerateUatScript;

    // Create a temporary test directory
    $this->testDir = storage_path('testing/uat');

    // Clean up any existing test directory
    if (File::exists($this->testDir)) {
        File::deleteDirectory($this->testDir);
    }
});

afterEach(function () {
    // Clean up test directory
    if (File::exists($this->testDir)) {
        File::deleteDirectory($this->testDir);
    }

    m::close();
});

it('can instantiate GenerateUatScript action', function () {
    expect($this->action)->toBeInstanceOf(GenerateUatScript::class);
});

it('throws exception when data service does not implement Data contract', function () {
    Config::set('uat.services.data', 'invalid-service');

    expect(fn () => $this->action->handle($this->testDir, 'json'))
        ->toThrow(Exception::class, 'must implements CleaniqueCoders\Uat\Contracts\Data interface');
});

it('throws exception when presentation does not implement Presentation contract', function () {
    // Mock a valid data service
    $dataService = m::mock(Data::class);
    Config::set('uat.services.data', $dataService);

    // Set invalid presentation
    Config::set('uat.formats.json', 'invalid-presentation');

    expect(fn () => $this->action->handle($this->testDir, 'json'))
        ->toThrow(Exception::class, 'must implements CleaniqueCoders\Uat\Contracts\Presentation interface');
});

it('successfully generates UAT script files', function () {
    // Mock data service
    $dataService = m::mock(Data::class);
    $dataService->shouldReceive('getProjectInformation')
        ->once()
        ->andReturn([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'version' => '1.0.0',
        ]);

    $dataService->shouldReceive('getUsers')
        ->once()
        ->andReturn(new Collection([
            [
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
        ]));

    $dataService->shouldReceive('getAvailableModules')
        ->once()
        ->andReturn([
            [
                'module' => 'Dashboard',
                'routes' => [
                    [
                        'uri' => '/',
                        'name' => 'dashboard',
                        'action' => 'DashboardController@index',
                        'middleware' => ['web'],
                        'prerequisites' => [],
                    ],
                ],
            ],
        ]);

    // Mock presentation service
    $presentation = m::mock(Presentation::class);
    $presentation->shouldReceive('getExtension')
        ->andReturn('json');

    $presentation->shouldReceive('generateProjectInfo')
        ->once()
        ->andReturn('{"project": "info"}');

    $presentation->shouldReceive('generateUsers')
        ->once()
        ->andReturn('{"users": "data"}');

    $presentation->shouldReceive('generateAvailableModules')
        ->once()
        ->andReturn('{"modules": "overview"}');

    $presentation->shouldReceive('generateModuleTestSuite')
        ->once()
        ->andReturn('{"module": "test suite"}');

    Config::set('uat.services.data', $dataService);
    Config::set('uat.formats.json', $presentation);
    Config::set('uat.directory', 'testing/uat');

    $result = $this->action->handle($this->testDir, 'json');

    expect($result)->toHaveKey('directory');
    expect($result)->toHaveKey('generated_files');
    expect($result)->toHaveKey('date');
    expect($result['generated_files'])->toHaveCount(4); // project info, users, modules overview, module test suite
});

it('cleans existing directory before generating new files', function () {
    // Create directory with existing files
    File::makeDirectory($this->testDir, 0755, true);
    File::put($this->testDir.'/existing-file.txt', 'existing content');

    expect(File::exists($this->testDir.'/existing-file.txt'))->toBeTrue();

    // Mock dependencies
    $dataService = m::mock(Data::class);
    $dataService->shouldReceive('getProjectInformation')->andReturn(['name' => 'Test']);
    $dataService->shouldReceive('getUsers')->andReturn(new Collection([]));
    $dataService->shouldReceive('getAvailableModules')->andReturn([]);

    $presentation = m::mock(Presentation::class);
    $presentation->shouldReceive('getExtension')->andReturn('json');
    $presentation->shouldReceive('generateProjectInfo')->andReturn('{}');
    $presentation->shouldReceive('generateUsers')->andReturn('{}');
    $presentation->shouldReceive('generateAvailableModules')->andReturn('{}');

    Config::set('uat.services.data', $dataService);
    Config::set('uat.formats.json', $presentation);
    Config::set('uat.directory', 'testing/uat');

    $this->action->handle($this->testDir, 'json');

    expect(File::exists($this->testDir.'/existing-file.txt'))->toBeFalse();
});

it('generates files with correct naming pattern', function () {
    // Mock dependencies
    $dataService = m::mock(Data::class);
    $dataService->shouldReceive('getProjectInformation')->andReturn(['name' => 'Test']);
    $dataService->shouldReceive('getUsers')->andReturn(new Collection([]));
    $dataService->shouldReceive('getAvailableModules')->andReturn([
        [
            'module' => 'User Management',
            'routes' => [],
        ],
    ]);

    $presentation = m::mock(Presentation::class);
    $presentation->shouldReceive('getExtension')->andReturn('md');
    $presentation->shouldReceive('generateProjectInfo')->andReturn('# Project Info');
    $presentation->shouldReceive('generateUsers')->andReturn('# Users');
    $presentation->shouldReceive('generateAvailableModules')->andReturn('# Modules');
    $presentation->shouldReceive('generateModuleTestSuite')->andReturn('# Module Test');

    Config::set('uat.services.data', $dataService);
    Config::set('uat.formats.md', $presentation);
    Config::set('uat.directory', 'testing/uat');

    $result = $this->action->handle($this->testDir, 'md');

    $expectedFiles = [
        '01-project-info.md',
        '02-users.md',
        '03-available-modules.md',
        '05-module-user-management.md',
    ];

    foreach ($expectedFiles as $expectedFile) {
        $fullPath = $this->testDir.'/'.$expectedFile;
        expect(File::exists($fullPath))->toBeTrue("File {$expectedFile} should exist");
    }
});
