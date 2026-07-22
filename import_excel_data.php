<?php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Models\Scholarship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Starting import process from JSON...\n";

function cleanPhone($phone) {
    if (empty($phone)) return null;
    // If it contains slashes, take the first part
    $parts = explode('/', $phone);
    $first = trim($parts[0]);
    // Remove non-numeric/non-space characters
    $first = preg_replace('/[^0-9\s\+\-\(\)]/', '', $first);
    return substr(trim($first), 0, 20) ?: null;
}

\App\Support\BranchContext::within(1, function () {
    // 1. Get or create active Academic Year 2025-2026
    $ay = AcademicYear::updateOrCreate(
        ['name' => '2025-2026'],
        [
            'start_date' => '2025-09-01',
            'end_date'   => '2026-06-30',
            'is_active'  => true,
        ]
    );
    echo "Academic Year: {$ay->name} (ID: {$ay->id})\n";

    // 2. Load JSON File
    $jsonPath = 'document/students_data.json';
    if (!file_exists($jsonPath)) {
        echo "FAIL: File not found at $jsonPath\n";
        exit(1);
    }

    $data = json_decode(file_get_contents($jsonPath), true);
    if (!is_array($data)) {
        echo "FAIL: Invalid JSON format!\n";
        exit(1);
    }
    echo "Total records in JSON: " . count($data) . "\n";

    // Default teacher if parsing fails
    $defaultTeacherUser = User::updateOrCreate(
        ['email' => 'default.teacher@edu.kh'],
        [
            'name' => 'Default Teacher',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]
    );
    $defaultTeacherUser->syncRoles(['teacher']);
    $defaultTeacherStaff = Staff::updateOrCreate(
        ['user_id' => $defaultTeacherUser->id],
        [
            'staff_code' => 'STF-DF01',
            'position' => 'Teacher',
            'department' => 'Academic',
            'joined_at' => '2025-09-01',
            'salary' => 800.00,
        ]
    );

    // Get current sequential counter for student codes
    $maxCode = DB::table('students')
        ->where('student_code', 'like', 'KIA-25-%')
        ->orderBy('student_code', 'desc')
        ->value('student_code');
    
    $startSeq = 7; // KIA-25-0001 to 0006 exist
    if ($maxCode) {
        $parts = explode('-', $maxCode);
        if (count($parts) === 3) {
            $startSeq = intval($parts[2]) + 1;
        }
    }
    echo "Starting student code sequence at: KIA-25-" . str_pad($startSeq, 4, '0', STR_PAD_LEFT) . "\n";

    $importCount = 0;
    $classCount = 0;
    $sectionCount = 0;
    $scholarshipCount = 0;

    // Cache to avoid querying teachers and classes/sections repeatedly
    $teacherCache = [];
    $classCache = [];
    $sectionCache = [];

    DB::beginTransaction();

    try {
        foreach ($data as $index => $item) {
            $teacherName = trim($item['teacher'] ?? '');
            $teacherStaffId = $defaultTeacherStaff->id;

            if (!empty($teacherName)) {
                if (isset($teacherCache[$teacherName])) {
                    $teacherStaffId = $teacherCache[$teacherName];
                } else {
                    // Create User & Staff for this teacher
                    $cleanEmail = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $teacherName));
                    if (empty($cleanEmail)) {
                        $cleanEmail = 'teacher.' . substr(md5($teacherName), 0, 8);
                    }
                    $teacherEmail = $cleanEmail . '@edu.kh';

                    $teacherUser = User::updateOrCreate(
                        ['email' => $teacherEmail],
                        [
                            'name' => $teacherName,
                            'password' => Hash::make('password'),
                            'status' => 'active',
                            'email_verified_at' => now(),
                        ]
                    );
                    $teacherUser->syncRoles(['teacher']);
                    
                    $teacherStaff = Staff::updateOrCreate(
                        ['user_id' => $teacherUser->id],
                        [
                            'staff_code' => 'STF-' . str_pad($teacherUser->id, 4, '0', STR_PAD_LEFT),
                            'position' => 'Teacher',
                            'department' => 'Academic',
                            'joined_at' => '2025-09-01',
                            'salary' => 900.00,
                        ]
                    );
                    $teacherStaffId = $teacherStaff->id;
                    $teacherCache[$teacherName] = $teacherStaffId;
                }
            }

            // Resolve Class & Section
            $classCol = trim($item['class_col'] ?? '');
            if (empty($classCol)) {
                $classCol = trim($item['header_class'] ?? '');
                // Clean header text to get class name
                if (preg_match('/\(([^)]+)\)/', $classCol, $matches)) {
                    $classCol = trim($matches[1]);
                }
            }

            $classCol = preg_replace('/\b(New|Copy|FT|ok|okey)\b/i', '', $classCol);
            $classCol = trim($classCol);

            if (empty($classCol)) {
                $classCol = "General Class";
            }

            $className = $classCol;
            $sectionName = 'Section A';

            // Split class and section
            if (preg_match('/^(Grade\s*\d+[A-Z]?)\s+(Morning|Afternoon|Evening|Night|ព្រឹក|ល្ងាច)/i', $classCol, $matches)) {
                $className = trim($matches[1]);
                $sect = strtolower(trim($matches[2]));
                $sectionName = match($sect) {
                    'morning', 'ព្រឹក' => 'Morning',
                    'afternoon', 'ល្ងាច' => 'Afternoon',
                    'evening' => 'Evening',
                    default => 'Morning',
                };
            } elseif (preg_match('/^(K\d+[A-Z]?)\s+(Morning|Afternoon|Evening|Night|ព្រឹក|ល្ងាច)/i', $classCol, $matches)) {
                $className = trim($matches[1]);
                $sect = strtolower(trim($matches[2]));
                $sectionName = match($sect) {
                    'morning', 'ព្រឹក' => 'Morning',
                    'afternoon', 'ល្ងាច' => 'Afternoon',
                    'evening' => 'Evening',
                    default => 'Morning',
                };
            } elseif (str_contains($classCol, 'ព្រឹក')) {
                $className = trim(str_replace('ព្រឹក', '', $classCol));
                $sectionName = 'Morning';
            } elseif (str_contains($classCol, 'ល្ងាច')) {
                $className = trim(str_replace('ល្ងាច', '', $classCol));
                $sectionName = 'Afternoon';
            }

            $className = trim($className);

            // Get or create SchoolClass
            $classCacheKey = $className;
            if (isset($classCache[$classCacheKey])) {
                $schoolClass = $classCache[$classCacheKey];
            } else {
                $level = 'General';
                if (str_contains(strtolower($className), 'grade')) {
                    $level = 'Primary School';
                } elseif (str_contains(strtolower($className), 'k1') || str_contains(strtolower($className), 'k2') || str_contains(strtolower($className), 'k3') || str_contains(strtolower($className), 'kindergarten')) {
                    $level = 'Preschool';
                }
                
                $schoolClass = SchoolClass::updateOrCreate(
                    ['name' => $className],
                    ['level' => $level, 'capacity' => 30]
                );
                $classCache[$classCacheKey] = $schoolClass;
                $classCount++;
            }

            // Get or create Section
            $sectionCacheKey = $schoolClass->id . '_' . $sectionName;
            if (isset($sectionCache[$sectionCacheKey])) {
                $section = $sectionCache[$sectionCacheKey];
            } else {
                $section = Section::updateOrCreate(
                    ['school_class_id' => $schoolClass->id, 'name' => $sectionName],
                    ['class_teacher_id' => $teacherStaffId]
                );
                $sectionCache[$sectionCacheKey] = $section;
                $sectionCount++;
            }

            // Parse Date of Birth
            $dob = null;
            if (isset($item['dob_info']) && is_array($item['dob_info'])) {
                $info = $item['dob_info'];
                if ($info['type'] === 'excel') {
                    try {
                        $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($info['val']);
                        $dob = $dateObj->format('Y-m-d');
                    } catch (\Exception $e) {
                        $dob = null;
                    }
                } else {
                    // Try parsing string date
                    $dobVal = $info['val'];
                    $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d', 'd.m.Y', 'd-M-Y', 'Y.m.d'];
                    foreach ($formats as $format) {
                        try {
                            $parsed = \DateTime::createFromFormat($format, $dobVal);
                            if ($parsed !== false) {
                                $dob = $parsed->format('Y-m-d');
                                break;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    if (!$dob) {
                        try {
                            $timestamp = strtotime($dobVal);
                            if ($timestamp !== false) {
                                $dob = date('Y-m-d', $timestamp);
                            }
                        } catch (\Exception $e) {}
                    }
                }
            }

            $nameEn = trim($item['name_en']);
            $nameKm = trim($item['name_km'] ?? '');
            $gender = trim($item['gender']);
            
            // Clean phone number to avoid database truncation error
            $phone = cleanPhone($item['phone'] ?? '');

            // Create Student Code
            $studentCode = 'KIA-25-' . str_pad($startSeq, 4, '0', STR_PAD_LEFT);
            $startSeq++;

            // Create Student User (if phone number is present)
            $studentUserId = null;
            if (!empty($phone)) {
                $studentEmail = strtolower(str_replace('-', '', $studentCode)) . '@student.kia.edu.kh';
                $studentUser = User::updateOrCreate(
                    ['email' => $studentEmail],
                    [
                        'name' => $nameEn,
                        'phone' => $phone,
                        'password' => Hash::make('password'),
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]
                );
                $studentUser->syncRoles(['student']);
                $studentUserId = $studentUser->id;
            }

            // Create Student
            $student = Student::create([
                'user_id' => $studentUserId,
                'student_code' => $studentCode,
                'name_en' => $nameEn,
                'name_km' => $nameKm ?: null,
                'gender' => $gender,
                'date_of_birth' => $dob,
                'address' => 'Phnom Penh, Cambodia',
                'status' => 'enrolled',
            ]);

            // Link to section for academic year
            DB::table('student_section')->insert([
                'student_id' => $student->id,
                'section_id' => $section->id,
                'academic_year_id' => $ay->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Scholarship
            $scholarshipVal = trim($item['scholarship'] ?? '');
            if (!empty($scholarshipVal)) {
                $value = 100.00;
                if (str_contains($scholarshipVal, '50%')) {
                    $value = 50.00;
                } elseif (str_contains($scholarshipVal, '30%')) {
                    $value = 30.00;
                }
                
                Scholarship::create([
                    'student_id' => $student->id,
                    'type' => 'percent',
                    'value' => $value,
                    'reason' => $scholarshipVal,
                    'is_active' => true,
                ]);
                $scholarshipCount++;
            }

            $importCount++;
            if ($importCount % 100 === 0) {
                echo "Processed $importCount students...\n";
            }
        }

        DB::commit();
        echo "SUCCESS: Imported $importCount students.\n";
        echo "Classes created/loaded: $classCount.\n";
        echo "Sections created/loaded: $sectionCount.\n";
        echo "Scholarships created: $scholarshipCount.\n";
    } catch (\Exception $e) {
        DB::rollBack();
        echo "ERROR during import: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
        exit(1);
    }
});
