<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StationStatusSeeder extends Seeder
{
    public function run()
    {
        // Load from .env
        $baseUrl  = rtrim(env('POLARIS_BASE_URL', 'https://41.203.191.15/api/polaris'), '/');
        $apiToken = env('POLARIS_API_TOKEN', '');
        $pageSize = env('POLARIS_PAGE_SIZE', 500);

        // Build full API URL
        $externalApiUrl = sprintf(
            '%s/stations/status?limit=%d&page=1&api_token=%s',
            $baseUrl,
            $pageSize,
            $apiToken
        );

        try {
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
                $db           = \Config\Database::connect();
                $stationTable = $db->table('stations');
                $statuses     = $data['items'];

                foreach ($statuses as $status) {
                    $updateData = [
                        'status'     => $status['status'] ?? 'N',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    $existingStation = $stationTable
                        ->where('id', $status['id'])
                        ->get()
                        ->getRow();

                    if ($existingStation) {
                        $stationTable->where('id', $status['id'])->update($updateData);
                        echo "Updated status for station ID: " . $status['id'] . "\n";
                    } else {
                        echo "Station ID: " . $status['id'] . " not found in the database.\n";
                    }
                }

                echo "Status updates completed.\n";
            } else {
                echo "No status data found in the API response.\n";
            }

        } catch (\Exception $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
        }
    }
}


// namespace App\Database\Seeds;

// use CodeIgniter\Database\Seeder;

// class StationStatusSeeder extends Seeder
// {
//     public function run()
//     {
//         $externalApiUrl = 'https://41.203.191.15/api/polaris/stations/status?limit=-1&page=1&api_token=dad1505aec32a71941f146fd5aaf9081';

//         try {
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
//                 // Get the database instance
//                 $db = \Config\Database::connect();
//                 $stationTable = $db->table('stations');
//                 $statuses = $data['items'];

//                 foreach ($statuses as $status) {
//                     // Prepare the data to update
//                     $updateData = [
//                         'status' => $status['status'] ?? 'N', // Default to 'N' if status is not provided
//                         'updated_at' => date('Y-m-d H:i:s'),
//                     ];

//                     // Check if the station exists
//                     $existingStation = $stationTable->where('id', $status['id'])->get()->getRow();

//                     if ($existingStation) {
//                         // Update the station's status field
//                         $stationTable->where('id', $status['id'])->update($updateData);
//                         echo "Updated status for station ID: " . $status['id'] . "\n";
//                     } else {
//                         echo "Station ID: " . $status['id'] . " not found in the database.\n";
//                     }
//                 }

//                 echo "Status updates completed.\n";
//             } else {
//                 echo "No status data found in the API response.\n";
//             }

//         } catch (\Exception $e) {
//             echo "Error occurred: " . $e->getMessage() . "\n";
//         }
//     }
// }
