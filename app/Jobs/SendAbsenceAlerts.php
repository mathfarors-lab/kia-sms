<?php

namespace App\Jobs;

use App\Models\Section;
use App\Models\Student;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAbsenceAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $sectionId,
        public readonly array $studentIds,
    ) {}

    public function handle(SmsService $sms): void
    {
        $sectionName = Section::with('schoolClass')->find($this->sectionId)?->schoolClass?->name;
        $date        = now()->format('d/m/Y');

        $students = Student::with('guardians')->whereIn('id', $this->studentIds)->get();

        foreach ($students as $student) {
            Log::info("Absence alert: student {$student->id} absent from section {$this->sectionId} today.");

            // Primary guardian first; fall back to any guardian with a phone.
            $guardian = $student->guardians->firstWhere('pivot.is_primary', true)
                     ?? $student->guardians->first();

            if (!$guardian?->phone) {
                continue;
            }

            $sms->send($guardian->phone, __('sms.absence', [
                'name'  => $student->name_km ?: $student->name_en,
                'class' => $sectionName ?? '',
                'date'  => $date,
            ]));
        }
    }
}
