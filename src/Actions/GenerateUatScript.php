<?php

declare(strict_types=1);

namespace CleaniqueCoders\Uat\Actions;

use CleaniqueCoders\Uat\Presentations\MarkdownGenerator;
use CleaniqueCoders\Uat\Services\DataService;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Action;

class GenerateUatScript extends Action
{
    public function handle(?string $outputDir = null): array
    {
        $uatDataService = app(DataService::class);
        $markdownGenerator = app(MarkdownGenerator::class);

        // get project information
        $projectInfo = $uatDataService->getProjectInformation();

        // create docs/uat/{date} directory
        $date = now()->format('Y-m-d');
        $uatDirectory = $outputDir ?: storage_path("uat/{$date}");

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

        // project info
        $projectInfoContent = $markdownGenerator->generateProjectInfo($projectInfo);
        $projectInfoFile = "{$uatDirectory}/01-project-info.md";
        File::put($projectInfoFile, $projectInfoContent);
        $generatedFiles[] = $projectInfoFile;

        // users
        $users = $uatDataService->getUsers();
        $usersContent = $markdownGenerator->generateUsers($users);
        $usersFile = "{$uatDirectory}/02-users.md";
        File::put($usersFile, $usersContent);
        $generatedFiles[] = $usersFile;

        // available modules (this should be group / find from routes (exclude vendor, only GET method))
        $modules = $uatDataService->getAvailableModules();
        $modulesOverviewContent = $markdownGenerator->generateAvailableModules($modules);
        $modulesOverviewFile = "{$uatDirectory}/03-available-modules.md";
        File::put($modulesOverviewFile, $modulesOverviewContent);
        $generatedFiles[] = $modulesOverviewFile;

        // Generate individual module test suite files
        foreach ($modules as $index => $module) {
            $fileNumber = str_pad((string) ($index + 5), 2, '0', STR_PAD_LEFT);
            $moduleFileName = strtolower(str_replace(' ', '-', $module['module']));
            $moduleTestSuiteContent = $markdownGenerator->generateModuleTestSuite($module, $index);
            $moduleTestSuiteFile = "{$uatDirectory}/{$fileNumber}-module-{$moduleFileName}.md";
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
