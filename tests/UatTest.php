<?php

declare(strict_types=1);

use CleaniqueCoders\Uat\Uat;

it('can instantiate Uat class', function () {
    $uat = new Uat;

    expect($uat)->toBeInstanceOf(Uat::class);
});

it('Uat class exists and is in correct namespace', function () {
    expect(class_exists(Uat::class))->toBeTrue();
});
