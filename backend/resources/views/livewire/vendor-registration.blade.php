<x-layouts.app>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Vendor Registration</h1>
                <p class="text-gray-600">Join our network of trusted vendors and partners</p>
            </div>

            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-green-700 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Progress Steps -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-8">
                    @for ($i = 1; $i <= $totalSteps; $i++)
                        <div class="flex items-center flex-1">
                            <div class="flex flex-col items-center">
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center font-semibold text-sm transition-all
                                {{ $currentStep >= $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    @if ($currentStep > $i)
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @else
                                        {{ $i }}
                                    @endif
                                </div>
                                <span
                                    class="mt-2 text-xs font-medium {{ $currentStep >= $i ? 'text-blue-600' : 'text-gray-500' }}">
                                    @if ($i == 1)
                                        Basic Info
                                    @elseif($i == 2)
                                        Address
                                    @elseif($i == 3)
                                        Legal
                                    @elseif($i == 4)
                                        Banking & Services
                                    @else
                                        Documents
                                    @endif
                                </span>
                            </div>
                            @if ($i < $totalSteps)
                                <div class="flex-1 h-1 mx-2 {{ $currentStep > $i ? 'bg-blue-600' : 'bg-gray-200' }}">
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <form wire:submit.prevent="submit">
                    <!-- Step 1: Basic Information -->
                    @if ($currentStep == 1)
                        <div class="space-y-6">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Basic Information</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                                    <input type="text" wire:model="company_name"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('company_name')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vendor Type *</label>
                                    <select wire:model="vendor_type"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select Type</option>
                                        <option value="medical_equipment">Medical Equipment Supplier</option>
                                        <option value="it_vendor">IT Vendor</option>
                                        <option value="maintenance">Maintenance Service</option>
                                        <option value="lab_partner">Lab Partner</option>
                                        <option value="consultant">Consultant</option>
                                    </select>
                                    @error('vendor_type')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person *</label>
                                    <input type="text" wire:model="contact_person"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('contact_person')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Designation</label>
                                    <input type="text" wire:model="designation"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input type="email" wire:model="email"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('email')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Alternate Email</label>
                                    <input type="email" wire:model="alt_email"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mobile *</label>
                                    <input type="tel" wire:model="mobile"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('mobile')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Alternate Mobile</label>
                                    <input type="tel" wire:model="alt_mobile"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                    <input type="url" wire:model="website"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Step 2: Business Address -->
                    @if ($currentStep == 2)
                        <div class="space-y-6">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Business Address</h2>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Registered Office
                                        Address
                                        *</label>
                                    <textarea wire:model="registered_office_address" rows="3"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                    @error('registered_office_address')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                        <input type="text" wire:model="city"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('city')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                                        <input type="text" wire:model="state"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('state')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">PIN Code *</label>
                                        <input type="text" wire:model="pin_code"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('pin_code')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                        <input type="text" wire:model="country"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('country')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch Office
                                        Address</label>
                                    <textarea wire:model="branch_office_address" rows="3"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Step 3: Legal & Compliance -->
                    @if ($currentStep == 3)
                        <div class="space-y-6">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Legal & Compliance</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">GST Number *</label>
                                    <input type="text" wire:model="gst_number"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('gst_number')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">PAN Number *</label>
                                    <input type="text" wire:model="pan_number"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @error('pan_number')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CIN Number</label>
                                    <input type="text" wire:model="cin_number"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">MSME Number</label>
                                    <input type="text" wire:model="msme_number"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">License Upload</label>
                                    <input type="file" wire:model="license_upload"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @if ($license_upload)
                                        <span class="text-sm text-green-600">File selected</span>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tax Exemption
                                        Upload</label>
                                    <input type="file" wire:model="tax_exemption_upload"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @if ($tax_exemption_upload)
                                        <span class="text-sm text-green-600">File selected</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Step 4: Banking & Products -->
                    @if ($currentStep == 4)
                        <div class="space-y-6">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Banking & Product Information</h2>

                            <div class="space-y-6">
                                <div class="border-b pb-4">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Banking Details</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name
                                                *</label>
                                            <input type="text" wire:model="bank_name"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @error('bank_name')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Branch Name
                                                *</label>
                                            <input type="text" wire:model="branch_name"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @error('branch_name')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Account Holder
                                                Name
                                                *</label>
                                            <input type="text" wire:model="account_holder"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @error('account_holder')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Account Number
                                                *</label>
                                            <input type="text" wire:model="account_number"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @error('account_number')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">IFSC Code
                                                *</label>
                                            <input type="text" wire:model="ifsc_code"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @error('ifsc_code')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred
                                                Payment
                                                Method *</label>
                                            <select wire:model="preferred_payment_method"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Select Method</option>
                                                <option value="NEFT">NEFT</option>
                                                <option value="RTGS">RTGS</option>
                                                <option value="UPI">UPI</option>
                                                <option value="Cheque">Cheque</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            @error('preferred_payment_method')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Billing Email
                                                *</label>
                                            <input type="email" wire:model="billing_email"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @error('billing_email')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Cancelled
                                                Cheque
                                                Upload</label>
                                            <input type="file" wire:model="cancelled_cheque_upload"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if ($cancelled_cheque_upload)
                                                <span class="text-sm text-green-600">File selected</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="border-b pb-4">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Product & Service Information
                                    </h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Service
                                                Description
                                                *</label>
                                            <textarea wire:model="service_description" rows="3"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                            @error('service_description')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Products
                                                Offered
                                                *</label>
                                            <textarea wire:model="products_offered" rows="3"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                            @error('products_offered')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Years in
                                                    Business *</label>
                                                <input type="number" wire:model="years_in_business"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                @error('years_in_business')
                                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Annual
                                                    Business
                                                    Volume</label>
                                                <input type="number" wire:model="annual_business_volume"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Catalog
                                                    Upload</label>
                                                <input type="file" wire:model="catalog_upload"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                @if ($catalog_upload)
                                                    <span class="text-sm text-green-600">File selected</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Existing
                                                Clients</label>
                                            <textarea wire:model="existing_clients" rows="2"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Step 5: Documents & Contact Preferences -->
                    @if ($currentStep == 5)
                        <div class="space-y-6">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Documents & Contact Preferences</h2>

                            <div class="space-y-6">
                                <div class="border-b pb-4">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Statutory Documents</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">PAN
                                                Copy</label>
                                            <input type="file" wire:model="pan_copy"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if ($pan_copy)
                                                <span class="text-sm text-green-600">File selected</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">GST
                                                Certificate</label>
                                            <input type="file" wire:model="gst_certificate"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if ($gst_certificate)
                                                <span class="text-sm text-green-600">File selected</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Company
                                                Registration</label>
                                            <input type="file" wire:model="company_registration"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if ($company_registration)
                                                <span class="text-sm text-green-600">File selected</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Authorized
                                                Signatory ID</label>
                                            <input type="file" wire:model="authorized_signatory_id"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if ($authorized_signatory_id)
                                                <span class="text-sm text-green-600">File selected</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Address
                                                Proof</label>
                                            <input type="file" wire:model="address_proof"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if ($address_proof)
                                                <span class="text-sm text-green-600">File selected</span>
                                            @endif
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Other
                                                Documents</label>
                                            <input type="file" wire:model="other_documents" multiple
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            @if (!empty($other_documents))
                                                <span class="text-sm text-green-600">{{ count($other_documents) }}
                                                    file(s)
                                                    selected</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Contact Preferences</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred
                                                Communication *</label>
                                            <select wire:model="preferred_communication"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">Select Method</option>
                                                <option value="email">Email</option>
                                                <option value="phone">Phone</option>
                                                <option value="portal">Portal Messages</option>
                                            </select>
                                            @error('preferred_communication')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="md:col-span-2 border-t pt-4">
                                            <h4 class="font-medium text-gray-700 mb-3">Primary Contact</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Name
                                                        *</label>
                                                    <input type="text" wire:model="primary_contact_name"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    @error('primary_contact_name')
                                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email
                                                        *</label>
                                                    <input type="email" wire:model="primary_contact_email"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    @error('primary_contact_email')
                                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone
                                                        *</label>
                                                    <input type="tel" wire:model="primary_contact_phone"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    @error('primary_contact_phone')
                                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="md:col-span-2 border-t pt-4">
                                            <h4 class="font-medium text-gray-700 mb-3">Secondary Contact (Optional)
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                                    <input type="text" wire:model="secondary_contact_name"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                                    <input type="email" wire:model="secondary_contact_email"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                                    <input type="tel" wire:model="secondary_contact_phone"
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between mt-8 pt-6 border-t">
                        <button type="button" wire:click="previousStep"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors {{ $currentStep == 1 ? 'invisible' : '' }}">
                            Previous
                        </button>

                        @if ($currentStep < $totalSteps)
                            <button type="button" wire:click="nextStep"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                Next Step
                            </button>
                        @else
                            <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                                Submit Registration
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Footer Note -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>Your information will be reviewed by our team. You will be notified once your registration is
                    approved.
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
