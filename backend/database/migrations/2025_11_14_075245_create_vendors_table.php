<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('company_name');
            $table->string('vendor_type');
            $table->string('contact_person');
            $table->string('designation')->nullable();
            $table->string('email')->unique();
            $table->string('alt_email')->nullable();
            $table->string('mobile');
            $table->string('alt_mobile')->nullable();
            $table->string('website')->nullable();

            // Business Address
            $table->text('registered_office_address');
            $table->string('city');
            $table->string('state');
            $table->string('pin_code');
            $table->string('country');
            $table->text('branch_office_address')->nullable();

            // Legal & Compliance
            $table->string('gst_number');
            $table->string('pan_number');
            $table->string('cin_number')->nullable();
            $table->string('msme_number')->nullable();
            $table->string('license_upload')->nullable();
            $table->string('tax_exemption_upload')->nullable();

            // Banking
            $table->string('bank_name');
            $table->string('branch_name');
            $table->string('account_holder');
            $table->string('account_number');
            $table->string('ifsc_code');
            $table->string('cancelled_cheque_upload')->nullable();
            $table->string('preferred_payment_method');
            $table->string('billing_email');

            // Product / Service Info
            $table->text('service_description');
            $table->text('products_offered');
            $table->string('catalog_upload')->nullable();
            $table->integer('annual_business_volume')->nullable();
            $table->integer('years_in_business');
            $table->text('existing_clients')->nullable();

            // Statutory Docs
            $table->string('pan_copy')->nullable();
            $table->string('gst_certificate')->nullable();
            $table->string('company_registration')->nullable();
            $table->string('authorized_signatory_id')->nullable();
            $table->string('address_proof')->nullable();
            $table->json('other_documents')->nullable();

            // Contact Preferences
            $table->string('preferred_communication');
            $table->string('primary_contact_name');
            $table->string('primary_contact_email');
            $table->string('primary_contact_phone');
            $table->string('secondary_contact_name')->nullable();
            $table->string('secondary_contact_email')->nullable();
            $table->string('secondary_contact_phone')->nullable();

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
