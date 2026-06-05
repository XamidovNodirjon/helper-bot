<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {email} {password} {name=Admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an administrator account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->argument('name');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address format.');
            return 1;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'password' => Hash::make($password),
                'name' => $name,
            ]);
            $this->info("Administrator {$email} password updated successfully!");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $this->info("Administrator {$email} created successfully!");
        }

        return 0;
    }
}
