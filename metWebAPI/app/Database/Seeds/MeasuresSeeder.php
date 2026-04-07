<?php
namespace App\Database\Seeds;

use App\Models\MeasureModel;
use CodeIgniter\Database\Seeder;

class MeasuresSeeder extends Seeder
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

        if ($apiToken === '') {
            echo "POLARIS_API_TOKEN is empty. Aborting.\n";
            return;
        }

        // Build first-page URL from env values
        $externalApiUrl = sprintf(
            '%s/base_measures?limit=%d&page=1&api_token=%s',
            $baseUrl,
            $pageSize,
            $apiToken
        );

        try {
            $measureModel    = new MeasureModel();
            $excludeMeasures = [61, 62, 63];
            $totalUpserts    = 0;

            // Pagination loop
            do {
                $httpCode = 0;
                $response = $this->fetchWithRetries(
                    $externalApiUrl,
                    $verifySSL,
                    $timeout,
                    $connectTO,
                    $maxRetries,
                    $retryDelaySec,
                    $httpCode
                );

                if ($httpCode !== 200 || ! $response) {
                    echo "Failed to fetch data from external API. HTTP Code: {$httpCode}\n";
                    break;
                }

                $data = json_decode($response, true);

                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $measure) {
                        // Skip excluded IDs
                        $id = $measure['id'] ?? null;
                        if ($id === null || in_array((int) $id, $excludeMeasures, true)) {
                            continue;
                        }

                        $measureData = [
                            'id'         => $id,
                            'name'       => $measure['name'] ?? null,
                            'short_name' => $measure['short_name'] ?? null,
                            'unit'       => $measure['unit'] ?? null,
                            'decimals'   => $measure['decimals'] ?? null,
                            // store as JSON strings (null-safe)
                            'validation' => isset($measure['validation']) ? json_encode($measure['validation']) : null,
                            'thresholds' => isset($measure['thresholds']) ? json_encode($measure['thresholds']) : null,
                        ];

                        // Upsert: update if exists, otherwise insert
                        $existing = $measureModel->find($id);
                        if ($existing) {
                            $measureModel->update($id, $measureData);
                            // echo "Updated measure: {$measureData['name']}\n";
                        } else {
                            $measureModel->insert($measureData);
                            // echo "Inserted new measure: {$measureData['name']}\n";
                        }

                        $totalUpserts++;
                    }
                } else {
                    echo "No measures data found in the API response.\n";
                }

                // Move to next page if available (absolute or relative URL)
                $next           = $data['metadata']['page']['next'] ?? null;
                $externalApiUrl = $this->resolveNextUrl($next, $baseUrl);

            } while ($externalApiUrl);

            echo "Measures seeding completed. Total upserts: {$totalUpserts}\n";
        } catch (\Throwable $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Simple cURL GET with retry support.
     */
    private function fetchWithRetries(
        string $url,
        bool $verifySSL,
        int $timeout,
        int $connectTO,
        int $maxRetries,
        int $retryDelaySec,
        ? int &$httpCode = null
    ): ?string {
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

            $isTransient = ($httpCode >= 500 && $httpCode <= 599) || ($response === false);
            if ($attempt < $maxRetries && $isTransient) {
                $why = ! empty($err) ? $err : "HTTP {$httpCode}";
                echo "Attempt {$attempt} failed: {$why}. Retrying in {$retryDelaySec}s...\n";
                sleep($retryDelaySec);
            } else {
                break;
            }
        } while ($attempt < $maxRetries);

        return null;
    }

    /**
     * Resolve a next-page URL that may be absolute or relative (or null).
     */
    private function resolveNextUrl(?string $next, string $baseUrl): ?string
    {
        if (! $next) {
            return null;
        }
        // If already absolute (starts with http/https), return as-is.
        if (preg_match('#^https?://#i', $next)) {
            return $next;
        }
        // Otherwise, treat as relative to the API root
        $next = ltrim($next, '/');
        return "{$baseUrl}/{$next}";
    }
}


// namespace App\Database\Seeds;

// use App\Models\MeasureModel;
// use App\Services\PolarisClient;
// use CodeIgniter\CLI\CLI;
// use CodeIgniter\Database\ConnectionInterface;
// use CodeIgniter\Database\Seeder;

// class MeasuresSeeder extends Seeder
// {
//     public function run()
//     {
//         $client = new PolarisClient();
//         $model  = new MeasureModel();
//         $db     = \Config\Database::connect();

//         // Pick the correct resource path (underscore vs hyphen)
//         $resource = $this->pickResource($client, ['base_measures', 'base-measures']);

//         $since = env('POLARIS_SINCE'); // optional incremental date
//         $query = array_filter(['since' => $since], fn($v) => $v !== null && $v !== '');

//         $batch = [];
//         $size  = 1000; // tune as needed

//         $db->transStart();

//         foreach ($client->paginate($resource, $query) as $items) {
//             foreach ($items as $m) {
//                 $batch[] = [
//                     'id'         => $m['id'] ?? null,
//                     'name'       => $m['name'] ?? null,
//                     'short_name' => $m['short_name'] ?? null,
//                     'unit'       => $m['unit'] ?? null,
//                     'decimals'   => $m['decimals'] ?? null,
//                     'validation' => isset($m['validation']) ? json_encode($m['validation']) : null,
//                     'thresholds' => isset($m['thresholds']) ? json_encode($m['thresholds']) : null,
//                 ];

//                 if (count($batch) >= $size) {
//                     $this->upsertBatch($db, $model->table, $batch);
//                     $batch = [];
//                 }
//             }
//         }

//         if ($batch) {
//             $this->upsertBatch($db, $model->table, $batch);
//         }

//         $db->transComplete();

//         if (! $db->transStatus()) {
//             throw new \RuntimeException('Measures seeding failed; transaction rolled back.');
//         }

//         CLI::write('Measures seeding completed.', 'green');
//     }

//     /** Try each candidate path until one returns 200; otherwise throw. */
//     private function pickResource(PolarisClient $client, array $candidates): string
//     {
//         foreach ($candidates as $path) {
//             try {
//                 // cheap probe
//                 $client->get($path, ['limit' => 1, 'page' => 1]);
//                 return $path;
//             } catch (\Throwable $e) {
//                 // keep trying
//             }
//         }
//         throw new \RuntimeException(
//             'Polaris endpoint not found: tried ' . implode(', ', $candidates)
//         );
//     }

//     /** Fast MySQL upsert in one round-trip: INSERT ... ON DUPLICATE KEY UPDATE */
//     private function upsertBatch(ConnectionInterface $db, string $table, array $rows): void
//     {
//         if (! $rows) {
//             return;
//         }

//         $cols         = array_keys($rows[0]);
//         $placeholders = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';

//         $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES ';
//         $sql .= implode(',', array_fill(0, count($rows), $placeholders));

//         $updates = [];
//         foreach ($cols as $c) {
//             if ($c === 'id') {
//                 continue;
//             }
//             // don't update PK
//             $updates[] = "`$c`=VALUES(`$c`)";
//         }
//         $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $updates);

//         $bind = [];
//         foreach ($rows as $r) {
//             foreach ($cols as $c) {
//                 $bind[] = $r[$c] ?? null;
//             }
//         }

//         $db->query($sql, $bind);
//     }
// }


// namespace App\Database\Seeds;

// use App\Models\MeasureModel;
// use App\Services\PolarisClient;
// use CodeIgniter\Database\Seeder;

// class MeasuresSeeder extends Seeder
// {
//     public function run()
//     {
//         $client  = new PolarisClient();
//         $model   = new MeasureModel();
//         $db      = \Config\Database::connect();
//         $builder = $db->table($model->table)->ignore(true); // ignore duplicates

//         $chunk = [];
//         $size  = 1000;

//         $db->transStart();

//         foreach ($client->paginate('base_measures') as $items) {
//             foreach ($items as $m) {
//                 $chunk[] = [
//                     'id'          => $m['id'] ?? null,
//                     'name'        => $m['name'] ?? null,
//                     'unit'        => $m['unit'] ?? null,
//                     'description' => $m['description'] ?? null,
//                 ];

//                 if (count($chunk) >= $size) {
//                     $builder->insertBatch($chunk, false, $size);
//                     $chunk = [];
//                 }
//             }
//         }

//         if ($chunk) {
//             $builder->insertBatch($chunk, false, $size);
//         }

//         $db->transComplete();

//         if (! $db->transStatus()) {
//             throw new \RuntimeException('Measures seeding failed; transaction rolled back.');
//         }

//         echo "Measures seeding completed.\n";
//     }
// }


// namespace App\Database\Seeds;

// use App\Models\MeasureModel;
// use CodeIgniter\Database\Seeder;

// class MeasuresSeeder extends Seeder
// {
//     public function run()
//     {
//         $externalApiUrl = 'https://41.203.191.15/api/polaris/base_measures?limit=-1&page=1&api_token=dad1505aec32a71941f146fd5aaf9081';

//         try {
//             $measureModel = new MeasureModel();

//             // Pagination handling
//             do {
//                 // Use cURL to fetch data
//                 $curl = curl_init();
//                 curl_setopt_array($curl, [
//                     CURLOPT_URL => $externalApiUrl,
//                     CURLOPT_RETURNTRANSFER => true,
//                     CURLOPT_SSL_VERIFYHOST => false, // Bypass SSL issues (for dev, avoid in prod)
//                     CURLOPT_SSL_VERIFYPEER => false,
//                     CURLOPT_TIMEOUT => 10,
//                 ]);

//                 $response = curl_exec($curl);
//                 $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//                 curl_close($curl);

//                 if ($httpCode !== 200 || !$response) {
//                     echo "Failed to fetch data from external API. HTTP Code: $httpCode\n";
//                     break;
//                 }

//                 $data = json_decode($response, true);

//                 if (isset($data['items'])) {
//                     foreach ($data['items'] as $measure) {
//                         $measureData = [
//                             'id' => $measure['id'],
//                             'name' => $measure['name'],
//                             'short_name' => $measure['short_name'] ?? null,
//                             'unit' => $measure['unit'] ?? null,
//                             'decimals' => $measure['decimals'] ?? null,
//                             'validation' => json_encode($measure['validation']),
//                             'thresholds' => json_encode($measure['thresholds']),
//                         ];

//                         // Check if the measure exists
//                         $existingMeasure = $measureModel->find($measure['id']);

//                         if ($existingMeasure) {
//                             // Update if the measure exists and has changes
//                             $measureModel->update($measure['id'], $measureData);
//                             echo "Updated measure: " . $measure['name'] . "\n";
//                         } else {
//                             if($measure['id'] == 61 || $measure['id'] == 62 || $measure['id'] == 63) {
//                                 continue;
//                             }
//                             // Insert new measure
//                             $measureModel->insert($measureData);
//                             echo "Inserted new measure: " . $measure['name'] . "\n";
//                         }
//                     }
//                 } else {
//                     echo "No measures data found in the API response.\n";
//                 }

//                 // Fetch next page if available
//                 $externalApiUrl = $data['metadata']['page']['next'] ?? null;

//             } while ($externalApiUrl);

//             echo "Measures seeding completed.\n";
//         } catch (\Exception $e) {
//             echo "Error occurred: " . $e->getMessage() . "\n";
//         }
//     }
// }
