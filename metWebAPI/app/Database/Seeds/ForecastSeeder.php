<?php
namespace App\Database\Seeds;

use App\Models\DistrictModel;
use CodeIgniter\Database\Seeder;

class ForecastSeeder extends Seeder
{
    public function run()
    {
        $districtModel = new DistrictModel();
        $filePath     = WRITEPATH . 'uploads/forecast.csv'; // Path to the CSV file
        $forecastData = $this->parseCSVFile($filePath);

        foreach ($forecastData as $forecast) {
            $districtId = $districtModel->getDistrictId($forecast['district_name']);

            if ($districtId) {
                // Insert data into the forecasts table
                $this->db->table('forecast')->insert([
                    'district_id'  => $districtId,
                    'current_temp' => $forecast['current_temp'],
                    'min_temp'     => $forecast['min_temp'],
                    'max_temp'     => $forecast['max_temp'],
                    'condition'    => $forecast['condition'],
                ]);
            }
        }
    }

    /**
     * Parse the CSV file and return forecast data.
     *
     * @param string $filePath
     * @return array
     */
    private function parseCSVFile(string $filePath): array
    {
        $data = [];
        if (! file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Example line: Maseru.txt,30,34,16,SUNNY
            $parts = explode(',', $line);
            if (count($parts) === 5) {
                $data[] = [
                    'district_name' => trim(str_replace('.txt', '', $parts[0])),
                    'current_temp'  => (int) $parts[1],
                    'max_temp'      => (int) $parts[2],
                    'min_temp'      => (int) $parts[3],
                    'condition'     => trim($parts[4]),
                ];
            }
        }

        return $data;
    }
}
