<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>ScholarLink Report — {{ $tab ?? 'applications' }}</title>
<style>
body{font-family:DejaVu Sans, sans-serif;font-size:10px;color:#222}
h2{margin:0} .sub{color:#555;margin:2px 0 12px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #999;padding:4px 6px;text-align:left}
th{background:#0a1931;color:#fff}
.summary{margin:0 0 12px} .summary span{display:inline-block;background:#eee;border:1px solid #999;padding:3px 10px;margin-right:6px}
.footer{margin-top:14px;color:#555;font-size:9px}
</style></head><body>
<h2>Asian Development Foundation College</h2>
<div class="sub">ScholarLink: Student Scholarship Management System — {{ ucfirst($tab ?? 'applications') }} Report · Generated {{ $generated }}</div>
@if(($tab ?? '')==='applications' && isset($summary))
<div class="summary">
<span>Total: {{ $summary['total'] }}</span><span>Approved: {{ $summary['approved'] }}</span><span>Rejected: {{ $summary['rejected'] }}</span><span>Pending: {{ $summary['pending'] }}</span>
</div>
<table><thead><tr><th>No</th><th>Student</th><th>Email</th><th>Program</th><th>Year</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
@forelse($rows as $a)<tr>
<td>{{ $a->application_no }}</td><td>{{ $a->student?->name }}</td><td>{{ $a->student?->email }}</td>
<td>{{ $a->program?->name }}</td><td>{{ $a->academic_year }}</td><td>{{ $a->status }}</td>
<td>{{ $a->submitted_at?->format('Y-m-d') ?? '—' }}</td></tr>
@empty<tr><td colspan="7">No records.</td></tr>@endforelse
</tbody></table>
@elseif(($tab ?? '')==='students')
<table><thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th></tr></thead><tbody>
@forelse($rows as $p)<tr><td>{{ $p->student_id_no }}</td><td>{{ $p->full_name }}</td><td>{{ $p->user?->email }}</td><td>{{ $p->course }}</td><td>{{ $p->year_level }}</td></tr>
@empty<tr><td colspan="5">No records.</td></tr>@endforelse
</tbody></table>
@elseif(($tab ?? '')==='programs')
<table><thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Slots</th><th>Applications</th><th>Awards</th></tr></thead><tbody>
@forelse($rows as $p)<tr><td>{{ $p->code }}</td><td>{{ $p->name }}</td><td>{{ $p->status }}</td><td>{{ $p->slots }}</td><td>{{ $p->applications_count }}</td><td>{{ $p->awards_count }}</td></tr>
@empty<tr><td colspan="6">No records.</td></tr>@endforelse
</tbody></table>
@else
<table><thead><tr><th>Application</th><th>Student</th><th>Program</th><th>Result</th><th>Checked</th></tr></thead><tbody>
@forelse($rows as $er)<tr><td>{{ $er->application?->application_no }}</td><td>{{ $er->application?->student?->name }}</td><td>{{ $er->application?->program?->name }}</td><td>{{ $er->result }}</td><td>{{ $er->checked_at?->format('Y-m-d') }}</td></tr>
@empty<tr><td colspan="5">No records.</td></tr>@endforelse
</tbody></table>
@endif
<div class="footer">Official system-generated report. For academic use only.</div>
</body></html>
