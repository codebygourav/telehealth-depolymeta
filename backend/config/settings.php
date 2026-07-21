<?php

return [
    'app' => [
        'label' => 'Application',
        'icon' => 'heroicon-o-building-office',
        'description' => 'General application settings',
        'sections' => [
            'basic' => [
                'label' => 'Basic Information',
                'description' => 'Application identity',
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'Application Name', 'placeholder' => 'CMC Telehealth', 'required' => true, 'is_public' => true],
                    'tagline' => ['type' => 'text', 'label' => 'Tagline', 'placeholder' => 'Your trusted healthcare partner', 'is_public' => true],
                    'version' => ['type' => 'text', 'label' => 'App Version', 'placeholder' => '1.0.0', 'is_public' => true],
                ],
            ],
            'branding' => [
                'label' => 'Branding',
                'description' => 'Logo and theme (applies to admin panel)',
                'fields' => [
                    'logo' => ['type' => 'file', 'label' => 'Logo', 'directory' => 'settings', 'is_public' => true],
                    'logo_dark' => ['type' => 'file', 'label' => 'Logo (Dark)', 'directory' => 'settings', 'is_public' => true],
                    'favicon' => ['type' => 'file', 'label' => 'Favicon', 'directory' => 'settings', 'is_public' => true],
                    'primary_color' => ['type' => 'color', 'label' => 'Primary Color', 'default' => '#073827', 'is_public' => true],
                    'secondary_color' => ['type' => 'color', 'label' => 'Secondary Color', 'default' => '#073827', 'is_public' => true],
                    'global_stamp' => ['type' => 'file', 'label' => 'Global Hospital Stamp', 'directory' => 'settings/stamps', 'is_public' => true],
                ],
            ],
        ],
    ],

    'mail' => [
        'label' => 'Mail',
        'icon' => 'heroicon-o-envelope',
        'description' => 'Email configuration',
        'sections' => [
            'smtp' => [
                'label' => 'SMTP Configuration',
                'description' => 'Email sending settings (saves to .env)',
                'fields' => [
                    'host' => ['type' => 'text', 'label' => 'SMTP Host', 'placeholder' => 'smtp.gmail.com', 'env_key' => 'MAIL_HOST'],
                    'port' => ['type' => 'number', 'label' => 'SMTP Port', 'placeholder' => '587', 'env_key' => 'MAIL_PORT'],
                    'username' => ['type' => 'text', 'label' => 'Username', 'env_key' => 'MAIL_USERNAME'],
                    'password' => ['type' => 'password', 'label' => 'Password', 'env_key' => 'MAIL_PASSWORD'],
                    'encryption' => [
                        'type' => 'select',
                        'label' => 'Encryption',
                        'options' => ['' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL'],
                        'default' => 'tls',
                        'env_key' => 'MAIL_ENCRYPTION',
                    ],
                    'from_address' => ['type' => 'email', 'label' => 'From Email', 'placeholder' => 'noreply@example.com', 'env_key' => 'MAIL_FROM_ADDRESS'],
                    'from_name' => ['type' => 'text', 'label' => 'From Name', 'placeholder' => 'CMC Telehealth', 'env_key' => 'MAIL_FROM_NAME'],
                ],
            ],
        ],
    ],

    'support' => [
        'label' => 'Support',
        'icon' => 'heroicon-o-shield-check',
        'description' => 'Support contact information for consultations',
        'sections' => [
            'video_consultation' => [
                'db_group' => 'video_consultation',
                'label' => 'Video Consultation',
                'description' => 'Contact information for video consultations',
                'fields' => [
                    'phone' => ['type' => 'tel', 'label' => 'Phone Number', 'placeholder' => '+91 1234567890', 'is_public' => true],
                    'support_email' => ['type' => 'email', 'label' => 'Support Email', 'placeholder' => 'video-support@example.com', 'is_public' => true],
                    'address' => ['type' => 'textarea', 'label' => 'Address', 'rows' => 2, 'is_public' => true],
                ],
            ],
            'inperson_consultation' => [
                'db_group' => 'inperson_consultation',
                'label' => 'In Person Consultation',
                'description' => 'Contact information for in-person consultations',
                'fields' => [
                    'phone' => ['type' => 'tel', 'label' => 'Phone Number', 'placeholder' => '+91 1234567890', 'is_public' => true],
                    'support_email' => ['type' => 'email', 'label' => 'Support Email', 'placeholder' => 'inperson-support@example.com', 'is_public' => true],
                    'address' => ['type' => 'textarea', 'label' => 'Address', 'rows' => 2, 'is_public' => true],
                ],
                'toggle' => ['enabled' => true, 'default_open' => true],
            ],
        ],
    ],

    'payment' => [
        'label' => 'Payment',
        'icon' => 'heroicon-o-credit-card',
        'description' => 'Payment gateway configuration',
        'sections' => [
            'currency' => [
                'label' => 'Currency Settings',
                'fields' => [
                    'currency' => ['type' => 'select', 'label' => 'Currency', 'options' => ['INR' => 'Indian Rupee (₹)', 'USD' => 'US Dollar ($)', 'EUR' => 'Euro (€)', 'GBP' => 'British Pound (£)'], 'default' => 'INR', 'is_public' => true],
                    'currency_symbol' => ['type' => 'text', 'label' => 'Currency Symbol', 'default' => '₹', 'is_public' => true],
                ],
            ],
        ],
    ],

    'security' => [
        'label' => 'Security',
        'icon' => 'heroicon-o-shield-check',
        'description' => 'Security and authentication settings',
        'sections' => [
            'auth' => [
                'label' => 'Authentication',
                'description' => 'Login security settings',
                'fields' => [
                    'max_login_attempts' => ['type' => 'number', 'label' => 'Max Login Attempts', 'default' => 5, 'helper' => 'Lock account after failed attempts'],
                    'lockout_duration' => ['type' => 'number', 'label' => 'Lockout Duration (minutes)', 'default' => 30],
                    'api_token_expiration_days' => ['type' => 'number', 'label' => 'App Login Duration (days)', 'default' => 30, 'min' => 0, 'max' => 365, 'helper' => 'Controls how long mobile/API users stay signed in. Use 0 to keep users signed in until logout or token removal.'],
                ],
            ],
        ],
    ],

    'mobile' => [
        'label' => 'Mobile App',
        'icon' => 'heroicon-o-device-phone-mobile',
        'description' => 'Mobile application settings',
        'sections' => [
            'store' => [
                'label' => 'App Store Links',
                'fields' => [
                    'play_store_url' => ['type' => 'url', 'label' => 'Play Store URL', 'is_public' => true],
                    'app_store_url' => ['type' => 'url', 'label' => 'App Store URL', 'is_public' => true],
                ],
            ],
        ],
    ],

    'third_party' => [
        'label' => 'Third Party API',
        'icon' => 'heroicon-o-puzzle-piece',
        'description' => 'Third party API integrations',
        'sections' => [
            'whereby' => [
                'label' => 'Whereby Video Consultation',
                'description' => 'Whereby video consultation service configuration (saves to .env)',
                'fields' => [
                    'whereby_api_key' => ['type' => 'textarea', 'label' => 'Whereby API Key', 'placeholder' => 'Enter your Whereby API Key (JWT token)', 'rows' => 3, 'env_key' => 'WHEREBY_API_KEY', 'helper' => 'Get your API key from https://whereby.com/information/embedded/. This is a JWT token and can be very long.'],
                    'whereby_base_url' => ['type' => 'text', 'label' => 'Whereby Base URL', 'placeholder' => 'https://api.whereby.dev/v1', 'default' => 'https://api.whereby.dev/v1', 'env_key' => 'WHEREBY_BASE_URL', 'helper' => 'Default: https://api.whereby.dev/v1'],
                ],
            ],
            'razorpay' => [
                'label' => 'Razorpay Payment Gateway',
                'description' => 'Razorpay payment gateway configuration (saves to .env)',
                'fields' => [
                    'razorpay_key_id' => ['type' => 'text', 'label' => 'Razorpay Key ID', 'env_key' => 'RAZORPAY_KEY_ID'],
                    'razorpay_key_secret' => ['type' => 'password', 'label' => 'Razorpay Key Secret', 'env_key' => 'RAZORPAY_KEY_SECRET'],
                    'razorpay_enabled' => ['type' => 'toggle', 'label' => 'Enable Razorpay', 'default' => false],
                    'mock_booking_enabled' => ['type' => 'toggle', 'label' => 'Enable Mock Booking Payment', 'default' => false, 'color' => 'success', 'helper' => 'Allows /book-appointment to confirm appointments with mock_payment=true without calling Razorpay.'],
                ],
            ],
        ],
    ],

    'wordpress_api_setting' => [
        'label' => 'WordPress API',
        'icon' => 'heroicon-o-globe-alt',
        'description' => 'Settings for WordPress website integration and API',
        'sections' => [
            'main' => [
                'label' => 'WordPress API Settings',
                'fields' => [
                    'wordpress_enabled' => ['type' => 'toggle', 'label' => 'Enable WordPress API', 'default' => true],
                    'wordpress_api_secret' => ['type' => 'password', 'label' => 'WordPress API Secret', 'env_key' => 'WP_TELEHEALTH_SECRET', 'helper' => 'Shared secret sent in the X-TELEHEALTH-SECRET header or as a Bearer token. Rotate periodically.'],
                    'wordpress_allowed_ips' => ['type' => 'tags', 'label' => 'Allowed IPs', 'helper' => 'Optional list of IP addresses allowed to call the WordPress API.', 'default' => []],
                ],
            ],
        ],
    ],

    'advanced' => [
        'label' => 'Advanced',
        'icon' => 'heroicon-o-wrench-screwdriver',
        'description' => 'System configuration',
        'sections' => [
            'debug' => [
                'label' => 'Debug Mode',
                'description' => 'Development settings (use with caution)',
                'fields' => [
                    'debug_mode' => ['type' => 'toggle', 'label' => 'Enable Debug Mode', 'helper' => 'Shows detailed errors - disable in production!', 'default' => false, 'env_key' => 'APP_DEBUG'],
                ],
            ],
        ],
    ],

    'app_information' => [
        'label' => 'App Content',
        'icon' => 'heroicon-o-information-circle',
        'description' => 'App content settings',
        'sections' => [
            'faq' => [
                'label' => 'FAQ',
                'description' => 'Frequently Asked Questions',
                'db_group' => 'faq',
                'fields' => [
                    'faq_items' => ['type' => 'repeater', 'label' => 'FAQs', 'item_label' => 'FAQ', 'fields' => ['title' => ['type' => 'text', 'label' => 'Question', 'required' => true], 'description' => ['type' => 'textarea', 'label' => 'Answer', 'rows' => 3], 'icon' => ['type' => 'file', 'label' => 'Icon', 'directory' => 'settings/faq']]],
                ],
            ],
            'about_us' => ['label' => 'About Us', 'description' => 'About Us content', 'db_group' => 'about_us', 'fields' => ['about_us_field' => ['type' => 'richtext', 'label' => 'About Us Content', 'placeholder' => '', 'required' => true, 'is_public' => true]]],
            'term_and_conditions' => ['label' => 'Term & Conditions', 'description' => 'Terms and Conditions content', 'db_group' => 'term_and_conditions', 'fields' => ['term_and_condition_field' => ['type' => 'richtext', 'label' => 'Terms & Conditions Content', 'placeholder' => '', 'required' => true, 'is_public' => true]]],
            'privacy_policy' => ['label' => 'Privacy & Policy', 'description' => 'Privacy and Policy content', 'db_group' => 'privacy_and_policy', 'fields' => ['privacy_and_policy_field' => ['type' => 'richtext', 'label' => 'Privacy & Policy Content', 'placeholder' => '', 'required' => true, 'is_public' => true]]],
        ],
    ],

    'booking' => [
        'wordpress_availability_months' => 3,
        'default_cutoff_rules' => [['value' => 4, 'unit' => 'hours'], ['value' => 1, 'unit' => 'hours']],
        'label' => 'Booking',
        'icon' => 'heroicon-o-calendar-days',
        'description' => 'Booking and API settings',
        'sections' => [
            'rules' => [
                'label' => 'Booking Close Time Rules',
                'description' => 'Default rules for how long before a slot starts booking closes.',
                'fields' => [
                    'wordpress_availability_months' => ['type' => 'number', 'label' => 'WordPress Availability Months', 'default' => 3, 'min' => 1, 'max' => 12, 'helper' => 'Defines how many future months of doctor availability slots are generated and served to the WordPress website.', 'is_public' => true],
                    'child_age' => ['type' => 'number', 'label' => 'Global Child Age Limit', 'default' => 12, 'min' => 1, 'max' => 18, 'is_public' => true],
                    'booking_cutoff_value' => ['type' => 'number', 'label' => 'Default Booking Close Time', 'default' => 4, 'min' => 1, 'helper' => 'Defines how long before a session starts booking is automatically closed.', 'is_public' => true],
                    'booking_cutoff_unit' => ['type' => 'select', 'label' => 'Default Booking Close Time Unit', 'options' => ['minutes' => 'Minutes', 'hours' => 'Hours', 'days' => 'Days'], 'default' => 'hours', 'is_public' => true],
                ],
            ],
        ],
    ],
];
