<?php

namespace App\Http\Controllers\Api\V2\Wordpress;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepartmentController extends Controller
{
    /**
     * Get departments with optional limit or pagination.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $perPage = $request->query('per_page', 8);

        $query = Department::select('id', 'slug', 'name', 'description')
            ->where('status', 'active')
            ->orderBy('name');

        if ($limit) {
            $departments = $query->limit($limit)->get();
        } else {
            $departments = $query->paginate($perPage);
        }

        $formattedDepartments = $this->formatDepartments($limit ? $departments : $departments->items());

        $data = [
            'departments' => $formattedDepartments,
        ];

        if (!$limit) {
            $data['pagination'] = [
                'total' => $departments->total(),
                'per_page' => $departments->perPage(),
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
            ];
        }

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: $data
        );
    }

    /**
     * Get single department details.
     */
    public function show($slug, Request $request)
    {
        $viewType = $request->query('view_type', 'simple'); // simple or tab

        $department = Department::where('slug', $slug)
            ->where('status', 'active')
            ->with(['doctors' => function ($query) {
                $query->withoutTestDoctors()
                    ->visibleInWordPressApi()
                    ->with('user');
            }])
            ->first();

        if (!$department) {
            return ApiResponseService::error('Department not found', ['message' => 'Department not found'], 404);
        }

        $otherDepartments = Department::select('id', 'slug', 'name', 'description')
            ->where('slug', '!=', $department->slug)
            ->where('status', 'active')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $formattedOtherDepartments = $this->formatDepartments($otherDepartments);

        if ($viewType === 'tab' || $department->is_tab_layout) {
            return $this->tabView($department, $formattedOtherDepartments);
        }

        return $this->simpleView($department, $formattedOtherDepartments);
    }

    protected function formatDepartments($departments)
    {
        return collect($departments)->map(function ($department) {
            return [
                'id' => $department->id,
                'slug'=> $department->slug,
                'name' => $department->name,
                'description' => $department->description,
                'featured_image' => storage_url($department->department_featured),
            ];
        })->values();
    }

    protected function simpleView($department, $otherDepartments = [])
    {
        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: [
                'view' => 'simple',
                'type' => 'simple',
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'featured_image' => storage_url($department->department_featured),
                'simple_view' => [
                    'additional_information' => $department->additional_information,
                    'faqs' => $department->faqs,
                    'publications' => $department->publications,
                ],
                'doctors' => $department->doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name ?? ($doctor->user?->name ?? trim($doctor->first_name . ' ' . $doctor->last_name)),
                        'experience' => $doctor->years_experience > 1 ? $doctor->years_experience . ' years' : $doctor->years_experience . ' year',
                        'department_role' => $doctor->pivot->role,
                        'department_order' => $doctor->pivot->order,
                        'image' => $doctor->avatar ? storage_url($doctor->avatar) : ($doctor->user?->avatar ? storage_url($doctor->user->avatar) : null),
                    ];
                }),
                'other_departments' => $otherDepartments
            ]
        );
    }

    protected function tabView($department, $otherDepartments = [])
    {
        $department->load('tabs');

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: [
                'view' => 'tab',
                'type' => 'tab',
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'featured_image' => storage_url($department->department_featured),
                'tabs' => $department->tabs->map(function ($tab) {
                    return [
                        'title' => $tab->tab_title,
                        'content' => $tab->tab_content,
                        'gallery' => collect($tab->tab_gallery)->map(fn($file) => Storage::disk('public')->url($file)),
                    ];
                }),
                'doctors' => $department->doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name ?? ($doctor->user?->name ?? trim($doctor->first_name . ' ' . $doctor->last_name)),
                        'experience' => $doctor->years_experience > 1 ? $doctor->years_experience . ' years' : $doctor->years_experience . ' year',
                        'department_role' => $doctor->pivot->role,
                        'department_order' => $doctor->pivot->order,
                        'image' => storage_url($doctor->avatar),
                    ];
                }),
                'other_departments' => $otherDepartments
            ]
        );
    }
}
