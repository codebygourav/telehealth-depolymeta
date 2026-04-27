@php
    $navigation = filament()->getNavigation();
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
    $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
    $hasNavigation = filament()->hasNavigation();
    $hasTopbar = filament()->hasTopbar();
    $menuItems = $this->getMenuItems();
@endphp

<div>
    <aside x-data="customSidebar()"
        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-cloak
        @else
            x-cloak="-lg" @endif
        x-bind:class="{ 'custom-sidebar-open': isOpen, 'fi-sidebar-open': isOpen }"
        class="custom-sidebar fi-sidebar fi-main-sidebar">
        {{-- Sidebar Header --}}
        @if (!$hasTopbar)
            <div class="custom-sidebar-header">
                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                    <button x-show="!isOpen" @click="open()" class="custom-sidebar-toggle-btn" type="button"
                        aria-label="{{ __('filament-panels::layout.actions.sidebar.expand.label') }}">
                        <x-filament::icon :icon="$isRtl ? 'heroicon-o-chevron-left' : 'heroicon-o-chevron-right'" class="w-5 h-5" />
                    </button>
                    <button x-show="isOpen" @click="close()" class="custom-sidebar-toggle-btn" type="button"
                        aria-label="{{ __('filament-panels::layout.actions.sidebar.collapse.label') }}">
                        <x-filament::icon :icon="$isRtl ? 'heroicon-o-chevron-right' : 'heroicon-o-chevron-left'" class="w-5 h-5" />
                    </button>
                @endif

                <div x-show="isOpen" class="custom-sidebar-logo">
                    @if ($homeUrl = filament()->getHomeUrl())
                        <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                            <x-filament-panels::logo />
                        </a>
                    @else
                        <x-filament-panels::logo />
                    @endif
                </div>
            </div>
        @endif

        {{-- Navigation Menu --}}
        <nav class="custom-sidebar-nav">
            <ul class="custom-sidebar-menu">
                @foreach ($menuItems as $menuItem)
                    @if ($menuItem['type'] === 'item')
                        {{-- Single Menu Item --}}
                        <li class="custom-sidebar-menu-item">
                            <a href="{{ $menuItem['url'] }}"
                                @click="window.matchMedia('(max-width: 1024px)').matches && close()"
                                class="custom-sidebar-link {{ $menuItem['isActive'] ? 'active' : '' }}"
                                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-show="isOpen"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100" @endif>
                                @if (!empty($menuItem['icon']))
                                    <span class="custom-sidebar-icon">
                                        @if ($menuItem['isActive'] && !empty($menuItem['activeIcon']))
                                            {!! \Filament\Support\generate_icon_html($menuItem['activeIcon'], ['class' => 'w-5 h-5']) !!}
                                        @else
                                            {!! \Filament\Support\generate_icon_html($menuItem['icon'], ['class' => 'w-5 h-5']) !!}
                                        @endif
                                    </span>
                                @endif
                                <span class="custom-sidebar-label">{{ $menuItem['label'] }}</span>
                                @if (!empty($menuItem['badge']))
                                    <span
                                        class="custom-sidebar-badge custom-sidebar-badge-{{ $menuItem['badgeColor'] ?? 'primary' }}">
                                        {{ $menuItem['badge'] }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @elseif ($menuItem['type'] === 'group')
                        {{-- Menu Group --}}
                        <li x-data="{
                            groupLabel: @js($menuItem['label']),
                            isCollapsed: getCollapsedState(@js($menuItem['label']))
                        }" x-effect="saveCollapsedState(groupLabel, isCollapsed)"
                            class="custom-sidebar-group" :class="{ 'is-collapsed': isCollapsed }">
                            {{-- Group Header --}}
                            <button @if ($menuItem['isCollapsible']) @click="isCollapsed = !isCollapsed" @endif
                                class="custom-sidebar-group-header"
                                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-show="isOpen"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100" @endif>
                                @if (!empty($menuItem['icon']))
                                    <span class="custom-sidebar-icon">
                                        {!! \Filament\Support\generate_icon_html($menuItem['icon'], ['class' => 'w-5 h-5']) !!}
                                    </span>
                                @endif
                                <span class="custom-sidebar-label">{{ $menuItem['label'] }}</span>
                                @if ($menuItem['isCollapsible'])
                                    <span class="custom-sidebar-chevron">
                                        <x-filament::icon icon="heroicon-o-chevron-down"
                                            class="w-4 h-4 transition-transform duration-200"
                                            x-bind:class="{ 'rotate-180': !isCollapsed }" />
                                    </span>
                                @endif
                            </button>

                            {{-- Group Items --}}
                            <ul class="custom-sidebar-group-items" x-show="!isCollapsed"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 max-h-0"
                                x-transition:enter-end="opacity-100 max-h-96"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 max-h-96"
                                x-transition:leave-end="opacity-0 max-h-0"
                                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-show="isOpen && !isCollapsed" @endif>
                                @foreach ($menuItem['items'] as $item)
                                    <li class="custom-sidebar-menu-item">
                                        <a href="{{ $item['url'] }}"
                                            @click="window.matchMedia('(max-width: 1024px)').matches && close()"
                                            class="custom-sidebar-link custom-sidebar-link-grouped {{ $item['isActive'] ? 'active' : '' }}"
                                            @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop) x-show="isOpen"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100" @endif>
                                            @if (!empty($item['icon']))
                                                <span class="custom-sidebar-icon">
                                                    @if ($item['isActive'] && !empty($item['activeIcon']))
                                                        {!! \Filament\Support\generate_icon_html($item['activeIcon'], ['class' => 'w-4 h-4']) !!}
                                                    @else
                                                        {!! \Filament\Support\generate_icon_html($item['icon'], ['class' => 'w-4 h-4']) !!}
                                                    @endif
                                                </span>
                                            @endif
                                            <span class="custom-sidebar-label">{{ $item['label'] }}</span>
                                            @if (!empty($item['badge']))
                                                <span
                                                    class="custom-sidebar-badge custom-sidebar-badge-{{ $item['badgeColor'] ?? 'primary' }}">
                                                    {{ $item['badge'] }}
                                                </span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>

        {{-- Sidebar Footer (User Menu, Notifications, etc.) --}}
        @php
            $isAuthenticated = filament()->auth()->check();
            $hasDatabaseNotificationsInSidebar =
                filament()->hasDatabaseNotifications() &&
                filament()->getDatabaseNotificationsPosition() ===
                    \Filament\Enums\DatabaseNotificationsPosition::Sidebar;
            $hasUserMenuInSidebar =
                filament()->hasUserMenu() &&
                filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Sidebar;
            $shouldRenderFooter = $isAuthenticated && ($hasDatabaseNotificationsInSidebar || $hasUserMenuInSidebar);
        @endphp

        @if ($shouldRenderFooter)
            <div class="custom-sidebar-footer">
                @if ($hasDatabaseNotificationsInSidebar)
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if ($hasUserMenuInSidebar)
                    <x-filament-panels::user-menu />
                @endif
            </div>
        @endif
    </aside>

    <script>
        function customSidebar() {
            return {
                isOpen: window.innerWidth >= 1024 ?
                    (localStorage.getItem('sidebarOpen') !== 'false') : false,

                init() {
                    // Sync with Filament's sidebar store if available
                    if (window.$store && window.$store.sidebar) {
                        this.isOpen = window.$store.sidebar.isOpen;
                        this.$watch('isOpen', (value) => {
                            if (window.$store && window.$store.sidebar) {
                                window.$store.sidebar.isOpen = value;
                            }
                        });
                    }

                    // Listen for Filament sidebar state changes
                    window.addEventListener('sidebar-state-changed', (e) => {
                        this.isOpen = e.detail.isOpen;
                    });
                },

                open() {
                    this.isOpen = true;
                    localStorage.setItem('sidebarOpen', 'true');
                    this.dispatchStateChange();
                },

                close() {
                    this.isOpen = false;
                    localStorage.setItem('sidebarOpen', 'false');
                    this.dispatchStateChange();
                },

                dispatchStateChange() {
                    window.dispatchEvent(new CustomEvent('sidebar-state-changed', {
                        detail: {
                            isOpen: this.isOpen
                        }
                    }));
                },

                getCollapsedState(groupLabel) {
                    const collapsedGroups = JSON.parse(
                        localStorage.getItem('collapsedGroups') || '[]'
                    );
                    return collapsedGroups.includes(groupLabel);
                },

                saveCollapsedState(groupLabel, isCollapsed) {
                    let collapsedGroups = JSON.parse(
                        localStorage.getItem('collapsedGroups') || '[]'
                    );

                    if (isCollapsed && !collapsedGroups.includes(groupLabel)) {
                        collapsedGroups.push(groupLabel);
                    } else if (!isCollapsed && collapsedGroups.includes(groupLabel)) {
                        collapsedGroups = collapsedGroups.filter(g => g !== groupLabel);
                    }

                    localStorage.setItem('collapsedGroups', JSON.stringify(collapsedGroups));
                }
            }
        }
    </script>
</div>
