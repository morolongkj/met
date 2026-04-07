<?php
namespace App\Controllers;

use App\Models\MeasureModel;
use App\Models\StationModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ObservationsController extends ResourceController
{
    protected $modelName = 'App\Models\ObservationModel';
    protected $format    = 'json';

    // Reference to the model
    protected $stationModel;
    protected $measureModel;

    public function __construct()
    {
        $this->stationModel = new StationModel();
        $this->measureModel = new MeasureModel();
    }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function index()
    {
        // Retrieve query parameters
        $page      = $this->request->getVar('page') ?? 1;
        $perPage   = $this->request->getVar('perPage') ?? 'all';
        $filters   = $this->request->getGet();
        $sortField = $this->request->getVar('sortField') ?? 'created_at';
        $sortOrder = $this->request->getVar('sortOrder') ?? 'DESC';

        $where             = [];
        $observationsCount = 0;

        // Apply station_id and measure_id filters if provided
        if (isset($filters['station_id'])) {
            $where['station_id'] = $filters['station_id'];
        }
        if (isset($filters['measure_id'])) {
            $where['measure_id'] = $filters['measure_id'];
        }

        // Get timestamp for 10 minutes ago (ensure correct timezone)
        $tenMinutesAgo = date('Y-m-d H:i:s', strtotime('-10 minutes'));

        // Ensure filtering by recent data (last 10 min) is always applied
        $where['record_date >='] = $tenMinutesAgo;

        // Handle closest station based on latitude and longitude, but only if station_id is NOT provided
        if (! isset($filters['station_id']) && isset($filters['latitude']) && isset($filters['longitude'])) {
            $latitude  = $filters['latitude'];
            $longitude = $filters['longitude'];

            // Get all stations ordered by distance
            $stations = $this->stationModel
                ->select("id, name, lat, lng, status,
            (6371 * acos(
                cos(radians($latitude)) * cos(radians(lat)) *
                cos(radians(lng) - radians($longitude)) +
                sin(radians($latitude)) * sin(radians(lat))
            )) AS distance")
                ->having('distance <=', 30)
                ->orderBy('distance', 'ASC')
                ->findAll();

            // Iterate through stations to find the first one with observations
            foreach ($stations as $station) {
                $tempWhere               = $where; // Preserve original filters
                $tempWhere['station_id'] = $station['id'];

                // Check if there are observations for this station within the last 10 minutes
                $observationsCount = $this->model->where($tempWhere)->countAllResults(true);

                if ($observationsCount > 0 && $station['status'] == 'N') {
                    $where['station_id'] = $station['id']; // Use first station with data
                    break;
                }
            }

            // If no nearby station has observations, return an error
            if ($observationsCount === 0) {
                return $this->respond([
                    'status'  => false,
                    'message' => 'No observations found for any nearby stations within the last 10 minutes.',
                    'data'    => [
                        'observations' => [],
                        'pagination'   => null,
                    ],
                ]);
            }
        }

        // Determine pagination behavior
        if ($perPage === 'all') {
            // Fetch all observations within last 10 minutes without pagination
            $observations = $this->model
                ->where($where)
                ->orderBy($sortField, $sortOrder)
                ->findAll();
            $totalObservations = count($observations);
        } else {
            // Paginate with specified perPage
            $totalObservations = $this->model->where($where)->countAllResults(false);
            $observations      = $this->model
                ->where($where)
                ->orderBy($sortField, $sortOrder)
                ->paginate((int) $perPage, 'observations', (int) $page);
        }

        // Attach station and measure details to each observation
        foreach ($observations as &$observation) {
            $observation['station'] = $this->stationModel->find($observation['station_id']);
            $observation['measure'] = $this->measureModel->find($observation['measure_id']);
        }

        // Prepare response data
        $data = [
            'status' => true,
            'data'   => [
                'observations' => $observations,
                'pagination'   => $perPage === 'all' ? null : [
                    'currentPage'       => $page,
                    'perPage'           => $perPage,
                    'totalPages'        => ceil($totalObservations / $perPage),
                    'totalObservations' => $totalObservations,
                ],
            ],
        ];

        return $this->respond($data);
    }

    // public function index()
    // {
    //     // Retrieve query parameters
    //     $page      = $this->request->getVar('page') ?? 1;
    //     $perPage   = $this->request->getVar('perPage') ?? 'all'; // Default to 'all' if not provided
    //     $filters   = $this->request->getGet();
    //     $sortField = $this->request->getVar('sortField') ?? 'created_at'; // Default sort field
    //     $sortOrder = $this->request->getVar('sortOrder') ?? 'DESC';       // Default sort order

    //     $where = [];

    //     // Apply station_id and measure_id filters if provided
    //     if (isset($filters['station_id'])) {
    //         $where['station_id'] = $filters['station_id'];
    //     }
    //     if (isset($filters['measure_id'])) {
    //         $where['measure_id'] = $filters['measure_id'];
    //     }

    //     // Get timestamp for 10 minutes ago (ensure correct timezone)
    //     $tenMinutesAgo = date('Y-m-d H:i:s', strtotime('-10 minutes'));

    //     // Ensure filtering by recent data (last 10 min)
    //     $this->model->where('record_date >=', $tenMinutesAgo);

    //     // Handle closest station based on latitude and longitude, but only if station_id is NOT provided
    //     if (! isset($filters['station_id']) && isset($filters['latitude']) && isset($filters['longitude'])) {
    //         $latitude  = $filters['latitude'];
    //         $longitude = $filters['longitude'];

    //         // Get all stations ordered by distance
    //         $stations = $this->stationModel
    //             ->select("id, name, lat, lng, status,
    //         (6371 * acos(
    //             cos(radians($latitude)) * cos(radians(lat)) *
    //             cos(radians(lng) - radians($longitude)) +
    //             sin(radians($latitude)) * sin(radians(lat))
    //         )) AS distance")
    //             ->orderBy('distance', 'ASC')
    //             ->findAll();

    //         // Iterate through stations to find the first one with observations
    //         foreach ($stations as $station) {
    //             $tempWhere               = $where; // Preserve original filters
    //             $tempWhere['station_id'] = $station['id'];

    //             // Check if there are observations for this station
    //             $observationsCount = $this->model->where($tempWhere)->countAllResults(true);

    //             if ($observationsCount > 0 && $station['status'] == 'N') {
    //                 $where['station_id'] = $station['id']; // Use first station with data
    //                 break;
    //             }
    //         }

    //         // If no nearby station has observations, return an error
    //         if ($observationsCount === 0) {
    //             return $this->respond([
    //                 'status'  => false,
    //                 'message' => 'No observations found for any nearby stations.',
    //             ]);
    //         }
    //     }

    //     // Determine pagination behavior
    //     if ($perPage === 'all') {
    //         // Fetch all observations within last 10 minutes without pagination
    //         $observations = $this->model
    //             ->where($where)
    //             ->orderBy($sortField, $sortOrder)
    //             ->findAll();
    //         $totalObservations = count($observations);
    //     } else {
    //         // Paginate with specified perPage
    //         $totalObservations = $this->model->where($where)->countAllResults(false);
    //         $observations      = $this->model
    //             ->where($where)
    //             ->orderBy($sortField, $sortOrder)
    //             ->paginate((int) $perPage, 'observations', (int) $page);
    //     }

    //     // Attach station and measure details to each observation
    //     foreach ($observations as &$observation) {
    //         $observation['station'] = $this->stationModel->find($observation['station_id']);
    //         $observation['measure'] = $this->measureModel->find($observation['measure_id']);
    //     }

    //     // Prepare response data
    //     $data = [
    //         'status' => true,
    //         'data'   => [
    //             'observations' => $observations,
    //             'pagination'   => $perPage === 'all' ? null : [
    //                 'currentPage'       => $page,
    //                 'perPage'           => $perPage,
    //                 'totalPages'        => ceil($totalObservations / $perPage),
    //                 'totalObservations' => $totalObservations,
    //             ],
    //         ],
    //     ];

    //     return $this->respond($data);
    // }

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

//         // Get timestamp for 10 minutes ago
//         $tenMinutesAgo = date('Y-m-d H:i:s', strtotime('-10 minutes'));

// // Add time filter
//         $this->model->where('record_date >=', $tenMinutesAgo);

//         // Handle closest station based on latitude and longitude
//         if (isset($filters['latitude']) && isset($filters['longitude'])) {
//             $latitude  = $filters['latitude'];
//             $longitude = $filters['longitude'];

//             // Get all stations ordered by distance
//             $stations = $this->stationModel
//                 ->select("id, name, lat, lng, status,
//             (6371 * acos(
//                 cos(radians($latitude)) * cos(radians(lat)) *
//                 cos(radians(lng) - radians($longitude)) +
//                 sin(radians($latitude)) * sin(radians(lat))
//             )) AS distance")
//                 ->orderBy('distance', 'ASC')
//                 ->findAll();

//             // Iterate through stations to find the first one with observations
//             foreach ($stations as $station) {
//                 $where['station_id'] = $station['id'];

//                 // Check if there are observations for the station
//                 $observationsCount = $this->model->where($where)->countAllResults(true);

//                 if ($observationsCount > 0 && $station['status'] == 'N') {
//                     break; // Exit loop when a station with observations is found
//                 }
//             }

//             // If no station with observations is found, respond with an error
//             if ($observationsCount === 0) {
//                 return $this->respond([
//                     'status'  => false,
//                     'message' => 'No observations found for any nearby stations.',
//                 ]);
//             }
//         }

// // Determine pagination behavior
//         if ($perPage === 'all') {
//             // Fetch all observations without pagination
//             $observations = $this->model
//                 ->where($where)
//                 ->orderBy($sortField, $sortOrder)
//                 ->findAll();
//             $totalObservations = count($observations);
//         } else {
//             // Paginate with specified perPage
//             $totalObservations = $this->model->where($where)->countAllResults(false);
//             $observations      = $this->model
//                 ->where($where)
//                 ->orderBy($sortField, $sortOrder)
//                 ->paginate((int) $perPage, 'observations', (int) $page);
//         }

//         foreach ($observations as &$observation) {
//             $observation['station'] = $this->stationModel->find($observation['station_id']);
//             $observation['measure'] = $this->measureModel->find($observation['measure_id']);
//         }

// // Prepare response data
//         $data = [
//             'status' => true,
//             'data'   => [
//                 'observations' => $observations,
//                 'pagination'   => $perPage === 'all' ? null : [
//                     'currentPage'       => $page,
//                     'perPage'           => $perPage,
//                     'totalPages'        => ceil($totalObservations / $perPage),
//                     'totalObservations' => $totalObservations,
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
