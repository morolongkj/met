<?php
namespace App\Database\Seeds;

use App\Models\ObservationModel;
use CodeIgniter\Database\Seeder;

class ObservationSeeder extends Seeder
{
    public function run()
    {
        // --- Read config from .env ---
        $baseUrl       = rtrim(env('POLARIS_BASE_URL', 'https://41.203.191.15/api/polaris'), '/');
        $apiToken      = env('POLARIS_API_TOKEN', '');
        $pageSize      = (int) env('POLARIS_PAGE_SIZE', 500);
        $verifySSL     = (bool) env('POLARIS_VERIFY_SSL', true);
        $timeout       = (int) env('POLARIS_TIMEOUT', 30);
        $connectTO     = (int) env('POLARIS_CONNECT_TIMEOUT', 10);
        $maxRetries    = (int) env('POLARIS_MAX_RETRIES', 3);
        $retryDelaySec = (int) env('POLARIS_RETRY_DELAY', 5);

        // Build URL from env values
        $externalApiUrl = sprintf(
            '%s/data/latest?limit=%d&page=1&api_token=%s',
            $baseUrl,
            $pageSize,
            $apiToken
        );

        try {
            // --- Fetch with retries ---
            $response = $this->fetchWithRetries($externalApiUrl, $verifySSL, $timeout, $connectTO, $maxRetries, $retryDelaySec, $httpCode);

            if ($httpCode !== 200 || ! $response) {
                echo "Failed to fetch data from external API. HTTP Code: {$httpCode}\n";
                return;
            }

            $data = json_decode($response, true);

            if (isset($data['items']) && is_array($data['items'])) {
                $observationModel = new ObservationModel();

                // exclude list (61, 62, 63) -> make it easy to tweak later
                $excludeMeasures = [61, 62, 63];

                foreach ($data['items'] as $stationId => $measures) {
                    foreach ($measures as $measureId => $observations) {
                        foreach ($observations as $observation) {
                            $obsMeasureId = $observation['measure_id'] ?? null;
                            if ($obsMeasureId === null || in_array((int) $obsMeasureId, $excludeMeasures, true)) {
                                continue;
                            }

                            $observationData = [
                                'station_id'     => $observation['station_id'],
                                'measure_id'     => $obsMeasureId,
                                'record_date'    => $this->toDateTime($observation['record_date'] ?? null),
                                'insert_date'    => $this->toDateTime($observation['insert_date'] ?? null),
                                'raw_data'       => $observation['raw_data'] ?? null,
                                'val_data'       => $observation['val_data'] ?? null,
                                'min_data'       => $observation['min_data'] ?? null,
                                'max_data'       => $observation['max_data'] ?? null,
                                'min_minute'     => $observation['min_minute'] ?? null,
                                'max_minute'     => $observation['max_minute'] ?? null,
                                'error_code'     => $observation['error_code'] ?? null,
                                'val_code'       => $observation['val_code'] ?? null,
                                'status'         => $observation['status'] ?? null,
                                'raw_data_str'   => $observation['raw_data_str'] ?? null,
                                'val_data_str'   => $observation['val_data_str'] ?? null,
                                'min_data_str'   => $observation['min_data_str'] ?? null,
                                'max_data_str'   => $observation['max_data_str'] ?? null,
                                'min_minute_str' => $observation['min_minute_str'] ?? null,
                                'max_minute_str' => $observation['max_minute_str'] ?? null,
                            ];

                            // Save or update (assumes your model implements saveOrUpdate)
                            $observationModel->saveOrUpdate($observationData);
                        }
                    }
                }

                echo "Successfully seeded observations from external API.\n";
            } else {
                echo "No valid data found in the API response.\n";
            }
        } catch (\Throwable $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Simple cURL GET with retry support.
     */
    private function fetchWithRetries(string $url, bool $verifySSL, int $timeout, int $connectTO, int $maxRetries, int $retryDelaySec,  ? int &$httpCode = null): ?string
    {
        $attempt  = 0;
        $httpCode = 0;

        do {
            $attempt++;

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => $verifySSL ? 2 : 0,
                CURLOPT_SSL_VERIFYPEER => $verifySSL,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $connectTO,
            ]);

            $response = curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err      = curl_error($curl);
            curl_close($curl);

            if ($httpCode === 200 && $response !== false) {
                return $response;
            }

            // Only retry on transient issues (HTTP 5xx or cURL failure)
            $isTransient = ($httpCode >= 500 && $httpCode <= 599) || ($response === false);
            if ($attempt < $maxRetries && $isTransient) {
                if (! empty($err)) {
                    echo "Attempt {$attempt} failed: {$err}. Retrying in {$retryDelaySec}s...\n";
                } else {
                    echo "Attempt {$attempt} failed: HTTP {$httpCode}. Retrying in {$retryDelaySec}s...\n";
                }
                sleep($retryDelaySec);
            } else {
                break;
            }
        } while ($attempt < $maxRetries);

        return null;
    }

    /**
     * Safely convert various date strings to 'Y-m-d H:i:s' or null.
     */
    private function toDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}

// namespace App\Database\Seeds;

// use App\Models\ObservationModel;
// use CodeIgniter\Database\Seeder;

// class ObservationSeeder extends Seeder
// {
//     public function run()
//     {
//         $externalApiUrl = 'https://41.203.191.15/api/polaris/data/latest?limit=-1&page=1&api_token=dad1505aec32a71941f146fd5aaf9081';

//         try {
//             // Fetch data from the external API
//             $curl = curl_init();
//             curl_setopt_array($curl, [
//                 CURLOPT_URL            => $externalApiUrl,
//                 CURLOPT_RETURNTRANSFER => true,
//                 CURLOPT_SSL_VERIFYHOST => false, // Skip SSL verification for development (use caution in production)
//                 CURLOPT_SSL_VERIFYPEER => false,
//                 CURLOPT_TIMEOUT        => 30,
//             ]);

//             $response = curl_exec($curl);
//             $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//             curl_close($curl);

//             if ($httpCode !== 200 || ! $response) {
//                 echo "Failed to fetch data from external API. HTTP Code: $httpCode\n";
//                 return;
//             }

//             $data = json_decode($response, true);

//             if (isset($data['items']) && is_array($data['items'])) {
//                 $observationModel = new ObservationModel();

//                 foreach ($data['items'] as $stationId => $measures) {
//                     foreach ($measures as $measureId => $observations) {
//                         foreach ($observations as $observation) {
//                             $observationData = [
//                                 'station_id'     => $observation['station_id'],
//                                 'measure_id'     => $observation['measure_id'],
//                                 'record_date'    => date('Y-m-d H:i:s', strtotime($observation['record_date'])),
//                                 'insert_date'    => date('Y-m-d H:i:s', strtotime($observation['insert_date'])),
//                                 'raw_data'       => $observation['raw_data'] ?? null,
//                                 'val_data'       => $observation['val_data'] ?? null,
//                                 'min_data'       => $observation['min_data'] ?? null,
//                                 'max_data'       => $observation['max_data'] ?? null,
//                                 'min_minute'     => $observation['min_minute'] ?? null,
//                                 'max_minute'     => $observation['max_minute'] ?? null,
//                                 'error_code'     => $observation['error_code'] ?? null,
//                                 'val_code'       => $observation['val_code'] ?? null,
//                                 'status'         => $observation['status'] ?? null,
//                                 'raw_data_str'   => $observation['raw_data_str'] ?? null,
//                                 'val_data_str'   => $observation['val_data_str'] ?? null,
//                                 'min_data_str'   => $observation['min_data_str'] ?? null,
//                                 'max_data_str'   => $observation['max_data_str'] ?? null,
//                                 'min_minute_str' => $observation['min_minute_str'] ?? null,
//                                 'max_minute_str' => $observation['max_minute_str'] ?? null,
//                             ];

//                             if ($observationData['measure_id'] != 61 && $observationData['measure_id'] != 62 && $observationData['measure_id'] != 63) {

//                                 // Use saveOrUpdate to avoid duplicate entries
//                                 $observationModel->saveOrUpdate($observationData);
//                             }
//                         }
//                     }
//                 }

//                 echo "Successfully seeded observations from external API.\n";
//             } else {
//                 echo "No valid data found in the API response.\n";
//             }
//         } catch (\Exception $e) {
//             echo "Error occurred: " . $e->getMessage() . "\n";
//         }
//     }
// }
