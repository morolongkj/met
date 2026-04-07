<?php
namespace App\Models;

use CodeIgniter\Model;

class DailyForecastModel extends Model
{
    protected $table      = 'daily_forecast';
    protected $primaryKey = 'id';

    protected $allowedFields = ['location_id', 'date', 'min_temperature', 'max_temperature', 'humidity', 'wind_speed', 'weather', 'created_at', 'updated_at', 'deleted_at'];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    public function getForecastByLocation($location_id)
    {
        return $this->where('location_id', $location_id)->findAll();
    }
}
