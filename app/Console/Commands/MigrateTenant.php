<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class MigrateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:tenant {db} {--fresh} {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tenant migrations on a specific tenant DB';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $db = $this->argument('db');

        // Dynamically set tenant DB
        Config::set('database.connections.tenant.database', $db);

        $options = [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
        ];

        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', $options, $this->output);
        } else {
            Artisan::call('migrate', $options, $this->output);
        }

        $this->info("Tenant DB [{$db}] migrated successfully.");
    }
}
