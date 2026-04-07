<?php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('DistrictSeeder');
        $this->call('MeasuresSeeder');
        $this->call('StationSeeder');
        $this->call('StationMeasureSeeder');
        $this->call('StationStatusSeeder');
        // $this->call('ObservationSeeder');
        // $this->call('ForecastSeeder');
        // $this->call('NotificationSeeder');
        // $this->call('DailyForecastSeeder');
        // $this->call('HourlyForecastSeeder');
    }
}
