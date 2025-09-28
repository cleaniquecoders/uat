<?php

namespace CleaniqueCoders\Uat\Commands;

use CleaniqueCoders\Uat\Actions\GenerateUatScript;
use Illuminate\Console\Command;

class UatCommand extends Command
{
    public $signature = 'uat:generate {--output-dir= : Output directory for UAT files}';

    public $description = 'Generate UAT Scripts';

    public function handle(): int
    {
        $this->info('🚀 Starting UAT Script Generation...');

        try {
            $outputDir = $this->option('output-dir');

            $result = GenerateUatScript::run($outputDir);

            $this->info('✅ UAT scripts generated successfully!');
            $this->info("📁 Directory: {$result['directory']}");
            $this->info('📝 Generated Markdown files:');

            foreach ($result['generated_files'] as $file) {
                $this->line('   - '.basename($file));
            }

            $this->newLine();
            $this->info("📋 Generated UAT documentation for date: {$result['date']}");
            $this->info('🔍 Review the generated files before proceeding with UAT testing.');

            return self::SUCCESS;
        } catch (\Throwable $th) {
            $this->error("❌ Failed to generate UAT scripts: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }

    }
}
