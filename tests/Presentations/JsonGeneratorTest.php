<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Contracts\Presentation;
use CleaniqueCoders\Uat\Presentations\JsonGenerator;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->generator = new JsonGenerator;
});

it('implements Presentation contract', function () {
    expect($this->generator)->toBeInstanceOf(Presentation::class);
});

it('can instantiate JsonGenerator', function () {
    expect($this->generator)->toBeInstanceOf(JsonGenerator::class);
});

it('returns correct extension', function () {
    expect($this->generator->getExtension())->toBe('json');
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
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('title', 'Project Information');
    expect($json)->toHaveKey('generated_at', '2023-01-01 12:00:00');
    expect($json)->toHaveKey('basic_information');
    expect($json)->toHaveKey('technical_stack');
    expect($json)->toHaveKey('uat_testing_notes');

    expect($json['basic_information'])->toHaveKey('project_name', 'Test Project');
    expect($json['basic_information'])->toHaveKey('description', 'Test Description');
    expect($json['basic_information'])->toHaveKey('version', '1.0.0');
    expect($json['basic_information'])->toHaveKey('environment', 'testing');

    expect($json['technical_stack'])->toHaveKey('php_version', '8.1.0');
    expect($json['technical_stack'])->toHaveKey('laravel_version', '9.0.0');
    expect($json['technical_stack'])->toHaveKey('database', 'mysql');
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
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('title', 'Access Controls');
    expect($json)->toHaveKey('roles_overview');
    expect($json)->toHaveKey('permissions_overview');
    expect($json)->toHaveKey('role_permission_matrix');
    expect($json)->toHaveKey('uat_testing_scenarios');
});

it('generates users correctly', function () {
    $users = new Collection([
        [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified' => true,
            'created_at' => '2023-01-01 12:00:00',
        ],
        [
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'email_verified' => false,
            'created_at' => '2023-01-02 12:00:00',
        ],
    ]);

    $result = $this->generator->generateUsers($users);

    expect($result)->toBeString();
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('title', 'Users');
    expect($json)->toHaveKey('users_overview');
    expect($json['users_overview'])->toHaveCount(2);
    expect($json['users_overview'][0])->toHaveKey('name', 'John Doe');
    expect($json['users_overview'][1])->toHaveKey('name', 'Jane Smith');
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
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('title', 'Available Modules Overview');
    expect($json)->toHaveKey('modules_summary');
    expect($json['modules_summary'])->toHaveCount(2);
    expect($json['modules_summary'][0])->toHaveKey('module', 'Dashboard');
    expect($json['modules_summary'][1])->toHaveKey('module', 'Users');
});

it('generates module test suite correctly', function () {
    $module = [
        'module' => 'Users',
        'routes' => [
            [
                'uri' => 'users',
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
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('title', 'Users Module - UAT Test Suite');
    expect($json)->toHaveKey('module', 'Users');
    expect($json)->toHaveKey('routes_count', 1);
    expect($json)->toHaveKey('module_overview');
    expect($json)->toHaveKey('test_cases');
    expect($json)->toHaveKey('test_summary');
});

it('handles empty users collection', function () {
    $users = new Collection([]);

    $result = $this->generator->generateUsers($users);

    expect($result)->toBeString();
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('users_overview');
    expect($json['users_overview'])->toBeArray();
    expect($json['users_overview'])->toBeEmpty();
});

it('handles empty modules array', function () {
    $modules = [];

    $result = $this->generator->generateAvailableModules($modules);

    expect($result)->toBeString();
    expect(json_decode($result, true))->not->toBeNull();

    $json = json_decode($result, true);
    expect($json)->toHaveKey('modules_summary');
    expect($json['modules_summary'])->toBeArray();
    expect($json['modules_summary'])->toBeEmpty();
});

it('formats JSON with proper formatting', function () {
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

    // Check that the JSON is properly formatted (contains newlines and indentation)
    expect($result)->toContain("\n");
    expect($result)->toContain('    ');

    // Verify it's valid JSON
    expect(json_decode($result, true))->not->toBeNull();
});
