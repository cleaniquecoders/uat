<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Actions;

use CleaniqueCoders\Uat\Contracts\Data;
use CleaniqueCoders\Uat\Contracts\Presentation;
use Exception;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Action;

class GenerateUatScript extends Action
{
    public function handle(?string $outputDir = null): array
    {
        // @see \CleaniqueCoders\Uat\Contracts\Data
        $dataService = config('uat.services.data');
        if (! $dataService instanceof Data) {
            throw new Exception(config('uat.services.data')." must implements CleaniqueCoders\Uat\Contracts\Data interface.");
        }

        // @see \CleaniqueCoders\Uat\Contracts\Presentation
        $presentation = config('uat.services.presentation');
        if (! $presentation instanceof Presentation) {
            throw new Exception(config('uat.services.presentation')." must implements CleaniqueCoders\Uat\Contracts\Presentation interface.");
        }

        // get project information
        $projectInfo = $dataService->getProjectInformation();

        // create docs/uat/{date} directory
        $date = now()->format('Y-m-d');
        $directory = config('uat.directory');
        $uatDirectory = $outputDir ?: storage_path("{$directory}/{$date}");

        if (File::exists($uatDirectory)) {
            // If directory exists, check if it has files and remove them
            $files = File::files($uatDirectory);
            if (count($files) > 0) {
                File::cleanDirectory($uatDirectory);
            }
        } else {
            File::makeDirectory($uatDirectory, 0755, true);
        }

        $generatedFiles = [];
        $extension = $presentation->getExtension();

        // project info
        $projectInfoContent = $presentation->generateProjectInfo($projectInfo);
        $projectInfoFile = "{$uatDirectory}/01-project-info.{$extension}";
        File::put($projectInfoFile, $projectInfoContent);
        $generatedFiles[] = $projectInfoFile;

        // users
        $users = $dataService->getUsers();
        $usersContent = $presentation->generateUsers($users);
        $usersFile = "{$uatDirectory}/02-users.{$extension}";
        File::put($usersFile, $usersContent);
        $generatedFiles[] = $usersFile;

        // available modules (this should be group / find from routes (exclude vendor, only GET method))
        $modules = $dataService->getAvailableModules();
        $modulesOverviewContent = $presentation->generateAvailableModules($modules);
        $modulesOverviewFile = "{$uatDirectory}/03-available-modules.{$extension}";
        File::put($modulesOverviewFile, $modulesOverviewContent);
        $generatedFiles[] = $modulesOverviewFile;

        // Generate individual module test suite files
        foreach ($modules as $index => $module) {
            $fileNumber = str_pad((string) ($index + 5), 2, '0', STR_PAD_LEFT);
            $moduleFileName = strtolower(str_replace(' ', '-', $module['module']));
            $moduleTestSuiteContent = $presentation->generateModuleTestSuite($module, $index);
            $moduleTestSuiteFile = "{$uatDirectory}/{$fileNumber}-module-{$moduleFileName}.{$extension}";
            File::put($moduleTestSuiteFile, $moduleTestSuiteContent);
            $generatedFiles[] = $moduleTestSuiteFile;
        }

        return [
            'directory' => $uatDirectory,
            'generated_files' => $generatedFiles,
            'date' => $date,
        ];
    }
}
