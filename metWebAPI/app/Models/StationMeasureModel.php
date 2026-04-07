<?php
namespace App\Models;

use CodeIgniter\Model;

class StationMeasureModel extends Model
{
    protected $table         = 'station_measure';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'station_id',
        'measure_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $dateFormat     = 'datetime';
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    /**
     * Get all measures for a given station ID.
     *
     * @param int $stationId The ID of the station.
     * @return array An array of measures associated with the station.
     */
    // public function getMeasuresByStationId(int $stationId): array
    // {
    //     return $this->select('measures.id, measures.name, measures.short_name, measures.unit, measures.decimals')
    //         ->join('measures', 'measures.id = station_measure.measure_id') // Join with the measures table
    //         ->where('station_measure.station_id', $stationId)              // Filter by station_id
    //         ->findAll();                                                   // Fetch all matching rows
    // }
    /**
     * Get all measures for a given station ID, excluding specific measure IDs.
     *
     * @param int $stationId The ID of the station.
     * @return array An array of measures associated with the station, excluding certain measures.
     */
    public function getMeasuresByStationId(int $stationId): array
    {
        return $this->select('measures.id, measures.name, measures.short_name, measures.unit, measures.decimals')
            ->join('measures', 'measures.id = station_measure.measure_id') // Join with the measures table
            ->where('station_measure.station_id', $stationId)              // Filter by station_id
            ->whereNotIn('measures.id', [61, 62, 63])                      // Exclude measures with IDs 61, 62, and 63
            ->findAll();                                                   // Fetch all matching rows
    }

}
