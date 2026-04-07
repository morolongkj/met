<?php
namespace App\Database\Seeds;

use App\Models\DistrictModel;
use CodeIgniter\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $districtModel = new DistrictModel();
        $filePath      = WRITEPATH . 'uploads/notifications.csv'; // Path to the CSV file
        $notifications = $this->parseCSVFile($filePath);

        foreach ($notifications as $notification) {

            // Insert data into the notifications table
            $this->db->table('notifications')->insert([
                'alert_type'      => $notification['alert_type'],
                'severity'        => $notification['severity'],
                'description'     => $notification['description'],
                'affected_region' => $notification['affected_region'],
            ]);

        }
    }

    /**
     * Parse the CSV file and return notification data.
     *
     * @param string $filePath
     * @return array
     */
    // private function parseCSVFile(string $filePath): array
    // {
    //     $data = [];
    //     if (! file_exists($filePath)) {
    //         throw new \RuntimeException("File not found: {$filePath}");
    //     }

    //     $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    //     foreach ($lines as $line) {
    //         // Example line: Maseru.txt,30,34,16,SUNNY
    //         $parts = explode(',', $line);
    //         if (count($parts) === 4) {
    //             $data[] = [
    //                 'alert_type'      => trim($parts[0]),
    //                 'severity'        => trim($parts[1]),
    //                 'description'     => trim($parts[2]),
    //                 'affected_region' => trim($parts[3]),
    //             ];
    //         }
    //     }

    //     return $data;
    // }

    private function parseCSVFile(string $filePath): array
    {
        $data = [];
        if (! file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open file: {$filePath}");
        }

        // Read and discard the first line (header row)
        fgetcsv($handle);

        while (($parts = fgetcsv($handle)) !== false) {
            if (count($parts) === 4) {
                $data[] = [
                    'alert_type'      => trim($parts[0]),
                    'severity'        => trim($parts[1]),
                    'description'     => trim($parts[2]),
                    'affected_region' => trim($parts[3]),
                ];
            }
        }

        fclose($handle);
        return $data;
    }

}
