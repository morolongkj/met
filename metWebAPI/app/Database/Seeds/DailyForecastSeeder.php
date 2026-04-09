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
        log_message('info', 'DailyForecastSeeder started');

        $filePath = $this->resolveCsvPath('DailyFcWx.csv');
        if (! $filePath) {
            log_message('error', 'DailyForecastSeeder failed because CSV could not be resolved');
            echo "CSV file not found locally and remote download failed: DailyFcWx.csv";
            return;
        }

        $csvFile  = fopen($filePath, 'r');

        if (! $csvFile) {
            log_message('error', 'DailyForecastSeeder failed opening CSV file: {path}', [
                'path' => $filePath,
            ]);
            echo "CSV file not found!";
            return;
        }

        fgetcsv($csvFile); // Skip header row

        $locationModel      = new LocationModel();
        $dailyForecastModel = new DailyForecastModel();
        $processedRows      = 0;
        $skippedRows        = 0;
        $insertedLocations  = 0;
        $insertedForecasts  = 0;
        $updatedForecasts   = 0;

        while (($row = fgetcsv($csvFile, 1000, ",")) !== false) {
            if (! is_array($row) || count($row) < 9) {
                $skippedRows++;
                continue;
            }

            $processedRows++;

            list($place, $latitude, $longitude, $date, $min_temp, $max_temp, $humidity, $wind_speed, $weather) = array_map('trim', $row);

            // Check if location exists
            $location = $locationModel->where(['latitude' => $latitude, 'longitude' => $longitude])->first();
            if (! $location) {
                $locationID = $locationModel->insert([
                    'place'     => $place,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                ], true);
                $insertedLocations++;
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
                $updatedForecasts++;
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
                $insertedForecasts++;
            }
        }

        fclose($csvFile);

        log_message('info', 'DailyForecastSeeder completed. Processed: {processed}, Skipped: {skipped}, New locations: {locations}, Inserts: {inserts}, Updates: {updates}', [
            'processed' => $processedRows,
            'skipped'   => $skippedRows,
            'locations' => $insertedLocations,
            'inserts'   => $insertedForecasts,
            'updates'   => $updatedForecasts,
        ]);
    }
}
