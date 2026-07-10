<x-filament-panels::page>
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 370px 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }

        .settings-sidebar {
            position: sticky;
            top: 2rem;
        }

        /* Support Tabs Styling */

        .fi-tabs-item {
            border-radius: 10px !important;
            transition: all 0.2s ease;
            padding: 10px 18px !important;
            font-weight: 600;
        }

        /* Active Tab */
        button.fi-tabs-item.fi-active {
            background: #055bd9 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(7, 56, 39, 0.18);
        }

        button.fi-tabs-item.fi-active .fi-tabs-item-label {
            color: #fff !important;
        }


        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .nav-item:hover {
            background: rgba(var(--primary-500), 0.05);
            border-color: #055bd917;
        }

        .nav-item.active {
            background: rgba(var(--primary-500), 0.1);
            border-color: #055bd917;
            box-shadow: 0 4px 12px -2px rgba(var(--primary-500), 0.1);
        }

        .nav-item .icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            margin-right: 1rem;
            transition: all 0.2s ease;
        }

        .nav-item.active .icon-wrapper {
            background: var(--app-primary-hex) !important;
            color: white;
        }

        .nav-item:not(.active) .icon-wrapper {
            background: color-mix(in srgb, var(--app-primary-hex) 14%, transparent);
            color: rgb(var(--gray-500));
        }

        .nav-item .label-wrapper {
            display: flex;
            flex-direction: column;
        }

        .nav-item .label {
            font-weight: 600;
            font-size: 0.95rem;
            color: rgb(var(--gray-900));
        }

        .nav-item.active .label {
            color: var(--app-primary-hex) !important;
        }

        .dark .nav-item .label {
            color: rgb(var(--gray-100));
        }

        .nav-item .desc {
            font-size: 0.75rem;
            color: rgb(var(--gray-500));
            margin-top: 0.125rem;
        }

        .form-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid rgb(var(--gray-200));
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .form-container {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-800));
        }

        .form-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgb(var(--gray-100));
            background: rgba(var(--gray-50), 0.5);
        }

        .dark .form-header {
            border-color: rgb(var(--gray-800));
            background: rgba(var(--gray-800), 0.3);
        }

        .form-content {
            padding: 2rem;
        }

        .form-footer {
            padding: 1.25rem 2rem;
            background: rgba(var(--gray-50), 0.8);
            border-top: 1px solid rgb(var(--gray-100));
            display: flex;
            justify-content: flex-end;
        }

        .dark .form-footer {
            background: rgba(var(--gray-800), 0.5);
            border-color: rgb(var(--gray-800));
        }

        .nav-item svg {
            color: var(--app-primary-hex);
        }

        .nav-item.active svg {
            color: #fff;
        }
    </style>

    <div class="settings-container">
    
        {{-- Sidebar Navigation --}}
        <aside class="settings-sidebar">
            

            <nav>
                @foreach (config('settings') as $key => $group)
                    @if (in_array($key, $this->getHiddenSettingsGroups(), true))
                        @continue
                    @endif

                    <div wire:click="$set('activeTab', '{{ $key }}')"
                        class="nav-item {{ $activeTab === $key ? 'active' : '' }}">
                        <div class="icon-wrapper">
                            @if (isset($group['icon']))
                                <x-dynamic-component :component="$group['icon']" class="w-5 h-5" />
                            @else
                                <x-heroicon-o-cog class="w-5 h-5" />
                            @endif
                        </div>
                        <div class="label-wrapper">
                            <span class="label">{{ $group['label'] ?? ucfirst($key) }}</span>
                            <span
                                class="desc">{{ $group['description'] ?? 'Manage ' . strtolower($group['label'] ?? $key) }}</span>
                        </div>

                    </div>
                @endforeach
            </nav>

        </aside>

        {{-- Main Content --}}
        <main class="min-w-0">
            <form wire:submit="save">
                <div class="form-container">
                    @php
                        $currentGroup = config("settings.{$activeTab}");
                    @endphp


                    <div class="form-content">
                        {{ $this->form }}
                    </div>

                    <div class="form-footer">
                        <x-filament::button type="submit" size="xl" class="shadow-lg shadow-primary-500/20">
                            <x-slot name="icon">
                                <x-heroicon-o-check class="w-5 h-5" />
                            </x-slot>
                            Save Changes
                        </x-filament::button>
                    </div>
                </div>
            </form>

            {{-- Mobile Cache View (only if needed, but let's keep it clean) --}}
            <div
                class="hidden mt-8 p-6 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                {{-- Hidden on desktop as it's in the sidebar --}}
            </div>
        </main>
    </div>

    {{-- Quick Actions Section --}}
    <div class="mt-10">
        <div class="form-container">
            <div class="form-header flex items-center justify-between py-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-shade-min dark:bg-gray-800 rounded-lg text-primary-600" style="color: #055bd9">
                        <x-heroicon-o-bolt class="w-5 h-5" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Quick Actions</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 hidden sm:block">Perform essential system maintenance
                    tasks</p>
            </div>
            <div class="form-content !py-6">
                <div class="flex flex-wrap gap-4">
                    <button wire:click="clearCache" wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-gray-100 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm"
                        style="color: #055bd9">
                        <x-heroicon-o-arrow-path class="w-5 h-5" wire:loading.class="animate-spin" />
                        Clear All System Cache
                    </button>

                    <button wire:click="clearConfigCache" wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-gray-100 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm"
                        style="color: #055bd9">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                        Refresh Configurations
                    </button>

                    <button wire:click="clearViewCache" wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-gray-100 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm"
                        style="color: #055bd9">
                        <x-heroicon-o-eye class="w-5 h-5" />
                        Clear Views
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
