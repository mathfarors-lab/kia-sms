<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GradeScaleSeeder::class,
            DemoUserSeeder::class,
            SettingSeeder::class,
            AcademicSeeder::class,
        ]);
    }
}
