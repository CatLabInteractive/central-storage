<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;


/**
 *
 */
class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        User::create([
            'name' => $this->ask('Name?'),
            'email' => $this->ask('Email?'),
            'password' => Hash::make($this->secret('Password?')),
        ]);
        $this->info('Account created. You can now login with your new account.');
    }
}
