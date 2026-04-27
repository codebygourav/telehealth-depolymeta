<x-filament::page>
    <style>
        .sticky-column {
            position: sticky;
            left: 0;
            z-index: 20;
        }

        .makeGrey {
            background: #b8b8b81c;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .zebra-dark {
            background-color: #f8fafc;
        }

        .module-row-header {
            background-color: #f1f5f9;
        }
    </style>

    <div class="bg-white rounded-2xl border overflow-hidden shadow-sm">
        <div class="overflow-x-auto overflow-y-auto max-h-[80vh]">
            <table class="w-full whitespace-nowrap text-sm border-separate [border-spacing:0] bg-white">
                <thead>
                    <tr>
                        <th class="sticky-column sticky-header px-4 py-3 text-[12px] font-bold text-white bg-primary rounded-tl-xl border-r border-primary/40 shadow-sm text-left tracking-wider uppercase"
                            style="min-width: 240px; z-index: 50;">
                            <span class="flex items-center gap-2">
                                <span>Module / Permission</span>
                            </span>
                        </th>
                        @foreach ($roles as $role)
                            <th class="sticky-header px-4 py-3 text-[12px] font-bold text-white bg-primary border-l border-primary/40 shadow-sm tracking-wider uppercase"
                                style="min-width: 140px;">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <div class="flex items-center justify-center gap-2">
                                        <span
                                            class="truncate">{{ \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', $role->name)) }}</span>
                                        @if (!in_array(strtolower($role->name), ['super_admin', 'super-admin']))
                                            <button wire:click="deleteRole({{ $role->id }})"
                                                wire:confirm="Are you sure you want to delete the '{{ $role->name }}' role? This action cannot be undone."
                                                class="p-1 hover:bg-white/20 rounded transition-colors text-white/60 hover:text-white"
                                                title="Delete Role">
                                                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                            </button>
                                        @endif
                                    </div>
                                    @if (in_array(strtolower($role->name), ['super_admin', 'super-admin']))
                                        <span class="px-2 py-0.5 text-[10px] bg-white/20 text-white rounded-full font-medium">Full Access</span>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                @php $moduleRowIndex = 0; @endphp
                @foreach ($modules as $groupName => $modulesList)
                    @foreach ($modulesList as $module => $moduleData)
                        @php
                            $label = $moduleData['label'] ?? $module;
                            $actions = $moduleData['actions'] ?? [];
                            $isEven = $moduleRowIndex % 2 != 0;
                            $rowBgClass = $isEven ? 'bg-slate-50' : 'bg-white';
                            $rowBgClass1 = $isEven ? 'makeGrey' : 'makeWhite';
                            $moduleRowIndex++;
                        @endphp
                        {{-- Module Row (collapsible) --}}
                        <tbody x-data="{ open: false }" x-cloak class="{{ $rowBgClass }}">
                            <tr class="group border-b border-gray-100">
                                <td class="bg-gray   sticky-column px-4 py-2.5 font-bold text-gray-800 cursor-pointer hover:bg-gray-100/80 transition-colors border-r border-gray-100 {{ $rowBgClass }} {{ $rowBgClass1 }}"
                                    @click="open = !open">
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-[0.85rem] tracking-tight">{{ $label }}</span>
                                        </div>
                                        <div
                                            class="flex items-center gap-2 text-gray-400 group-hover:text-primary-500 transition-colors">
                                            <x-heroicon-o-chevron-right x-show="!open" class="w-4 h-4" />
                                            <x-heroicon-o-chevron-down x-show="open" class="w-4 h-4" />
                                        </div>
                                    </div>
                                </td>
                                @foreach ($roles as $role)
                                    @php
                                        $modulePermissionNames = collect($actions)
                                            ->map(fn($a) => $module . '.' . $a)
                                            ->toArray();
                                        $hasModuleAll = true;
                                        foreach ($modulePermissionNames as $pname) {
                                            if (!$role->hasPermissionTo($pname)) {
                                                $hasModuleAll = false;
                                                break;
                                            }
                                        }
                                    @endphp
                                    @php
                                        $isProtected = in_array(strtolower($role->name), ['super_admin', 'super-admin']);
                                    @endphp
                                    <td class="px-3 py-2 text-center {{ $rowBgClass1 }}">
                                        <div
                                            wire:key="module-all-{{ \Illuminate\Support\Str::slug($module) }}-role-{{ $role->id }}">
                                            <label
                                                class="relative w-8 h-8 flex items-center justify-center {{ $isProtected ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }} mx-auto transition duration-150
                                                    {{ $hasModuleAll ? 'bg-primary-50 border-primary-200' : 'bg-gray-50 border-gray-200' }}
                                                    rounded-lg border {{ !$isProtected ? 'hover:shadow-md' : '' }}">
                                                <input type="checkbox"
                                                    @if($isProtected) disabled @endif
                                                    wire:click="toggleModulePermissions({{ $role->id }}, '{{ $module }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="toggleModulePermissions({{ $role->id }}, '{{ $module }}')"
                                                    class="appearance-none w-full h-full @if(!$isProtected) cursor-pointer @endif absolute inset-0 opacity-0 z-20">
                                                <span wire:loading.flex
                                                    wire:target="toggleModulePermissions({{ $role->id }}, '{{ $module }}')"
                                                    class="absolute inset-0 items-center justify-center z-10 bg-white/60 rounded-lg">
                                                    <x-heroicon-o-arrow-path
                                                        class="animate-spin text-primary-600 w-4 h-4" />
                                                </span>
                                                <span wire:loading.remove
                                                    wire:target="toggleModulePermissions({{ $role->id }}, '{{ $module }}')"
                                                    class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                                                    @if ($hasModuleAll)
                                                        <x-heroicon-o-check-circle class="w-6 h-6 text-primary-600" />
                                                    @else
                                                        <x-heroicon-o-minus-circle class="w-5 h-5 text-gray-300" />
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Permission Rows (collapsible body) --}}
                            @foreach ($actions as $action)
                                @php
                                    $permissionName = $module . '.' . $action;
                                @endphp
                                <tr x-show="open"
                                    class="hover:bg-primary-50/40 transition-all border-b border-gray-50">
                                    <td
                                        class="sticky-column px-4 py-1.5 text-gray-600 border-r border-gray-100 {{ $rowBgClass }}">
                                        <div class="flex items-center gap-3">
                                            <div class="w-1 h-1 rounded-full bg-gray-300"></div>
                                            <span
                                                class="text-[13px] font-medium capitalize">{{ str_replace('_', ' ', $action) }}</span>
                                        </div>
                                    </td>
                                    @foreach ($roles as $role)
                                        @php
                                            $isProtected = in_array(strtolower($role->name), ['super_admin', 'super-admin']);
                                            $hasPermission = $role->hasPermissionTo($permissionName);
                                            $loadingKey =
                                                'permission-btn-' .
                                                $role->id .
                                                '-' .
                                                \Illuminate\Support\Str::slug($permissionName);
                                        @endphp
                                        <td class="px-3 py-1.5 text-center">
                                            <div wire:key="{{ $loadingKey }}">
                                                <label
                                                    class="relative w-8 h-8 flex items-center justify-center {{ $isProtected ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }} mx-auto transition duration-150
                                                        {{ $hasPermission ? 'bg-primary-50 border-primary-100' : 'bg-white border-gray-100 shadow-sm' }}
                                                        rounded-lg border">
                                                    <input type="checkbox"
                                                        @if($isProtected) disabled @endif
                                                        wire:click="togglePermission({{ $role->id }}, '{{ $permissionName }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="togglePermission({{ $role->id }}, '{{ $permissionName }}')"
                                                        class="appearance-none w-full h-full @if(!$isProtected) cursor-pointer @endif absolute inset-0 opacity-0 z-20">

                                                    <span wire:loading.flex
                                                        wire:target="togglePermission({{ $role->id }}, '{{ $permissionName }}')"
                                                        class="absolute inset-0 items-center justify-center z-10 bg-white/60 rounded-lg">
                                                        <x-heroicon-o-arrow-path
                                                            class="animate-spin text-primary-600 w-4 h-4" />
                                                    </span>

                                                    <span wire:loading.remove
                                                        wire:target="togglePermission({{ $role->id }}, '{{ $permissionName }}')"
                                                        class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                                                        @if ($hasPermission)
                                                            <x-heroicon-o-check class="w-5 h-5 text-primary-600" />
                                                        @else
                                                            <div class="w-1.5 h-1.5 bg-gray-200 rounded-full"></div>
                                                        @endif
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                @endforeach
            </table>
        </div>
    </div>
</x-filament::page>
