<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class StudentsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithChunkReading
{
    protected $errors = [];
    protected $successCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row, $index + 2);
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    public function chunkSize(): int
    {
        return 500; // Process 500 rows at a time
    }

    protected function processRow($row, $rowNumber)
    {
        // Get column values (adjust these based on your Excel file structure)
        $studentCode = trim($row['student_code'] ?? $row['code'] ?? $row['id'] ?? '');
        $nameEn = trim($row['name_en'] ?? $row['name'] ?? $row['english_name'] ?? '');
        $nameKm = trim($row['name_km'] ?? $row['khmer_name'] ?? '');
        $gender = strtolower(trim($row['gender'] ?? 'male'));
        $dob = $this->parseDate($row['date_of_birth'] ?? $row['dob'] ?? null);
        $address = trim($row['address'] ?? '');

        // Validate required fields
        if (empty($studentCode)) {
            throw new \Exception("Row $rowNumber: Student code is required");
        }
        if (empty($nameEn)) {
            throw new \Exception("Row $rowNumber: Student name is required");
        }

        // Validate gender
        if (!in_array($gender, ['male', 'female', 'other'])) {
            $gender = 'male';
        }

        // Check if student already exists
        if (Student::where('student_code', $studentCode)->exists()) {
            throw new \Exception("Row $rowNumber: Student code '$studentCode' already exists");
        }

        // Create student
        Student::create([
            'student_code' => $studentCode,
            'name_en' => $nameEn,
            'name_km' => $nameKm ?: null,
            'gender' => $gender,
            'date_of_birth' => $dob,
            'address' => $address ?: null,
            'status' => 'enrolled',
        ]);

        $this->successCount++;
    }

    protected function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Try to parse Excel date (numeric)
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Handle string dates
        $value = (string) $value;
        
        // Try common date formats
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d', 'd.m.Y'];
        foreach ($formats as $format) {
            try {
                $parsed = \DateTime::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }
}
