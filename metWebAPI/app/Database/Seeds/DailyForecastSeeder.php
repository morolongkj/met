<?php
namespace App\Database\Seeds;

use App\Models\DailyForecastModel;
use App\Models\LocationModel;
use CodeIgniter\Database\Seeder;

class DailyForecastSeeder extends Seeder
{
    use RemoteCsvSeederTrait;

    public function run()
    {
        $filePath = $this->resolveCsvPath('DailyFcWx.csv');
        if (! $filePath) {
            echo "CSV file not found locally and remote download failed: DailyFcWx.csv";
            return;
        }

        $csvFile  = fopen($filePath, 'r');

        if (! $csvFile) {
            echo "CSV file not found!";
            return;
        }

        fgetcsv($csvFile); // Skip header row

        $locationModel      = new LocationModel();
        $dailyForecastModel = new DailyForecastModel();

        while (($row = fgetcsv($csvFile, 1000, ",")) !== false) {
            if (! is_array($row) || count($row) < 9) {
                continue;
            }

            list($place, $latitude, $longitude, $date, $min_temp, $max_temp, $humidity, $wind_speed, $weather) = array_map('trim', $row);

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

            // Check if daily forecast already exists for the given location and date
            $dailyForecast = $dailyForecastModel->where([
                'location_id' => $locationID,
                'date'        => $date,
            ])->first();

            if ($dailyForecast) {
                // Update existing record
                $dailyForecastModel->update($dailyForecast['id'], [
                    'min_temperature' => $min_temp,
                    'max_temperature' => $max_temp,
                    'humidity'        => $humidity,
                    'wind_speed'      => $wind_speed,
                    'weather'         => $weather,
                ]);
            } else {
                // Insert new daily forecast
                $dailyForecastModel->insert([
                    'location_id'     => $locationID,
                    'date'            => $date,
                    'min_temperature' => $min_temp,
                    'max_temperature' => $max_temp,
                    'humidity'        => $humidity,
                    'wind_speed'      => $wind_speed,
                    'weather'         => $weather,
                ]);
            }
        }

        fclose($csvFile);
    }
}
