<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "Updating all users' passwords to 'P@ssw0rd123' on the server...\n";

\App\Support\BranchContext::within(1, function () {
    // 1. Hash the password once to speed up bulk updates
    $newPasswordHash = Hash::make('P@ssw0rd123');
    
    // 2. Perform bulk update
    $updatedCount = DB::table('users')->update([
        'password' => $newPasswordHash,
        'updated_at' => now(),
    ]);
    
    echo "Successfully updated passwords for $updatedCount users.\n";

    // 3. Export all users to a CSV file
    $csvPath = 'document/users_credentials_list.csv';
    $file = fopen($csvPath, 'w');
    
    // Add UTF-8 BOM for Excel Khmer language compatibility
    fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($file, ['No', 'Name', 'Email', 'Role', 'Password']);
    
    $users = User::with('roles')->orderBy('name')->get();
    
    $index = 1;
    $rolesSummary = [];
    
    foreach ($users as $user) {
        $roleName = $user->roles->pluck('name')->first() ?? 'student';
        
        fputcsv($file, [
            $index++,
            $user->name,
            $user->email,
            $roleName,
            'P@ssw0rd123'
        ]);
        
        if (!isset($rolesSummary[$roleName])) {
            $rolesSummary[$roleName] = [];
        }
        
        if (count($rolesSummary[$roleName]) < 10) {
            $rolesSummary[$roleName][] = [
                'name' => $user->name,
                'email' => $user->email
            ];
        }
    }
    
    fclose($file);
    echo "Successfully generated credentials CSV at: $csvPath\n";
    
    // Print a quick summary of roles
    echo "\nSummary of Accounts:\n";
    foreach ($rolesSummary as $role => $sampleList) {
        echo "- Role '$role': " . count($users->filter(fn($u) => $u->hasRole($role))) . " users total. Sample accounts:\n";
        foreach ($sampleList as $sample) {
            echo "  * {$sample['name']} ({$sample['email']})\n";
        }
    }
});
