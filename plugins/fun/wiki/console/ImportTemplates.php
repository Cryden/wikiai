<?php declare(strict_types=1);

namespace Fun\Wiki\Console;

use Illuminate\Console\Command;
use Fun\Wiki\Services\TemplateTransferService;

class ImportTemplates extends Command
{
    protected $signature = 'wiki:import-templates {file? : Path to JSON file} {--truncate : Truncate existing templates and fields before import}';

    protected $description = 'Import templates and fields from a JSON export file';

    public function handle(TemplateTransferService $service): int
    {
        $filePath = $this->argument('file');

        if (!$filePath) {
            $this->error('Please specify a JSON file path.');
            return 1;
        }

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $jsonContent = file_get_contents($filePath);
        $truncate = (bool) $this->option('truncate');

        try {
            $this->info('Starting import...');
            $result = $service->import($jsonContent, $truncate);
            $this->info("Import completed successfully! Templates: {$result['templates']}, Fields: {$result['fields']}.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }
}

