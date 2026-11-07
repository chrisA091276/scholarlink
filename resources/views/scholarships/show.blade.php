@extends('layouts.app')
@section('title','Manage '.$scholarship->name)
@section('page-title','Manage: '.$scholarship->name)
@section('content')
<div class="row g-3">
<div class="col-lg-7">
<div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between"><div><span class="badge bg-primary">{{ $scholarship->category }}</span> <span class="badge bg-secondary">{{ $scholarship->status }}</span> <span class="badge bg-dark">{{ $scholarship->code }}</span></div>
<div class="d-flex gap-1"><a href="{{ route('scholarships.edit',$scholarship) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
<form method="POST" action="{{ route('scholarships.destroy',$scholarship) }}" onsubmit="return confirm('Delete/archive this program?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></div></div>
<p class="mt-2">{{ $scholarship->description }}</p>
<div class="small"><b>Benefits:</b> {{ $scholarship->benefits ?? '—' }}</div>
<div class="small"><b>Opens:</b> {{ $scholarship->opening_date?->format('M d, Y') ?? '—' }} · <b>Deadline:</b> {{ $scholarship->deadline?->format('M d, Y') ?? '—' }} · <b>Slots:</b> {{ $scholarship->slots ?? '—' }}</div>
</div></div>
<div class="card mb-3"><div class="card-body">
<h6 class="fw-bold">Required documents ({{ $scholarship->requirements->count() }})</h6>
<div class="table-scroll-region" role="region" aria-label="Required documents" tabindex="0">
<table class="table table-sm"><thead><tr><th>Name</th><th>Required</th><th></th></tr></thead><tbody>
@forelse($scholarship->requirements as $r)<tr><td>{{ $r->name }}<div class="small text-muted">{{ $r->description }}</div></td><td>@if($r->is_required)<span class="badge bg-danger">yes</span>@else<span class="badge bg-secondary">optional</span>@endif</td>
<td><form method="POST" action="{{ route('scholarships.requirements.destroy',[$scholarship,$r->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')">×</button></form></td></tr>
@empty<tr><td colspan="3" class="text-muted small">No requirements yet.</td></tr>@endforelse
</tbody></table></div>
<form method="POST" action="{{ route('scholarships.requirements.store',$scholarship) }}" class="row g-2">@csrf
<div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="e.g. Report Card" required></div>
<div class="col-md-5"><input name="description" class="form-control form-control-sm" placeholder="Description (optional)"></div>
<div class="col-md-3 d-flex gap-2"><div class="form-check mt-1"><input type="checkbox" name="is_required" value="1" checked class="form-check-input"><label class="form-check-label small">Required</label></div><button class="btn btn-sm btn-dark">Add</button></div>
</form>
</div></div>
<div class="card"><div class="card-body">
<h6 class="fw-bold">Eligibility rules</h6>
<div class="table-scroll-region" role="region" aria-label="Eligibility rules" tabindex="0">
<table class="table table-sm"><thead><tr><th>Rule</th><th>Condition</th><th>Failure behavior</th><th></th></tr></thead><tbody>
@forelse($scholarship->eligibilityRules as $rule)<tr><td><b>{{ $rule->rule_label }}</b><div class="small text-muted">{{ $rule->rule_key }} · {{ $rule->rule_type }} · {{ $rule->is_active ? 'active' : 'off' }}</div></td><td><code>{{ $rule->operator }} {{ $rule->rule_value }}</code></td><td class="small">{{ $rule->failure_mode === 'mandatory' ? 'Disqualifying' : 'Manual review' }}</td>
<td><form method="POST" action="{{ route('scholarships.rules.destroy',[$scholarship,$rule->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')">×</button></form></td></tr>
@empty<tr><td colspan="3" class="text-muted small">No rules yet — screening will require manual review.</td></tr>@endforelse
</tbody></table></div>
<form method="POST" action="{{ route('scholarships.rules.store',$scholarship) }}" class="row g-2">@csrf
<div class="col-md-4"><label class="form-label small mb-1">Field</label><input name="rule_key" list="eligibilityFields" class="form-control form-control-sm" placeholder="e.g. form_data.residency" required><datalist id="eligibilityFields"><option value="enrollment_status">Enrollment status</option><option value="course">Course</option><option value="year_level">Year level</option><option value="monthly_household_income">Monthly income</option><option value="household_size">Household size</option><option value="documents.complete">All required documents verified</option><option value="documents.submitted_count">Required documents uploaded</option><option value="profile.date_of_birth">Date of birth</option><option value="profile.department">Department</option><option value="profile.address">Address</option><option value="form_data.community_service_hours">Custom application field</option></datalist></div>
<div class="col-md-3"><label class="form-label small mb-1">Label</label><input name="rule_label" class="form-control form-control-sm" placeholder="Human-readable rule" required></div>
<div class="col-md-2"><label class="form-label small mb-1">Type</label><select name="rule_type" class="form-select form-select-sm"><option value="text">Text</option><option value="number">Number</option><option value="date">Date</option><option value="boolean">Yes / no</option><option value="select">Choice</option></select></div>
<div class="col-md-3"><label class="form-label small mb-1">Operator</label><select name="operator" class="form-select form-select-sm"><option value="=">equals</option><option value="!=">does not equal</option><option value=">=">at least (≥)</option><option value="<=">at most (≤)</option><option value=">">greater than</option><option value="<">less than</option><option value="in">is one of</option><option value="not_in">is not one of</option><option value="between">between</option><option value="contains">contains</option><option value="not_contains">does not contain</option><option value="exists">is provided</option><option value="not_exists">is not provided</option></select></div>
<div class="col-md-5"><label class="form-label small mb-1">Expected value</label><input name="rule_value" class="form-control form-control-sm" placeholder="e.g. 25000, Manila,Quezon City, or true"></div>
<div class="col-md-3"><label class="form-label small mb-1">If it fails</label><select name="failure_mode" class="form-select form-select-sm"><option value="mandatory">Mark not eligible</option><option value="manual_review">Send to manual review</option></select></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-dark w-100">Add rule</button></div>
</form>
<div class="form-text small">Examples: enrollment_status = enrolled,continuing · course = BSIT,BSCS (or "all") · year_level = 1st,2nd (or "all").</div>
</div></div>
</div>
<div class="col-lg-5">
<div class="card"><div class="card-body">
<h6 class="fw-bold">Recent applications ({{ $scholarship->applications->count() }})</h6>
<div class="table-scroll-region" role="region" aria-label="Recent applications" tabindex="0">
<table class="table table-sm"><thead><tr><th>No</th><th>Student</th><th>Status</th></tr></thead><tbody>
@forelse($scholarship->applications->take(15) as $a)<tr><td><a href="{{ route('applications.show',$a) }}">{{ $a->application_no }}</a></td><td class="small">{{ $a->student?->name }}</td><td><span class="badge bg-info">{{ str_replace('_',' ',$a->status) }}</span></td></tr>
@empty<tr><td colspan="3" class="text-muted small">No applications yet.</td></tr>@endforelse
</tbody></table></div>
</div></div>
</div>
</div>
@endsection
