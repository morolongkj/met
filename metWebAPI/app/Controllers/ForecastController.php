<?php
namespace App\Controllers;

use App\Models\DailyForecastModel;
use App\Models\DistrictModel;
use App\Models\HourlyForecastModel;
use App\Models\LocationModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ForecastController extends ResourceController
{
    protected $modelName = 'App\Models\ForecastModel';
    protected $format    = 'json';

    // Reference to the model
    protected $districtModel;
    protected $locationModel;
    protected $dailyForecastModel;
    protected $hourlyForecastModel;

    public function __construct()
    {
        $this->districtModel       = new DistrictModel();
        $this->locationModel       = new LocationModel();
        $this->dailyForecastModel  = new DailyForecastModel();
        $this->hourlyForecastModel = new HourlyForecastModel();
    }

    public function getForecast()
    {
   // Get request parameters
$latitude  = $this->request->getGet('latitude');
$longitude = $this->request->getGet('longitude');
$dateFrom  = $this->request->getGet('date_from') ?? date('Y-m-d');
$dateTo    = $this->request->getGet('date_to') ?? date('Y-m-d');


        // Validate required parameters
        if (! $latitude || ! $longitude || ! $dateFrom || ! $dateTo) {
            return $this->fail("Missing required parameters: latitude, longitude, date_from, date_to.");
        }

        // Find the nearest location using the Haversine formula
        $location = $this->locationModel
            ->select("id, place, latitude, longitude,
                 (6371 * acos(cos(radians($latitude)) * cos(radians(latitude))
                 * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude))
                 * sin(radians(latitude)))) AS distance")
            ->orderBy('distance', 'ASC')
            ->first();

        if (! $location) {
            return $this->failNotFound("No nearby location found.");
        }

        // Get daily forecasts within the date range
        $dailyForecasts = $this->dailyForecastModel
            ->where('location_id', $location['id'])
            ->where('date >=', $dateFrom)
            ->where('date <=', $dateTo)
            ->orderBy('date', 'ASC')
            ->findAll();

        if (empty($dailyForecasts)) {
            return $this->failNotFound("No forecast data available for the given date range.");
        }

        $forecastData = [];

        foreach ($dailyForecasts as $daily) {
            // Get hourly forecast for each day
            $hourlyForecasts = $this->hourlyForecastModel
                ->where('daily_forecast_id', $daily['id'])
                ->orderBy('time', 'ASC')
                ->findAll();

            // Format hourly forecast
            $hourlyData = [];
            foreach ($hourlyForecasts as $hourly) {
                $hourlyData[] = [
                    'time'        => $hourly['time'],
                    'temperature' => (float) $hourly['temperature'],
                    'humidity'    => (int) $hourly['humidity'],
                    'wind_speed'  => (float) $hourly['wind_speed'],
                    'weather'     => $hourly['weather'],
                ];
            }

            // Format daily forecast
            $forecastData[] = [
                'date'        => $daily['date'],
                'temperature' => [
                    'min' => (float) $daily['min_temperature'],
                    'max' => (float) $daily['max_temperature'],
                ],
                'humidity'    => (int) $daily['humidity'],
                'wind_speed'  => (float) $daily['wind_speed'],
                'weather'     => $daily['weather'],
                'hourly'      => $hourlyData,
            ];
        }

        // Final API Response
        $response = [
            'location' => [
                'place'     => $location['place'],
                'latitude'  => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
            ],
            'forecast' => $forecastData,
            'unit'     => [
                'temperature' => "Celsius",
                'wind_speed'  => "km/h",
                'humidity'    => "%",
            ],
        ];

        return $this->respond($response);
    }

    // public function getForecast()
    // {
    //     // Get request parameters
    //     $latitude = $this->request->getGet('latitude');
    //     $longitude = $this->request->getGet('longitude');
    //     $dateFrom = $this->request->getGet('date_from');
    //     $dateTo = $this->request->getGet('date_to');

    //     // Validate required parameters
    //     if (!$latitude || !$longitude || !$dateFrom || !$dateTo) {
    //         return $this->fail("Missing required parameters: latitude, longitude, date_from, date_to.");
    //     }

    //     // Find location by latitude and longitude
    //     $location = $this->locationModel->where(['latitude' => $latitude, 'longitude' => $longitude])->first();

    //     if (!$location) {
    //         return $this->failNotFound("Location not found.");
    //     }

    //     // Get daily forecasts within the date range
    //     $dailyForecasts = $this->dailyForecastModel
    //         ->where('location_id', $location['id'])
    //         ->where('date >=', $dateFrom)
    //         ->where('date <=', $dateTo)
    //         ->orderBy('date', 'ASC')
    //         ->findAll();

    //     if (empty($dailyForecasts)) {
    //         return $this->failNotFound("No forecast data available for the given date range.");
    //     }

    //     $forecastData = [];

    //     foreach ($dailyForecasts as $daily) {
    //         // Get hourly forecast for each day
    //         $hourlyForecasts = $this->hourlyForecastModel
    //             ->where('daily_forecast_id', $daily['id'])
    //             ->orderBy('time', 'ASC')
    //             ->findAll();

    //         // Format hourly forecast
    //         $hourlyData = [];
    //         foreach ($hourlyForecasts as $hourly) {
    //             $hourlyData[] = [
    //                 'time' => $hourly['time'],
    //                 'temperature' => (float) $hourly['temperature'],
    //                 'humidity' => (int) $hourly['humidity'],
    //                 'wind_speed' => (float) $hourly['wind_speed'],
    //                 'weather' => $hourly['weather'],
    //             ];
    //         }

    //         // Format daily forecast
    //         $forecastData[] = [
    //             'date' => $daily['date'],
    //             'temperature' => [
    //                 'min' => (float) $daily['min_temperature'],
    //                 'max' => (float) $daily['max_temperature']
    //             ],
    //             'humidity' => (int) $daily['humidity'],
    //             'wind_speed' => (float) $daily['wind_speed'],
    //             'weather' => $daily['weather'],
    //             'hourly' => $hourlyData
    //         ];
    //     }

    //     // Final API Response
    //     $response = [
    //         'location' => [
    //             'place' => $location['place'],
    //             'latitude' => (float) $location['latitude'],
    //             'longitude' => (float) $location['longitude']
    //         ],
    //         'forecast' => $forecastData,
    //         'unit' => [
    //             'temperature' => "Celsius",
    //             'wind_speed' => "km/h",
    //             'humidity' => "%"
    //         ]
    //     ];

    //     return $this->respond($response);
    // }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function index($id = null)
    {
        if ($id !== null) {
            $forecast = $this->model->find($id);
            if (! $forecast) {
                return $this->failNotFound('Forecast not found');
            }

            return $this->respond($forecast, 200);
        }

        $perPage   = $this->request->getGet('perPage') ?? 'all';
        $page      = $this->request->getGet('page') ?? 1;
        $filters   = $this->request->getGet();
        $sortField = $this->request->getGet('sortField') ?? 'created_at';
        $sortOrder = $this->request->getGet('sortOrder') ?? 'desc';

        $builder = $this->model;

        // Apply filters
        if (isset($filters['district_id'])) {
            $builder = $builder->where('district_id', $filters['district_id']);
        }
        if (isset($filters['day'])) {
            // filter by date from [date_for_forecast] field
        }
        if (isset($filters['hour'])) {
            //  filter by date and hour from [date_for_forecast] field
        }
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $builder = $builder->where('date_for_forecast >=', $filters['date_from'])
                ->where('date_for_forecast <=', $filters['date_to']);
        }

        $builder = $builder->orderBy($sortField, $sortOrder);

        if ($perPage === 'all') {
            // Fetch all forecasts without pagination
            $forecasts      = $builder->get()->getResultArray();
            $totalForecasts = count($forecasts);
        } else {
                                                                // Fetch paginated forecasts
            $totalForecasts = $builder->countAllResults(false); // Count without applying limits
            $forecasts      = $builder->limit((int) $perPage, ((int) $page - 1) * (int) $perPage)->get()->getResultArray();
        }

        $data = [
            'status' => true,
            'data'   => [
                'forecasts'  => $forecasts,
                'pagination' => $perPage === 'all' ? null : [
                    'currentPage'    => $page,
                    'perPage'        => $perPage,
                    'totalPages'     => ceil($totalForecasts / $perPage),
                    'totalForecasts' => $totalForecasts,
                ],
            ],
        ];

        return $this->respond($data, 200);
    }

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
