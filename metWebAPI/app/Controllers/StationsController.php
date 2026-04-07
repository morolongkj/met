<?php
namespace App\Controllers;

use App\Models\StationMeasureModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class StationsController extends ResourceController
{

    protected $modelName = 'App\Models\StationModel';
    protected $format    = 'json';

    // Reference to the model
    protected $stationMeasureModel;

    public function __construct()
    {
        $this->stationMeasureModel = new StationMeasureModel();
    }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    // public function index()
    // {
    //     // Retrieve query parameters
    //     $page      = $this->request->getVar('page') ?? 1;
    //     $perPage   = $this->request->getVar('perPage') ?? 'all'; // Default to 'all' if not provided
    //     $filters   = $this->request->getGet();
    //     $sortField = $this->request->getVar('sortField') ?? 'created_at'; // Default sort field
    //     $sortOrder = $this->request->getVar('sortOrder') ?? 'DESC';       // Default sort order

    //     $where = [];
    //     if (isset($filters['station_id'])) {
    //         $where['id'] = $filters['station_id'];
    //     }
    //     if (isset($filters['status'])) {
    //         $where['status'] = $filters['status'];
    //     }

    //     if (isset($filters['name'])) {
    //         $where['name'] = $filters['name'];
    //     }

    //     $distanceField = null; // Initialize with null
    //     $latitude      = null;
    //     $longitude     = null;

    //     // Handle closest station based on latitude and longitude
    //     if (isset($filters['latitude']) && isset($filters['longitude'])) {
    //         $latitude  = $filters['latitude'];
    //         $longitude = $filters['longitude'];

    //         // Define the distance calculation formula
    //         $distanceField = "(6371 * acos(
    //         cos(radians($latitude)) * cos(radians(lat)) *
    //         cos(radians(lng) - radians($longitude)) +
    //         sin(radians($latitude)) * sin(radians(lat))
    //     ))";
    //         $sortField = 'distance'; // Use distance for sorting
    //         $sortOrder = 'ASC';
    //     }

    //     // Determine pagination behavior
    //     if ($perPage === 'all') {
    //         // Fetch all stations without pagination
    //         $query = $this->model->select("id, name, lat, lng, status, created_at, updated_at");
    //         if ($distanceField) {
    //             $query->select("$distanceField AS distance");
    //         }
    //         $stations = $query
    //             ->where($where)
    //             ->orderBy($sortField, $sortOrder)
    //             ->findAll();
    //         $totalStations = count($stations);
    //     } else {
    //         // Paginate with specified perPage
    //         $query = $this->model->select("id, name, lat, lng, status, created_at, updated_at");
    //         if ($distanceField) {
    //             $query->select("$distanceField AS distance");
    //         }
    //         $totalStations = $query
    //             ->where($where)
    //             ->orderBy($sortField, $sortOrder)
    //             ->countAllResults(false);

    //         $stations = $query
    //             ->where($where)
    //             ->orderBy($sortField, $sortOrder)
    //             ->paginate((int) $perPage, 'stations', (int) $page);
    //     }

    //     foreach ($stations as &$station) {
    //         $station['measures'] = $this->stationMeasureModel->getMeasuresByStationId($station['id']);
    //     }

    //     // Prepare response data
    //     $data = [
    //         'status' => true,
    //         'data'   => [
    //             'stations'   => $stations,
    //             'pagination' => $perPage === 'all' ? null : [
    //                 'currentPage'   => $page,
    //                 'perPage'       => $perPage,
    //                 'totalPages'    => ceil($totalStations / $perPage),
    //                 'totalStations' => $totalStations,
    //             ],
    //         ],
    //     ];

    //     return $this->respond($data);
    // }

    public function index()
    {
        // Retrieve query parameters
        $page      = $this->request->getVar('page') ?? 1;
        $perPage   = $this->request->getVar('perPage') ?? 'all'; // Default to 'all' if not provided
        $filters   = $this->request->getGet();
        $sortField = $this->request->getVar('sortField') ?? 'created_at'; // Default sort field
        $sortOrder = $this->request->getVar('sortOrder') ?? 'DESC';       // Default sort order

        // Initialize query builder
        $query = $this->model->select("id, name, lat, lng, status, created_at, updated_at");

        // Apply exact matches for station_id and status
        if (isset($filters['station_id'])) {
            $query->where('id', $filters['station_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply LIKE filter for name
        if (isset($filters['name'])) {
            $query->like('name', $filters['name']);
        }

        // Handle closest station based on latitude and longitude
        $distanceField = null;
        if (isset($filters['latitude']) && isset($filters['longitude'])) {
            $latitude  = $filters['latitude'];
            $longitude = $filters['longitude'];

            // Define the distance calculation formula
            $distanceField = "(6371 * acos(
            cos(radians($latitude)) * cos(radians(lat)) *
            cos(radians(lng) - radians($longitude)) +
            sin(radians($latitude)) * sin(radians(lat))
        ))";

            $query->select("$distanceField AS distance");
            $sortField = 'distance'; // Use distance for sorting
            $sortOrder = 'ASC';
        }

        // Determine pagination behavior
        if ($perPage === 'all') {
            $stations      = $query->orderBy($sortField, $sortOrder)->findAll();
            $totalStations = count($stations);
        } else {
            $totalStations = $query->orderBy($sortField, $sortOrder)->countAllResults(false);

            $stations = $query
                ->orderBy($sortField, $sortOrder)
                ->paginate((int) $perPage, 'stations', (int) $page);
        }

        // Fetch measures for each station
        foreach ($stations as &$station) {
            $station['measures'] = $this->stationMeasureModel->getMeasuresByStationId($station['id']);
        }

        // Prepare response data
        $data = [
            'status' => true,
            'data'   => [
                'stations'   => $stations,
                'pagination' => $perPage === 'all' ? null : [
                    'currentPage'   => $page,
                    'perPage'       => $perPage,
                    'totalPages'    => ceil($totalStations / $perPage),
                    'totalStations' => $totalStations,
                ],
            ],
        ];

        return $this->respond($data);
    }

//     public function index()
//     {
//         // Retrieve query parameters
//         $page      = $this->request->getVar('page') ?? 1;
//         $perPage   = $this->request->getVar('perPage') ?? 'all'; // Default to 'all' if not provided
//         $filters   = $this->request->getGet();
//         $sortField = $this->request->getVar('sortField') ?? 'created_at'; // Default sort field
//         $sortOrder = $this->request->getVar('sortOrder') ?? 'DESC';       // Default sort order

//         $where = [];
//         if (isset($filters['station_id'])) {
//             $where['station_id'] = $filters['station_id'];

//         }
//         if (isset($filters['measure_id'])) {
//             $where['measure_id'] = $filters['measure_id'];

//         }

//         // Handle closest station based on latitude and longitude
//         if (isset($filters['latitude']) && isset($filters['longitude'])) {
//             $latitude  = $filters['latitude'];
//             $longitude = $filters['longitude'];
//         }

// // Determine pagination behavior
//         if ($perPage === 'all') {
//             // Fetch all stations without pagination
//             $stations = $this->model
//                 ->where($where)
//                 ->orderBy($sortField, $sortOrder)
//                 ->findAll();
//             $totalStations = count($stations);
//         } else {
//             // Paginate with specified perPage
//             $totalStations = $this->model->where($where)->countAllResults(false);
//             $stations      = $this->model
//                 ->where($where)
//                 ->orderBy($sortField, $sortOrder)
//                 ->paginate((int) $perPage, 'stations', (int) $page);
//         }

//         foreach ($stations as &$station) {
//             $station['measures'] = $this->stationMeasureModel->getMeasuresByStationId($station['id']);
//         }

// // Prepare response data
//         $data = [
//             'status' => true,
//             'data'   => [
//                 'stations' => $stations,
//                 'pagination'   => $perPage === 'all' ? null : [
//                     'currentPage'       => $page,
//                     'perPage'           => $perPage,
//                     'totalPages'        => ceil($totalStations / $perPage),
//                     'totalStations' => $totalStations,
//                 ],
//             ],
//         ];

//         return $this->respond($data);

//     }

    /**
     * Return the properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties.
     *
     * @return ResponseInterface
     */
    public function new ()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        //
    }
}
