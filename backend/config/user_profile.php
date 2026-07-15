<?php

return [

    'patient' => [
        'personal_information' => [
            'fields' => [
                'existing_patient_id',
                'first_name',
                'last_name',
                'mobile_no',
                'date_of_birth',
                'gender',
                'avatar',
                'bio'
            ],
            'validation' => [
                'existing_patient_id' => 'nullable|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'mobile_no' => 'sometimes|string|max:20',
                'gender' => 'sometimes|in:male,female,other',
                'date_of_birth' => 'sometimes|date',
                'avatar' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
                'bio' => 'sometimes|string|max:2000',
            ],
            'field_types' => [
                'existing_patient_id' => 'text',
                'first_name' => 'text',
                'last_name' => 'text',
                'mobile_no' => 'text',
                'gender' => 'select',
                'date_of_birth' => 'date',
                'avatar' => 'file',
                'bio' => 'textarea',
            ],
        ],
        'address' => [
            'fields' => [
                'address',
                'area',
                'landmark',
                'pincode',
                'city',
                'state',
            ],
            'validation' => [
                'address' => 'sometimes|string',
                'area' => 'nullable|string',
                'landmark' => 'nullable|string',
                'city' => 'sometimes|string',
                'state' => 'sometimes|string',
                'pincode' => 'sometimes|string|max:10',
            ],
            'field_types' => [
                'address' => 'textarea',
                'area' => 'text',
                'landmark' => 'text',
                'city' => 'text',
                'state' => 'text',
                'pincode' => 'text',
            ],
        ],
    ],

    'doctor' => [
        'personal_information' => [
            'fields' => [
                'first_name',
                'last_name',
                'bio',
                'doctor_departments',
                'email',
                'avatar',
            ],
            'validation' => [
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'bio' => 'sometimes|string|max:2000',
                'doctor_departments' => 'sometimes|array',
                'doctor_departments.*.department_id' => 'nullable|exists:departments,id',
                'doctor_departments.*.role' => 'nullable|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email',
                'avatar' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ],
            'field_types' => [
                'first_name' => 'text',
                'last_name' => 'text',
                'bio' => 'textarea',
                'doctor_departments' => 'json',
                'email' => 'email',
                'avatar' => 'file',
            ],
            'file_configs' => [
                'avatar' => 'user_avatar',
            ],
        ],
        'working_experience' => [
            'fields' => [
                'professional_experience_info',
            ],
            'validation' => [
                'professional_experience_info' => 'sometimes|array',
                'professional_experience_info.*.association' => 'sometimes|string',
                'professional_experience_info.*.description' => 'sometimes|string',
            ],
            'field_types' => [
                'professional_experience_info' => 'json',
            ],
        ],
        'education_info' => [
            'fields' => [
                'education_info',
            ],
            'validation' => [
                'education_info' => 'sometimes|array',
                'education_info.*.degree' => 'sometimes|string|max:255',
                'education_info.*.institution' => 'sometimes|string|max:255',
                'education_info.*.completion_year' => 'sometimes|string|max:255',
            ],
            'field_types' => [
                'education_info' => 'json',
            ],
        ],
        'certifications_info' => [
            'fields' => [
                'certifications_info',
            ],
            'validation' => [
                'certifications_info' => 'sometimes|array',
                'certifications_info.*.name' => 'sometimes|string|max:255',
                'certifications_info.*.organization' => 'sometimes|string|max:255',
                'certifications_info.*.description' => 'sometimes|string|max:1000',
                'certifications_info.*.certification_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ],
            'field_types' => [
                'certifications_info' => 'json',
            ],
            'file_configs' => [
                'certifications_info.*.certification_image' => 'doctorDocument',
            ],
        ],
        'address' => [
            'fields' => [
                'address_line1',
                'address_line2',
                'country',
                'state',
                'area',
                'city',
                'pincode',
                'landmark',
            ],

            'validation' => [
                'address_line1' => 'sometimes|string|max:255',
                'address_line2' => 'sometimes|string|max:255',
                'country' => 'nullable|string|max:255',
                'state' => 'sometimes|string|max:255',
                'area' => 'sometimes|string|max:255',
                'city' => 'sometimes|string|max:255',
                'pincode' => 'sometimes|string|max:20',
                'landmark' => 'sometimes|string|max:255',
            ],
            'field_types' => [
                'address_line1' => 'textarea',
                'address_line2' => 'textarea',
                'country' => 'text',
                'state' => 'text',
                'area' => 'text',
                'city' => 'text',
                'pincode' => 'text',
                'landmark' => 'text',
            ],
        ],
        'awards_info' => [
            'fields' => [
                'awards_info',
            ],
            'validation' => [
                'awards_info' => 'sometimes|array',
                'awards_info.*.title' => 'sometimes|string|max:255',
                'awards_info.*.year' => 'sometimes|string|max:4',
                'awards_info.*.description' => 'sometimes|string|max:1000',
                'awards_info.*.award_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ],
            'field_types' => [
                'awards_info' => 'json',
            ],
            'file_configs' => [
                'awards_info.*.award_image' => 'doctorDocument',
            ],
        ],
        'fellowships_training' => [
            'fields' => [
                'fellowships_info',
            ],
            'validation' => [
                'fellowships_info' => 'sometimes|array',
                'fellowships_info.*.title' => 'sometimes|string|max:255',
                'fellowships_info.*.institution' => 'sometimes|string|max:255',
                'fellowships_info.*.year_started' => 'sometimes|string|max:4',
                'fellowships_info.*.description' => 'sometimes|string',
            ],
            'field_types' => [
                'fellowships_info' => 'json',
            ],
        ],
        'additional_information' => [
            'fields' => [
                'special_interests',
                'availability_info',
                'memberships_info',
                'specializations_info',
                'key_procedures_info',
                'expertise_info'
            ],
            'validation' => [
                'special_interests' => 'sometimes|string',
                'availability_info' => 'sometimes|string',
                'memberships_info' => 'sometimes|string',
                'specializations_info' => 'sometimes|string',
                'key_procedures_info' => 'sometimes|string',
                'expertise_info' => 'sometimes|string',
            ],
            'field_types' => [
                'special_interests' => 'textarea',
                'availability_info' => 'textarea',
                'memberships_info' => 'textarea',
                'specializations_info' => 'textarea',
                'key_procedures_info' => 'textarea',
                'expertise_info' => 'textarea',
            ],
        ],
        'social_media' => [
            'fields' => [
                'social_links',
            ],
            'validation' => [
                'social_links' => 'sometimes|array',
                'social_links.facebook' => 'sometimes|url|max:255',
                'social_links.twitter' => 'sometimes|url|max:255',
                'social_links.linkedin' => 'sometimes|url|max:255',
                'social_links.instagram' => 'sometimes|url|max:255',
                'social_links.website' => 'sometimes|url|max:255',
            ],
            'field_types' => [
                'social_links' => 'json',
            ],
        ],
        'voice_settings' => [
            'fields' => [
                'voice_name',
                'speech_rate',
                'speech_pitch',
                'speech_locale',
            ],
            'validation' => [
                'voice_name' => 'nullable|string|max:255',
                'speech_rate' => 'sometimes|numeric|min:0.5|max:2.0',
                'speech_pitch' => 'sometimes|numeric|min:0.5|max:2.0',
                'speech_locale' => 'nullable|string|max:20',
            ],
            'field_types' => [
                'voice_name' => 'text',
                'speech_rate' => 'text',
                'speech_pitch' => 'text',
                'speech_locale' => 'text',
            ],
        ],
        'ai_training' => [
            'fields' => [
                'ai_training_profile',
            ],
            'validation' => [
                'ai_training_profile' => 'sometimes|array',
                'ai_training_profile.pronunciation_dictionary' => 'sometimes|array',
                'ai_training_profile.pronunciation_dictionary.*.doctor_says' => 'required_with:ai_training_profile.pronunciation_dictionary|string|max:255',
                'ai_training_profile.pronunciation_dictionary.*.ai_converts_to' => 'required_with:ai_training_profile.pronunciation_dictionary|string|max:255',
                'ai_training_profile.speech_word_corrections' => 'sometimes|array',
                'ai_training_profile.speech_word_corrections.*.heard_word' => 'required_with:ai_training_profile.speech_word_corrections|string|max:255',
                'ai_training_profile.speech_word_corrections.*.corrected_word' => 'required_with:ai_training_profile.speech_word_corrections|string|max:255',
                'ai_training_profile.medicine_shortcuts' => 'sometimes|array',
                'ai_training_profile.medicine_shortcuts.*.medicine' => 'required_with:ai_training_profile.medicine_shortcuts|string|max:255',
                'ai_training_profile.medicine_shortcuts.*.shortcut' => 'required_with:ai_training_profile.medicine_shortcuts|string|max:80',
                'ai_training_profile.medicine_shortcuts.*.priority' => 'nullable|integer|min:1|max:5',
                'ai_training_profile.common_diagnoses' => 'sometimes|array',
                'ai_training_profile.common_diagnoses.*' => 'string|max:255',
                'ai_training_profile.frequently_used_instructions' => 'sometimes|array',
                'ai_training_profile.frequently_used_instructions.*' => 'string|max:500',
                'ai_training_profile.procedures_investigations' => 'sometimes|array',
                'ai_training_profile.procedures_investigations.*' => 'string|max:255',
            ],
            'field_types' => [
                'ai_training_profile' => 'json',
            ],
        ],
    ],

];
