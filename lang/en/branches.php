<?php

return [
    'title'             => 'Branches',
    'new_branch'        => 'New Branch',
    'edit_branch'       => 'Edit Branch',
    'name_en'           => 'Name (English)',
    'name_km'           => 'Name (Khmer)',
    'address'           => 'Address',
    'code'              => 'Code',
    'code_hint'         => 'Short prefix used in invoice and student numbering, e.g. MC. Letters, numbers, dashes only.',
    'logo'              => 'Logo',
    'current_logo'      => 'Current logo',
    'status'            => 'Status',
    'status_active'     => 'Active',
    'status_suspended'  => 'Suspended',
    'students_count'    => 'Students',
    'staff_count'       => 'Staff',
    'manage_admins'     => 'Manage Admins',
    'suspend'           => 'Suspend',
    'suspend_confirm'   => 'Suspend this branch? Its users will be blocked from logging in until reactivated. No data is deleted.',
    'reactivate'        => 'Reactivate',

    // Admins
    'admins_of'         => 'Admins of :name',
    'no_admins'         => 'This branch has no admin yet.',
    'appoint_admin'      => 'Appoint Admin',
    'appoint_existing'  => 'Appoint an existing user',
    'appoint_existing_hint' => 'Enter the email of an existing user — they will be granted the admin role for this branch (moving them out of any other branch).',
    'or_create_new'     => 'Or create a new admin',
    'remove_admin'      => 'Remove',
    'remove_admin_confirm' => 'Remove admin access for :name? Their account is not deleted.',
    'user_not_found'    => 'No user found with that email.',

    // Flash messages
    'created'           => 'Branch created.',
    'updated'           => 'Branch updated.',
    'suspended'         => ':name suspended.',
    'reactivated'       => ':name reactivated.',
    'admin_appointed'   => ':name is now an admin of this branch.',
    'admin_removed'     => 'Admin access removed for :name.',

    // Suspension block page
    'suspended_title'   => 'Branch Suspended',
    'suspended_message' => 'Access for :branch has been temporarily suspended. Please contact the school owner if you believe this is a mistake. No data has been lost.',
];
