<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ResetDatabase extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:reset';
    protected $description = 'Drops all tables, runs migrations, and seeds the database.';

    public function run(array $params)
    {
        CLI::write('Resetting the database...', 'yellow');

        // Step 1: Drop all tables
        CLI::write('Dropping all tables...', 'blue');
        $this->dropAllTables();

        // Step 2: Run migrations
        CLI::write('Running migrations...', 'blue');
        $this->runMigrations();

        // Step 3: Seed the database
        CLI::write('Seeding the database...', 'blue');
        $this->runSeeders();

        CLI::write('Database reset completed!', 'green');
    }

    /**
     * Drops all tables from the database.
     */
    private function dropAllTables()
    {
        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $tables = $db->listTables();

        foreach ($tables as $table) {
            $forge->dropTable($table, true); // Drop table if it exists
            CLI::write("Dropped table: $table", 'yellow');
        }
    }

    /**
     * Runs all migrations.
     */
    // private function runMigrations()
    // {
    //     $migrations = \Config\Services::migrations();
    //     try {
    //         $migrations->latest(); // Run all migrations
    //         CLI::write('Migrations successfully completed.', 'green');
    //     } catch (\Throwable $e) {
    //         CLI::error('Error during migrations: ' . $e->getMessage());
    //         exit(1);
    //     }
    // }
/**
 * Runs all migrations, including those from all namespaces.
 */
private function runMigrations()
{
    try {
        CLI::write('Running all migrations...', 'blue');

        // Run `php spark migrate --all` programmatically
        $output = command('migrate --all');

        CLI::write($output, 'yellow'); // Display migration output
        CLI::write('All migrations executed successfully.', 'green');
    } catch (\Throwable $e) {
        CLI::error('Error during migrations: ' . $e->getMessage());
        exit(1);
    }
}


    /**
     * Runs the DatabaseSeeder.
     */
    private function runSeeders()
    {
        $seeder = \Config\Database::seeder();
        try {
            $seeder->call('DatabaseSeeder'); // Call the main seeder
            CLI::write('Seeders executed successfully.', 'green');
        } catch (\Throwable $e) {
            CLI::error('Error during seeding: ' . $e->getMessage());
            exit(1);
        }
    }
}
