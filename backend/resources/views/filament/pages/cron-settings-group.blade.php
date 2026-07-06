<x-filament-panels::page>
    <style>
        .settings-group-shell {
            display: grid;
            gap: 1.5rem;
        }

        .settings-group-hero {
            padding: 1.25rem 1.5rem;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 1.25rem;
            background: linear-gradient(135deg, rgba(5, 91, 217, 0.06), rgba(14, 165, 233, 0.04));
        }

        .dark .settings-group-hero {
            border-color: rgb(var(--gray-800));
            background: rgba(15, 23, 42, 0.45);
        }

        .settings-group-card {
            background: white;
            border: 1px solid rgb(var(--gray-200));
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.05);
        }

        .dark .settings-group-card {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-800));
        }

        .settings-group-card-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgb(var(--gray-100));
            background: rgba(var(--gray-50), 0.65);
        }

        .dark .settings-group-card-head {
            border-color: rgb(var(--gray-800));
            background: rgba(15, 23, 42, 0.4);
        }

        .settings-group-card-body {
            padding: 1.5rem;
        }

        .settings-group-card-foot {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid rgb(var(--gray-100));
            display: flex;
            justify-content: flex-end;
            background: rgba(var(--gray-50), 0.55);
        }

        .dark .settings-group-card-foot {
            border-color: rgb(var(--gray-800));
            background: rgba(15, 23, 42, 0.45);
        }
    </style>

    <div class="settings-group-shell">
        <div class="settings-group-hero">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $pageHeading ?? $this->getTitle() }}</h1>
            @if (!empty($pageDescription ?? null))
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $pageDescription }}</p>
            @endif
        </div>

        <form wire:submit="save">
            <div class="settings-group-card">
                <div class="settings-group-card-head">
                    <div class="text-sm font-semibold text-primary-600 dark:text-primary-400">System Configuration</div>
                    <div class="text-base font-bold text-gray-900 dark:text-white">{{ $pageHeading ?? $this->getTitle() }}</div>
                </div>

                <div class="settings-group-card-body">
                    {{ $this->form }}
                </div>

                <div class="settings-group-card-foot">
                    <x-filament::button type="submit" size="lg">
                        Save Changes
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>
