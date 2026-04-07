<?php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'Maseru'],
            ['name' => 'Butha-Buthe'],
            ['name' => 'Leribe'],
            ['name' => 'Mafeteng'],
            ['name' => 'Mohale\'s Hoek'],
            ['name' => 'Mokhotlong'],
            ['name' => 'Thaba-Tseka'],
            ['name' => 'Quthing'],
            ['name' => 'Qacha\'s Nek'],
            ['name' => 'Teyateyaneng'],
        ];

        // Get existing district names
        $existing = $this->db->table('districts')->select('name')->get()->getResultArray();
        $existingNames = array_column($existing, 'name');

        // Filter out existing districts
        $newData = array_filter($data, function($district) use ($existingNames) {
            return !in_array($district['name'], $existingNames);
        });

        // Insert only new districts
        if (!empty($newData)) {
            $this->db->table('districts')->insertBatch($newData);
        }
    }
}
