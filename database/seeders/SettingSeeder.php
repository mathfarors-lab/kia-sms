<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // School profile
            ['key' => 'school_name_en',    'value' => 'Khmer Intellectual Academy', 'group' => 'school'],
            ['key' => 'school_name_km',    'value' => 'វិទ្យាស្ថានបញ្ញាខ្មែរ',          'group' => 'school'],
            ['key' => 'school_email',      'value' => 'info@kia.edu.kh',              'group' => 'school'],
            ['key' => 'school_phone',      'value' => '+855 23 000 000',              'group' => 'school'],
            ['key' => 'school_address',    'value' => 'Phnom Penh, Cambodia',         'group' => 'school'],
            ['key' => 'school_website',    'value' => 'https://kia.edu.kh',           'group' => 'school'],
            ['key' => 'school_currency',   'value' => 'USD',                          'group' => 'school'],
            ['key' => 'school_timezone',   'value' => 'Asia/Phnom_Penh',             'group' => 'school'],
            // Academic settings
            ['key' => 'pass_mark',         'value' => '50',                           'group' => 'academic'],
            ['key' => 'student_code_prefix', 'value' => 'KIA',                        'group' => 'academic'],
            // Finance
            ['key' => 'invoice_prefix',    'value' => 'INV',                          'group' => 'finance'],
            ['key' => 'invoice_due_days',  'value' => '30',                           'group' => 'finance'],
            // Sibling/family discount (M4) — percent off the subtotal, auto-applied
            // at invoice generation. Set to 0 to disable for a branch.
            ['key' => 'sibling_discount_percent', 'value' => '10', 'group' => 'finance'],
            // Gate scan station (M3) — per-branch, overridable from Settings like any other row
            ['key' => 'gate_late_cutoff',    'value' => '07:30', 'group' => 'gate'],
            ['key' => 'gate_absent_cutoff',  'value' => '09:00', 'group' => 'gate'],
            ['key' => 'gate_track_departure','value' => '0',     'group' => 'gate'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], ['value' => $s['value'], 'group' => $s['group']]);
        }
    }
}
