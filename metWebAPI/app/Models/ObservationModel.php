<?php

namespace App\Models;

use CodeIgniter\Model;

class ObservationModel extends Model
{
    protected $table = 'observations';
    protected $primaryKey = 'id';
   protected $useSoftDeletes   = true;
    protected $allowedFields = [
        'station_id',
        'measure_id',
        'record_date',
        'insert_date',
        'raw_data',
        'val_data',
        'min_data',
        'max_data',
        'min_minute',
        'max_minute',
        'error_code',
        'val_code',
        'status',
        'raw_data_str',
        'val_data_str',
        'min_data_str',
        'max_data_str',
        'min_minute_str',
        'max_minute_str',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Fetch observations by station and measure ID.
     *
     * @param int $stationId
     * @param int $measureId
     * @return array
     */
    public function getObservationsByStationAndMeasure(int $stationId, int $measureId): array
    {
        return $this->where('station_id', $stationId)
            ->where('measure_id', $measureId)
            ->orderBy('record_date', 'DESC')
            ->findAll();
    }

    /**
     * Insert or update an observation based on unique keys.
     *
     * @param array $data
     * @return bool
     */
    public function saveOrUpdate(array $data): bool
    {
        $existing = $this->where('station_id', $data['station_id'])
            ->where('measure_id', $data['measure_id'])
            ->where('record_date', $data['record_date'])
            ->first();

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return $this->insert($data) !== false;
    }
}
