<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 ">
        <section class="space-y-4">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-400">
                            Display Content Manager
                        </div>
                        <h1 class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            Schedule, media, and doctor-targeted display content
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                            Keep this content clean for big waiting-room screens. Use scheduling only when needed,
                            target specific doctors when required, and keep the preview panel updated while editing.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                {{ $this->table }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
