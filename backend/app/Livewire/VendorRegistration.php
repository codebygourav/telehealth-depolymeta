<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class VendorRegistration extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 5;

    // Step 1: Basic Information
    public $company_name;
    public $vendor_type;
    public $contact_person;
    public $designation;
    public $email;
    public $alt_email;
    public $mobile;
    public $alt_mobile;
    public $website;

    // Step 2: Business Address
    public $registered_office_address;
    public $city;
    public $state;
    public $pin_code;
    public $country;
    public $branch_office_address;

    // Step 3: Legal & Compliance
    public $gst_number;
    public $pan_number;
    public $cin_number;
    public $msme_number;
    public $license_upload;
    public $tax_exemption_upload;

    // Step 4: Banking & Products
    public $bank_name;
    public $branch_name;
    public $account_holder;
    public $account_number;
    public $ifsc_code;
    public $cancelled_cheque_upload;
    public $preferred_payment_method;
    public $billing_email;
    public $service_description;
    public $products_offered;
    public $catalog_upload;
    public $annual_business_volume;
    public $years_in_business;
    public $existing_clients;

    // Step 5: Documents & Contact Preferences
    public $pan_copy;
    public $gst_certificate;
    public $company_registration;
    public $authorized_signatory_id;
    public $address_proof;
    public $other_documents = [];
    public $preferred_communication;
    public $primary_contact_name;
    public $primary_contact_email;
    public $primary_contact_phone;
    public $secondary_contact_name;
    public $secondary_contact_email;
    public $secondary_contact_phone;

    protected $rules = [
        // Step 1
        'company_name' => 'required|string|max:255',
        'vendor_type' => 'required|string',
        'contact_person' => 'required|string|max:255',
        'email' => 'required|email|unique:vendors,email',
        'mobile' => 'required|string|max:20',

        // Step 2
        'registered_office_address' => 'required|string',
        'city' => 'required|string|max:255',
        'state' => 'required|string|max:255',
        'pin_code' => 'required|string|max:10',
        'country' => 'required|string|max:255',

        // Step 3
        'gst_number' => 'required|string|max:50',
        'pan_number' => 'required|string|max:50',

        // Step 4
        'bank_name' => 'required|string|max:255',
        'branch_name' => 'required|string|max:255',
        'account_holder' => 'required|string|max:255',
        'account_number' => 'required|string|max:50',
        'ifsc_code' => 'required|string|max:20',
        'preferred_payment_method' => 'required|string',
        'billing_email' => 'required|email',
        'service_description' => 'required|string',
        'products_offered' => 'required|string',
        'years_in_business' => 'required|integer|min:0',

        // Step 5
        'preferred_communication' => 'required|string',
        'primary_contact_name' => 'required|string|max:255',
        'primary_contact_email' => 'required|email',
        'primary_contact_phone' => 'required|string|max:20',
    ];

    public function nextStep()
    {
        $this->validateStep();
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function validateStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate([
                    'company_name' => 'required|string|max:255',
                    'vendor_type' => 'required|string',
                    'contact_person' => 'required|string|max:255',
                    'email' => 'required|email|unique:vendors,email',
                    'mobile' => 'required|string|max:20',
                ]);
                break;
            case 2:
                $this->validate([
                    'registered_office_address' => 'required|string',
                    'city' => 'required|string|max:255',
                    'state' => 'required|string|max:255',
                    'pin_code' => 'required|string|max:10',
                    'country' => 'required|string|max:255',
                ]);
                break;
            case 3:
                $this->validate([
                    'gst_number' => 'required|string|max:50',
                    'pan_number' => 'required|string|max:50',
                ]);
                break;
            case 4:
                $this->validate([
                    'bank_name' => 'required|string|max:255',
                    'branch_name' => 'required|string|max:255',
                    'account_holder' => 'required|string|max:255',
                    'account_number' => 'required|string|max:50',
                    'ifsc_code' => 'required|string|max:20',
                    'preferred_payment_method' => 'required|string',
                    'billing_email' => 'required|email',
                    'service_description' => 'required|string',
                    'products_offered' => 'required|string',
                    'years_in_business' => 'required|integer|min:0',
                ]);
                break;
            case 5:
                $this->validate([
                    'preferred_communication' => 'required|string',
                    'primary_contact_name' => 'required|string|max:255',
                    'primary_contact_email' => 'required|email',
                    'primary_contact_phone' => 'required|string|max:20',
                ]);
                break;
        }
    }

    public function submit()
    {
        $this->validateStep();

        // Handle file uploads
        $data = [
            'company_name' => $this->company_name,
            'vendor_type' => $this->vendor_type,
            'contact_person' => $this->contact_person,
            'designation' => $this->designation,
            'email' => $this->email,
            'alt_email' => $this->alt_email,
            'mobile' => $this->mobile,
            'alt_mobile' => $this->alt_mobile,
            'website' => $this->website,
            'registered_office_address' => $this->registered_office_address,
            'city' => $this->city,
            'state' => $this->state,
            'pin_code' => $this->pin_code,
            'country' => $this->country,
            'branch_office_address' => $this->branch_office_address,
            'gst_number' => $this->gst_number,
            'pan_number' => $this->pan_number,
            'cin_number' => $this->cin_number,
            'msme_number' => $this->msme_number,
            'bank_name' => $this->bank_name,
            'branch_name' => $this->branch_name,
            'account_holder' => $this->account_holder,
            'account_number' => $this->account_number,
            'ifsc_code' => $this->ifsc_code,
            'preferred_payment_method' => $this->preferred_payment_method,
            'billing_email' => $this->billing_email,
            'service_description' => $this->service_description,
            'products_offered' => $this->products_offered,
            'annual_business_volume' => $this->annual_business_volume,
            'years_in_business' => $this->years_in_business,
            'existing_clients' => $this->existing_clients,
            'preferred_communication' => $this->preferred_communication,
            'primary_contact_name' => $this->primary_contact_name,
            'primary_contact_email' => $this->primary_contact_email,
            'primary_contact_phone' => $this->primary_contact_phone,
            'secondary_contact_name' => $this->secondary_contact_name,
            'secondary_contact_email' => $this->secondary_contact_email,
            'secondary_contact_phone' => $this->secondary_contact_phone,
            'status' => 'pending',
        ];

        // Upload files if present
        if ($this->license_upload) {
            $data['license_upload'] = $this->license_upload->store('vendors/licenses', 'public');
        }
        if ($this->tax_exemption_upload) {
            $data['tax_exemption_upload'] = $this->tax_exemption_upload->store('vendors/tax-exemptions', 'public');
        }
        if ($this->cancelled_cheque_upload) {
            $data['cancelled_cheque_upload'] = $this->cancelled_cheque_upload->store('vendors/cheques', 'public');
        }
        if ($this->catalog_upload) {
            $data['catalog_upload'] = $this->catalog_upload->store('vendors/catalogs', 'public');
        }
        if ($this->pan_copy) {
            $data['pan_copy'] = $this->pan_copy->store('vendors/documents/pan', 'public');
        }
        if ($this->gst_certificate) {
            $data['gst_certificate'] = $this->gst_certificate->store('vendors/documents/gst', 'public');
        }
        if ($this->company_registration) {
            $data['company_registration'] = $this->company_registration->store('vendors/documents/registration', 'public');
        }
        if ($this->authorized_signatory_id) {
            $data['authorized_signatory_id'] = $this->authorized_signatory_id->store('vendors/documents/signatory', 'public');
        }
        if ($this->address_proof) {
            $data['address_proof'] = $this->address_proof->store('vendors/documents/address', 'public');
        }
        if (!empty($this->other_documents)) {
            $otherDocs = [];
            foreach ($this->other_documents as $doc) {
                $otherDocs[] = $doc->store('vendors/documents/other', 'public');
            }
            $data['other_documents'] = $otherDocs;
        }

        // Link to user if authenticated
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }

        Vendor::create($data);

        session()->flash('success', 'Your vendor registration has been submitted successfully! Our team will review your application and get back to you soon.');

        return redirect()->route('vendor.register')->with('success', true);
    }

    public function render()
    {
        return view('livewire.vendor-registration');
    }
}
