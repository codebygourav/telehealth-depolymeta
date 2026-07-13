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
                    'primary_color' => ['type' => 'color', 'label' => 'Primary Color', 'default' => '#055bd9', 'is_public' => true],
                    'secondary_color' => ['type' => 'color', 'label' => 'Secondary Color', 'default' => '#055bd9', 'is_public' => true],
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


    /*
    |--------------------------------------------------------------------------
    | Prescription Voice Settings
    |--------------------------------------------------------------------------
    */
    'prescription_voice' => [
        'label' => 'Prescription Voice',
        'icon' => 'heroicon-o-speaker-wave',
        'description' => 'Global browser speech templates for medicine and prescription guidance.',
        'sections' => [
            'general' => [
                'label' => 'General',
                'description' => 'These templates are used anywhere the browser-based prescription speech player is enabled.',
                'fields' => [
                    'enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Browser SpeechSynthesis',
                        'default' => true,
                        'helper' => 'Uses the browser SpeechSynthesis API. No server-side TTS service is required.',
                        'is_public' => false,
                    ],
                    'default_language' => [
                        'type' => 'select',
                        'label' => 'Default Language',
                        'options' => [
                            'en' => 'English',
                            'hi' => 'Hindi',
                            'pa' => 'Punjabi',
                        ],
                        'default' => \App\Support\PrescriptionSpeech::DEFAULT_LANGUAGE,
                        'helper' => 'This is the default selected language in the prescription voice preview.',
                        'is_public' => false,
                    ],
                ],
            ],
            'templates' => [
                'label' => 'Template',
                'description' => \App\Support\PrescriptionSpeech::placeholdersHelpText(),
                'fields' => [
                    'template' => [
                        'type' => 'textarea',
                        'label' => 'Speech Template',
                        'rows' => 4,
                        'default' => \App\Support\PrescriptionSpeech::defaultTemplate(),
                        'helper' => \App\Support\PrescriptionSpeech::placeholdersHelpText(),
                        'placeholder' => \App\Support\PrescriptionSpeech::defaultTemplate(),
                        'is_public' => false,
                    ],
                ],
            ],
            'dictation' => [
                'db_group' => 'prescription_dictation',
                'label' => 'Prescription Dictation',
                'description' => 'Manage browser voice dictation settings for prescriptions.',
                'fields' => [
                    'enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Voice Dictation',
                        'default' => false,
                        'helper' => 'Enable or disable browser-based voice dictation for prescriptions.',
                        'env_key' => 'PRESCRIPTION_DICTATION_ENABLED',
                        'is_public' => false,
                    ],
                    'input_mode' => [
                        'type' => 'select',
                        'label' => 'Input Mode',
                        'options' => [
                            'off' => 'Disabled',
                            'text' => 'Text Mode (Keyboard Input)',
                            'speech' => 'Speech Mode (Browser Voice)',
                        ],
                        'default' => 'off',
                        'helper' => 'Choose how doctors can enter prescription drafts.',
                        'env_key' => 'PRESCRIPTION_DICTATION_INPUT_MODE',
                        'is_public' => false,
                    ],
                    'text_mode_max_chars' => [
                        'type' => 'number',
                        'label' => 'Max Characters for Text Input',
                        'default' => 1000,
                        'helper' => 'Maximum character limit allowed for the dictation input text.',
                        'env_key' => 'PRESCRIPTION_DICTATION_TEXT_MAX_CHARS',
                        'is_public' => false,
                    ],
                    'speech_locale' => [
                        'type' => 'select',
                        'label' => 'Default Speech Locale',
                        'options' => [
                            'en-IN' => 'English (India)',
                            'en-US' => 'English (US)',
                            'hi-IN' => 'Hindi',
                            'pa-IN' => 'Punjabi',
                        ],
                        'default' => 'en-IN',
                        'helper' => 'Default locale used when the doctor opens browser voice dictation.',
                        'env_key' => 'PRESCRIPTION_DICTATION_SPEECH_LOCALE',
                        'is_public' => false,
                    ],
                    'supported_locales' => [
                        'type' => 'text',
                        'label' => 'Supported Locales',
                        'default' => 'auto,en-IN,en-US,hi-IN,pa-IN',
                        'placeholder' => 'auto,en-IN,en-US,hi-IN,pa-IN',
                        'helper' => 'Comma-separated locales exposed to doctors for prescription dictation.',
                        'env_key' => 'PRESCRIPTION_DICTATION_SUPPORTED_LOCALES',
                        'is_public' => false,
                    ],
                    'allow_custom_locale' => [
                        'type' => 'toggle',
                        'label' => 'Allow Custom Locale',
                        'default' => true,
                        'helper' => 'Keep this enabled if you may provide locales outside the default preset list.',
                        'env_key' => 'PRESCRIPTION_DICTATION_ALLOW_CUSTOM_LOCALE',
                        'is_public' => false,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Screen Settings
    |--------------------------------------------------------------------------
    */
    'display' => [
        'label' => 'Display Screen',
        'icon' => 'heroicon-o-tv',
        'description' => 'Hospital token board and doctor advertisement screen',
        'sections' => [
            'general' => [
                'label' => 'General',
                'description' => 'Top-level screen configuration.',
                'fields' => [
                    'screen_name' => [
                        'type' => 'text',
                        'label' => 'Display Name',
                        'default' => 'Main OPD Waiting Hall',
                        'is_public' => false,
                    ],
                    'screen_location' => [
                        'type' => 'text',
                        'label' => 'Screen Location',
                        'default' => 'Ground Floor OPD',
                        'is_public' => false,
                    ],
                    'default_notice' => [
                        'type' => 'textarea',
                        'label' => 'Default Notice',
                        'rows' => 3,
                        'default' => 'Please keep your token ready. Wait near your assigned OPD room.',
                        'is_public' => false,
                    ],
                ],
            ],
            'access' => [
                'label' => 'Access Control',
                'description' => 'Password and doctor targeting rules for the public display.',
                'fields' => [
                    'password' => [
                        'type' => 'password',
                        'label' => 'Display Password',
                        'placeholder' => 'Enter a secret password for the screen',
                        'helper' => 'Leave blank only if you want the screen open to everyone on the network.',
                        'is_public' => false,
                    ],
                    'doctor_mode' => [
                        'type' => 'select',
                        'label' => 'Doctor Selection',
                        'options' => [
                            'all' => 'Auto from Today’s Appointments',
                            'single' => 'Single Doctor',
                            'multiple' => 'Multiple Doctors',
                        ],
                        'default' => 'all',
                        'is_public' => false,
                    ],
                    'display_mode' => [
                        'type' => 'select',
                        'label' => 'Screen Layout Mode',
                        'options' => [
                            'auto' => 'Auto Detect',
                            'split_ads' => '50/50 Doctor Card + Ads',
                            'grid_modal_ads' => 'Doctor Grid + Modal Ads',
                            'doctor_schedule_sidebar' => 'Doctor OPD + Schedule Sidebar',
                            'events_only' => 'Events / Announcements Only',
                        ],
                        'default' => 'auto',
                        'helper' => 'Choose the exact screen composition for doctors, ads, and fallback events.',
                        'is_public' => false,
                    ],
                    'same_time_card_columns' => [
                        'type' => 'select',
                        'label' => 'Maximum Doctor Grid Columns',
                        'options' => [
                            '2' => '2 Columns',
                            '3' => '3 Columns',
                        ],
                        'default' => '2',
                        'helper' => 'Large screens will expand up to this many doctor cards in one row while still adapting automatically on smaller screens.',
                        'is_public' => false,
                    ],
                    'selected_doctors' => [
                        'type' => 'doctor_select',
                        'label' => 'Doctors With Appointments Today',
                        'helper' => 'Only doctors with appointments today are shown here.',
                        'default' => [],
                        'is_public' => false,
                    ],
                    'refresh_seconds' => [
                        'type' => 'number',
                        'label' => 'Live Refresh Seconds',
                        'default' => 30,
                        'min' => 10,
                        'max' => 300,
                        'is_public' => false,
                    ],
                ],
            ],
            'copy' => [
                'label' => 'Screen Copy',
                'description' => 'All labels and helper text used on the big display.',
                'fields' => [
                    'page_title' => [
                        'type' => 'text',
                        'label' => 'Page Title',
                        'default' => 'OPD Token Display',
                        'is_public' => false,
                    ],
                    'page_subtitle' => [
                        'type' => 'text',
                        'label' => 'Subtitle',
                        'default' => 'Please keep your token ready and be seated.',
                        'is_public' => false,
                    ],
                    'queue_label' => [
                        'type' => 'text',
                        'label' => 'Queue Label',
                        'default' => "Today's Queue",
                        'is_public' => false,
                    ],
                    'queue_subtitle' => [
                        'type' => 'text',
                        'label' => 'Queue Subtitle',
                        'default' => 'Current token, next patient and queue position',
                        'is_public' => false,
                    ],
                    'advertisement_badge' => [
                        'type' => 'text',
                        'label' => 'Ad Badge',
                        'default' => 'Doctor Advertisement Slider',
                        'is_public' => false,
                    ],
                    'now_showing_label' => [
                        'type' => 'text',
                        'label' => 'Now Showing Label',
                        'default' => 'Now showing',
                        'is_public' => false,
                    ],
                    'cta_label' => [
                        'type' => 'text',
                        'label' => 'CTA Label',
                        'default' => 'Please keep your token ready',
                        'is_public' => false,
                    ],
                    'empty_state_title' => [
                        'type' => 'text',
                        'label' => 'Empty State Title',
                        'default' => 'No active doctor assigned',
                        'is_public' => false,
                    ],
                    'empty_state_text' => [
                        'type' => 'textarea',
                        'label' => 'Empty State Text',
                        'rows' => 3,
                        'default' => 'Assign one or more doctors in the display settings to start the live board.',
                        'is_public' => false,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Ads Settings
    |--------------------------------------------------------------------------
    */
    'display_ads' => [
        'label' => 'Display Ads',
        'icon' => 'heroicon-o-photo',
        'description' => 'Automatic media behavior for the public display.',
        'sections' => [
            'layout' => [
                'label' => 'Layout Modes',
                'description' => 'Layout behavior for doctor cards, ads, and fallback content.',
                'fields' => [
                    'randomize_bottom_content' => [
                        'type' => 'toggle',
                        'label' => 'Randomize Bottom Content',
                        'default' => true,
                        'helper' => 'Shuffle announcements, ads, and event slides before they loop at the bottom of the screen.',
                        'is_public' => false,
                    ],
                ],
            ],
            'media' => [
                'label' => 'Media Rules',
                'description' => 'How doctor media, ads, and event videos behave on screen.',
                'fields' => [
                    'slide_duration_seconds' => [
                        'type' => 'number',
                        'label' => 'Slide Duration (seconds)',
                        'default' => 8,
                        'min' => 3,
                        'max' => 60,
                        'helper' => 'How long a non-video slide stays on screen before moving to the next one.',
                    ],
                    'doctor_rotation_seconds' => [
                        'type' => 'number',
                        'label' => 'Doctor Rotation (seconds)',
                        'default' => 12,
                        'min' => 5,
                        'max' => 120,
                        'helper' => 'How often the active doctor card changes when multiple doctors are enabled.',
                    ],
                    'refresh_seconds' => [
                        'type' => 'number',
                        'label' => 'Live Refresh (seconds)',
                        'default' => 30,
                        'min' => 10,
                        'max' => 300,
                        'helper' => 'Refresh the board from Livewire on this interval.',
                    ],
                    'pause_between_doctors_seconds' => [
                        'type' => 'number',
                        'label' => 'Pause Between Doctors (seconds)',
                        'default' => 2,
                        'min' => 0,
                        'max' => 20,
                        'helper' => 'Optional pause before the screen switches to the next doctor or content panel.',
                    ],
                    'popup_enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Patient Popup',
                        'default' => true,
                        'helper' => 'Show the next-patient alert popup automatically when the current turn changes.',
                        'is_public' => false,
                    ],
                    'popup_duration_seconds' => [
                        'type' => 'number',
                        'label' => 'Patient Popup Duration (seconds)',
                        'default' => 8,
                        'min' => 3,
                        'max' => 30,
                        'helper' => 'How long the next-patient popup stays visible.',
                    ],
                    'ad_popup_enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Advertisement Popup',
                        'default' => true,
                        'helper' => 'Show a doctor-related ad popup automatically while the board is running.',
                        'is_public' => false,
                    ],
                    'ad_popup_interval_seconds' => [
                        'type' => 'number',
                        'label' => 'Ad Popup Interval (seconds)',
                        'default' => 180,
                        'min' => 30,
                        'max' => 600,
                        'helper' => 'How often doctor-related ad popups should appear.',
                    ],
                    'ad_popup_duration_seconds' => [
                        'type' => 'number',
                        'label' => 'Ad Popup Duration (seconds)',
                        'default' => 12,
                        'min' => 5,
                        'max' => 60,
                        'helper' => 'How long the advertisement popup stays open before closing automatically.',
                    ],
                    'show_media_images' => [
                        'type' => 'toggle',
                        'label' => 'Show Image Content',
                        'default' => true,
                        'helper' => 'When off, image banners are hidden from the display slider and popup.',
                        'is_public' => false,
                    ],
                    'show_media_videos' => [
                        'type' => 'toggle',
                        'label' => 'Show Video Content',
                        'default' => true,
                        'helper' => 'When off, video and embedded media are hidden from the display slider and popup.',
                        'is_public' => false,
                    ],
                    'show_media_links' => [
                        'type' => 'toggle',
                        'label' => 'Show Link Content',
                        'default' => true,
                        'helper' => 'When off, link-only items are hidden from the display slider and popup.',
                        'is_public' => false,
                    ],
                ],
            ],
            'voice' => [
                'label' => 'Voice',
                'description' => 'Speech announcement settings.',
                'fields' => [
                    'voice_enabled' => [
                        'type' => 'toggle',
                        'label' => 'Enable Voice Announcement',
                        'default' => true,
                        'is_public' => false,
                    ],
                    'voice_language' => [
                        'type' => 'text',
                        'label' => 'Voice Language',
                        'default' => 'en-US',
                        'helper' => \App\Support\DisplayVoiceAnnouncement::languageHelpText(),
                        'is_public' => false,
                    ],
                    'voice_name' => [
                        'type' => 'text',
                        'label' => 'Preferred Voice Name',
                        'default' => '',
                        'helper' => 'Optional. Leave blank to auto-pick the best natural browser voice for the selected language.',
                        'is_public' => false,
                    ],
                    'announcement_template' => [
                        'type' => 'textarea',
                        'label' => 'Announcement Template',
                        'rows' => 3,
                        'default' => \App\Support\DisplayVoiceAnnouncement::defaultTemplate(),
                        'helper' => \App\Support\DisplayVoiceAnnouncement::placeholdersHelpText(),
                        'placeholder' => \App\Support\DisplayVoiceAnnouncement::defaultTemplate(),
                        'is_public' => false,
                    ],
                ],
            ],
            'content' => [
                'label' => 'Ad Content Copy',
                'description' => 'Default labels and placeholders shown in the media templates.',
                'fields' => [
                    'badge_label' => [
                        'type' => 'text',
                        'label' => 'Badge Label',
                        'placeholder' => 'Doctor Advertisement Slider',
                        'default' => 'Doctor Advertisement Slider',
                    ],
                    'now_showing_label' => [
                        'type' => 'text',
                        'label' => 'Now Showing Label',
                        'placeholder' => 'Now showing',
                        'default' => 'Now showing',
                    ],
                    'empty_slide_title' => [
                        'type' => 'text',
                        'label' => 'Empty Slide Title',
                        'placeholder' => 'No advertisement assigned',
                        'default' => 'No advertisement assigned',
                    ],
                    'empty_slide_text' => [
                        'type' => 'textarea',
                        'label' => 'Empty Slide Text',
                        'rows' => 3,
                        'placeholder' => 'Add at least one active advertisement for this doctor.',
                        'default' => 'Add at least one active advertisement for this doctor.',
                    ],
                    'cta_label' => [
                        'type' => 'text',
                        'label' => 'CTA Label',
                        'placeholder' => 'Please keep your token ready',
                        'default' => 'Please keep your token ready',
                    ],
                    'bottom_content_label' => [
                        'type' => 'text',
                        'label' => 'Bottom Content Label',
                        'placeholder' => 'Health Updates',
                        'default' => 'Health Updates',
                    ],
                    'next_patient_label' => [
                        'type' => 'text',
                        'label' => 'Next Patient Label',
                        'placeholder' => 'Next Patient',
                        'default' => 'Next Patient',
                    ],
                    'popup_title' => [
                        'type' => 'text',
                        'label' => 'Popup Title',
                        'placeholder' => 'Next Patient Alert',
                        'default' => 'Next Patient Alert',
                    ],
                    'show_slide_heading' => [
                        'type' => 'toggle',
                        'label' => 'Show Slider Heading',
                        'default' => false,
                        'helper' => 'Toggle the header/title above the advertisement slider.',
                        'is_public' => false,
                    ],
                    'show_slide_description' => [
                        'type' => 'toggle',
                        'label' => 'Show Slide Description',
                        'default' => false,
                        'helper' => 'Toggle textual description/captions on slides and ad popups.',
                        'is_public' => false,
                    ],
                    'show_events_title' => [
                        'type' => 'toggle',
                        'label' => 'Show Events Title',
                        'default' => false,
                        'helper' => 'Toggle title text in the events/content slider.',
                        'is_public' => false,
                    ],
                    'show_events_description' => [
                        'type' => 'toggle',
                        'label' => 'Show Events Description',
                        'default' => false,
                        'helper' => 'Toggle description text in the events/content slider.',
                        'is_public' => false,
                    ],
                ],
            ],
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

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
