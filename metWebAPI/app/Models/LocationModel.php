<?php
namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table      = 'locations';
    protected $primaryKey = 'id';

    protected $allowedFields = ['place', 'latitude', 'longitude', 'created_at', 'updated_at', 'deleted_at'];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
}
