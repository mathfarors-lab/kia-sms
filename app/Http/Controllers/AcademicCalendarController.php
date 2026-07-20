<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Holiday;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $this->resolveMonth($request->query('month'));

        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $eventsByDate = $this->buildEventsByDate($gridStart, $gridEnd);

        $weeks = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $week[] = [
                    'date' => $cursor->copy(),
                    'inCurrentMonth' => $cursor->month === $month->month,
                    'isToday' => $cursor->isToday(),
                    'events' => $eventsByDate[$key] ?? [],
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $monthEvents = collect($eventsByDate)
            ->filter(fn ($events, $date) => Carbon::parse($date)->month === $month->month)
            ->sortKeys()
            ->flatMap(fn ($events, $date) => collect($events)->map(fn ($e) => $e + ['date' => $date]));

        return view('academic-calendar.index', [
            'month' => $month,
            'weeks' => $weeks,
            'prevMonth' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            'monthEvents' => $monthEvents,
        ]);
    }

    private function resolveMonth(?string $raw): Carbon
    {
        if ($raw && preg_match('/^\d{4}-\d{2}$/', $raw)) {
            return Carbon::createFromFormat('Y-m-d', $raw.'-01')->startOfDay();
        }

        return now()->startOfMonth();
    }

    private function buildEventsByDate(Carbon $gridStart, Carbon $gridEnd): array
    {
        $events = [];

        $push = function (string $date, array $event) use (&$events) {
            $events[$date][] = $event;
        };

        foreach (Holiday::overlapping($gridStart->toDateString(), $gridEnd->toDateString())->get() as $holiday) {
            // String min/max on Y-m-d dates (not Carbon's own max()/min()) so
            // the shared $gridStart/$gridEnd instances can never be aliased
            // and accidentally mutated by the addDay() below.
            $cursor = Carbon::parse(max($holiday->start_date->toDateString(), $gridStart->toDateString()));
            $end = Carbon::parse(min($holiday->end_date->toDateString(), $gridEnd->toDateString()));
            while ($cursor->lte($end)) {
                $push($cursor->toDateString(), ['type' => 'holiday', 'label' => $holiday->name]);
                $cursor->addDay();
            }
        }

        foreach (Exam::query()->whereBetween('exam_date', [$gridStart->toDateString(), $gridEnd->toDateString()])->get() as $exam) {
            $push($exam->exam_date->toDateString(), ['type' => 'exam', 'label' => $exam->name]);
        }

        foreach (Semester::query()->with('academicYear')->get() as $semester) {
            if ($semester->start_date->between($gridStart, $gridEnd)) {
                $push($semester->start_date->toDateString(), ['type' => 'semester', 'label' => __('academic_calendar.semester_starts', ['name' => $semester->displayName()])]);
            }
            if ($semester->end_date->between($gridStart, $gridEnd)) {
                $push($semester->end_date->toDateString(), ['type' => 'semester', 'label' => __('academic_calendar.semester_ends', ['name' => $semester->displayName()])]);
            }
        }

        foreach (AcademicYear::query()->get() as $year) {
            if ($year->start_date->between($gridStart, $gridEnd)) {
                $push($year->start_date->toDateString(), ['type' => 'year', 'label' => __('academic_calendar.year_starts', ['name' => $year->name])]);
            }
            if ($year->end_date->between($gridStart, $gridEnd)) {
                $push($year->end_date->toDateString(), ['type' => 'year', 'label' => __('academic_calendar.year_ends', ['name' => $year->name])]);
            }
        }

        return $events;
    }
}
