<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin {email} {--name=Administrator}';
    protected $description = 'Create an administrator using a hidden interactive password prompt';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Provide a valid email that is not already registered. Existing accounts are never overwritten.');
            return self::FAILURE;
        }
        $password = $this->secret('Admin password (12-72 characters, uppercase, lowercase, number and symbol)');

        $validator = \Illuminate\Support\Facades\Validator::make(['password' => $password], [
            'password' => ['required', 'string', 'max:72', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));
            return self::FAILURE;
        }

        $user = User::create([
            'email' => strtolower(trim($email)),
            'name' => $this->option('name'),
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->info("Administrator ready: {$user->email}");
        return self::SUCCESS;
    }
}
