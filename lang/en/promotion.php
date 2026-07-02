<?php

return [
    'title'              => 'Year-End Promotion & Rollover',
    'subtitle'           => 'Advance students into a new academic year based on annual results.',

    'step1_heading'      => 'Step 1 — Select Academic Years',
    'from_year'          => 'Promote FROM Year',
    'to_year'            => 'Promote TO Year',
    'to_year_hint'       => 'The target year must already exist. Create it under Academic Years first.',
    'preview_btn'        => 'Preview Outcomes',

    'step2_heading'      => 'Step 2 — Review & Override',
    'step2_subtitle'     => 'Review each student\'s default outcome. Override individual students if needed, then execute.',
    'activate_new_year'  => 'Set ":year" as the active academic year after rollover',

    'outcome_promote'    => 'Promote',
    'outcome_retain'     => 'Retain',
    'outcome_graduate'   => 'Graduate',
    'outcome_withdraw'   => 'Withdraw',
    'outcome_skipped'    => 'Already enrolled',

    'reason_pass'        => 'Pass',
    'reason_fail'        => 'Fail',
    'reason_no_result'   => 'No annual result (retained by default)',
    'reason_final_grade' => 'Final grade — no next class',
    'reason_override'    => 'Admin override',

    'summary_promote'    => ':n to promote',
    'summary_retain'     => ':n to retain',
    'summary_graduate'   => ':n to graduate',
    'summary_withdraw'   => ':n to withdraw',
    'summary_skipped'    => ':n already enrolled (will skip)',
    'summary_errors'     => ':n cannot be promoted (next class has no sections)',

    'col_student'        => 'Student',
    'col_code'           => 'Code',
    'col_current_class'  => 'Current Class / Section',
    'col_result'         => 'Annual Result',
    'col_outcome'        => 'Outcome',
    'col_override'       => 'Override',

    'errors_warning'     => ':n student(s) cannot be promoted — their next class has no sections. Create sections for those classes first, then re-run the preview.',

    'execute_btn'        => 'Execute Promotion',
    'executing_warning'  => 'This will write new enrollments. Ensure you have reviewed the preview above.',

    'step3_heading'      => 'Promotion Complete',
    'result_promoted'    => ':n student(s) promoted to next grade',
    'result_retained'    => ':n student(s) retained in same grade',
    'result_graduated'   => ':n student(s) graduated',
    'result_withdrawn'   => ':n student(s) withdrawn',
    'result_skipped'     => ':n student(s) skipped (already enrolled in target year)',
    'result_errors'      => ':n student(s) could not be processed (next class missing sections)',
    'year_activated'     => '":year" is now the active academic year.',

    'next_class'         => 'Promotes to (Next Class)',
    'next_class_none'    => '— None (Final Grade / Graduate) —',

    'no_students'        => 'No students are enrolled in the selected year.',
    'same_year_error'    => 'From-year and to-year must be different.',
];
