const fs = require('fs');
function edit(path, fn) { const old = fs.readFileSync(path, 'utf8'); const next = fn(old); if (next === old) throw Error('No change: '+path); fs.writeFileSync(path, next); }
edit('app/Http/Requests/RegisterRequest.php', s => s.replace('class RegisterRequest extends FormRequest\n{', 'class RegisterRequest extends FormRequest\n{\n    use \\App\\Concerns\\NormalizesContactDetails;').replace("'contact_number' => ['nullable'", "'contact_number' => ['required'").replace("'terms_accepted' =>", "'device_name' => ['nullable', 'string', 'max:100'],\n            'terms_accepted' =>"));
edit('app/Http/Requests/StudentProfileRequest.php', s => s.replace('class StudentProfileRequest extends FormRequest\n{', 'class StudentProfileRequest extends FormRequest\n{\n    use \\App\\Concerns\\NormalizesContactDetails;'));
edit('app/Http/Controllers/AuthController.php', s => {
  const start = s.indexOf('        $data = $request->validated();', s.indexOf('public function register'));
  const end = s.indexOf('        Auth::login($user);', start);
  return s.slice(0,start) + '        $user = app(\\App\\Services\\RegistrationService::class)->register($request->validated());\n' + s.slice(end);
});
edit('app/Http/Controllers/Api/AuthController.php', s => {
  const start = s.indexOf('    public function register('), end = s.indexOf('        $token = $user->createToken', start);
  return s.slice(0,start) + '    public function register(\\App\\Http\\Requests\\RegisterRequest $request)\n    {\n        $data = $request->validated();\n        $user = app(\\App\\Services\\RegistrationService::class)->register($data);\n\n' + s.slice(end).replace("            'delivery_status' => $status === Password::RESET_LINK_SENT ? 'queued' : 'not_sent',\n", '');
});
edit('app/Models/User.php', s => s.replace('class User extends Authenticatable', 'class User extends Authenticatable implements \\Illuminate\\Contracts\\Auth\\MustVerifyEmail').replace('    public const ROLES', `    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \\App\\Notifications\\VerifyStudentEmail);
    }

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if ($user->isDirty('email')) $user->email_verified_at = null;
        });
    }

    public const ROLES`));
edit('app/Services/NotifyService.php', s => s.replace('return AppNotification::create([', '$notification = AppNotification::create([').replace("        ]);\n", "        ]);\n        if (config('scholarlink.email_notifications') && ! in_array(config('mail.default'), ['log', 'array'], true)) {\n            \\App\\Jobs\\SendScholarshipEmail::dispatch($notification->id);\n        }\n        return $notification;\n"));
edit('app/Models/ApplicationDocument.php', s=>s.replace('use HasFactory;', 'use HasFactory, \\Illuminate\\Database\\Eloquent\\SoftDeletes;').replace("'document_name', 'file_path'", "'storage_disk', 'document_name', 'file_path'").replace('    public function application()', '    public function reviews() { return $this->hasMany(DocumentReview::class); }\n\n    public function application()'));
edit('app/Models/ScholarshipApplication.php', s=>s.replace("'form_data',", "'form_data', 'last_saved_step', 'declaration_accepted_at',").replace("'submitted_at' => 'datetime',", "'submitted_at' => 'datetime',\n        'declaration_accepted_at' => 'datetime',").replace("strtoupper(substr(uniqid(), -6))", "strtoupper(bin2hex(random_bytes(8)))"));
edit('app/Models/ApplicationStatusHistory.php', s=>s.replace('    protected $fillable', `    protected static function booted(): void
    {
        static::updating(fn () => throw new \\LogicException('Status history is append-only.'));
        static::deleting(fn () => throw new \\LogicException('Status history is append-only.'));
    }

    protected $fillable`));
edit('routes/web.php', s=>s.replace("Route::middleware('auth')->group", "Route::middleware(['auth', \\App\\Http\\Middleware\\EnsureActiveAccount::class])->group"));
edit('app/Http/Middleware/RoleMiddleware.php', s=>s.replace('$user->currentAccessToken()?->delete();', "$token = $user->currentAccessToken();\n                if ($token && method_exists($token, 'delete')) $token->delete();"));
edit('app/Http/Controllers/AccountController.php', s=>s.replace("        AuditLog::record($user, 'password_change'", "        $user->tokens()->delete();\n        $request->session()->regenerate();\n        AuditLog::record($user, 'password_change'"));
edit('database/seeders/DatabaseSeeder.php', s=>s.replace('    {\n        //', "    {\n        if (! app()->environment(['local', 'testing'])) {\n            throw new \\RuntimeException('Fictional demo seeding is disabled outside local/testing. Use app:create-admin.');\n        }\n        //").replaceAll("'name' => 'ADFC ", "'name' => '[DEMO] ADFC ").replace("'name' => 'Juan Dela Cruz'", "'name' => 'Fictional Student Example'").replace("'name' => 'Scholarship Evaluator'", "'name' => 'Demo Scholarship Evaluator'"));
edit('resources/views/dashboards/staff.blade.php', s=>s.replace("@section('title', 'Staff Dashboard')", "@section('title', 'Scholarship Staff Dashboard')").replace('<strong>Recommendations</strong>', '<strong>Open recommendations</strong>'));
