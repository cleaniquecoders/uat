<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Services\ProjectAnalyzer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->analyzer = new ProjectAnalyzer;
});

it('can instantiate ProjectAnalyzer', function () {
    expect($this->analyzer)->toBeInstanceOf(ProjectAnalyzer::class);
});

it('analyzes project correctly', function () {
    // Mock file system calls
    File::shouldReceive('exists')->andReturn(false);

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis)->toBeArray();
    expect($analysis)->toHaveKeys([
        'middleware_patterns',
        'policy_patterns',
        'authentication_methods',
        'authorization_patterns',
        'validation_patterns',
    ]);
});

it('discovers middleware patterns when directory exists', function () {
    $middlewarePath = app_path('Http/Middleware');

    File::shouldReceive('exists')
        ->with($middlewarePath)
        ->andReturn(true);

    $file1 = new SplFileInfo('/path/to/AuthMiddleware.php');
    $file2 = new SplFileInfo('/path/to/AdminMiddleware.php');

    File::shouldReceive('files')
        ->with($middlewarePath)
        ->andReturn([$file1, $file2]);

    File::shouldReceive('get')
        ->with('/path/to/AuthMiddleware.php')
        ->andReturn('<?php class AuthMiddleware { /** * Authentication middleware */ }');

    File::shouldReceive('get')
        ->with('/path/to/AdminMiddleware.php')
        ->andReturn('<?php class AdminMiddleware { /** * Admin middleware */ }');

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis['middleware_patterns'])->toBeArray();
    expect($analysis['middleware_patterns'])->toHaveCount(2);
    expect($analysis['middleware_patterns'][0])->toHaveKey('class', 'AuthMiddleware');
    expect($analysis['middleware_patterns'][1])->toHaveKey('class', 'AdminMiddleware');
});

it('returns empty array when middleware directory does not exist', function () {
    File::shouldReceive('exists')->andReturn(false);

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis['middleware_patterns'])->toBeArray();
    expect($analysis['middleware_patterns'])->toBeEmpty();
});

it('discovers policy patterns when directory exists', function () {
    $policiesPath = app_path('Policies');

    File::shouldReceive('exists')
        ->with($policiesPath)
        ->andReturn(true);

    // Mock other paths to return false
    File::shouldReceive('exists')
        ->with(app_path('Http/Middleware'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Controllers/Auth'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Controllers'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Requests'))
        ->andReturn(false);

    $policyFile = new SplFileInfo('/path/to/UserPolicy.php');

    File::shouldReceive('files')
        ->with($policiesPath)
        ->andReturn([$policyFile]);

    File::shouldReceive('get')
        ->with('/path/to/UserPolicy.php')
        ->andReturn('<?php class UserPolicy { /** * User policy */ public function view() {} public function create() {} }');

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis['policy_patterns'])->toBeArray();
    expect($analysis['policy_patterns'])->toHaveCount(1);
    expect($analysis['policy_patterns'][0])->toHaveKey('class', 'UserPolicy');
    expect($analysis['policy_patterns'][0])->toHaveKey('methods');
    expect($analysis['policy_patterns'][0]['methods'])->toContain('view', 'create');
});

it('discovers authentication methods from config', function () {
    Config::set('auth.guards', [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
        ],
    ]);

    File::shouldReceive('exists')->andReturn(false);

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis['authentication_methods'])->toBeArray();
    expect($analysis['authentication_methods'])->toHaveCount(2);
    expect($analysis['authentication_methods'][0])->toHaveKey('guard', 'web');
    expect($analysis['authentication_methods'][0])->toHaveKey('driver', 'session');
    expect($analysis['authentication_methods'][1])->toHaveKey('guard', 'api');
    expect($analysis['authentication_methods'][1])->toHaveKey('driver', 'token');
});

it('discovers authorization patterns from controllers', function () {
    $controllersPath = app_path('Http/Controllers');

    File::shouldReceive('exists')
        ->with($controllersPath)
        ->andReturn(true);

    // Mock other paths
    File::shouldReceive('exists')
        ->with(app_path('Http/Middleware'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Policies'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Controllers/Auth'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Requests'))
        ->andReturn(false);

    $controllerFile = new SplFileInfo('/path/to/UserController.php');

    File::shouldReceive('allFiles')
        ->with($controllersPath)
        ->andReturn([$controllerFile]);

    File::shouldReceive('get')
        ->with('/path/to/UserController.php')
        ->andReturn('<?php class UserController { public function index() { $this->authorize("view-users"); } }');

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis['authorization_patterns'])->toBeArray();
    expect($analysis['authorization_patterns'])->toHaveCount(1);
    expect($analysis['authorization_patterns'][0])->toHaveKey('type', 'controller_authorization');
    expect($analysis['authorization_patterns'][0])->toHaveKey('permissions');
    expect($analysis['authorization_patterns'][0]['permissions'])->toContain('view-users');
});

it('discovers validation patterns from form requests', function () {
    $requestsPath = app_path('Http/Requests');

    File::shouldReceive('exists')
        ->with($requestsPath)
        ->andReturn(true);

    // Mock other paths
    File::shouldReceive('exists')
        ->with(app_path('Http/Middleware'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Policies'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Controllers/Auth'))
        ->andReturn(false);
    File::shouldReceive('exists')
        ->with(app_path('Http/Controllers'))
        ->andReturn(false);

    $requestFile = new SplFileInfo('/path/to/UserRequest.php');

    File::shouldReceive('allFiles')
        ->with($requestsPath)
        ->andReturn([$requestFile]);

    File::shouldReceive('get')
        ->with('/path/to/UserRequest.php')
        ->andReturn('<?php class UserRequest { public function rules() { return ["name" => "required", "email" => "required|email"]; } }');

    $analysis = $this->analyzer->analyzeProject();

    expect($analysis['validation_patterns'])->toBeArray();
    expect($analysis['validation_patterns'])->toHaveCount(1);
    expect($analysis['validation_patterns'][0])->toHaveKey('class', 'UserRequest');
    expect($analysis['validation_patterns'][0])->toHaveKey('rules');
    expect($analysis['validation_patterns'][0]['rules'])->toContain('name', 'email');
});

it('extracts middleware requirements correctly', function () {
    $reflection = new ReflectionClass(ProjectAnalyzer::class);
    $method = $reflection->getMethod('extractMiddlewareRequirements');
    $method->setAccessible(true);

    $content = '<?php
    class TestMiddleware {
        public function handle($request, $next) {
            if (!Auth::check()) {
                return redirect("login");
            }
            if (!auth()->user()->hasRole("admin")) {
                abort(403);
            }
            return $next($request);
        }
    }';

    $requirements = $method->invoke($this->analyzer, $content);

    expect($requirements)->toBeArray();
    expect($requirements)->toContain('authentication_required', 'role_check');
});

it('extracts policy methods correctly', function () {
    $reflection = new ReflectionClass(ProjectAnalyzer::class);
    $method = $reflection->getMethod('extractPolicyMethods');
    $method->setAccessible(true);

    $content = '<?php
    class UserPolicy {
        public function view() {}
        public function create() {}
        public function update() {}
        public function __construct() {}
    }';

    $methods = $method->invoke($this->analyzer, $content);

    expect($methods)->toBeArray();
    expect($methods)->toContain('view', 'create', 'update');
    expect($methods)->not->toContain('__construct');
});

it('extracts class description from docblock', function () {
    $reflection = new ReflectionClass(ProjectAnalyzer::class);
    $method = $reflection->getMethod('extractClassDescription');
    $method->setAccessible(true);

    $content = '<?php
    /**
     * This is a test class description
     * with multiple lines
     */
    class TestClass {}';

    $description = $method->invoke($this->analyzer, $content);

    expect($description)->toBe('This is a test class description');
});

it('returns null when no class description found', function () {
    $reflection = new ReflectionClass(ProjectAnalyzer::class);
    $method = $reflection->getMethod('extractClassDescription');
    $method->setAccessible(true);

    $content = '<?php class TestClass {}';

    $description = $method->invoke($this->analyzer, $content);

    expect($description)->toBeNull();
});
