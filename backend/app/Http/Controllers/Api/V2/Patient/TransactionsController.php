<?php

namespace App\Http\Controllers\Api\V2\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Patient\{TransactionResource, TransactionDetailResource};
use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Storage;

class TransactionsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || (!$user->patient && !$user->doctor)) {
            return ApiResponseService::unauthorized();
        }

        $paymentsQuery = Payment::query()->with('appointment.patient.user');

        if ($user->patient) {
            $paymentsQuery->whereHas('appointment', function ($q) use ($user) {
                $q->where('patient_id', $user->patient->id);
            });
        } elseif ($user->doctor) {
            $paymentsQuery->whereHas('appointment', function ($q) use ($user) {
                $q->where('doctor_id', $user->doctor->id);
            });
        }

        $payments = $paymentsQuery->latest()->paginate(10);

        return ApiResponseService::paginated(
           TransactionResource::collection($payments),
           responseKey: 'responses.success'
        );
    }
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user || (!$user->patient && !$user->doctor)) {
            return ApiResponseService::unauthorized();
        }

        $paymentQuery = Payment::with('appointment.doctor')->where('id', $id);

        if ($user->patient) {
            $paymentQuery->whereHas(
                'appointment',
                fn($q) => $q->where('patient_id', $user->patient->id)
            );
        } elseif ($user->doctor) {
            $paymentQuery->whereHas(
                'appointment',
                fn($q) => $q->where('doctor_id', $user->doctor->id)
            );
        }

        $payment = $paymentQuery->firstOrFail();

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new TransactionDetailResource($payment)
        );
    }
}
