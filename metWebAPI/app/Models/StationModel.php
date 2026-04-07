<?php
namespace App\Models;

use CodeIgniter\Model;

class StationModel extends Model
{
    // Specify the database table
    protected $table = 'stations';

    // Define the primary key
    protected $primaryKey = 'id';

    // Enable the use of timestamps
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $dateFormat     = 'datetime';
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    // Specify allowed fields for mass assignment
    protected $allowedFields = [
        'id', 'name', 'lat', 'lng', 'altitude', 'state', 'status', 'extra_data', 'created_at', 'updated_at', 'deleted_at',
    ];

    // Example custom query method: Get stations by status
    public function getStationsByStatus(string $status)
    {
        return $this->where('status', $status)->findAll();
    }

    // Example custom query method: Get stations within proximity (lat, lng, distance)
    public function getStationsWithinProximity(float $latitude, float $longitude, float $distanceInKm)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $query = $this->select("id, name, lat, lng, altitude, state, status,
            ($earthRadius * ACOS(COS(RADIANS(lat))
            * COS(RADIANS($latitude))
            * COS(RADIANS(lng) - RADIANS($longitude))
            + SIN(RADIANS(lat))
            * SIN(RADIANS($latitude)))) AS distance")
            ->having('distance <=', $distanceInKm)
            ->orderBy('distance', 'ASC');

        return $query->findAll();
    }
}
