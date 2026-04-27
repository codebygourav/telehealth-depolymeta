<x-filament-panels::page>
    <div class="space-y-6" wire:init="loadData" x-data="{ expandedFilters: false }">

        {{-- Header + Period Tabs --}}
        <div
            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between px-6 bg-white shadow-sm dark:bg-gray-800 rounded-lg p-6 border border-grey-100">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                    Doctor Report
                </h1>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                    Performance and audit overview.
                </p>
            </div>

            <div
                class="flex items-center self-start md:self-auto gap-1 rounded-lg bg-gray-100/80 p-1.5 dark:bg-white/5 w-fit">
                <button wire:click="setPeriod('weekly')"
                    class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ $activePeriod === 'weekly' ? 'bg-primary text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-200  bg-white shadow-sm' }}">
                    Weekly
                </button>
                <button wire:click="setPeriod('monthly')"
                    class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ $activePeriod === 'monthly' ? 'bg-primary text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-200 bg-white shadow-sm' }}">
                    Monthly
                </button>
                <button wire:click="setPeriod('yearly')"
                    class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ $activePeriod === 'yearly' ? 'bg-primary text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-200  bg-white shadow-sm' }}">
                    Yearly
                </button>
            </div>
        </div>

        @if (!$isLoaded)
            {{-- Loading State --}}
            <div class="space-y-6 animate-pulse">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="h-28 rounded-2xl bg-gray-100 dark:bg-white/5"></div>
                    @endfor
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="h-80 rounded-2xl bg-gray-100 dark:bg-white/5"></div>
                    <div class="h-80 rounded-2xl bg-gray-100 dark:bg-white/5"></div>
                </div>
            </div>
        @else
            <div class="space-y-8">

                {{-- Summary KPI Cards --}}
                <div class="transition-all duration-300 ease-in-out">
                    @livewire(\App\Filament\Widgets\DoctorStatsWidget::class, ['filters' => $data])
                </div>

                {{-- Charts Section --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div
                        class="transition-all duration-300 ease-in-out rounded-2xl overflow-hidden bg-gray-50/50 dark:bg-white/5">
                        @livewire(\App\Filament\Widgets\DoctorRevenueChart::class, ['filters' => $data])
                    </div>
                    <div
                        class="transition-all duration-300 ease-in-out rounded-2xl overflow-hidden bg-gray-50/50 dark:bg-white/5">
                        @livewire(\App\Filament\Widgets\DoctorConsultationChart::class, ['filters' => $data])
                    </div>
                </div>


            </div>
        @endif

        {{-- Expanded Record Table --}}
        <div class="rounded-2xl bg-gray-50 dark:bg-white/5 flex flex-col w-full border border-gray-200">
            <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-table-cells class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    <h2 class="text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                        Detailed Audit Log
                    </h2>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <div class="w-full sm:w-64">
                        <form wire:submit.prevent="submit">
                            {{ $this->form }}
                        </form>
                    </div>
                    <button wire:loading.attr="disabled" wire:click="mountTableAction('export_audit_excel')" type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:hover:bg-white/10 transition w-full sm:w-auto mt-2 sm:mt-0 bg-primary text-white" style="height: 38px;">
                        <x-heroicon-o-document-arrow-down class="h-5 w-5 text-white dark:text-gray-500" />
                        <span class="text-white">Export Audit Excel</span>
                    </button>
                </div>
            </div>
            <div class="p-0 overflow-x-auto border-t border-gray-200 dark:border-white/5">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
