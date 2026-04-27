<?php

namespace App\Repositories;

use App\Models\Department;
use App\Models\Symptom;
use Illuminate\Support\Facades\DB;

class DepartmentRepository
{
    /**
     * Get departments with optional symptom filtering.
     */
    public function getDepartmentsWithSymptoms(array $params = [])
    {
        $symptomId = $params['symptom_id'] ?? null;
        $symptomName = $params['symptom_name'] ?? null;
        $limit = $params['limit'] ?? null;

        $query = Department::query()
            ->select('id', 'name', 'slug', 'description', 'symptom_ids', 'created_at');

        if (!$symptomId && $symptomName) {
            $symptomId = Symptom::where('name', 'like', '%' . $symptomName . '%')
                ->value('id');
            
            if (!$symptomId) return collect();
        }

        if ($symptomId) {
            $query->whereJsonContains('symptom_ids', $symptomId);
        }

        $query->orderBy('name');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get symptoms map for a collection of departments.
     */
    public function getSymptomsMap($departments)
    {
        $allSymptomIds = $departments->pluck('symptom_ids')->flatten()->unique()->filter();
        return Symptom::whereIn('id', $allSymptomIds)->get()->keyBy('id');
    }
}
