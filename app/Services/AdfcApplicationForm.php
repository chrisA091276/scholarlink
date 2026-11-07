<?php

namespace App\Services;

use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdfcApplicationForm
{
    public const VERSION = 'adfc-professional-2026';

    public function sections(): array { return config('adfc_form.sections'); }

    /** Render older applications in the reference layout without changing their stored answers. */
    public function pdfAnswers(ScholarshipApplication $application): array
    {
        if ($application->form_version === self::VERSION) return $application->adfc_form ?? [];

        $profile = $application->student?->studentProfile;

        return array_replace([
            'first_name' => $profile?->first_name,
            'middle_name' => $profile?->middle_name,
            'last_name' => $profile?->last_name,
            'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
            'mobile_number' => $profile?->contact_number,
            'email' => $application->student?->email,
            'course' => $profile?->course,
            'year_level' => $profile?->year_level,
            'school_id_number' => $profile?->student_id_no,
            'printed_name' => $profile?->full_name ?: $application->student?->name,
        ], $application->adfc_form ?? []);
    }

    public function defaults(User $user): array
    {
        $profile = $user->studentProfile;
        return [
            'processing_office' => 'ADFC Scholarship Office',
            'first_name' => $profile?->first_name, 'middle_name' => $profile?->middle_name,
            'last_name' => $profile?->last_name, 'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
            'mobile_number' => $profile?->contact_number, 'email' => $user->email,
            'school_name' => 'Asian Development Foundation College', 'campus' => 'Tacloban City',
            'course' => $profile?->course, 'year_level' => $profile?->year_level,
            'school_id_number' => $profile?->student_id_no,
            'printed_name' => $profile?->full_name ?: $user->name,
        ];
    }

    public function validate(array $input, bool $submitting = false): array
    {
        $rules = ['adfc_form.processing_office' => [$submitting ? 'required' : 'nullable', 'string', 'max:200'],
            'adfc_form.indigency_unavailable' => ['nullable', 'boolean']];
        $attributes = ['adfc_form.processing_office' => 'regional / processing office'];
        $keys = ['processing_office', 'indigency_unavailable'];
        foreach ($this->sections() as $section) {
            foreach ($section['fields'] as $key => $field) {
                $keys[] = $key;
                $required = $field['required'] ?? false;
                if ($key === 'other_assistance_details') $required = ($input['other_assistance'] ?? null) === 'yes';
                $rule = [$submitting && $required ? 'required' : 'nullable', 'string', 'max:500'];
                if (isset($field['options'])) $rule[] = Rule::in(array_keys($field['options']));
                if (($field['type'] ?? '') === 'date') $rule = [$submitting && $required ? 'required' : 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'];
                if ($key === 'email') $rule[] = 'email:rfc';
                if ($key === 'mobile_number') $rule[] = 'regex:/^(?:\+63|0)9\d{9}$/';
                if ($key === 'zip_code') $rule[] = 'regex:/^[0-9]{4}$/';
                $rules['adfc_form.'.$key] = $rule;
                $attributes['adfc_form.'.$key] = $field['label'];
            }
        }
        $data = Arr::only($input, $keys);
        if (($data['other_assistance'] ?? null) === 'no') $data['other_assistance_details'] = null;
        return Validator::make(['adfc_form' => $data], $rules, [], $attributes)->validate()['adfc_form'] ?? [];
    }

    public function progress(array $data): array
    {
        $sections = [];
        foreach ($this->sections() as $key => $section) {
            $required = collect($section['fields'])->filter(fn ($field) => $field['required'] ?? false)->keys();
            if ($key === 'assistance' && ($data['other_assistance'] ?? null) === 'yes') $required->push('other_assistance_details');
            $filled = $required->filter(fn ($field) => filled($data[$field] ?? null))->count();
            $sections[$key] = ['title' => $section['title'], 'icon' => $section['icon'], 'filled' => $filled,
                'total' => $required->count(), 'complete' => $filled === $required->count()];
        }
        return $sections;
    }

    public function missingDocuments(ScholarshipApplication $application, bool $verified = false): array
    {
        if ($application->form_version !== self::VERSION) return [];
        $application->loadMissing('documents');
        $categories = $application->documents->whereIn('verification_status', $verified ? ['verified'] : ['pending', 'verified'])->pluck('document_category')->all();
        $missing = [];
        foreach (['id_photo', 'enrollment'] as $category) {
            if (! in_array($category, $categories, true)) $missing[] = config('adfc_form.documents.'.$category);
        }
        $income = ! empty($application->adfc_form['indigency_unavailable'])
            ? ['indigency', 'tax_exemption', 'social_case_study'] : ['indigency'];
        if (! array_intersect($income, $categories)) $missing[] = count($income) > 1
            ? 'Certificate of Indigency, Tax Exemption, or Social Case Study' : 'Certificate of Indigency';
        return $missing;
    }

    /** The same evidence groups used by submission, displayed as an actionable checklist. */
    public function documentChecklist(?ScholarshipApplication $application): array
    {
        $application?->loadMissing(['documents', 'program.requirements']);
        $documents = $application?->documents ?? collect();
        $rows = [];
        if (! $application || $application->form_version === self::VERSION) {
            $alternative = ! empty($application?->adfc_form['indigency_unavailable']);
            $groups = [
                ['label' => '2 × 2 ID photo', 'categories' => ['id_photo'], 'icon' => 'bi-person-bounding-box'],
                ['label' => 'COR / COE', 'categories' => ['enrollment'], 'icon' => 'bi-file-earmark-text'],
                ['label' => $alternative ? 'Indigency or permitted alternative' : 'Certificate of Indigency',
                    'categories' => $alternative ? ['indigency', 'tax_exemption', 'social_case_study'] : ['indigency'],
                    'icon' => 'bi-file-earmark-check'],
            ];
            foreach ($groups as $group) {
                $rows[] = $this->documentChecklistRow($group['label'], $group['icon'], $documents->whereIn('document_category', $group['categories']));
            }
        }
        foreach ($application?->program?->requirements ?? [] as $requirement) {
            if ($requirement->is_required) {
                $rows[] = $this->documentChecklistRow($requirement->name, 'bi-file-earmark-plus', $documents->where('scholarship_requirement_id', $requirement->id));
            }
        }

        return $rows;
    }

    private function documentChecklistRow(string $label, string $icon, $documents): array
    {
        $status = collect(['verified', 'pending', 'requires_correction', 'rejected'])
            ->first(fn ($status) => $documents->contains('verification_status', $status)) ?? 'missing';

        return ['label' => $label, 'icon' => $icon, 'status' => $status, 'status_label' => match ($status) {
            'verified' => 'Verified', 'pending' => 'Awaiting verification',
            'requires_correction', 'rejected' => 'Needs replacement', default => 'Not uploaded',
        }];
    }

    public function reviewed(ScholarshipApplication $application): bool
    {
        if ($application->form_version !== self::VERSION) return true;
        $review = $application->form_review ?? [];
        return collect(['applicant', 'school', 'family', 'assistance', 'consent'])->every(fn ($key) => ! empty($review[$key]))
            && in_array($review['qualification'] ?? null, ['meets', 'not_qualified'], true);
    }

    public function display(string $key, mixed $value): string
    {
        foreach ($this->sections() as $section) {
            if (isset($section['fields'][$key]['options'][$value ?? ''])) return $section['fields'][$key]['options'][$value];
        }
        if (blank($value)) return 'Not provided';
        if (in_array($key, ['date_of_birth', 'date_accomplished'], true)) return \Carbon\Carbon::parse($value)->format('d/m/Y');
        return (string) $value;
    }

    public function photoData(ScholarshipApplication $application): ?string
    {
        $photo = $application->documents->where('document_category', 'id_photo')->whereIn('verification_status', ['pending','verified'])->first();
        if (! $photo || $photo->storage_disk !== 'local' || ! in_array($photo->file_type, ['jpg','jpeg','png'])) return null;
        $path = storage_path('app/private/'.$photo->file_path);
        if (! is_file($path)) return null;
        $bytes = file_get_contents($path);
        // Dompdf needs GD to process transparent PNGs. Its fallback box keeps PDF export available.
        if ($photo->file_type === 'png' && ! extension_loaded('gd') && ! extension_loaded('imagick')) return null;
        return 'data:image/'.($photo->file_type === 'png' ? 'png' : 'jpeg').';base64,'.base64_encode($bytes);
    }
}
