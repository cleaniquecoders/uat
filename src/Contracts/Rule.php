<?php

namespace CleaniqueCoders\Uat\Contracts;

use Illuminate\Routing\Route;

interface Rule
{
    public function discoverMiddlewareRules(array $middleware): array;

    public function discoverPolicyRules(Route $route): array;
}
