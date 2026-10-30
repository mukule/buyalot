<?php

namespace App\Console\Commands;

use App\Mail\NewUserCredentialsMail;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Bootstraps the first super-admin. Does nothing if a super-admin already exists.
 *
 * - New email: creates the account (same as admin "Add user"), assigns
 *   super-admin and emails a random password with the login URL.
 * - Existing email: promotes that account to super-admin. Its password is only
 *   replaced (and emailed) with --reset-password.
 *
 *   php artisan app:ensure-super-admin you@example.com --name="Jane Doe" --phone=07... --gender=female
 */
class EnsureSuperAdmin extends Command
{
    protected $signature = 'app:ensure-super-admin
        {email : Email of the super-admin account}
        {--name= : Full name (required when creating)}
        {--phone= : Phone number}
        {--gender= : male, female or other}
        {--inactive : Create the account as inactive}
        {--reset-password : For an existing account, set and email a new random password}
        {--login-url= : Login link for the email (defaults to route("login"), i.e. APP_URL)}';

    protected $description = 'Create or promote the first super-admin (only when none exists) and email login credentials';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return self::FAILURE;
        }

        $gender = $this->option('gender');
        if ($gender !== null && ! in_array($gender, ['male', 'female', 'other'], true)) {
            $this->error('Gender must be male, female or other.');
            return self::FAILURE;
        }

        $role = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if (! $role) {
            $this->error('The super-admin role does not exist. Run: php artisan db:seed --class=PermissionSeeder');
            return self::FAILURE;
        }

        if (($existingCount = User::role('super-admin')->count()) > 0) {
            $this->info("A super-admin already exists ({$existingCount}). Nothing to do.");
            return self::SUCCESS;
        }

        $loginUrl = $this->option('login-url') ?: route('login');
        $user = User::where('email', $email)->first();
        $password = null;

        DB::transaction(function () use (&$user, &$password, $email, $gender, $role) {
            if ($user) {
                $user->assignRole($role);
                if ($this->option('reset-password')) {
                    $password = Str::password(14, symbols: false);
                    $user->forceFill(['password' => Hash::make($password)])->save();
                }
                return;
            }

            $name = trim((string) $this->option('name'));
            if ($name === '') {
                throw new \InvalidArgumentException('--name is required when creating a new account.');
            }

            $password = Str::password(14, symbols: false);
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'phone'             => $this->option('phone'),
                'password'          => Hash::make($password),
                'status'            => ! $this->option('inactive'),
                'email_verified_at' => now(),
                'user_type'         => 'user',
            ]);
            $user->assignRole($role);

            UserDetail::create([
                'user_id'      => $user->id,
                'gender'       => $gender,
                'phone'        => $this->option('phone'),
                'contact_name' => $name,
            ]);
        });

        $this->info(($password && $user->wasRecentlyCreated ? 'Created' : 'Promoted') . " super-admin: {$user->name} <{$user->email}> (id {$user->id})");

        if (! $password) {
            $this->line('Existing password kept. Use --reset-password to email a new one.');
            return self::SUCCESS;
        }

        try {
            Mail::to($user->email)->send(new NewUserCredentialsMail($user, $password, $loginUrl));
            $this->info("Welcome email with a random password sent to {$user->email} (login: {$loginUrl}).");
        } catch (\Throwable $e) {
            // Never print the password; the account can recover it via "Forgot password".
            $this->warn('Account is ready but the email could not be sent: ' . $e->getMessage());
            $this->warn('Use "Forgot password" on the login page to set a password.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
