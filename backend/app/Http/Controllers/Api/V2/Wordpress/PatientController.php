<?php

namespace App\Http\Controllers\Api\V2\Wordpress;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function store(Request $request)
    {
        // Define the validation rules
        $rules = [
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'gender'                => 'required|in:male,female,other',
            'age'                   => 'required|integer|min:1|max:120',
            'father_name'           => 'nullable|string|max:255',
            'mother_name'           => 'nullable|string|max:255',
            'mobile'                => 'required|string|digits:10',
            'alternate_mobile'      => 'nullable|string|digits:10',
            'email'                 => 'required|email|max:255',
            'address'               => 'required|string|max:1000',
            'is_existing_patient'   => 'nullable|boolean',
            'existing_patient_id'   => 'nullable|string|max:255',
            'source'                => 'nullable|string|max:50',
            'password'              => 'required|string|min:8|confirmed',
        ];

        // Use validator and handle errors properly
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Map form field names to database field names if required
        $patientData = [
            'first_name'             => $validated['first_name'],
            'last_name'              => $validated['last_name'],
            'gender'                 => $validated['gender'],
            'age'                    => $validated['age'],
            'father_name'            => $validated['father_name'] ?? null,
            'mother_name'            => $validated['mother_name'] ?? null,
            'mobile_no'              => $validated['mobile'],
            'alternate_no'           => $validated['alternate_mobile'] ?? null,
            'email'                  => $validated['email'],
            'password'               => Hash::make($validated['password']),
            'address'                => $validated['address'],
            'is_existing_patient'    => (bool) ($validated['is_existing_patient'] ?? false),
            'existing_patient_id'    => $validated['existing_patient_id'] ?? null,
            'source'                 => $validated['source'] ?? 'wordpress',
        ];

        $userData = [
            'name' => trim($validated['first_name'] . " " . $validated['last_name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'mobile' => $validated['mobile'],
        ];
        $user = \App\Models\User::create($userData);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('patient');
        }
        if (array_key_exists('user_id', (new Patient())->getFillable())) {
            $patientData['user_id'] = $user->id;
        }

        $patient = Patient::create($patientData);

        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $patient,
            'user' => $user,
        ]);
    }
}

