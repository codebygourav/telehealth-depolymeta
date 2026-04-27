<?php

namespace App\Http\Controllers\Api\V2\Common\Department;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use App\Enums\DepartmentRole;

class DepartmentController extends Controller
{
    /**
     * Get all departments for selection.
     */
    public function index()
    {
        $departments = Department::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                ];
            })
            ->values();
        $roles = DepartmentRole::Keylabels();

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: [
                'departments' => $departments,
                'roles' => $roles,
            ],
        );
    }
}