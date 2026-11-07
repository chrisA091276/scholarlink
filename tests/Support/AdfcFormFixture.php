<?php

namespace Tests\Support;

use App\Models\ScholarshipApplication;

trait AdfcFormFixture
{
    protected function adfcAnswers(array $overrides = []): array
    {
        return array_replace([
            'processing_office' => 'ADFC Scholarship Office', 'last_name' => 'Example', 'first_name' => 'Fictional',
            'middle_name' => 'Test', 'maiden_name' => 'NA', 'date_of_birth' => '2004-02-15',
            'place_of_birth' => 'Tacloban City', 'sex' => 'female', 'citizenship' => 'Filipino',
            'street_barangay' => 'Fictional Barangay', 'municipality' => 'Tacloban City', 'province' => 'Leyte',
            'zip_code' => '6500', 'mobile_number' => '09171234567', 'email' => 'fictional@example.test',
            'school_name' => 'Asian Development Foundation College', 'campus' => 'Tacloban City',
            'course' => 'BSIT', 'year_level' => '2nd', 'school_id_number' => 'FICTIONAL-2026',
            'school_address' => 'Tacloban City, Leyte', 'disability' => 'NA', 'school_sector' => 'private',
            'father_status' => 'living', 'mother_status' => 'living', 'father_name' => 'Fictional Father',
            'mother_name' => 'Fictional Mother', 'father_address' => 'Fictional address', 'mother_address' => 'Fictional address',
            'other_assistance' => 'no', 'other_assistance_details' => null, 'indigency_unavailable' => false,
            'printed_name' => 'Fictional Test Example', 'date_accomplished' => today()->toDateString(),
        ], $overrides);
    }

    protected function attachAdfcDocuments(ScholarshipApplication $application, string $income = 'indigency', string $status = 'pending'): void
    {
        foreach (['id_photo', 'enrollment', $income] as $category) {
            $application->documents()->create(['document_name' => config('adfc_form.documents.'.$category),
                'document_category' => $category, 'storage_disk' => 'local',
                'file_path' => 'documents/fixture-'.$application->id.'-'.$category.'.pdf',
                'file_type' => $category === 'id_photo' ? 'png' : 'pdf', 'file_size' => 100, 'verification_status' => $status]);
        }
    }

    protected function adfcReview(): array
    {
        return ['applicant' => true, 'school' => true, 'family' => true, 'assistance' => true, 'consent' => true,
            'qualification' => 'meets', 'notes' => 'Reviewed the supplied form and verified supporting evidence.'];
    }
}
