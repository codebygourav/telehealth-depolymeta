<x-filament-panels::page>
    @php($preview = $this->getPreviewStats())

    <div class="grid grid-cols-1 gap-6">
        <section class="space-y-4">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid gap-0 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.95fr)]">
                    <div class="p-6 lg:p-8">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-400">
                            Display Content Manager
                        </div>
                        <h1 class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                            Clean, focused content control for OPD display screens
                        </h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-gray-400">
                            Add only the content your team needs to manage. Essential fields stay visible first,
                            while scheduling and doctor targeting stay tucked away until they are needed.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                Essential fields first
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300">
                                Optional timing rules
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300">
                                Fast doctor targeting
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-slate-50 p-6 dark:border-gray-800 dark:bg-gray-950/40 lg:border-l lg:border-t-0">
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-gray-400">
                            Current Highlight
                        </div>
                        <h2 class="mt-3 text-lg font-bold text-slate-900 dark:text-white">
                            {{ $preview['title'] }}
                        </h2>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-gray-400">
                            {{ $preview['description'] }}
                        </p>
                        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">Category</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $preview['category'] }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">Format</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $preview['media_type'] }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">Audience</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $preview['doctors'] }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-gray-400">Schedule</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $preview['schedule'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                {{ $this->table }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
