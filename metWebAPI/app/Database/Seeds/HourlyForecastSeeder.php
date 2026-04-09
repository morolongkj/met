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
        log_message('info', 'HourlyForecastSeeder started');

        $filePath = $this->resolveCsvPath('HrlyFcWx.csv');
        if (! $filePath) {
            log_message('error', 'HourlyForecastSeeder failed because CSV could not be resolved');
            echo "CSV file not found locally and remote download failed: HrlyFcWx.csv";
            return;
        }

        $csvFile  = fopen($filePath, 'r');

        if (! $csvFile) {
            log_message('error', 'HourlyForecastSeeder failed opening CSV file: {path}', [
                'path' => $filePath,
            ]);
            echo "CSV file not found!";
            return;
        }

        fgetcsv($csvFile); // Skip header row

        $locationModel       = new LocationModel();
        $dailyForecastModel  = new DailyForecastModel();
        $hourlyForecastModel = new HourlyForecastModel();
        $processedRows       = 0;
        $skippedRows         = 0;
        $insertedLocations   = 0;
        $insertedDaily       = 0;
        $insertedHourly      = 0;
        $updatedHourly       = 0;

        while (($row = fgetcsv($csvFile, 1000, ",")) !== false) {
            if (! is_array($row) || count($row) < 9) {
                $skippedRows++;
                continue;
            }

            $processedRows++;

            list($place, $latitude, $longitude, $date, $time, $temperature, $humidity, $wind_speed, $weather) = array_map('trim', $row);

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

            // Check if daily forecast exists
            $dailyForecast = $dailyForecastModel->where(['location_id' => $locationID, 'date' => $date])->first();
            if (! $dailyForecast) {
                $dailyForecastID = $dailyForecastModel->insert([
                    'location_id' => $locationID,
                    'date'        => $date,
                ], true);
                $insertedDaily++;
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
                $updatedHourly++;
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
                $insertedHourly++;
            }
        }

        fclose($csvFile);

        log_message('info', 'HourlyForecastSeeder completed. Processed: {processed}, Skipped: {skipped}, New locations: {locations}, New daily forecasts: {daily}, Inserts: {inserts}, Updates: {updates}', [
            'processed' => $processedRows,
            'skipped'   => $skippedRows,
            'locations' => $insertedLocations,
            'daily'     => $insertedDaily,
            'inserts'   => $insertedHourly,
            'updates'   => $updatedHourly,
        ]);
    }
}

// edited