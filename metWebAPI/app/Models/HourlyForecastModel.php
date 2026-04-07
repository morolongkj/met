<?php
namespace App\Models;

use CodeIgniter\Model;

class HourlyForecastModel extends Model
{
    protected $table      = 'hourly_forecast';
    protected $primaryKey = 'id';

    protected $allowedFields = ['daily_forecast_id', 'time', 'temperature', 'humidity', 'wind_speed', 'weather', 'created_at', 'updated_at', 'deleted_at'];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    public function getHourlyByDailyForecast($daily_forecast_id)
    {
        return $this->where('daily_forecast_id', $daily_forecast_id)->findAll();
    }
}
