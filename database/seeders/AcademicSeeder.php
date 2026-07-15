<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        // Demo academic structure belongs to Main Campus (branch 1).
        \App\Support\BranchContext::within(1, function () {
        // 1. Active academic year
        AcademicYear::query()->update(['is_active' => false]);
        AcademicYear::updateOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date' => '2025-09-01',
                'end_date'   => '2026-06-30',
                'is_active'  => true,
            ]
        );

        // 2. Teacher staff
        $teacher = Staff::whereHas('user', fn($q) => $q->where('email', 'teacher@edu.kh'))->first();

        // 3. School classes
        $classNames = ['Grade 10', 'Grade 11', 'Grade 12'];
        $createdClasses = [];

        foreach ($classNames as $name) {
            $createdClasses[] = SchoolClass::updateOrCreate(
                ['name' => $name],
                ['level' => 'High School', 'capacity' => 30]
            );
        }

        // 4. Sections per class
        $sectionNames = ['Section A', 'Section B'];
        $grade10Sections = [];

        foreach ($createdClasses as $class) {
            foreach ($sectionNames as $sectionName) {
                $section = Section::updateOrCreate(
                    ['school_class_id' => $class->id, 'name' => $sectionName],
                    ['class_teacher_id' => $teacher?->id]
                );
                if ($class->name === 'Grade 10') {
                    $grade10Sections[] = $section;
                }
            }
        }

        // 5. Subjects
        $subjectsData = [
            ['name_en' => 'Mathematics', 'name_km' => 'គណិតវិទ្យា',    'code' => 'MATH101', 'full_mark' => 100],
            ['name_en' => 'English',     'name_km' => 'ភាសាអង់គ្លេស', 'code' => 'ENG101',  'full_mark' => 100],
            ['name_en' => 'Khmer',       'name_km' => 'ភាសាខ្មែរ',     'code' => 'KHM101',  'full_mark' => 100],
            ['name_en' => 'Science',     'name_km' => 'វិទ្យាសាស្ត្រ', 'code' => 'SCI101',  'full_mark' => 100],
        ];

        $subjects = [];
        foreach ($subjectsData as $data) {
            $subjects[] = Subject::updateOrCreate(['code' => $data['code']], $data);
        }

        // 6. Assign all 4 subjects to Grade 10
        $grade10 = $createdClasses[0];
        foreach ($subjects as $subject) {
            ClassSubject::updateOrCreate(
                ['school_class_id' => $grade10->id, 'subject_id' => $subject->id],
                ['teacher_id' => $teacher?->id]
            );
        }

        // 7. Sample timetable for Grade 10 Section A (Monday periods 1-4)
        $sectionA = $grade10Sections[0] ?? null;
        if ($sectionA) {
            $slots = [
                ['period' => 1, 'subject' => $subjects[0], 'start' => '07:00', 'end' => '08:00'],
                ['period' => 2, 'subject' => $subjects[1], 'start' => '08:00', 'end' => '09:00'],
                ['period' => 3, 'subject' => $subjects[2], 'start' => '09:15', 'end' => '10:15'],
                ['period' => 4, 'subject' => $subjects[3], 'start' => '10:15', 'end' => '11:15'],
            ];

            foreach ($slots as $slot) {
                Timetable::updateOrCreate(
                    [
                        'section_id' => $sectionA->id,
                        'day'        => 'monday',
                        'period'     => $slot['period'],
                    ],
                    [
                        'subject_id' => $slot['subject']->id,
                        'teacher_id' => $teacher?->id,
                        'start_time' => $slot['start'],
                        'end_time'   => $slot['end'],
                    ]
                );
            }
        }

        // 8. Demo exam + marks for Grade 10 Section A
        $year = AcademicYear::where('name', '2025-2026')->first();
        if ($sectionA && $year) {
            $exam = Exam::updateOrCreate(
                ['name' => 'Midterm 2025-2026', 'academic_year_id' => $year->id],
                ['type' => 'midterm', 'is_published' => false]
            );

            // Enroll student demo in section A
            $student = Student::first();
            if ($student) {
                \DB::table('student_section')->updateOrInsert(
                    ['student_id' => $student->id, 'academic_year_id' => $year->id],
                    ['section_id' => $sectionA->id, 'created_at' => now(), 'updated_at' => now()]
                );

                foreach ($subjects as $subject) {
                    ExamMark::updateOrCreate(
                        ['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $subject->id],
                        ['score' => rand(60, 95), 'grade' => null]
                    );
                }
            }
        }
        }); // end BranchContext::within(1)
    }
}
