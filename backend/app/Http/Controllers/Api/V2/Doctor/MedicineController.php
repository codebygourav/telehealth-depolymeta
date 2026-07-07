<?php

namespace App\Http\Controllers\Api\V2\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorAddedMedicine;
use App\Models\Medicine;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $includeDoctorAdded = $request->boolean('include_doctor_added');
        $doctorId = $request->user()?->doctor?->id
            ?? $request->user()?->doctor_id
            ?? null;

        $inventoryMedicines = Medicine::query()
            ->with(['category:id,name', 'type:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->get()
            ->map(function (Medicine $medicine) {
                return [
                    'id' => $medicine->id,
                    'name' => $medicine->name,
                    'type' => $medicine->type?->name,
                    'category' => $medicine->category?->name,
                    'source' => 'inventory',
                    'created_at' => $medicine->created_at?->format('d/m/Y H:i:s'),
                    'updated_at' => $medicine->updated_at?->format('d/m/Y H:i:s'),
                ];
            });

        $doctorAddedMedicines = collect();

        if ($includeDoctorAdded && $doctorId) {
            $doctorAddedMedicines = DoctorAddedMedicine::query()
                ->where('added_by_doctor', $doctorId)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->get()
                ->map(function (DoctorAddedMedicine $medicine) {
                    return [
                        'id' => $medicine->id,
                        'name' => $medicine->name,
                        'type' => null,
                        'category' => 'Doctor Added',
                        'source' => 'doctor_added',
                        'created_at' => $medicine->created_at?->format('d/m/Y H:i:s'),
                        'updated_at' => $medicine->updated_at?->format('d/m/Y H:i:s'),
                    ];
                });
        }

        $merged = $inventoryMedicines
            ->concat($doctorAddedMedicines)
            ->reduce(function ($carry, array $medicine) {
                $key = mb_strtolower(trim((string) $medicine['name']));

                if (! isset($carry[$key]) || $carry[$key]['source'] !== 'inventory') {
                    $carry[$key] = $medicine;
                }

                return $carry;
            }, []);

        $collection = collect(array_values($merged))
            ->sortBy(fn (array $medicine) => mb_strtolower($medicine['name']))
            ->values();

        $paginated = new LengthAwarePaginator(
            items: $collection->slice(($page - 1) * $perPage, $perPage)->values(),
            total: $collection->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return ApiResponseService::paginated($paginated, responseKey: 'responses.success');
    }
}
