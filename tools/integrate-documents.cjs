const fs=require('fs');
function edit(p,f){let s=fs.readFileSync(p,'utf8');fs.writeFileSync(p,f(s));}
for(const api of [false,true]){
const p='app/Http/Controllers/'+(api?'Api/':'')+'DocumentController.php';
edit(p,s=>{
let start=s.indexOf('        abort_unless(',s.indexOf('    public function store('));
let end=s.indexOf(api?'        return response()->json([':"        return back()->with('success', 'Document uploaded.');",start);
s=s.slice(0,start)+`        $${api?'document':'doc'} = app(\\App\\Services\\DocumentWorkflowService::class)->upload($request, $application);\n\n`+s.slice(end);
start=s.indexOf('        abort_unless(',s.indexOf('    public function destroy('));
end=s.indexOf(api?'        return response()->json([':"        return back()->with('success', 'Document removed.');",start);
s=s.slice(0,start)+'        app(\\App\\Services\\DocumentWorkflowService::class)->remove($application, $document);\n\n'+s.slice(end);
start=s.indexOf("        abort_unless(Storage::disk('local')->exists",s.indexOf('    public function download('));
end=s.indexOf('\n    public function verify(',start);
s=s.slice(0,start)+'        return app(\\App\\Services\\PrivateDocumentStorage::class)->download($document);\n    }\n'+s.slice(end);
s=s.replace("'verification_notes' => 'nullable|string|max:2000'", "'verification_notes' => 'required_if:verification_status,rejected,requires_correction|nullable|string|max:2000'")
.replace("'verification_notes' => ['nullable', 'string', 'max:2000']", "'verification_notes' => ['required_if:verification_status,rejected,requires_correction', 'nullable', 'string', 'max:2000']");
s=s.replace('        $document->update([', `        abort_unless(in_array($application->status, ['submitted', 'under_review', 'incomplete_requirements', 'for_evaluation'], true), 403, 'This application is not open for document review.');
        \\Illuminate\\Support\\Facades\\DB::transaction(function () use ($document, $data, $request) {
        $document->reviews()->create([
            'reviewed_by' => $request->user()->id,
            'status' => $data['verification_status'],
            'remarks' => $data['verification_notes'] ?? null,
        ]);
        $document->update([`);
s=s.replace("            'verified_at' => now(),\n        ]);", "            'verified_at' => now(),\n        ]);\n        });");
return s;
});
}
edit('app/Http/Requests/DocumentUploadRequest.php', s=>s.replace("'max:5120'", "'max:'.config('scholarlink.max_upload_kb', 5120)"));
edit('routes/web.php',s=>s+`
Route::middleware(['auth', \\App\\Http\\Middleware\\EnsureActiveAccount::class])->group(function () {
    Route::get('/email/verify', [\\App\\Http\\Controllers\\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\\App\\Http\\Controllers\\EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [\\App\\Http\\Controllers\\EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
});
Route::view('/privacy', 'privacy')->name('privacy');
`);
edit('resources/views/auth/account.blade.php',s=>s.replace('Contact number</label>', 'Contact number *</label>').replace('placeholder="09171234567" inputmode="tel"', 'placeholder="09171234567" inputmode="tel" required').replace('I accept the privacy notice and terms.', 'I accept the <a href="{{ route(\'privacy\') }}" target="_blank" rel="noopener">privacy notice and terms</a>.'));
edit('resources/views/layouts/app.blade.php',s=>s.replace("@yield('content')", `@if(config('scholarlink.verify_email') && auth()->user()->isStudent() && !auth()->user()->hasVerifiedEmail())
<div class="alert alert-warning">Verify your email before submitting an application. <a href="{{ route('verification.notice') }}">Verify email</a></div>
@endif
@yield('content')`));
