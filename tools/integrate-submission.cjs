const fs=require('fs');function edit(p,f){fs.writeFileSync(p,f(fs.readFileSync(p,'utf8')));}
edit('app/Models/ScholarshipProgram.php',s=>s.replace('    public function isOpenForApplication()', `    public function financialFields(): array
    {
        $possible = ['guardian_name', 'guardian_occupation', 'household_size', 'monthly_household_income'];
        $keys = $this->eligibilityRules()->where('is_active', true)->pluck('rule_key')->map(fn ($key) => str_replace('application.', '', $key))->all();
        return array_values(array_intersect($possible, $keys));
    }

    public function isOpenForApplication()`));
edit('app/Http/Requests/ApplicationRequest.php',s=>s.replace("'academic_year' => ['required'", "'academic_year' => ['nullable'").replace("'enrollment_status' => ['required', 'string', 'max:30']", "'enrollment_status' => ['nullable', 'in:enrolled,continuing,new,freshman,transferee,returning']").replace("'action' =>", "'last_saved_step' => ['nullable', 'integer', 'between:1,7'],\n            'declaration' => ['nullable', 'boolean'],\n            'action' =>"));
for(const api of [false,true]) edit('app/Http/Controllers/'+(api?'Api/':'')+'ApplicationController.php',s=>{
function replaceMethod(name,body){let start=s.indexOf('    public function '+name+'('),end=s.indexOf('\n    public function ',start+1);if(end<0)throw Error(name);s=s.slice(0,start)+body+'\n'+s.slice(end);}
replaceMethod('store',`    public function store(ApplicationRequest $request)
    {
        $application = app(\\App\\Services\\ApplicationDraftService::class)->save($request->user(), $request->validated());
        if ($request->action === 'submit') {
            try {
                $application = app(ApplicationSubmissionService::class)->submit($application, $request->user(), $request->boolean('declaration'));
            } catch (\\Illuminate\\Validation\\ValidationException $error) {
                ${api?`return response()->json(['message' => 'Draft saved. Complete the requirements before submitting.', 'errors' => $error->errors(), 'data' => new ScholarshipApplicationResource($application->load('program'))], 422);`:`return redirect()->route('my-applications.edit', $application)->withErrors($error->errors())->with('error', 'Draft saved. Complete the requirements before submitting.');`}
            }
        }
        ${api?`return response()->json(['message' => $application->status === 'submitted' ? 'Application submitted.' : 'Draft saved.', 'data' => new ScholarshipApplicationResource($application->load('program'))], 201);`:`return redirect()->route($application->status === 'submitted' ? 'my-applications.show' : 'my-applications.edit', $application)->with('success', $application->status === 'submitted' ? 'Application submitted! Reference: '.$application->application_no : 'Draft saved.');`}
    }`);
replaceMethod('update',`    public function update(ApplicationRequest $request, ScholarshipApplication $application)
    {
        $application = app(\\App\\Services\\ApplicationDraftService::class)->save($request->user(), $request->validated(), $application);
        if ($request->action === 'submit') $application = app(ApplicationSubmissionService::class)->submit($application, $request->user(), $request->boolean('declaration'));
        ${api?`return response()->json(['message' => $application->status === 'submitted' ? 'Application submitted.' : 'Draft updated.', 'data' => new ScholarshipApplicationResource($application->load('program'))]);`:`return redirect()->route($application->status === 'submitted' ? 'my-applications.show' : 'my-applications.edit', $application)->with('success', $application->status === 'submitted' ? 'Application submitted! Reference: '.$application->application_no : 'Draft updated.');`}
    }`);
replaceMethod(api?'submit':'submitDraft',`    public function ${api?'submit':'submitDraft'}(Request $request, ScholarshipApplication $application, ApplicationSubmissionService $submission)
    {
        $application = $submission->submit($application, $request->user(), $request->boolean('declaration'));
        ${api?`return response()->json(['message' => 'Application submitted.', 'data' => new ScholarshipApplicationResource($application->load('program'))]);`:`return redirect()->route('my-applications.show', $application)->with('success', 'Application submitted. Reference: '.$application->application_no);`}
    }`);
if(!api) s=s.replaceAll("ScholarshipProgram::where('status', 'published')->with('requirements')->orderBy('deadline')->get()", "ScholarshipProgram::where('status', 'published')->with('requirements')->orderBy('deadline')->get()->filter(fn ($program) => $program->isOpenForApplication())");
return s;
});
// Keep public password reset responses indistinguishable for registered and unknown email addresses.
edit('routes/web.php',s=>s.replace(/        return \$status === \\Illuminate\\Support\\Facades\\Password::RESET_LINK_SENT[\s\S]*?: back\(\)->withErrors\(\['email' => __\(\$status\)\]\);/, "        return back()->with('success', 'If this account exists, a reset link has been requested. Delivery depends on the configured mail service.');")
.replace("fn ($user) => $user->forceFill(['password' => \\Illuminate\\Support\\Facades\\Hash::make($request->password)])->save()", "function ($user) use ($request) { $user->forceFill(['password' => \\Illuminate\\Support\\Facades\\Hash::make($request->password), 'remember_token' => \\Illuminate\\Support\\Str::random(60)])->save(); $user->tokens()->delete(); \\Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id', $user->id)->delete(); }") );
edit('resources/views/applications/my-show.blade.php',s=>s.replace("['draft','incomplete_requirements','submitted']", "['draft','incomplete_requirements']").replace('@csrf<button class="btn btn-gold">Submit for evaluation</button>', '@csrf<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="declaration" value="1" id="submitDeclaration" required><label class="form-check-label" for="submitDeclaration">I confirm that my application is accurate and accept the <a href="{{ route(\'privacy\') }}">privacy notice</a>.</label></div><button class="btn btn-gold">Submit for evaluation</button>'));
edit('resources/views/profile/edit.blade.php',s=>s.replace(/<section class="student-form-section"><h2>Family and financial information<\/h2>[\s\S]*?<\/section>/, '<section class="student-form-section"><h2>Family and financial information</h2><p>For your privacy, provide financial details only in an application when the chosen scholarship requires them.</p></section>'));
edit('app/Http/Requests/StudentProfileRequest.php',s=>s.replace(/            '(parent_guardian_name|parent_guardian_occupation|household_size|monthly_household_income)' => .*\n/g, ''));
