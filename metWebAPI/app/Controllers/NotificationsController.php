<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class NotificationsController extends ResourceController
{
    protected $modelName = 'App\Models\NotificationModel';
    protected $format    = 'json';

    public function __construct()
    {

    }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function index($id = null)
    {
        if ($id !== null) {
            $notification = $this->model->find($id);
            if (! $notification) {
                return $this->failNotFound('Notification not found');
            }

            return $this->respond($notification, 200);
        }

        $perPage   = $this->request->getGet('perPage') ?? 'all';
        $page      = $this->request->getGet('page') ?? 1;
        $filters   = $this->request->getGet();
        $sortField = $this->request->getGet('sortField') ?? 'created_at';
        $sortOrder = $this->request->getGet('sortOrder') ?? 'desc';

        $builder = $this->model;

        // Apply filter for description using LIKE
        if (! empty($filters['description'])) {
            $builder = $builder->like('description', $filters['description']);
        }

        $builder = $builder->orderBy($sortField, $sortOrder);

        if ($perPage === 'all') {
            // Fetch all notifications without pagination
            $notifications      = $builder->get()->getResultArray();
            $totalNotifications = count($notifications);
        } else {
                                                                    // Fetch paginated notifications
            $totalNotifications = $builder->countAllResults(false); // Count without applying limits
            $notifications      = $builder->limit((int) $perPage, ((int) $page - 1) * (int) $perPage)->get()->getResultArray();
        }

        $data = [
            'status' => true,
            'data'   => [
                'notifications' => $notifications,
                'pagination'    => $perPage === 'all' ? null : [
                    'currentPage'        => $page,
                    'perPage'            => $perPage,
                    'totalPages'         => ceil($totalNotifications / $perPage),
                    'totalNotifications' => $totalNotifications,
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
