<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Contracts\Presentation;
use CleaniqueCoders\Uat\Presentations\MarkdownGenerator;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->generator = new MarkdownGenerator;
});

it('implements Presentation contract', function () {
    expect($this->generator)->toBeInstanceOf(Presentation::class);
});

it('can instantiate MarkdownGenerator', function () {
    expect($this->generator)->toBeInstanceOf(MarkdownGenerator::class);
});

it('returns correct extension', function () {
    expect($this->generator->getExtension())->toBe('md');
});

it('generates project info correctly', function () {
    $projectInfo = [
        'name' => 'Test Project',
        'description' => 'Test Description',
        'version' => '1.0.0',
        'php_version' => '8.1.0',
        'laravel_version' => '9.0.0',
        'generated_at' => '2023-01-01 12:00:00',
        'environment' => 'testing',
        'database_connection' => 'mysql',
        'queue_connection' => 'database',
        'cache_driver' => 'redis',
        'session_driver' => 'file',
        'mail_driver' => 'smtp',
    ];

    $result = $this->generator->generateProjectInfo($projectInfo);

    expect($result)->toBeString();
    expect($result)->toContain('# Project Information');
    expect($result)->toContain('> Generated on: 2023-01-01 12:00:00');
    expect($result)->toContain('## Basic Information');
    expect($result)->toContain('## Technical Stack');
    expect($result)->toContain('## UAT Testing Notes');
    expect($result)->toContain('| **Project Name** | Test Project |');
    expect($result)->toContain('| **PHP Version** | 8.1.0 |');
    expect($result)->toContain('| **Laravel Version** | 9.0.0 |');
});

it('generates access controls correctly', function () {
    $accessControls = [
        'roles' => [
            [
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'web',
                'permissions_count' => 10,
                'users_count' => 2,
                'created_at' => '2023-01-01 12:00:00',
            ],
        ],
        'permissions' => [
            [
                'id' => 1,
                'name' => 'edit-users',
                'guard_name' => 'web',
                'roles' => ['admin'],
                'created_at' => '2023-01-01 12:00:00',
            ],
        ],
        'role_permissions' => [
            'admin' => ['edit-users', 'delete-users'],
        ],
    ];

    $result = $this->generator->generateAccessControls($accessControls);

    expect($result)->toBeString();
    expect($result)->toContain('# Access Controls');
    expect($result)->toContain('## Roles Overview');
    expect($result)->toContain('## Permissions Overview');
    expect($result)->toContain('## Role-Permission Matrix');
    expect($result)->toContain('## UAT Testing Scenarios');
    expect($result)->toContain('| 1 | **admin** | web | 10 | 2 |');
    expect($result)->toContain('### admin');
    expect($result)->toContain('- edit-users');
    expect($result)->toContain('- delete-users');
});

it('generates users correctly', function () {
    $users = new Collection([
        [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified' => true,
            'roles' => ['admin', 'user'],
            'created_at' => '2023-01-01 12:00:00',
        ],
        [
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'email_verified' => false,
            'roles' => ['user'],
            'created_at' => '2023-01-02 12:00:00',
        ],
    ]);

    $result = $this->generator->generateUsers($users);

    expect($result)->toBeString();
    expect($result)->toContain('# Users');
    expect($result)->toContain('## Users Overview');
    expect($result)->toContain('| User ID | Name | Email | Email Verified | Roles | Created At |');
    expect($result)->toContain('| 1 | **John Doe** | john@example.com | ✅ | admin, user | 2023-01-01 12:00:00 |');
    expect($result)->toContain('| 2 | **Jane Smith** | jane@example.com | ❌ | user | 2023-01-02 12:00:00 |');
    expect($result)->toContain('## User Roles Distribution');
});

it('generates available modules correctly', function () {
    $modules = [
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
        [
            'module' => 'Users',
            'routes' => [
                [
                    'uri' => '/users',
                    'name' => 'users.index',
                    'action' => 'UserController@index',
                    'middleware' => ['web', 'auth'],
                    'prerequisites' => [
                        [
                            'type' => 'authentication',
                            'description' => 'User must be authenticated',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = $this->generator->generateAvailableModules($modules);

    expect($result)->toBeString();
    expect($result)->toContain('# Available Modules Overview');
    expect($result)->toContain('## Modules Summary');
    expect($result)->toContain('| Module | Routes | File |');
    expect($result)->toContain('| **Dashboard** | 1 | `05-module-Dashboard.md` |');
    expect($result)->toContain('| **Users** | 1 | `06-module-Users.md` |');
    expect($result)->toContain('## General UAT Testing Guidelines');
});

it('generates module test suite correctly', function () {
    $module = [
        'module' => 'Users',
        'routes' => [
            [
                'uri' => '/users',
                'name' => 'users.index',
                'action' => 'UserController@index',
                'middleware' => ['web', 'auth'],
                'prerequisites' => [
                    [
                        'type' => 'authentication',
                        'description' => 'User must be authenticated',
                        'action' => 'Login with valid credentials',
                        'validation' => 'Verify user is redirected to login page when not authenticated',
                    ],
                ],
            ],
        ],
    ];

    $result = $this->generator->generateModuleTestSuite($module, 0);

    expect($result)->toBeString();
    expect($result)->toContain('# Users Module - UAT Test Suite');
    expect($result)->toContain('## Module Overview');
    expect($result)->toContain('## Test Scenarios');
    expect($result)->toContain('| **Module Name** | Users |');
    expect($result)->toContain('### Route: /users (users.index)');
    expect($result)->toContain('#### Prerequisites');
    expect($result)->toContain('**Type:** authentication');
    expect($result)->toContain('**Description:** User must be authenticated');
});

it('handles empty users collection', function () {
    $users = new Collection([]);

    $result = $this->generator->generateUsers($users);

    expect($result)->toBeString();
    expect($result)->toContain('# Users');
    expect($result)->toContain('> Total Users: 0');
});

it('handles empty modules array', function () {
    $modules = [];

    $result = $this->generator->generateAvailableModules($modules);

    expect($result)->toBeString();
    expect($result)->toContain('# Available Modules Overview');
    expect($result)->toContain('> Total Modules: 0');
});

it('handles module with no routes', function () {
    $modules = [
        [
            'module' => 'EmptyModule',
            'routes' => [],
        ],
    ];

    $result = $this->generator->generateAvailableModules($modules);

    expect($result)->toBeString();
    expect($result)->toContain('| **EmptyModule** | 0 | `05-module-EmptyModule.md` |');
});

it('handles route with no prerequisites', function () {
    $module = [
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
    ];

    $result = $this->generator->generateModuleTestSuite($module, 0);

    expect($result)->toBeString();
    expect($result)->toContain('### Route: `/`');
    expect($result)->toContain('| `//` | dashboard | DashboardController@index |');
});

it('handles empty role permissions', function () {
    $accessControls = [
        'roles' => [
            [
                'id' => 1,
                'name' => 'guest',
                'guard_name' => 'web',
                'permissions_count' => 0,
                'users_count' => 0,
                'created_at' => '2023-01-01 12:00:00',
            ],
        ],
        'permissions' => [],
        'role_permissions' => [
            'guest' => [],
        ],
    ];

    $result = $this->generator->generateAccessControls($accessControls);

    expect($result)->toBeString();
    expect($result)->toContain('### guest');
    expect($result)->toContain('_No permissions assigned_');
});

it('formats markdown with proper structure', function () {
    $projectInfo = [
        'name' => 'Test Project',
        'description' => 'Test Description',
        'version' => '1.0.0',
        'generated_at' => '2023-01-01 12:00:00',
        'environment' => 'testing',
        'php_version' => '8.1.0',
        'laravel_version' => '9.0.0',
        'database_connection' => 'mysql',
        'queue_connection' => 'database',
        'cache_driver' => 'redis',
        'session_driver' => 'file',
        'mail_driver' => 'smtp',
    ];

    $result = $this->generator->generateProjectInfo($projectInfo);

    // Check for proper markdown structure
    expect($result)->toContain("\n");
    expect($result)->toContain('|-------|-------|'); // Table separator
    expect($result)->toContain('**'); // Bold formatting
    expect($result)->toMatch('/^#\s/m'); // Header formatting
});
