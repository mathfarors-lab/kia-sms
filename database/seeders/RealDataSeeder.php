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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        \App\Support\BranchContext::within(1, function () {
            // 1. Create Academic Year 2024-2025
            $ay = AcademicYear::updateOrCreate(
                ['name' => '2024-2025'],
                [
                    'start_date' => '2024-09-01',
                    'end_date'   => '2025-06-30',
                    'is_active'  => false,
                ]
            );

            // 2. Create Teachers
            $teachersData = [
                'Yorn Boran' => 'yorn.boran@edu.kh',
                'Nan Vanny Kimlay' => 'nan.vanny.kimlay@edu.kh',
                'Nan Soriya Sinaroth' => 'nan.soriya.sinaroth@edu.kh',
            ];
            $teachers = [];
            foreach ($teachersData as $name => $email) {
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make('password'),
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]
                );
                $user->syncRoles(['teacher']);
                
                $staff = Staff::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'staff_code' => 'STF-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                        'position' => 'Teacher',
                        'department' => 'Academic',
                        'joined_at' => '2024-09-01',
                        'salary' => 900.00,
                    ]
                );
                $teachers[$name] = $staff;
            }

            // 3. Create Classes and Sections
            $classes = [
                'K1' => [
                    'name' => 'Level K1',
                    'level' => 'Preschool',
                    'teacher' => 'Yorn Boran',
                ],
                'L2' => [
                    'name' => 'Level 2',
                    'level' => 'Primary School',
                    'teacher' => 'Nan Vanny Kimlay',
                ],
                'L1B' => [
                    'name' => 'Level 1B',
                    'level' => 'Primary School',
                    'teacher' => 'Nan Soriya Sinaroth',
                ]
            ];
            $createdClasses = [];
            $createdSections = [];

            foreach ($classes as $key => $info) {
                $class = SchoolClass::updateOrCreate(
                    ['name' => $info['name']],
                    ['level' => $info['level'], 'capacity' => 30]
                );
                $createdClasses[$key] = $class;

                $section = Section::updateOrCreate(
                    ['school_class_id' => $class->id, 'name' => 'Section A'],
                    ['class_teacher_id' => $teachers[$info['teacher']]->id]
                );
                $createdSections[$key] = $section;
            }

            // 4. Create Subjects
            $subjectsData = [
                'K1' => [
                    ['K1_ATT_CP', 'Attendance & Class Participation', 'វត្តមាន និងការចូលរួម', 0.10],
                    ['K1_HW_QUIZ', 'Homework & Quiz', 'កិច្ចការផ្ទះ និងកម្រងសំណួរ', 0.05],
                    ['K1_LISTENING', 'Listening', 'ការស្តាប់', 0.05],
                    ['K1_PRONUNCIATION', 'Pronunciation', 'ការបញ្ចេញសំឡេង', 0.05],
                    ['K1_PROJECT', 'Project', 'គម្រោង', 0.10],
                    ['K1_ORAL_SPEAKING', 'Oral Test Speaking', 'ការប្រឡងនិយាយផ្ទាល់មាត់', 0.10],
                    ['K1_PE', 'Physical Education', 'អប់រំកាយ', 0.05],
                    ['K1_FOREIGN_MID', 'Foreign Midterm', 'ប្រឡងពាក់កណ្តាលឆមាសបរទេស', 0.10],
                    ['K1_KH_MID', 'Khmer Midterm', 'ប្រឡងពាក់កណ្តាលឆមាសខ្មែរ', 0.10],
                    ['K1_FOREIGN_FINAL', 'Foreign Final', 'ប្រឡងឆមាសបរទេស', 0.10],
                    ['K1_KH_FINAL', 'Khmer Final', 'ប្រឡងឆមាសខ្មែរ', 0.20],
                ],
                'L2' => [
                    ['L2_ATT_CP', 'Attendance & Class Participation', 'វត្តមាន និងការចូលរួម', 0.10],
                    ['L2_HW_QUIZ', 'Homework & Quiz', 'កិច្ចការផ្ទះ និងកម្រងសំណួរ', 0.05],
                    ['L2_SOCIAL_STUDY', 'Social Study', 'សិក្សាសង្គម', 0.05],
                    ['L2_SCIENCE', 'Science', 'វិទ្យាសាស្ត្រ', 0.05],
                    ['L2_ASS_PP', 'Assignment & Pupil Profile', 'កិច្ចការ និងប្រវត្តិកុមារ', 0.10],
                    ['L2_ORAL_SPEAKING', 'Oral Test Speaking Test', 'ការប្រឡងនិយាយផ្ទាល់មាត់', 0.10],
                    ['L2_PE', 'Physical Education', 'អប់រំកាយ', 0.05],
                    ['L2_FOREIGN_MID', 'Foreign Midterm', 'ប្រឡងពាក់កណ្តាលឆមាសបរទេស', 0.10],
                    ['L2_KH_MID', 'Khmer Midterm', 'ប្រឡងពាក់កណ្តាលឆមាសខ្មែរ', 0.10],
                    ['L2_FOREIGN_FINAL', 'Foreign Final', 'ប្រឡងឆមាសបរទេស', 0.10],
                    ['L2_KH_FINAL', 'Khmer Final', 'ប្រឡងឆមាសខ្មែរ', 0.20],
                ],
                'L1B' => [
                    ['L1B_ATT_CP', 'Attendance & Class Participation', 'វត្តមាន និងការចូលរួម', 0.10],
                    ['L1B_HW_QUIZ', 'Homework & Quiz', 'កិច្ចការផ្ទះ និងកម្រងសំណួរ', 0.05],
                    ['L1B_SOCIAL_STUDY', 'Social Study', 'សិក្សាសង្គម', 0.05],
                    ['L1B_SCIENCE', 'Science', 'វិទ្យាសាស្ត្រ', 0.05],
                    ['L1B_ASS_PP', 'Assignment & Pupil Profile', 'កិច្ចការ និងប្រវត្តិកុមារ', 0.10],
                    ['L1B_ORAL_SPEAKING', 'Oral Test Speaking Test', 'ការប្រឡងនិយាយផ្ទាល់មាត់', 0.10],
                    ['L1B_FOREIGN_MID', 'Foreign Midterm', 'ប្រឡងពាក់កណ្តាលឆមាសបរទេស', 0.10],
                    ['L1B_KH_MID', 'Khmer Midterm', 'ប្រឡងពាក់កណ្តាលឆមាសខ្មែរ', 0.10],
                    ['L1B_FOREIGN_FINAL', 'Foreign Final', 'ប្រឡងឆមាសបរទេស', 0.10],
                    ['L1B_KH_FINAL', 'Khmer Final', 'ប្រឡងឆមាសខ្មែរ', 0.20],
                    ['L1B_PE_DUMMY', 'Physical Education', 'អប់រំកាយ', 0.05],
                ]
            ];

            $subjects = [];
            foreach ($subjectsData as $key => $list) {
                $subjects[$key] = [];
                $classObj = $createdClasses[$key];
                $teacherObj = $teachers[$classes[$key]['teacher']];
                
                foreach ($list as $data) {
                    $sub = Subject::updateOrCreate(
                        ['code' => $data[0]],
                        [
                            'name_en' => $data[1],
                            'name_km' => $data[2],
                            'coefficient' => $data[3],
                            'full_mark' => 100
                        ]
                    );
                    $subjects[$key][$data[0]] = $sub;

                    ClassSubject::updateOrCreate(
                        ['school_class_id' => $classObj->id, 'subject_id' => $sub->id],
                        ['teacher_id' => $teacherObj->id]
                    );
                }
            }

            // 5. Create Exams
            $examTerm1 = Exam::updateOrCreate(
                ['academic_year_id' => $ay->id, 'name' => 'Term 1 Assessment'],
                [
                    'type' => 'final',
                    'semester' => 1,
                    'weight' => 1.00,
                    'is_published' => true,
                    'exam_date' => '2024-12-20'
                ]
            );

            $examTerm2 = Exam::updateOrCreate(
                ['academic_year_id' => $ay->id, 'name' => 'Term 2 Assessment'],
                [
                    'type' => 'final',
                    'semester' => 2,
                    'weight' => 1.00,
                    'is_published' => true,
                    'exam_date' => '2025-03-20'
                ]
            );

            // 6. Seed Students and Marks
            // Level K1 Data (Term 1)
            $studentsK1 = [
                ['KIA-24-K101', 'Channa Vannak', 'male', '2019-05-15', [55, 98, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K102', 'Channy Zuly', 'female', '2020-03-22', [70, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K103', 'Choeun Raksmey', 'male', '2019-11-08', [75, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K104', 'Eng Lee Xaing', 'male', '2020-01-12', [75, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K105', 'Hay Changmonea', 'female', '2019-07-19', [0, 90, 100, 50, 98, 80, 90, 90, 89, 89, 90]],
                ['KIA-24-K106', 'Heng Porling', 'female', '2020-09-05', [65, 90, 100, 50, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K107', 'Hun Kimcheng', 'female', '2019-12-30', [85, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K108', 'Keo Kimhoung', 'female', '2020-06-14', [100, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K109', 'Kimseng Sivling', 'female', '2019-08-25', [100, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
                ['KIA-24-K110', 'Ly Sereyboth', 'male', '2020-04-18', [100, 90, 100, 100, 100, 100, 100, 100, 100, 100, 100]],
            ];

            $subCodesK1 = [
                'K1_ATT_CP', 'K1_HW_QUIZ', 'K1_LISTENING', 'K1_PRONUNCIATION',
                'K1_PROJECT', 'K1_ORAL_SPEAKING', 'K1_PE', 'K1_FOREIGN_MID',
                'K1_KH_MID', 'K1_FOREIGN_FINAL', 'K1_KH_FINAL'
            ];

            foreach ($studentsK1 as $data) {
                $student = Student::updateOrCreate(
                    ['student_code' => $data[0]],
                    [
                        'name_en' => $data[1],
                        'gender' => $data[2],
                        'date_of_birth' => $data[3],
                        'address' => 'Phnom Penh, Cambodia',
                        'status' => 'enrolled'
                    ]
                );

                DB::table('student_section')->updateOrInsert(
                    ['student_id' => $student->id, 'academic_year_id' => $ay->id],
                    ['section_id' => $createdSections['K1']->id, 'created_at' => now(), 'updated_at' => now()]
                );

                // Insert marks for Term 1
                foreach ($subCodesK1 as $idx => $code) {
                    ExamMark::updateOrCreate(
                        [
                            'exam_id' => $examTerm1->id,
                            'student_id' => $student->id,
                            'subject_id' => $subjects['K1'][$code]->id
                        ],
                        [
                            'score' => $data[4][$idx],
                            'grade' => null
                        ]
                    );
                }
            }

            // Level 2 Data (Term 2)
            $studentsL2 = [
                ['KIA-24-L201', 'Borey Vivattana', 'male', '2017-02-14', [100, 100, 85, 90, 95, 64, 95, 90, 82, 100, 92]],
                ['KIA-24-L202', 'Buntha Prathna', 'male', '2018-05-09', [95, 100, 91, 100, 94, 80, 50, 100, 91, 100, 94]],
                ['KIA-24-L203', 'Chat Chanmengty', 'male', '2017-10-11', [95, 100, 80, 89, 85, 52, 93, 89, 55, 100, 87]],
                ['KIA-24-L204', 'Chen Bormen', 'female', '2018-01-25', [100, 99.67, 83.5, 85, 100, 60, 95, 85, 85, 100, 87]],
                ['KIA-24-L205', 'Da Lymeng', 'male', '2017-06-30', [90, 99.75, 71, 70, 90, 52, 95, 70, 64, 75, 70]],
                ['KIA-24-L206', 'Da Mouyseang', 'female', '2018-09-18', [90, 99.67, 87, 93, 96, 68, 93, 93, 71, 100, 93]],
                ['KIA-24-L207', 'Dy Chichhun', 'male', '2017-12-05', [95, 100, 86, 91, 85, 80, 95, 91, 95, 100, 97]],
                ['KIA-24-L208', 'Dy Huoyling', 'female', '2018-04-03', [90, 99.50, 91, 100, 100, 96, 50, 100, 75, 100, 96]],
                ['KIA-24-L209', 'Dy Kimyong', 'male', '2017-08-27', [95, 99.33, 79, 98, 100, 88, 50, 98, 76, 100, 97]],
                ['KIA-24-L210', 'Hong Lyly', 'female', '2018-11-12', [100, 100, 93, 100, 100, 76, 60, 100, 97, 100, 96]],
                ['KIA-24-L211', 'Horn Limoney', 'female', '2017-03-08', [100, 100, 85.5, 97, 100, 92, 50, 97, 98, 100, 96]],
                ['KIA-24-L212', 'Hy Bunsengli', 'male', '2018-07-21', [95, 99.00, 91, 81, 100, 72, 50, 81, 52, 100, 81]],
                ['KIA-24-L213', 'Kai Kimchhay', 'male', '2017-09-14', [75, 100, 81, 97, 80, 76, 50, 97, 86, 80, 96]],
                ['KIA-24-L214', 'Keo Julida', 'female', '2018-10-02', [100, 100, 69, 71, 80, 84, 95, 71, 96, 80, 94]],
            ];

            $subCodesL2 = [
                'L2_ATT_CP', 'L2_HW_QUIZ', 'L2_SOCIAL_STUDY', 'L2_SCIENCE',
                'L2_ASS_PP', 'L2_ORAL_SPEAKING', 'L2_PE', 'L2_FOREIGN_MID',
                'L2_KH_MID', 'L2_FOREIGN_FINAL', 'L2_KH_FINAL'
            ];

            foreach ($studentsL2 as $data) {
                $student = Student::updateOrCreate(
                    ['student_code' => $data[0]],
                    [
                        'name_en' => $data[1],
                        'gender' => $data[2],
                        'date_of_birth' => $data[3],
                        'address' => 'Phnom Penh, Cambodia',
                        'status' => 'enrolled'
                    ]
                );

                DB::table('student_section')->updateOrInsert(
                    ['student_id' => $student->id, 'academic_year_id' => $ay->id],
                    ['section_id' => $createdSections['L2']->id, 'created_at' => now(), 'updated_at' => now()]
                );

                // Insert marks for Term 2
                foreach ($subCodesL2 as $idx => $code) {
                    ExamMark::updateOrCreate(
                        [
                            'exam_id' => $examTerm2->id,
                            'student_id' => $student->id,
                            'subject_id' => $subjects['L2'][$code]->id
                        ],
                        [
                            'score' => $data[4][$idx],
                            'grade' => null
                        ]
                    );
                }
            }

            // Level 1B Data (Term 2)
            $studentsL1B = [
                ['KIA-24-L1B01', 'Cheourn Sokleap', 'female', '2018-04-12', [100, 96.67, 95, 80, 70, 70, 66, 52, 70, 51, 0]],
                ['KIA-24-L1B02', 'Dy Leakhena', 'female', '2019-01-05', [100, 81.67, 95, 70, 61, 75, 77, 73, 91, 69, 0]],
                ['KIA-24-L1B03', 'Hai Vannita', 'female', '2018-09-22', [70, 91.67, 86, 70, 70, 70, 89, 50, 70, 55, 0]],
                ['KIA-24-L1B04', 'Hor Gechleng', 'female', '2019-03-30', [80, 90.00, 91, 90, 60, 80, 64, 58, 86, 45, 0]],
                ['KIA-24-L1B05', 'Lak Souling', 'female', '2018-12-14', [70, 90.00, 95, 80, 68, 80, 82, 55, 86, 70, 0]],
                ['KIA-24-L1B06', 'Lim Sinhav', 'female', '2019-07-08', [80, 90.00, 94, 100, 65, 95, 84, 64, 92, 59, 0]],
                ['KIA-24-L1B07', 'Lorn Ousilling', 'female', '2018-11-25', [0, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0]],
                ['KIA-24-L1B08', 'Lorn Tola', 'male', '2019-06-03', [70, 85.00, 80, 75, 72, 75, 65, 58, 64, 64, 0]],
                ['KIA-24-L1B09', 'Meng Rachana', 'female', '2018-10-18', [80, 81.67, 94, 85, 65, 70, 77, 50, 80, 50, 0]],
                ['KIA-24-L1B10', 'Nan Chansotheareak', 'male', '2019-02-28', [60, 83.33, 98, 85, 60, 80, 93, 84, 87, 77, 0]],
                ['KIA-24-L1B11', 'Nan Seavfong', 'male', '2018-08-15', [70, 90.00, 91, 80, 85, 93, 87, 72, 95, 75, 0]],
                ['KIA-24-L1B12', 'Pengkry Tonika', 'female', '2019-05-09', [95, 80.00, 91, 80, 55, 70, 95, 64, 90, 64, 0]],
                ['KIA-24-L1B13', 'Phal Kimleakhena', 'female', '2018-07-27', [95, 95.00, 96, 95, 78, 75, 93, 71, 90, 81, 0]],
                ['KIA-24-L1B14', 'Phan Marayuth', 'male', '2019-04-03', [85, 88.33, 92, 70, 72, 68, 92, 51, 77, 66, 0]],
                ['KIA-24-L1B15', 'Pouen Panhavorn', 'male', '2018-12-05', [90, 75.00, 92, 80, 75, 65, 89, 56, 75, 64, 0]],
                ['KIA-24-L1B16', 'Poun Keopunleu', 'male', '2019-01-18', [90, 76.67, 86, 80, 61, 75, 78, 63, 85, 50, 0]],
                ['KIA-24-L1B17', 'Ret Seavthav', 'female', '2018-09-08', [95, 91.67, 90, 85, 67, 64, 82, 51, 70, 48, 0]],
                ['KIA-24-L1B18', 'Rith Meychhing', 'female', '2019-03-25', [60, 80.00, 96, 90, 70, 70, 80, 53, 80, 55, 0]],
                ['KIA-24-L1B19', 'Ros Vannmonika', 'female', '2018-10-30', [60, 85.00, 93, 78, 70, 75, 78, 59, 88, 50, 0]],
            ];

            $subCodesL1B = [
                'L1B_ATT_CP', 'L1B_HW_QUIZ', 'L1B_SOCIAL_STUDY', 'L1B_SCIENCE',
                'L1B_ASS_PP', 'L1B_ORAL_SPEAKING', 'L1B_FOREIGN_MID', 'L1B_KH_MID',
                'L1B_FOREIGN_FINAL', 'L1B_KH_FINAL', 'L1B_PE_DUMMY'
            ];

            foreach ($studentsL1B as $data) {
                $student = Student::updateOrCreate(
                    ['student_code' => $data[0]],
                    [
                        'name_en' => $data[1],
                        'gender' => $data[2],
                        'date_of_birth' => $data[3],
                        'address' => 'Phnom Penh, Cambodia',
                        'status' => 'enrolled'
                    ]
                );

                DB::table('student_section')->updateOrInsert(
                    ['student_id' => $student->id, 'academic_year_id' => $ay->id],
                    ['section_id' => $createdSections['L1B']->id, 'created_at' => now(), 'updated_at' => now()]
                );

                // Insert marks for Term 2
                foreach ($subCodesL1B as $idx => $code) {
                    ExamMark::updateOrCreate(
                        [
                            'exam_id' => $examTerm2->id,
                            'student_id' => $student->id,
                            'subject_id' => $subjects['L1B'][$code]->id
                        ],
                        [
                            'score' => $data[4][$idx],
                            'grade' => null
                        ]
                    );
                }
            }

            // 7. Compute Term Results using TermGradingService
            $gradingService = app(\App\Services\TermGradingService::class);
            
            // Compute Semester 1 (Term 1)
            $gradingService->compute($ay, 1);
            
            // Compute Semester 2 (Term 2)
            $gradingService->compute($ay, 2);
        });
    }
}
