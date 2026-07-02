<x-filament-panels::page>
    <style>
        .display-settings-shell {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            align-items: start;
        }

        .display-settings-topbar {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 1.25rem;
            background: linear-gradient(135deg, rgba(5, 91, 217, 0.05), rgba(34, 197, 94, 0.04));
        }

        .dark .display-settings-topbar {
            border-color: rgb(var(--gray-800));
            background: rgba(15, 23, 42, 0.45);
        }

        .display-settings-topbar-copy {
            min-width: 0;
        }

        .display-settings-preview-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1rem;
            border-radius: 0.95rem;
            background: var(--app-primary-hex);
            color: white;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 20px rgba(5, 91, 217, 0.18);
        }

        @media (max-width: 1024px) {
            .display-settings-shell {
                grid-template-columns: 1fr;
            }
        }

        .display-settings-sidebar {
            position: sticky;
            top: 2rem;
        }

        .display-settings-nav-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.875rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.875rem;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .display-settings-nav-item:hover {
            background: rgba(var(--primary-500), 0.05);
            border-color: #055bd917;
        }

        .display-settings-nav-item.active {
            background: rgba(var(--primary-500), 0.1);
            border-color: #055bd917;
            box-shadow: 0 4px 12px -2px rgba(var(--primary-500), 0.1);
        }

        .display-settings-nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: color-mix(in srgb, var(--app-primary-hex) 14%, transparent);
            color: rgb(var(--gray-500));
        }

        .display-settings-nav-item.active .display-settings-nav-icon {
            background: var(--app-primary-hex);
            color: white;
        }

        .display-settings-nav-label {
            font-weight: 700;
            font-size: 0.95rem;
            color: rgb(var(--gray-900));
        }

        .dark .display-settings-nav-label {
            color: rgb(var(--gray-100));
        }

        .display-settings-nav-desc {
            font-size: 0.75rem;
            color: rgb(var(--gray-500));
            margin-top: 0.125rem;
        }

        .display-settings-card {
            background: white;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.05);
        }

        .dark .display-settings-card {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-800));
        }

        .display-settings-card-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgb(var(--gray-100));
            background: linear-gradient(135deg, rgba(5, 91, 217, 0.06), rgba(34, 197, 94, 0.04));
        }

        .dark .display-settings-card-head {
            border-color: rgb(var(--gray-800));
            background: rgba(15, 23, 42, 0.35);
        }

        .display-settings-card-body {
            padding: 1.5rem;
        }

        .display-settings-card-foot {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid rgb(var(--gray-100));
            display: flex;
            justify-content: flex-end;
            background: rgba(var(--gray-50), 0.55);
        }

        .dark .display-settings-card-foot {
            border-color: rgb(var(--gray-800));
            background: rgba(15, 23, 42, 0.45);
        }
    </style>

    <div class="display-settings-topbar">
        <div class="display-settings-topbar-copy">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $pageHeading ?? $this->getTitle() }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $pageDescription ?? '' }}</p>
        </div>

        <a href="{{ route('opd-token.display') }}" target="_blank" rel="noreferrer" class="display-settings-preview-link">
            View OPD Token Page
            <span aria-hidden="true">↗</span>
        </a>
    </div>

    <div class="display-settings-shell">
        <aside class="display-settings-sidebar">
            <div class="mb-6 px-1">
                <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">Sections</h2>
            </div>

            <nav>
                @foreach ($this->getDisplaySettingsTabs() as $key => $tab)
                    <div wire:click="$set('activeTab', '{{ $key }}')" class="display-settings-nav-item {{ $activeTab === $key ? 'active' : '' }}">
                        <div class="display-settings-nav-icon">
                            <x-dynamic-component :component="$tab['icon']" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="display-settings-nav-label">{{ $tab['label'] }}</div>
                            <div class="display-settings-nav-desc">{{ $tab['description'] }}</div>
                        </div>
                    </div>
                @endforeach
            </nav>
        </aside>

        <main class="min-w-0">
            <form wire:submit="save">
                <div class="display-settings-card">
                    <div class="display-settings-card-head">
                        <div class="text-sm font-semibold text-primary-600 dark:text-primary-400">Admin managed</div>
                        <div class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $this->getDisplaySettingsTabs()[$activeTab]['label'] ?? ($pageHeading ?? $this->getTitle()) }}
                        </div>
                    </div>

                    <div class="display-settings-card-body">
                        {{ $this->form }}
                    </div>

                    <div class="display-settings-card-foot">
                        <x-filament::button type="submit" size="lg">
                            Save Changes
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</x-filament-panels::page>
