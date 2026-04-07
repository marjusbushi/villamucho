<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class HotelSetup extends Command
{
    protected $signature = 'hotel:setup';

    protected $description = 'First-time hotel setup: run migrations, seed admin user';

    public function handle(): int
    {
        $this->info('Setting up Chanel Manager...');

        // Run migrations
        $this->call('migrate', ['--force' => true]);

        // Seed database
        if (User::count() === 0) {
            $this->call('db:seed', ['--force' => true]);
            $this->info('Admin user created: admin@chanelmanager.com / password');
        } else {
            $this->warn('Database already has users — skipping seed.');
        }

        $this->info('Setup complete!');

        return Command::SUCCESS;
    }
}
