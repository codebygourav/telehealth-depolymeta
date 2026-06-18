<?php

namespace App\Filament\Resources\PatientDietPlans\Pages;

use App\Filament\Resources\PatientDietPlans\PatientDietPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListPatientDietPlans extends ListRecords
{
    protected static string $resource = PatientDietPlanResource::class;
}
