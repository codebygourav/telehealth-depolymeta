<?php

namespace App\Http\Controllers\Api\V2\Vaccination;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vaccination\VaccinationModuleContentResource;
use App\Repositories\VaccinationModuleContentRepository;
use App\Services\ApiResponseService;

class VaccinationModuleContentController extends Controller
{
    public function __construct(
        protected VaccinationModuleContentRepository $contentRepository
    ) {}

    public function index()
    {
        return ApiResponseService::success(
            data: new VaccinationModuleContentResource(
                $this->contentRepository->getOverviewSupplementaryContent()
            )
        );
    }
}
