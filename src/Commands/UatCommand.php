<?php

namespace CleaniqueCoders\Uat\Commands;

use CleaniqueCoders\Uat\Actions\GenerateUatScript;
use Exception;
use Illuminate\Console\Command;

class UatCommand extends Command
{
    public $signature = 'uat:generate
        {--output-dir= : Output directory for UAT files}
        {--format=markdown : Output format. See config/uat.php for available presentation outputs}';

    public $description = 'Generate UAT Scripts';

    public function handle(): int
    {
        $this->info('🚀 Starting UAT Script Generation...');

        try {
            $outputDir = $this->option('output-dir');
            $format = $this->option('format');
            $formats = config('uat.formats') ?? ['markdown', 'json'];

            if (! in_array($format, $formats)) {
                throw new Exception('Invalid format. Only '.implode(', ', $formats).' are accepted');
            }
            $result = GenerateUatScript::run($outputDir, $format);

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
            $this->error("❌ Failed to generate UAT scripts: {$th->getMessage()}");

            if ($this->option('verbose')) {
                $this->error($th->getTraceAsString());
            }

            return self::FAILURE;
        }

    }
}
