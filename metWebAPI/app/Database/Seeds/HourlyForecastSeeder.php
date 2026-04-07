<?php
namespace App\Database\Seeds;

use App\Models\DailyForecastModel;
use App\Models\HourlyForecastModel;
use App\Models\LocationModel;
use CodeIgniter\Database\Seeder;

class HourlyForecastSeeder extends Seeder
{
    use RemoteCsvSeederTrait;

    public function run()
    {
        $filePath = $this->resolveCsvPath('HrlyFcWx.csv');
        if (! $filePath) {
            echo "CSV file not found locally and remote download failed: HrlyFcWx.csv";
            return;
        }

        $csvFile  = fopen($filePath, 'r');

        if (! $csvFile) {
            echo "CSV file not found!";
            return;
        }

        fgetcsv($csvFile); // Skip header row

        $locationModel       = new LocationModel();
        $dailyForecastModel  = new DailyForecastModel();
        $hourlyForecastModel = new HourlyForecastModel();

        while (($row = fgetcsv($csvFile, 1000, ",")) !== false) {
            if (! is_array($row) || count($row) < 9) {
                continue;
            }

            list($place, $latitude, $longitude, $date, $time, $temperature, $humidity, $wind_speed, $weather) = array_map('trim', $row);

            // Check if location exists
            $location = $locationModel->where(['latitude' => $latitude, 'longitude' => $longitude])->first();
            if (! $location) {
                $locationID = $locationModel->insert([
                    'place'     => $place,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                ], true);
            } else {
                $locationID = $location['id'];
            }

            // Check if daily forecast exists
            $dailyForecast = $dailyForecastModel->where(['location_id' => $locationID, 'date' => $date])->first();
            if (! $dailyForecast) {
                $dailyForecastID = $dailyForecastModel->insert([
                    'location_id' => $locationID,
                    'date'        => $date,
                ], true);
            } else {
                $dailyForecastID = $dailyForecast['id'];
            }

            // Check if hourly forecast already exists for the given time
            $hourlyForecast = $hourlyForecastModel->where([
                'daily_forecast_id' => $dailyForecastID,
                'time'              => $time,
            ])->first();

            if ($hourlyForecast) {
                // Update existing record
                $hourlyForecastModel->update($hourlyForecast['id'], [
                    'temperature' => $temperature,
                    'humidity'    => $humidity,
                    'wind_speed'  => $wind_speed,
                    'weather'     => $weather,
                ]);
            } else {
                // Insert new hourly forecast
                $hourlyForecastModel->insert([
                    'daily_forecast_id' => $dailyForecastID,
                    'time'              => $time,
                    'temperature'       => $temperature,
                    'humidity'          => $humidity,
                    'wind_speed'        => $wind_speed,
                    'weather'           => $weather,
                ]);
            }
        }

        fclose($csvFile);
    }
}
