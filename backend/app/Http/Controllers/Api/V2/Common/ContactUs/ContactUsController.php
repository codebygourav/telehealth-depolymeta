<?php

namespace App\Http\Controllers\Api\V2\Common\ContactUs;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;

class ContactUsController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'nullable|exists:users,id',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'required|email|max:150',
            'message'    => 'required|string|max:2000',
        ]);

        $contactUs = ContactUs::create($validated);

        return ApiResponseService::created(
            'Message submitted successfully. Thank you for contacting us!',
            $contactUs
        );
    }
}
