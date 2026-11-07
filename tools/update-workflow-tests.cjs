const fs=require('fs');function edit(p,f){fs.writeFileSync(p,f(fs.readFileSync(p,'utf8')));}
for(const p of ['tests/Feature/AuthTest.php','tests/Feature/ApiTest.php','tests/Feature/HardeningTest.php'])edit(p,s=>s.replaceAll("'terms_accepted' =>", "'contact_number' => '09171234567', 'terms_accepted' =>"));
edit('tests/Feature/ApiTest.php',s=>s.replaceAll("$student = User::factory()->create(['role' => 'student']);", "$student = User::factory()->create(['role' => 'student']);\n        $student->studentProfile()->create(['student_id_no' => 'S-'.$student->id, 'first_name' => 'Test', 'last_name' => 'Student', 'course' => 'BSIT', 'year_level' => '2nd', 'profile_completed' => true]);")
.replace("'action' => 'submit',", "'action' => 'submit', 'declaration' => true,")
.replace('"/api/my-applications/{$application->id}/submit")', '"/api/my-applications/{$application->id}/submit", [\'declaration\' => true])')
.replace('        Sanctum::actingAs($admin);\n\n        $this->postJson("/api/applications/{$application->id}/documents', '        $application->update([\'status\' => \'submitted\']);\n        Sanctum::actingAs($admin);\n\n        $this->postJson("/api/applications/{$application->id}/documents'));
edit('tests/Feature/HardeningTest.php',s=>s.replace("->assertJsonPath('missing_requirements.0', 'Certificate of Enrollment')", "->assertJsonValidationErrors('documents')"));
edit('tests/Feature/ScholarshipWorkflowTest.php',s=>s.replace("])->assertSessionHas('error');\n        $this->assertDatabaseMissing", "])->assertSessionHasErrors('scholarship_program_id');\n        $this->assertDatabaseMissing").replace("post('/my-applications', $payload)->assertSessionHas('error')", "post('/my-applications', $payload)->assertSessionHasErrors('scholarship_program_id')"));
for(const api of [false,true])edit('app/Http/Controllers/'+(api?'Api/':'')+'ApplicationController.php',s=>{
let start=s.indexOf('    private function changeStatus('),end=s.indexOf('\n    }',start)+6;
s=s.slice(0,start)+`    private function changeStatus(ScholarshipApplication $application, string $to, $user, ?string $remarks = null): void
    {
        app(\\App\\Services\\ApplicationTransitionService::class)->transition($application, $to, $user, $remarks);
    }`+s.slice(end);
if(api){start=s.indexOf('    public static function changeStatusStatic(');end=s.indexOf('\n    }',start)+6;s=s.slice(0,start)+`    public static function changeStatusStatic(ScholarshipApplication $application, string $to, $user, ?string $remarks = null): void
    {
        app(\\App\\Services\\ApplicationTransitionService::class)->transition($application, $to, $user, $remarks);
    }`+s.slice(end);}
return s;
});
edit('routes/api.php',s=>s.replace("Route::middleware('auth:sanctum')", "Route::middleware(['auth:sanctum', \\App\\Http\\Middleware\\EnsureActiveAccount::class])"));
