<?php

return [
    'title' => 'Role & Interface Guide',
    'subtitle' => 'What each role can see and do in the system, generated from the real permission grants — not reconstructed from memory.',
    'features_visible' => 'features visible',
    'always_visible' => 'Always visible',
    'scoped_label' => 'Scoped to own records',
    'full_label' => 'Same access as every other holder',
    'footer_note' => 'Owner and Admin hold every permission and are omitted from the chips below — assume both on every row.',

    'role_names' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'principal' => 'Principal',
        'teacher' => 'Teacher',
        'accountant' => 'Accountant',
        'librarian' => 'Librarian',
        'receptionist' => 'Receptionist',
        'student' => 'Student',
        'parent' => 'Parent',
    ],

    'role_blurbs' => [
        'owner' => 'Every permission, everywhere — plus branch management and the branch switcher.',
        'admin' => 'Every permission, everywhere. The same access as Owner except for branch-level tools.',
        'principal' => 'School leadership — broad read access and most management consoles, but not user accounts, timetables, or entering exam marks directly.',
        'teacher' => 'Classroom-facing. Every student- and section-scoped feature is restricted to their own homeroom plus any class they teach a subject in.',
        'accountant' => 'Finance-focused — invoices, fees, payments, and finance reports. No academic or attendance consoles.',
        'librarian' => 'Library-focused — full catalog and checkout management. Does not hold Messages.',
        'receptionist' => 'Front-desk — admissions, gate scan, visitor log, and transport. Can create and edit students but not view deeper academic records.',
        'student' => 'Self-service portal — own attendance, own invoices, own transcript. No staff console ever appears.',
        'parent' => 'Self-service portal for their own children only, IDOR-guarded on every child-scoped action.',
    ],
];
