<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;
use Config\Services;

class MigrateController extends Controller
{
    public function run()
    {
        // Prevent accidental web access
        if (! is_cli() && env('CI_ENVIRONMENT') === 'production') {
            return $this->response->setStatusCode(403, 'Forbidden');
        }

        $migrate = Services::migrations();
        $db      = Database::connect();

        try {
            // ⚠️ WARNING: This will DROP ALL TABLES
            $forge  = Database::forge($db);
            $tables = $db->listTables();

            foreach ($tables as $table) {
                $forge->dropTable($table, true); // true = add IF EXISTS
                echo "Dropped table: {$table}\n";
            }

            // Reset migration history
            // $db->table('migrations')->truncate();

            // Run all migrations fresh
            $migrate->latest();

            echo "Database reset and migrations executed successfully.\n";
        } catch (\Throwable $e) {
            echo "Migration error: " . $e->getMessage() . "\n";
        }
    }
}
