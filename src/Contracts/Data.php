<?php

namespace CleaniqueCoders\Uat\Contracts;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;

interface Data
{
    public function getProjectInformation(): array;

    public function getUsers(): Collection;

    public function getAvailableModules(): array;

    public function getRoutePrerequisites(Route $route, array $middleware): array;

    public function getMiddlewarePrerequisites(array $middleware): array;
}
