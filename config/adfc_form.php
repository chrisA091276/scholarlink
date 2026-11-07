<?php

// Field order follows docs/reference/ADFC_Form_Professional.docx.
return [
    'sections' => [
        'applicant' => ['title' => 'Applicant information', 'subtitle' => 'Personal details', 'icon' => 'bi-person-vcard', 'fields' => [
            'last_name' => ['label' => 'Last name', 'required' => true],
            'first_name' => ['label' => 'First name', 'required' => true],
            'middle_name' => ['label' => 'Middle name'],
            'maiden_name' => ['label' => 'Maiden name (for married women)'],
            'date_of_birth' => ['label' => 'Date of birth', 'type' => 'date', 'required' => true],
            'place_of_birth' => ['label' => 'Place of birth', 'required' => true],
            'sex' => ['label' => 'Sex', 'options' => ['male' => 'Male', 'female' => 'Female'], 'required' => true],
            'citizenship' => ['label' => 'Citizenship', 'required' => true],
            'street_barangay' => ['label' => 'Street / barangay', 'required' => true],
            'municipality' => ['label' => 'Town / city / municipality', 'required' => true],
            'province' => ['label' => 'Province', 'required' => true],
            'zip_code' => ['label' => 'ZIP code', 'required' => true],
            'mobile_number' => ['label' => 'Mobile number', 'type' => 'tel', 'required' => true],
            'email' => ['label' => 'Email address', 'type' => 'email', 'required' => true],
        ]],
        'school' => ['title' => 'School information', 'subtitle' => 'Current enrollment details', 'icon' => 'bi-mortarboard', 'fields' => [
            'school_name' => ['label' => 'Name of school attended', 'required' => true],
            'campus' => ['label' => 'Campus', 'required' => true],
            'course' => ['label' => 'Course', 'required' => true],
            'year_level' => ['label' => 'Year level', 'required' => true],
            'school_id_number' => ['label' => 'School ID number', 'required' => true],
            'school_address' => ['label' => 'School address', 'required' => true],
            'disability' => ['label' => 'Type of disability (if applicable)'],
            'school_sector' => ['label' => 'School sector', 'options' => ['public' => 'Public', 'private' => 'Private'], 'required' => true],
        ]],
        'family' => ['title' => 'Family background', 'subtitle' => 'Parent / guardian details', 'icon' => 'bi-people', 'fields' => [
            'father_status' => ['label' => 'Father', 'options' => ['living' => 'Living', 'deceased' => 'Deceased', 'na' => 'Not applicable / unknown'], 'required' => true],
            'mother_status' => ['label' => 'Mother', 'options' => ['living' => 'Living', 'deceased' => 'Deceased', 'na' => 'Not applicable / unknown'], 'required' => true],
            'father_name' => ['label' => 'Father — name', 'required' => true],
            'mother_name' => ['label' => 'Mother — name', 'required' => true],
            'father_address' => ['label' => 'Father — address', 'required' => true],
            'mother_address' => ['label' => 'Mother — address', 'required' => true],
        ]],
        'assistance' => ['title' => 'Other educational financial assistance', 'subtitle' => 'Funding assistance declaration', 'icon' => 'bi-cash-coin', 'fields' => [
            'other_assistance' => ['label' => 'Are you currently enjoying other educational financial assistance?', 'options' => ['yes' => 'Yes', 'no' => 'No'], 'required' => true, 'wide' => true],
            'other_assistance_details' => ['label' => 'If yes, specify the program / assistance', 'wide' => true],
        ]],
        'consent' => ['title' => 'Consent and certification', 'subtitle' => 'Applicant declaration', 'icon' => 'bi-pen', 'fields' => [
            'printed_name' => ['label' => 'Printed name of applicant', 'required' => true],
            'date_accomplished' => ['label' => 'Date accomplished', 'type' => 'date', 'required' => true],
        ]],
    ],
    'documents' => [
        'id_photo' => '2 × 2 ID photo',
        'enrollment' => 'Certificate of Registration / Enrollment (COR / COE)',
        'indigency' => 'Certificate of Indigency',
        'tax_exemption' => 'Certificate of Tax Exemption (alternative)',
        'social_case_study' => 'Social Case Study (alternative)',
        'other' => 'Other supporting document',
    ],
];
