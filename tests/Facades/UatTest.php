<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Facades\Uat as UatFacade;
use CleaniqueCoders\Uat\Uat;
use Illuminate\Support\Facades\Facade;

beforeEach(function () {
    // Clear any existing facade instances
    Facade::clearResolvedInstances();
});

it('is a facade', function () {
    expect(is_subclass_of(UatFacade::class, Facade::class))->toBeTrue();
});

it('has correct facade accessor', function () {
    $reflection = new ReflectionClass(UatFacade::class);
    $method = $reflection->getMethod('getFacadeAccessor');
    $method->setAccessible(true);

    $accessor = $method->invoke(null);

    expect($accessor)->toBe(Uat::class);
});

it('can resolve the facade', function () {
    // Bind the Uat class to the container
    $this->app->singleton(Uat::class, function () {
        return new Uat;
    });

    $instance = UatFacade::getFacadeRoot();

    expect($instance)->toBeInstanceOf(Uat::class);
});

it('maintains singleton behavior', function () {
    // Bind the Uat class to the container
    $this->app->singleton(Uat::class, function () {
        return new Uat;
    });

    $instance1 = UatFacade::getFacadeRoot();
    $instance2 = UatFacade::getFacadeRoot();

    expect($instance1)->toBe($instance2);
});

it('can be mocked', function () {
    UatFacade::shouldReceive('testMethod')
        ->once()
        ->andReturn('mocked response');

    $result = UatFacade::testMethod();

    expect($result)->toBe('mocked response');
});

it('can be spied on', function () {
    // Bind the Uat class to the container
    $this->app->singleton(Uat::class, function () {
        return new Uat;
    });

    $spy = UatFacade::spy();

    expect($spy)->not->toBeNull();
});

it('can be partially mocked', function () {
    UatFacade::partialMock()
        ->shouldReceive('testMethod')
        ->once()
        ->andReturn('partial mock response');

    $result = UatFacade::testMethod();

    expect($result)->toBe('partial mock response');
});

it('has correct docblock reference', function () {
    $reflection = new ReflectionClass(UatFacade::class);
    $docComment = $reflection->getDocComment();

    expect($docComment)->toContain('@see \CleaniqueCoders\Uat\Uat');
});

it('facade accessor returns the correct class name', function () {
    $reflection = new ReflectionClass(UatFacade::class);
    $method = $reflection->getMethod('getFacadeAccessor');
    $method->setAccessible(true);

    $accessor = $method->invoke(null);

    expect($accessor)->toBe(\CleaniqueCoders\Uat\Uat::class);
    expect(class_exists($accessor))->toBeTrue();
});
