<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RunSeeder extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'seeder:run';
    protected $description = 'Run database seeder';

    public function run(array $params)
    {
        $seeder = \Config\Database::seeder();

        $seeder->call('StationMeasureSeeder');
        $seeder->call('StationStatusSeeder');
        $seeder->call('ObservationSeeder');
        $seeder->call('ForecastSeeder');
        // $seeder->call('NotificationSeeder');
        $seeder->call('DailyForecastSeeder');
        $seeder->call('HourlyForecastSeeder');
        CLI::write('Seeder executed successfully at ' . date('Y-m-d H:i:s'), 'green');
    }
}
