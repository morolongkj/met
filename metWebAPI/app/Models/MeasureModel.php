<?php
namespace App\Models;

use CodeIgniter\Model;

class MeasureModel extends Model
{
    protected $table          = 'measures';
    protected $primaryKey     = 'id';
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['id', 'name', 'short_name', 'unit', 'decimals', 'validation', 'thresholds', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps  = true;
    protected $dateFormat     = 'datetime';
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    // Fetch all measures
    public function getAllMeasures()
    {
        return $this->findAll();
    }
}
