@php
    // Get viewData passed from the component first
    $itemKey = $itemKey ?? 'items';
    $selectedField = $selectedField ?? 'selected';
    $isCategory = $isCategory ?? false;

    // Get the state of the repeater item (not the field itself)
    // In a repeater, we can access other fields using $get()
    try {
        $itemId = $get('id') ?? null;
        $itemName = $get('name') ?? '';
        $itemSlug = $get('slug') ?? '';
        $isEditing = $get('is_editing') ?? false;
        $selected = $get($selectedField) ?? false;
    } catch (\Exception $e) {
        $itemId = null;
        $itemName = '';
        $itemSlug = '';
        $isEditing = false;
        $selected = false;
    }

    // Get record key from state path
    $statePath = '';
    try {
        $statePath = $getStatePath() ?? '';
    } catch (\Exception $e) {
        // State path not available
    }

    // Extract record key from state path (e.g., "categories.0.table_row" -> 0)
    $recordKey = 0;
    if ($statePath) {
        $parts = explode('.', $statePath);
        // Look for numeric parts which represent the repeater index
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $recordKey = (int) $part;
                break;
            }
        }
    }

    // Build proper state paths for Filament form
    $basePath = "{$itemKey}.{$recordKey}";
    $checkboxPath = "{$basePath}.{$selectedField}";
    $namePath = "{$basePath}.name";
    $editingPath = "{$basePath}.is_editing";
@endphp

<div class="flex items-center gap-4 py-2 border-b border-gray-200 category-type-table-row" style="padding: 10px 0px 10px;"
    x-data="{
        isEditing: @js($isEditing),
        itemName: @js($itemName),
        itemId: @js($itemId),
        itemKey: @js($itemKey),
        recordKey: @js($recordKey),
        isCategory: @js($isCategory),
        selected: @js($selected),
        syncSelected() {
            // Sync selected state from form data
            try {
                if ($wire.mountedActionsData && $wire.mountedActionsData[0] && $wire.mountedActionsData[0][itemKey]) {
                    const items = $wire.mountedActionsData[0][itemKey];
                    // Try to find by index first
                    if (items[recordKey] && items[recordKey].id == itemId) {
                        if (items[recordKey].selected !== undefined) {
                            selected = !!items[recordKey].selected;
                            return;
                        }
                    }
                    // If not found by index, search by ID
                    for (let key in items) {
                        if (items[key] && items[key].id == itemId) {
                            if (items[key].selected !== undefined) {
                                selected = !!items[key].selected;
                                break;
                            }
                        }
                    }
                }
            } catch (e) {
                // Silently fail
            }
        },
        init() {
            // Sync on initialization
            this.syncSelected();
            // Use Livewire hook to sync after updates
            Livewire.hook('message.processed', (message, component) => {
                setTimeout(() => this.syncSelected(), 10);
            });
        }
    }" x-on:livewire:update="syncSelected()" x-on:livewire:update.window="syncSelected()">
    {{-- Checkbox Column --}}
    <div class="flex items-center flex-shrink-0 w-8">
        <input type="checkbox" x-model="selected" x-on:change="$wire.toggleSelection(itemKey, itemId)"
            class="w-4 h-4 border-gray-300 rounded fi-checkbox-input text-primary-600 focus:ring-primary-500 focus:ring-2 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-primary-600 dark:checked:border-primary-600 dark:focus:ring-primary-500 dark:focus:ring-offset-gray-800" />
    </div>

    {{-- Name Column --}}
    <div class="flex-1 min-w-0">
        <div x-show="isEditing">
            <x-filament::input.wrapper>
                <input type="text" x-model="itemName"
                    x-on:input="
                        itemName = $event.target.value;
                        $wire.updateItemName(itemKey, recordKey, itemName);
                    "
                    class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400" />
            </x-filament::input.wrapper>
        </div>
        <div x-show="!isEditing">
            <span class="text-sm font-medium text-gray-900" x-text="itemName"></span>
        </div>
    </div>

    {{-- Actions Column --}}

    <div class="flex items-center flex-shrink-0 gap-2">
        <div x-show="isEditing" class="flex items-center gap-2">
            <button type="button"
                x-on:click="
                    if (!itemName || itemName.trim() === '') {
                        alert('Name is required');
                        return;
                    }
                    if (!itemId) {
                        alert('Item ID is missing');
                        return;
                    }
                    $wire.updateItemName(itemKey, recordKey, itemName);
                    $wire.saveItem(itemKey, itemId, isCategory, itemName);
                    $nextTick(() => { isEditing = false; });
                "
                class="p-1 transition text-success-600 hover:text-success-700" title="Save">
                <x-heroicon-m-check class="w-5 h-5" />
            </button>
            <button type="button"
                x-on:click="
                    $wire.cancelEdit(itemKey, recordKey, isCategory);
                    $nextTick(() => { isEditing = false; });
                "
                class="p-1 text-gray-400 transition hover:text-gray-600" title="Cancel">
                <x-heroicon-m-x-mark class="w-5 h-5" />
            </button>
        </div>
        <div x-show="!isEditing" class="flex items-center gap-2">
            <button type="button" class="p-1 text-gray-500 transition hover:text-primary-600"
                x-on:click="
                    isEditing = true;
                    $wire.setEditing(itemKey, recordKey, true);
                ">
                <x-heroicon-m-pencil-square class="w-5 h-5" />
            </button>
            <button type="button" class="p-1 text-red-600 transition hover:text-red-600"
                x-on:click="$wire.deleteItem(itemKey, itemId, isCategory)">
                <x-heroicon-m-trash class="w-5 h-5" />
            </button>
        </div>
    </div>
</div>

<style>
    .category-type-table-row label {
        display: none !important;
    }

    .category-type-table-wrapper .fi-fo-repeater-item {
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }

    .category-type-table-wrapper .fi-fo-repeater-items {
        gap: 0 !important;
    }

    .category-type-table-wrapper .fi-fo-repeater-item-content {
        padding: 0 !important;
    }
</style>
