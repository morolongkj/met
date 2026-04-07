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

        // Using Query Builder to insert data
        $this->db->table('districts')->insertBatch($data);
    }
}
