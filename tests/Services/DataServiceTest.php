<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Contracts\Data;
use CleaniqueCoders\Uat\Contracts\Rule;
use CleaniqueCoders\Uat\Services\DataService;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mockery as m;

beforeEach(function () {
    // Define default composer.json content
    $this->composerJson = [
        'name' => 'test/project',
        'description' => 'Test Project Description',
        'version' => '1.0.0',
    ];

    File::shouldReceive('get')
        ->with(base_path('composer.json'))
        ->andReturn(json_encode($this->composerJson));

    $this->dataService = new DataService;
});

afterEach(function () {
    m::close();
});

it('implements Data contract', function () {
    expect($this->dataService)->toBeInstanceOf(Data::class);
});

it('can instantiate DataService', function () {
    expect($this->dataService)->toBeInstanceOf(DataService::class);
});

it('gets project information correctly', function () {
    File::shouldReceive('get')
        ->with(base_path('composer.json'))
        ->andReturn(json_encode([
            'name' => 'test/project',
            'description' => 'Test Project Description',
            'version' => '1.0.0',
        ]));

    $projectInfo = $this->dataService->getProjectInformation();

    expect($projectInfo)->toBeArray();
    expect($projectInfo)->toHaveKey('name', 'test/project');
    expect($projectInfo)->toHaveKey('description', 'Test Project Description');
    expect($projectInfo)->toHaveKey('version', '1.0.0');
    expect($projectInfo)->toHaveKey('php_version');
    expect($projectInfo)->toHaveKey('laravel_version');
    expect($projectInfo)->toHaveKey('generated_at');
    expect($projectInfo)->toHaveKey('environment');
    expect($projectInfo)->toHaveKey('database_connection');
    expect($projectInfo)->toHaveKey('queue_connection');
    expect($projectInfo)->toHaveKey('cache_driver');
    expect($projectInfo)->toHaveKey('session_driver');
    expect($projectInfo)->toHaveKey('mail_driver');
});

it('handles missing composer.json fields gracefully', function () {
    File::shouldReceive('get')
        ->with(base_path('composer.json'))
        ->andReturn('{}');

    $projectInfo = $this->dataService->getProjectInformation();

    expect($projectInfo['name'])->toBe('Laravel');
    expect($projectInfo['description'])->toBe('Laravel Application');
    expect($projectInfo['version'])->toBe('1.0.0');
});

it('gets users correctly', function () {
    // Mock User model
    $mockUser = m::mock(User::class);
    $mockUser->shouldReceive('setAttribute')->andReturnSelf();
    $mockUser->id = 1;
    $mockUser->name = 'Test User';
    $mockUser->email = 'test@example.com';
    $mockUser->email_verified_at = now();
    $mockUser->created_at = now();

    $mockQuery = m::mock();
    $mockQuery->shouldReceive('select')
        ->with(['id', 'name', 'email', 'created_at', 'email_verified_at'])
        ->andReturnSelf();
    $mockQuery->shouldReceive('orderBy')
        ->with('created_at')
        ->andReturnSelf();
    $mockQuery->shouldReceive('get')
        ->andReturn(collect([$mockUser]));

    User::shouldReceive('query')
        ->andReturn($mockQuery);

    $users = $this->dataService->getUsers();

    expect($users)->toBeInstanceOf(Collection::class);
    expect($users->count())->toBe(1);

    $user = $users->first();
    expect($user)->toHaveKey('id', 1);
    expect($user)->toHaveKey('name', 'Test User');
    expect($user)->toHaveKey('email', 'test@example.com');
    expect($user)->toHaveKey('email_verified', true);
    expect($user)->toHaveKey('created_at');
});

it('gets available modules correctly', function () {
    // Mock routes
    $route1 = m::mock(Route::class);
    $route1->shouldReceive('methods')->andReturn(['GET']);
    $route1->shouldReceive('uri')->andReturn('/dashboard');
    $route1->shouldReceive('getName')->andReturn('dashboard.index');
    $route1->shouldReceive('getActionName')->andReturn('DashboardController@index');
    $route1->shouldReceive('gatherMiddleware')->andReturn(['web']);

    $route2 = m::mock(Route::class);
    $route2->shouldReceive('methods')->andReturn(['GET']);
    $route2->shouldReceive('uri')->andReturn('/users');
    $route2->shouldReceive('getName')->andReturn('users.index');
    $route2->shouldReceive('getActionName')->andReturn('UserController@index');
    $route2->shouldReceive('gatherMiddleware')->andReturn(['web']);

    RouteFacade::shouldReceive('getRoutes')
        ->andReturn([$route1, $route2]);

    $modules = $this->dataService->getAvailableModules();

    expect($modules)->toBeArray();
    expect($modules)->not->toBeEmpty();
});

it('excludes routes with parameters', function () {
    $route = m::mock(Route::class);
    $route->shouldReceive('methods')->andReturn(['GET']);
    $route->shouldReceive('uri')->andReturn('/users/{id}');

    RouteFacade::shouldReceive('getRoutes')
        ->andReturn([$route]);

    $modules = $this->dataService->getAvailableModules();

    expect($modules)->toBeArray();
    expect($modules)->toBeEmpty();
});

it('excludes non-web routes', function () {
    $route = m::mock(Route::class);
    $route->shouldReceive('methods')->andReturn(['GET']);
    $route->shouldReceive('uri')->andReturn('api/users');
    $route->shouldReceive('gatherMiddleware')->andReturn(['api']);

    RouteFacade::shouldReceive('getRoutes')
        ->andReturn([$route]);

    $modules = $this->dataService->getAvailableModules();

    expect($modules)->toBeArray();
    expect($modules)->toBeEmpty();
});

it('gets route prerequisites correctly', function () {
    $route = m::mock(Route::class);
    $route->shouldReceive('getActionName')->andReturn('TestController@index');
    $route->shouldReceive('getName')->andReturn('test.index');

    $middleware = ['auth', 'verified'];

    Config::set('uat.rules.middleware', [
        'auth' => [
            'type' => 'authentication',
            'description' => 'User must be authenticated',
        ],
        'verified' => [
            'type' => 'email_verification',
            'description' => 'User must have verified email',
        ],
    ]);

    $prerequisites = $this->dataService->getRoutePrerequisites($route, $middleware);

    expect($prerequisites)->toBeArray();
    expect($prerequisites)->toHaveCount(2);
    expect($prerequisites[0]['type'])->toBe('authentication');
    expect($prerequisites[1]['type'])->toBe('email_verification');
});

it('gets middleware prerequisites correctly', function () {
    $middleware = ['auth', 'role:admin'];

    Config::set('uat.rules.middleware', [
        'auth' => [
            'type' => 'authentication',
            'description' => 'User must be authenticated',
        ],
    ]);

    Config::set('uat.rules.pattern', [
        'role:*' => [
            'type' => 'role_check',
            'description' => 'User must have role: {placeholder}',
        ],
    ]);

    $prerequisites = $this->dataService->getMiddlewarePrerequisites($middleware);

    expect($prerequisites)->toBeArray();
    expect($prerequisites)->toHaveCount(2);
    expect($prerequisites[0]['type'])->toBe('authentication');
    expect($prerequisites[1]['type'])->toBe('role_check');
    expect($prerequisites[1]['description'])->toBe('User must have role: admin');
});

it('handles rule discovery service correctly', function () {
    $ruleService = m::mock(Rule::class);
    $ruleService->shouldReceive('discoverMiddlewareRules')
        ->with(['auth'])
        ->andReturn([
            [
                'type' => 'dynamic_auth',
                'description' => 'Dynamic authentication rule',
            ],
        ]);

    $ruleService->shouldReceive('discoverPolicyRules')
        ->andReturn([]);

    Config::set('uat.services.rule', $ruleService);

    $dataService = new DataService;
    $route = m::mock(Route::class);

    $prerequisites = $dataService->getRoutePrerequisites($route, ['auth']);

    expect($prerequisites)->toBeArray();
    expect($prerequisites)->toHaveCount(1);
    expect($prerequisites[0]['type'])->toBe('dynamic_auth');
});

it('extracts module from route correctly', function () {
    // Use reflection to test private method
    $reflection = new ReflectionClass(DataService::class);
    $method = $reflection->getMethod('extractModuleFromRoute');
    $method->setAccessible(true);

    $route = m::mock(Route::class);
    $route->shouldReceive('getName')->andReturn('users.index');
    $route->shouldReceive('uri')->andReturn('/users');

    $module = $method->invoke($this->dataService, $route);

    expect($module)->toBe('Users');
});

it('defaults to Dashboard for root routes', function () {
    $reflection = new ReflectionClass(DataService::class);
    $method = $reflection->getMethod('extractModuleFromRoute');
    $method->setAccessible(true);

    $route = m::mock(Route::class);
    $route->shouldReceive('getName')->andReturn(null);
    $route->shouldReceive('uri')->andReturn('/');

    $module = $method->invoke($this->dataService, $route);

    expect($module)->toBe('Dashboard');
});
