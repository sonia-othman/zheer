<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDBConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-d-b-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $pdo = DB::connection()->getPdo();
            $this->info('Successfully connected to: '.DB::connection()->getDatabaseName());
            $this->info('Database version: '.$pdo->query('SELECT version()')->fetchColumn());
        } catch (\Exception $e) {
            $this->error('Connection failed: '.$e->getMessage());
        }

        return 0;
    }
}
