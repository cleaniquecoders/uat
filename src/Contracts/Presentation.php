<?php

namespace CleaniqueCoders\Uat\Contracts;

use Illuminate\Support\Collection;

interface Presentation
{
    public function generateProjectInfo(array $projectInfo): string;

    public function generateAccessControls(array $accessControls): string;

    public function generateUsers(Collection $users): string;

    public function generateAvailableModules(array $modules): string;

    public function generateModuleTestSuite(array $module, int $moduleIndex): string;

    public function getExtension(): string;
}
