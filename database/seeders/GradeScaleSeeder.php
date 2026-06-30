<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use Illuminate\Database\Seeder;

class GradeScaleSeeder extends Seeder
{
    public function run(): void
    {
        $scales = [
            ['grade' => 'A', 'min_score' => 90, 'max_score' => 100, 'gpa' => 4.0, 'remark_en' => 'Excellent',       'remark_km' => 'ល្អប្រសើរ'],
            ['grade' => 'B', 'min_score' => 80, 'max_score' => 89,  'gpa' => 3.0, 'remark_en' => 'Good',            'remark_km' => 'ល្អ'],
            ['grade' => 'C', 'min_score' => 70, 'max_score' => 79,  'gpa' => 2.0, 'remark_en' => 'Satisfactory',    'remark_km' => 'គ្រប់គ្រាន់'],
            ['grade' => 'D', 'min_score' => 50, 'max_score' => 69,  'gpa' => 1.0, 'remark_en' => 'Needs Improvement','remark_km' => 'ត្រូវការកែប្រែ'],
            ['grade' => 'F', 'min_score' =>  0, 'max_score' => 49,  'gpa' => 0.0, 'remark_en' => 'Fail',            'remark_km' => 'បរាជ័យ'],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(['grade' => $scale['grade']], $scale);
        }
    }
}
