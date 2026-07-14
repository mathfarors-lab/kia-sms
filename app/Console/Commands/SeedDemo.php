<?php

namespace App\Console\Commands;

use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;

class SeedDemo extends Command
{
    protected $signature = 'kia:seed-demo {--fresh : Drop and re-migrate first}';
    protected $description = 'Seed demo users/data (do NOT run in production)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to seed demo data in production. Set APP_ENV != production.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
            $this->call('db:seed');
        }

        // Roles and permissions must exist before users can be assigned roles.
        $this->call('db:seed', ['--class' => RolePermissionSeeder::class]);
        $this->call('db:seed', ['--class' => DemoUserSeeder::class]);
        $this->info('Demo data seeded.');
        $this->newLine();
        $this->warn('Every seeded account shares the password "password" — including owner@kia.edu.kh,');
        $this->warn('which controls every branch. Before this is reachable by anyone real, run:');
        $this->line('    php artisan kia:secure-demo');
        return self::SUCCESS;
    }
}
