<x-filament-panels::page>
    <style>
        .doctor-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .doctor-layout {
                grid-template-columns: 1fr;
            }
        }
        .profile-card {
            position: sticky;
            top: 2rem;
            background: white;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .dark .profile-card {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-800));
        }
        .avatar {
            width: 84px;
            height: 84px;
            border-radius: 20px;
            background: linear-gradient(135deg, #dbeafe, #fff);
            border: 1px dashed #93c5fd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-weight: 800;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #047857;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .quick {
            margin-top: 16px;
            border-top: 1px solid rgb(var(--gray-200));
            padding-top: 12px;
        }
        .dark .quick {
            border-top-color: rgb(var(--gray-800));
        }
        .quick div {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 0;
            color: rgb(var(--gray-700));
            font-size: 13px;
        }
        .dark .quick div {
            color: rgb(var(--gray-300));
        }
        .quick span {
            color: rgb(var(--gray-500));
        }
        .form-panel {
            background: white;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        .dark .form-panel {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-800));
        }
        
        /* Force active tab item to use primary bg color & white text */
        .fi-sc-tabs nav button[aria-selected="true"],
        .fi-sc-tabs nav button[active],
        .fi-sc-tabs nav button.fi-active,
        .fi-sc-tabs nav a[aria-selected="true"],
        .fi-sc-tabs nav a.fi-active,
        .fi-tabs nav button[aria-selected="true"],
        .fi-tabs nav button.fi-active {
            background: var(--app-primary-hex) !important;
            color: white !important;
            font-weight: 600 !important;
            border-radius: 0.5rem !important;
        }
    </style>

    @php
        $isCreate = $this instanceof \Filament\Resources\Pages\CreateRecord;
        $submitMethod = $isCreate ? 'create' : 'save';
    @endphp

    <div class="doctor-layout">
        <aside class="profile-card">
            @php
                $firstName = $this->data['first_name'] ?? '';
                $lastName = $this->data['last_name'] ?? '';
                $name = trim("{$firstName} {$lastName}");
                if (empty($name)) {
                    $name = $isCreate ? 'New Doctor' : 'Doctor Details';
                }
                
                $initials = '';
                if (!empty($firstName)) {
                    $initials .= strtoupper(substr($firstName, 0, 1));
                }
                if (!empty($lastName)) {
                    $initials .= strtoupper(substr($lastName, 0, 1));
                }
                if (empty($initials)) {
                    $initials = 'DR';
                }

                $licenseNum = $this->data['medical_license_number'] ?? 'N/A';
                $startYear = $this->data['career_start_year'] ?? 'N/A';
                
                $langs = $this->data['languages_known'] ?? [];
                $langsCount = is_array($langs) ? count($langs) : 0;
                
                $status = $this->data['status'] ?? 'active';
            @endphp
            
            <div class="avatar">{{ $initials }}</div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" style="margin: 0 0 4px">{{ $name }}</h3>
            
            <span class="status" style="margin-top: 8px;">
                ● Profile {{ $status }}
            </span>
            
            <div class="quick">
                <div><span>License No.</span><b>{{ $licenseNum }}</b></div>
                <div><span>Career Start</span><b>{{ $startYear }}</b></div>
                <div><span>Languages</span><b>{{ $langsCount }} known</b></div>
            </div>
            
            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 rounded-xl text-xs text-amber-800 dark:text-amber-300">
                Awards, certificates, working experience, and education can be added as structured fields or as free-text HTML using the toggle switch inside their respective tabs.
            </div>
        </aside>
        
        <div class="form-panel">
            <form wire:submit="{{ $submitMethod }}">
                <!-- Top Actions Bar -->
                <div class="mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $isCreate ? 'Create Doctor Profile' : 'Edit Doctor Profile' }}
                    </h2>
                    <x-filament::actions
                        :actions="$this->getFormActions()"
                        :alignment="$this->getFormActionsAlignment()"
                    />
                </div>

                {{ $this->form }}
                
                <!-- Bottom Actions Bar -->
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <x-filament::actions
                        :actions="$this->getFormActions()"
                        :alignment="$this->getFormActionsAlignment()"
                    />
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
