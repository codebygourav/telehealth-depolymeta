@php
    $user = filament()->auth()->user();
@endphp

<div
    class="flex flex-col gap-2 px-3 py-1 border-t border-gray-200 dark:border-white/10 shrink-0 bg-gray-50 dark:bg-gray-900 transition-all duration-300">

    <div class="flex items-center gap-1 transition-all duration-300 justify-between">

        {{-- Expanded State: Full Dropdown (Top-Start) --}}
        <div x-show="$store.sidebar.isOpen" class="flex-1 min-w-0" style="display: none;">
            <x-filament::dropdown placement="top-end" teleport>
                <x-slot name="trigger">
                    <button
                        class="flex items-center gap-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg p-2 transition-colors group w-full">
                        <x-filament::avatar :src="filament()->getUserAvatarUrl($user)" :alt="filament()->getUserName($user)"
                            class="w-7 h-7 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700" />

                        <div class="text-start flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white truncate">
                                {{ filament()->getUserName($user) }}
                            </p>
                        </div>

                        <x-heroicon-m-chevron-down
                            class="w-4 h-4 text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors shrink-0" />
                    </button>
                </x-slot>

                <x-filament::dropdown.list class="w-auto pb-4">
                    @if (filament()->getProfileUrl())
                        <x-filament::dropdown.list.item :href="filament()->getProfileUrl()" tag="a" icon="heroicon-m-user-circle">
                            Profile
                        </x-filament::dropdown.list.item>
                    @endif

                    <x-filament::dropdown.list.item :href="filament()->getUrl() . '/settings'" tag="a" icon="heroicon-m-cog-6-tooth">
                        Settings
                    </x-filament::dropdown.list.item>

                    <form action="{{ filament()->getLogoutUrl() }}" method="post" class="contents">
                        @csrf
                        <x-filament::dropdown.list.item tag="button" type="submit"
                            icon="heroicon-m-arrow-right-start-on-rectangle" color="danger">
                            Sign Out
                        </x-filament::dropdown.list.item>
                    </form>
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        </div>

        {{-- Collapsed State: Icon Dropdown (Right-End) -> Changed to Top-Start --}}
        <div x-show="! $store.sidebar.isOpen" class="w-full flex justify-center" style="display: none;">
            <x-filament::dropdown placement="top-end" teleport>
                <x-slot name="trigger">
                    <button
                        class="flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full p-1 transition-colors group">
                        <x-filament::avatar :src="filament()->getUserAvatarUrl($user)" :alt="filament()->getUserName($user)"
                            class="w-8 h-8 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700" />
                    </button>
                </x-slot>

                <x-filament::dropdown.list class="w-auto pb-4">
                    @if (filament()->getProfileUrl())
                        <x-filament::dropdown.list.item :href="filament()->getProfileUrl()" tag="a" icon="heroicon-m-user-circle">
                            Profile
                        </x-filament::dropdown.list.item>
                    @endif

                    <x-filament::dropdown.list.item :href="filament()->getUrl() . '/settings'" tag="a" icon="heroicon-m-cog-6-tooth">
                        Settings
                    </x-filament::dropdown.list.item>

                    <form action="{{ filament()->getLogoutUrl() }}" method="post" class="contents">
                        @csrf
                        <x-filament::dropdown.list.item tag="button" type="submit"
                            icon="heroicon-m-arrow-right-start-on-rectangle" color="danger">
                            Sign Out
                        </x-filament::dropdown.list.item>
                    </form>
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        </div>

    </div>

</div>
