<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Updating all non-owner users' passwords to 'P@ssw0rd123' on the server...\n";

$ownerIds = User::role('owner')->pluck('id');

$updatedCount = DB::table('users')
    ->whereNotIn('id', $ownerIds)
    ->update([
        'password' => Hash::make('P@ssw0rd123'),
        'updated_at' => now(),
    ]);

echo "Successfully updated passwords for {$updatedCount} users.\n";
echo 'Skipped '.$ownerIds->count()." owner account(s) — left untouched.\n";
