<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use App\Models\Department;
use App\Models\DepartmentDoctor;
use Filament\Forms\Components\{
    FileUpload,
    Repeater,
    Toggle,
    Select,
    TimePicker,
    DatePicker,
    TextInput,
    Placeholder
};
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Arr;

use App\Enums\{
    DayOfWeek,
    LanguageOption,
    MaritalStatus,
    GenderOption,
    BloodGroupOption,
    DepartmentRole
};
use App\Models\Doctor;
use Carbon\Carbon;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Wizard::make([
                    Step::make('General Information')
                        ->schema([
                            Section::make('Basic Information')
                                ->schema([
                                    TextInput::make('company_name'),
                                    Select::make('vendor_type')
                                        ->options([
                                            'medical_equipment' => 'Medical Equipment Supplier',
                                            'it_vendor' => 'IT Vendor',
                                            'maintenance' => 'Maintenance Service',
                                            'lab_partner' => 'Lab Partner',
                                            'consultant' => 'Consultant',
                                        ]),
                                    TextInput::make('contact_person'),
                                    TextInput::make('designation'),
                                    TextInput::make('email')->email(),
                                    TextInput::make('alt_email')->email(),
                                    TextInput::make('mobile')->tel(),
                                    TextInput::make('alt_mobile')->tel(),
                                    TextInput::make('website')->url(),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Business Details')
                        ->schema([
                            Section::make('Address Details')
                                ->schema([
                                    Textarea::make('registered_office_address'),
                                    TextInput::make('city'),
                                    TextInput::make('state'),
                                    TextInput::make('pin_code'),
                                    TextInput::make('country'),
                                    Textarea::make('branch_office_address'),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Compliance & Legal')
                        ->schema([
                            Section::make('Legal Information')
                                ->schema([
                                    TextInput::make('gst_number'),
                                    TextInput::make('pan_number'),
                                    TextInput::make('cin_number'),
                                    TextInput::make('msme_number'),
                                    FileUpload::make('license_upload'),
                                    FileUpload::make('tax_exemption_upload'),
                                ])
                                ->columns(2),

                            Section::make('Statutory Documents')
                                ->schema([
                                    FileUpload::make('pan_copy'),
                                    FileUpload::make('gst_certificate'),
                                    FileUpload::make('company_registration'),
                                    FileUpload::make('authorized_signatory_id'),
                                    FileUpload::make('address_proof'),
                                    FileUpload::make('other_documents')->multiple(),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Financials')
                        ->schema([
                            Section::make('Banking & Payment Details')
                                ->schema([
                                    TextInput::make('bank_name'),
                                    TextInput::make('branch_name'),
                                    TextInput::make('account_holder'),
                                    TextInput::make('account_number'),
                                    TextInput::make('ifsc_code'),
                                    FileUpload::make('cancelled_cheque_upload'),
                                    Select::make('preferred_payment_method')
                                        ->options([
                                            'NEFT' => 'NEFT',
                                            'RTGS' => 'RTGS',
                                            'UPI' => 'UPI',
                                            'Cheque' => 'Cheque',
                                            'Others' => 'Others',
                                        ]),
                                    TextInput::make('billing_email')->email(),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Products & Services')
                        ->schema([
                            Section::make('Offerings')
                                ->schema([
                                    Textarea::make('service_description'),
                                    Textarea::make('products_offered'),
                                    FileUpload::make('catalog_upload'),
                                    TextInput::make('annual_business_volume')->numeric(),
                                    TextInput::make('years_in_business')->numeric(),
                                    Textarea::make('existing_clients'),
                                ])
                                ->columns(2),

                            Section::make('Contact Preferences')
                                ->schema([
                                    Select::make('preferred_communication')
                                        ->options([
                                            'email' => 'Email',
                                            'phone' => 'Phone',
                                            'portal' => 'Portal Messages',
                                        ]),
                                    TextInput::make('primary_contact_name'),
                                    TextInput::make('primary_contact_email')->email(),
                                    TextInput::make('primary_contact_phone')->tel(),
                                    TextInput::make('secondary_contact_name'),
                                    TextInput::make('secondary_contact_email')->email(),
                                    TextInput::make('secondary_contact_phone'),
                                ])
                                ->columns(2),

                            Section::make('Status')
                                ->schema([
                                    Select::make('status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                        ])
                                        ->default('pending')
                                        ->disabled(),
                                ])
                                ->columns(1),
                        ]),
                ])
                ->columnSpanFull(),
            ]);
    }
}
