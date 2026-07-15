<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The pre-launch blocker: every seeded demo account (including
 * owner@edu.kh, which controls every branch) shares the password
 * "password" until this runs. Deliberately NOT production-guarded like
 * kia:seed-demo — this is the opposite kind of command: it's meant to be
 * run wherever demo accounts exist, production included, to lock them down
 * before real use.
 */
class SecureDemo extends Command
{
    protected $signature = 'kia:secure-demo';
    protected $description = 'Replace every seeded demo account\'s password with a fresh random one — run before real use';

    public function handle(): int
    {
        $rows = [];

        foreach (DemoUserSeeder::DEMO_EMAILS as $email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                continue; // that demo account was never seeded on this install
            }

            $password = Str::password(20);
            $user->forceFill(['password' => Hash::make($password)])->save();

            $rows[] = [$email, $password];
        }

        if (empty($rows)) {
            $this->warn('No seeded demo accounts found on this install — nothing to secure.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('New passwords below — record these now. They are shown ONCE, are not stored anywhere in');
        $this->warn('plain text, and this terminal output is the only place you will ever see them again.');
        $this->newLine();
        $this->table(['Email', 'New Password'], $rows);
        $this->newLine();
        $this->info(count($rows) . ' demo account(s) secured. Safe to run again — it will simply generate fresh passwords.');

        return self::SUCCESS;
    }
}
