<?php
namespace App\Database\Seeds;

use App\Services\PolarisClient;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Seeder;

class StationSeeder extends Seeder
{
    /** Lower this (e.g., 200) if debugging row issues */
    private int $batchSize = 1000;

    public function run()
    {
        $client = new PolarisClient();
        $db     = \Config\Database::connect();

        // Endpoint name per your original working code
        $resource = 'stations';

        // Optional incremental sync date: set with --since=YYYY-MM-DD on seeder:run
        $since = env('POLARIS_SINCE');
        $query = array_filter(['since' => $since], fn($v) => $v !== null && $v !== '');

        $table = 'stations';
        $cols  = $this->getExistingColumns($db, $table);
        if (! in_array('id', $cols, true)) {
            throw new \RuntimeException("Table `$table` must have an `id` column (unique/primary).");
        }

        $haveCreatedAt = in_array('created_at', $cols, true);
        $haveUpdatedAt = in_array('updated_at', $cols, true);

        $batch = [];
        $now   = date('Y-m-d H:i:s');

        $db->transStart();

        try {
            foreach ($client->paginate($resource, $query) as $items) {
                foreach ($items as $s) {
                    $lat = $s['coordinates']['lat'] ?? $s['lat'] ?? null;
                    $lng = $s['coordinates']['lng'] ?? $s['lng'] ?? null;
                    $alt = $s['altitude'] ?? $s['elevation'] ?? null;

                    $row = [
                        'id'       => $s['id'] ?? null,
                        'name'     => $s['name'] ?? null,
                        'lat'      => $lat,
                        'lng'      => $lng,
                        'altitude' => $alt,
                        'state'    => $s['state'] ?? ($s['status'] ?? null),
                    ];

                    if ($haveCreatedAt && $haveUpdatedAt) {
                        // For inserts; ON DUPLICATE will refresh updated_at
                        $row['created_at'] = $now;
                        $row['updated_at'] = $now;
                    }

                    // keep only columns that truly exist in the table
                    $batch[] = array_intersect_key($row, array_flip($cols));

                    if (count($batch) >= $this->batchSize) {
                        $this->upsertBatch($db, $table, array_keys($batch[0]), $batch, $haveUpdatedAt);
                        $batch = [];
                    }
                }
            }

            if ($batch) {
                $this->upsertBatch($db, $table, array_keys($batch[0]), $batch, $haveUpdatedAt);
            }
        } catch (\Throwable $e) {
            $err  = $db->error();
            $last = method_exists($db, 'getLastQuery') && $db->getLastQuery()
            ? (string) $db->getLastQuery() : '';
            log_message('error', 'Station upsert failed: {msg} | DB: {code} {dberr} | LastQuery: {q}', [
                'msg'   => $e->getMessage(),
                'code'  => $err['code'] ?? 'n/a',
                'dberr' => $err['message'] ?? 'n/a',
                'q'     => $last,
            ]);
            throw $e;
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            $err  = $db->error();
            $code = $err['code'] ?? 'n/a';
            $msg  = $err['message'] ?? 'Unknown DB error';
            throw new \RuntimeException("Station seeding failed; DB error {$code}: {$msg}");
        }

        CLI::write('Stations seeding completed.', 'green');
    }

    /** Fetch actual table columns so we only write what exists */
    private function getExistingColumns(ConnectionInterface $db, string $table): array
    {
        try {
            return $db->getFieldNames($table) ?: [];
        } catch (\Throwable $e) {
            throw new \RuntimeException("Unable to read columns for `$table`: " . $e->getMessage());
        }
    }

    /**
     * Fast MySQL upsert:
     * INSERT (...) VALUES (...),(...),... ON DUPLICATE KEY UPDATE col=VALUES(col),...
     * If updated_at exists, bump it to CURRENT_TIMESTAMP on updates.
     */
    private function upsertBatch(
        ConnectionInterface $db,
        string $table,
        array $cols,
        array $rows,
        bool $haveUpdatedAt
    ): void {
        if (! $rows) {
            return;
        }

        $placeholders = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';

        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES ';
        $sql .= implode(',', array_fill(0, count($rows), $placeholders));

        $updates = [];
        foreach ($cols as $c) {
            if ($c === 'id' || $c === 'created_at') {
                continue;
            }
            // don't overwrite PK or created_at
            $updates[] = "`$c`=VALUES(`$c`)";
        }
        // Prefer DB-driven timestamp on update
        if ($haveUpdatedAt && ! in_array('`updated_at`=VALUES(`updated_at`)', $updates, true)) {
            $updates[] = "`updated_at`=CURRENT_TIMESTAMP";
        }

        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $updates);

        $bind = [];
        foreach ($rows as $r) {
            foreach ($cols as $c) {
                $bind[] = $r[$c] ?? null;
            }
        }

        if (! $db->query($sql, $bind)) {
            $err = $db->error();
            throw new \RuntimeException("DB upsert error {$err['code']}: {$err['message']}");
        }
    }
}


// namespace App\Database\Seeds;

// use CodeIgniter\Database\Seeder;

// class StationSeeder extends Seeder
// {
//     public function run()
//     {
//         $externalApiUrl = 'https://41.203.191.15/api/polaris/stations?limit=-1&page=1&api_token=dad1505aec32a71941f146fd5aaf9081';

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
//                 $stations = $data['items'];

//                 foreach ($stations as $station) {
//                     // Prepare the station data
//                     $stationData = [
//                         'id' => $station['id'],
//                         'name' => $station['name'] ?? '',
//                         'lat' => $station['coordinates']['lat'] ?? null,
//                         'lng' => $station['coordinates']['lng'] ?? null,
//                         'altitude' => $station['altitude'] ?? null,
//                         'state' => $station['state'] ?? '',
//                         'updated_at' => date('Y-m-d H:i:s'),
//                     ];

//                     // Check if the station already exists
//                     $existingStation = $stationTable->where('id', $station['id'])->get()->getRow();

//                     if ($existingStation) {
//                         // Update only if any field has changed
//                         $changes = array_diff_assoc($stationData, (array) $existingStation);
//                         unset($changes['updated_at']); // Ignore the updated_at field for comparison

//                         if (!empty($changes)) {
//                             $stationTable->where('id', $station['id'])->update($stationData);
//                             echo "Updated station ID: " . $station['id'] . "\n";
//                         } else {
//                             echo "No changes for station ID: " . $station['id'] . "\n";
//                         }
//                     } else {
//                         // Insert new station
//                         $stationData['created_at'] = date('Y-m-d H:i:s');
//                         $stationTable->insert($stationData);
//                         echo "Inserted new station ID: " . $station['id'] . "\n";
//                     }
//                 }

//                 echo "Seeding completed.\n";
//             } else {
//                 echo "No stations data found in the API response.\n";
//             }

//         } catch (\Exception $e) {
//             echo "Error occurred: " . $e->getMessage() . "\n";
//         }
//     }
// }
