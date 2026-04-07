<?php
namespace App\Database\Seeds;

use App\Models\StationMeasureModel;
use CodeIgniter\Database\Seeder;

class StationMeasureSeeder extends Seeder
{
    public function run()
    {
        // Read from .env
        $baseUrl  = rtrim(env('POLARIS_BASE_URL', 'https://41.203.191.15/api/polaris'), '/');
        $apiToken = env('POLARIS_API_TOKEN', '');
        $pageSize = env('POLARIS_PAGE_SIZE', 500);

        // Build full URL
        $externalApiUrl = sprintf(
            '%s/measures?limit=%d&page=1&api_token=%s',
            $baseUrl,
            $pageSize,
            $apiToken
        );

        try {
            $stationMeasureModel = new StationMeasureModel();

            // Use cURL to fetch data
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $externalApiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => env('POLARIS_VERIFY_SSL', true) ? 2 : 0,
                CURLOPT_SSL_VERIFYPEER => env('POLARIS_VERIFY_SSL', true),
                CURLOPT_TIMEOUT        => env('POLARIS_TIMEOUT', 30),
                CURLOPT_CONNECTTIMEOUT => env('POLARIS_CONNECT_TIMEOUT', 10),
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode !== 200 || ! $response) {
                echo "Failed to fetch data from external API. HTTP Code: $httpCode\n";
                return;
            }

            $data = json_decode($response, true);

            if (isset($data['items'])) {
                foreach ($data['items'] as $stationId => $measures) {
                    foreach ($measures as $measure) {
                        $measureData = [
                            'station_id' => $measure['station_id'],
                            'measure_id' => $measure['measure_id'],
                        ];

                        $existing = $stationMeasureModel
                            ->where('station_id', $measure['station_id'])
                            ->where('measure_id', $measure['measure_id'])
                            ->first();

                        if ($existing) {
                            $stationMeasureModel->update($existing['id'], $measureData);
                            echo "Updated station measure for station_id: {$measure['station_id']}, measure_id: {$measure['measure_id']}\n";
                        } else {
                            $stationMeasureModel->insert($measureData);
                            echo "Inserted station measure for station_id: {$measure['station_id']}, measure_id: {$measure['measure_id']}\n";
                        }
                    }
                }
                echo "Seeding completed.\n";
            } else {
                echo "No station measure data found in the API response.\n";
            }
        } catch (\Exception $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
        }
    }
}


// namespace App\Database\Seeds;

// use App\Models\StationMeasureModel;
// use CodeIgniter\Database\Seeder;

// class StationMeasureSeeder extends Seeder
// {
//     public function run()
//     {
//         $externalApiUrl = 'https://41.203.191.15/api/polaris/measures?limit=-1&page=1&api_token=dad1505aec32a71941f146fd5aaf9081';

//         try {
//             $stationMeasureModel = new StationMeasureModel();

//             // Use cURL to fetch data
//             $curl = curl_init();
//             curl_setopt_array($curl, [
//                 CURLOPT_URL => $externalApiUrl,
//                 CURLOPT_RETURNTRANSFER => true,
//                 CURLOPT_SSL_VERIFYHOST => false, // Bypass SSL issues (for dev, avoid in prod)
//                 CURLOPT_SSL_VERIFYPEER => false,
//                 CURLOPT_TIMEOUT => 10,
//             ]);

//             $response = curl_exec($curl);
//             $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//             curl_close($curl);

//             if ($httpCode !== 200 || !$response) {
//                 echo "Failed to fetch data from external API. HTTP Code: $httpCode\n";
//                 return;
//             }

//             $data = json_decode($response, true);

//             if (isset($data['items'])) {
//                 foreach ($data['items'] as $stationId => $measures) {
//                     foreach ($measures as $measure) {
//                         $measureData = [
//                             'station_id' => $measure['station_id'],
//                             'measure_id' => $measure['measure_id'],
//                         ];

//                         // Check if the station_measure entry exists
//                         $existing = $stationMeasureModel
//                             ->where('station_id', $measure['station_id'])
//                             ->where('measure_id', $measure['measure_id'])
//                             ->first();

//                         if ($existing) {
//                             // Update if it exists
//                             $stationMeasureModel->update($existing['id'], $measureData);
//                             echo "Updated station measure for station_id: {$measure['station_id']}, measure_id: {$measure['measure_id']}\n";
//                         } else {
//                             // Insert if it does not exist
//                             $stationMeasureModel->insert($measureData);
//                             echo "Inserted station measure for station_id: {$measure['station_id']}, measure_id: {$measure['measure_id']}\n";
//                         }
//                     }
//                 }
//                 echo "Seeding completed.\n";
//             } else {
//                 echo "No station measure data found in the API response.\n";
//             }
//         } catch (\Exception $e) {
//             echo "Error occurred: " . $e->getMessage() . "\n";
//         }
//     }
// }
