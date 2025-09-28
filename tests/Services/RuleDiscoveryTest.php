<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Contracts\Rule;
use CleaniqueCoders\Uat\Services\RuleDiscovery;
use Illuminate\Routing\Route;
use Mockery as m;

beforeEach(function () {
    $this->ruleDiscovery = new RuleDiscovery;
});

afterEach(function () {
    m::close();
});

it('implements Rule contract', function () {
    expect($this->ruleDiscovery)->toBeInstanceOf(Rule::class);
});

it('can instantiate RuleDiscovery', function () {
    expect($this->ruleDiscovery)->toBeInstanceOf(RuleDiscovery::class);
});

it('discovers middleware rules correctly', function () {
    $middleware = ['auth', 'verified', 'role:admin'];

    $rules = $this->ruleDiscovery->discoverMiddlewareRules($middleware);

    expect($rules)->toBeArray();
});

it('handles closure middleware correctly', function () {
    $closure = function () {
        return 'test';
    };

    $middleware = [$closure];

    $rules = $this->ruleDiscovery->discoverMiddlewareRules($middleware);

    expect($rules)->toBeArray();
    expect($rules)->toHaveCount(1);
    expect($rules[0])->toHaveKey('type', 'closure_middleware');
    expect($rules[0])->toHaveKey('description', 'Custom closure middleware');
});

it('handles object middleware correctly', function () {
    $middlewareObject = new stdClass;
    $middleware = [$middlewareObject];

    $rules = $this->ruleDiscovery->discoverMiddlewareRules($middleware);

    expect($rules)->toBeArray();
    expect($rules)->toHaveCount(1);
    expect($rules[0])->toHaveKey('type', 'object_middleware');
    expect($rules[0])->toHaveKey('middleware_class', 'stdClass');
});

it('skips non-string non-object middleware', function () {
    $middleware = [123, true, []];

    $rules = $this->ruleDiscovery->discoverMiddlewareRules($middleware);

    expect($rules)->toBeArray();
    expect($rules)->toBeEmpty();
});

it('discovers policy rules correctly', function () {
    $route = m::mock(Route::class);
    $route->shouldReceive('getActionName')
        ->andReturn('UserController@index');
    $route->shouldReceive('getName')
        ->andReturn('users.index');

    $rules = $this->ruleDiscovery->discoverPolicyRules($route);

    expect($rules)->toBeArray();
});

it('handles routes without @ in action name', function () {
    $route = m::mock(Route::class);
    $route->shouldReceive('getActionName')
        ->andReturn('Closure');

    $rules = $this->ruleDiscovery->discoverPolicyRules($route);

    expect($rules)->toBeArray();
    expect($rules)->toBeEmpty();
});

it('analyzes auth middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'auth');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('type');
    expect($result)->toHaveKey('description');
    expect($result)->toHaveKey('action');
    expect($result)->toHaveKey('validation');
});

it('analyzes guest middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'guest');

    expect($result)->toBeArray();
    expect($result['type'])->toBe('unauthenticated');
});

it('analyzes verified middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'verified');

    expect($result)->toBeArray();
    expect($result['type'])->toBe('email_verification');
});

it('analyzes throttle middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'throttle:60,1');

    expect($result)->toBeArray();
    // Based on actual implementation behavior - throttle falls to unknown_builtin
    expect($result['type'])->toBe('unknown_builtin');
});

it('analyzes role middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'role:admin');

    expect($result)->toBeArray();
    expect($result['type'])->toBe('role_authorization');
    expect($result['description'])->toContain('admin');
});

it('analyzes permission middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'permission:edit-users');

    expect($result)->toBeArray();
    expect($result['type'])->toBe('permission_authorization');
    expect($result['description'])->toContain('edit-users');
});

it('analyzes can middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'can:view,App\\Models\\User');

    expect($result)->toBeArray();
    expect($result['type'])->toBe('gate_authorization');
    expect($result['description'])->toContain('view');
});

it('handles unknown middleware gracefully', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('analyzeMiddleware');
    $method->setAccessible(true);

    $result = $method->invoke($this->ruleDiscovery, 'unknown-middleware');

    // Unknown middleware returns null based on the implementation
    expect($result)->toBeNull();
});

it('identifies built-in middleware correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('isBuiltInMiddleware');
    $method->setAccessible(true);

    expect($method->invoke($this->ruleDiscovery, 'auth'))->toBeTrue();
    expect($method->invoke($this->ruleDiscovery, 'guest'))->toBeTrue();
    expect($method->invoke($this->ruleDiscovery, 'verified'))->toBeTrue();
    expect($method->invoke($this->ruleDiscovery, 'role:admin'))->toBeTrue();
    expect($method->invoke($this->ruleDiscovery, 'can:view,User'))->toBeTrue();
    expect($method->invoke($this->ruleDiscovery, 'throttle:60,1'))->toBeTrue();
    expect($method->invoke($this->ruleDiscovery, 'custom-middleware'))->toBeFalse();
});

it('determines policy method correctly', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('determinePolicyMethod');
    $method->setAccessible(true);

    expect($method->invoke($this->ruleDiscovery, 'index', 'users.index'))->toBe('index');
    expect($method->invoke($this->ruleDiscovery, 'show', 'users.show'))->toBe('show');
    expect($method->invoke($this->ruleDiscovery, 'create', 'users.create'))->toBe('create');
    expect($method->invoke($this->ruleDiscovery, 'store', 'users.store'))->toBe('store');
    expect($method->invoke($this->ruleDiscovery, 'edit', 'users.edit'))->toBe('edit');
    expect($method->invoke($this->ruleDiscovery, 'update', 'users.update'))->toBe('update');
    expect($method->invoke($this->ruleDiscovery, 'destroy', 'users.destroy'))->toBe('destroy');
});

it('falls back to method name for unknown policy methods', function () {
    $reflection = new ReflectionClass(RuleDiscovery::class);
    $method = $reflection->getMethod('determinePolicyMethod');
    $method->setAccessible(true);

    expect($method->invoke($this->ruleDiscovery, 'customMethod', 'users.custom'))->toBe('customMethod');
});
