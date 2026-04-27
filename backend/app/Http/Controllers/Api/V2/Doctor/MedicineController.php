<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Services\ApiResponseService;
use App\Http\Resources\Doctor\MedicineResource;

class MedicineController extends Controller
{
    public function index()
    {
        $medicines = Medicine::with(['category', 'type'])->paginate(5);
        $medicines->setCollection(
            MedicineResource::collection($medicines->getCollection())->collection
        );

        return ApiResponseService::paginated($medicines, responseKey: 'responses.success');
    }
}