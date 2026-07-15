<?php

return [
    'title'            => 'Admissions',
    'new_application'  => 'New Application',
    'edit_application' => 'Edit Application',
    'application_no'   => 'Application No.',
    'applicant'        => 'Applicant',
    'guardian'         => 'Guardian',
    'guardian_phone'   => 'Guardian Phone',
    'guardian_relation'=> 'Relation',
    'desired_class'    => 'Desired Class',
    'academic_year'    => 'Academic Year',
    'status'           => 'Status',
    'notes'            => 'Notes',
    'document'         => 'Supporting Document',
    'download_document'=> 'Download Document',
    'all_statuses'     => '— All statuses —',
    'search_hint'      => 'Name or application no.',
    'no_applications'  => 'No applications found.',
    'submitted'        => 'Submitted',

    // Statuses
    'status_enquiry'      => 'Enquiry',
    'status_applied'      => 'Applied',
    'status_under_review' => 'Under Review',
    'status_accepted'     => 'Accepted',
    'status_rejected'     => 'Rejected',
    'status_converted'    => 'Converted',

    // Actions
    'mark_under_review' => 'Mark Under Review',
    'accept'            => 'Accept',
    'reject'            => 'Reject',
    'convert_to_student'=> 'Convert to Student',
    'convert_confirm'   => 'Create an enrolled student from this application?',

    // Flash messages
    'created'                  => 'Application created.',
    'updated'                  => 'Application updated.',
    'status_updated'           => 'Application status updated.',
    'converted'                => 'Converted to student :code.',
    'converted_locked'         => 'This application has been converted and can no longer be changed.',
    'only_accepted_can_convert'=> 'Only accepted applications can be converted to students.',

    // Dashboard
    'pending_admissions' => 'Pending Admissions',
    'view_all'           => 'View All Applications',

    // Admissions ↔ Students relationship clarity
    'relationship_note'   => 'Admissions tracks applicants before they\'re enrolled. Accepting and converting an application automatically creates the student\'s full record — including their ID card and enrollment certificate.',
    'ready_to_convert'    => 'Ready to convert',
    'ready_to_convert_hint' => 'This application is accepted. Converting creates the enrolled student record now — name, gender, date of birth, and address carry over automatically, and their ID card and enrollment certificate are issued at the same time.',
    'guardian_on_file'    => 'Guardian on file (from admission)',
];
