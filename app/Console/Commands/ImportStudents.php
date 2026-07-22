<?php

namespace App\Console\Commands;

use App\Imports\StudentsImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Console\Input\InputArgument;

class ImportStudents extends Command
{
    protected $signature = 'students:import {file : Path to Excel file}';
    protected $description = 'Import students from an Excel file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        $this->info("Importing students from: $filePath");

        try {
            $import = new StudentsImport();
            Excel::import($import, $filePath);

            $successCount = $import->getSuccessCount();
            $errors = $import->getErrors();

            if ($successCount > 0) {
                $this->info("✓ Successfully imported $successCount student(s)");
            }

            if (!empty($errors)) {
                $this->warn("⚠ Errors encountered:");
                foreach ($errors as $error) {
                    $this->line("  Row {$error['row']}: {$error['message']}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
