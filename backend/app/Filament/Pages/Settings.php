<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\Doctor;
use Filament\Forms\Components\{
    ColorPicker,
    DatePicker,
    FileUpload,
    Select,
    TagsInput,
    Textarea,
    TextInput,
    Toggle,
    Repeater,
    RichEditor
};
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\{
    Grid,
    Section,
    Tabs,
    Tabs\Tab
};
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\{
    Artisan,
    File
};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use App\Traits\HasCustomSidebar;

class Settings extends Page
{
    use HasCustomSidebar;
    protected string $view = 'filament.pages.settings';
    protected static ?string $title = 'Settings';
    protected static ?string $slug = 'settings';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Settings', // Explicit label
            'icon'  => 'heroicon-o-cog-6-tooth',
            'sort'  => 99,
            'group' => 'System & Settings',
            'visible' => false,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];
    #[Url(as: 'tab')]
    public string $activeTab = 'app';

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    protected function loadSettings(): array
    {
        $settings = Setting::all()->groupBy('group');
        $data = [];
        $config = config('settings', []);

        // First, load all values from database
        foreach ($settings as $group => $groupSettings) {
            foreach ($groupSettings as $setting) {
                $key = "{$group}.{$setting->key}";
                $value = $setting->value;

                if ($setting->type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } elseif ($setting->type === 'json' || $setting->type === 'array') {
                    $value = json_decode($value, true) ?? [];
                } elseif ($setting->type === 'integer') {
                    $value = (int) $value;
                }

                data_set($data, $key, $value);
            }
        }

        // Then, for fields with env_key, use .env value if database value is empty
        foreach ($config as $groupKey => $group) {
            foreach (($group['sections'] ?? []) as $section) {
                $dbGroup = $section['db_group'] ?? $groupKey;

                foreach (($section['fields'] ?? []) as $fieldKey => $field) {
                    if (isset($field['env_key'])) {
                        $fullKey = "{$dbGroup}.{$fieldKey}";
                        $envValue = env($field['env_key']);

                        // If database value is empty but .env has a value, use .env value
                        if ((empty(data_get($data, $fullKey)) || data_get($data, $fullKey) === '') && $envValue !== null) {
                            data_set($data, $fullKey, $envValue);
                        }
                    }
                }
            }
        }

        foreach ($config as $groupKey => $group) {
            foreach (($group['sections'] ?? []) as $section) {
                $dbGroup = $section['db_group'] ?? $groupKey;

                foreach (($section['fields'] ?? []) as $fieldKey => $field) {
                    if (! array_key_exists('default', $field)) {
                        continue;
                    }

                    $fullKey = "{$dbGroup}.{$fieldKey}";
                    $currentValue = data_get($data, $fullKey);

                    if ($currentValue === null || $currentValue === '') {
                        data_set($data, $fullKey, $field['default']);
                    }
                }
            }
        }

        return $data;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema($this->buildFormSchema())
            ->statePath('data');
    }

    public static function getHiddenSettingsGroups(): array
    {
        return ['display', 'display_ads', 'prescription_voice'];
    }

    protected function buildFormSchema(): array
    {
        $schema = [];
        $config = config('settings', []);

        foreach ($config as $groupKey => $group) {
            if (in_array($groupKey, static::getHiddenSettingsGroups(), true)) {
                continue;
            }

            // Skip if 'label' key does not exist (prevent undefined key error)
            if (!isset($group['label'])) {
                continue;
            }

            $schema[] = Section::make($group['label'])
                ->description($group['description'] ?? null)
                ->icon($group['icon'] ?? null)
                ->schema($this->buildSectionsFromConfig($groupKey, $group['sections'] ?? []))
                ->visible(fn() => $this->activeTab === $groupKey)
                ->id($groupKey);
        }

        return $schema;
    }

    protected function buildSectionsFromConfig(string $groupKey, array $sections): array
    {
        $schema = [];

        /*
        |--------------------------------------------------------------------------
        | Special Support Tab UI
        |--------------------------------------------------------------------------
        */
        if ($groupKey === 'support') {

            $tabs = [];

            foreach ($sections as $sectionKey => $section) {

                $dbGroup = $section['db_group'] ?? $groupKey;

                $tabs[] = Tab::make($section['label'])
                    ->schema([
                        Section::make()
                            ->description($section['description'] ?? null)
                            ->schema(
                                $this->buildFieldsFromConfig(
                                    $dbGroup,
                                    $section['fields'] ?? [],
                                    $groupKey
                                )
                            )
                    ]);
            }

            $schema[] = Tabs::make('Support Tabs')
                ->tabs($tabs)
                ->columnSpanFull();

            return $schema;
        }

        /*
        |--------------------------------------------------------------------------
        | Default Sections
        |--------------------------------------------------------------------------
        */
        foreach ($sections as $sectionKey => $section) {

            $dbGroup = $section['db_group'] ?? $groupKey;

            $sectionSchema = Section::make($section['label'] ?? ucfirst($sectionKey))
                ->description($section['description'] ?? null)
                ->schema(
                    $this->buildFieldsFromConfig(
                        $dbGroup,
                        $section['fields'] ?? [],
                        $groupKey
                    )
                )
                ->collapsible($section['collapsed'] ?? false)
                ->collapsed($section['collapsed'] ?? false);

            $schema[] = $sectionSchema;
        }

        return $schema;
    }

    protected function buildFieldsFromConfig(string $groupKey, array $fields, string $tabId): array
    {
        $schema = [];
        $currentPair = [];

        foreach ($fields as $fieldKey => $field) {
            $fullKey = "{$groupKey}.{$fieldKey}";
            $component = $this->createFieldComponent($fullKey, $field, $tabId, $groupKey);

            if ($component) {
                if ($this->shouldPairField($field['type'])) {
                    $currentPair[] = $component;
                    if (count($currentPair) === 2) {
                        $schema[] = Grid::make(2)->schema($currentPair);
                        $currentPair = [];
                    }
                } else {
                    if (!empty($currentPair)) {
                        $schema[] = Grid::make(2)->schema($currentPair);
                        $currentPair = [];
                    }
                    $schema[] = $component;
                }
            }
        }

        if (!empty($currentPair)) {
            $schema[] = Grid::make(2)->schema($currentPair);
        }

        return $schema;
    }

    protected function buildSchemaFromFields(array $fields, string $tabId): array
    {
        $schema = [];

        foreach ($fields as $fieldKey => $fieldConfig) {
            $component = $this->createDynamicFieldComponent($fieldKey, $fieldConfig, $tabId);

            if ($component) {
                $schema[] = $component;
            }
        }

        return $schema;
    }

    /**
     * Toggle fix:
     * The Filament Toggle component in v4 expects explicit onColor/offColor settings and will not
     * attempt Alpine expressions with undefined variables like "state".
     * We do NOT use expressions in Toggle->color() that reference "state".
     * Use 'success' and 'gray' or 'danger' for clearly defined colors.
     */
    protected function createDynamicFieldComponent(string $key, array $field, string $tabId): mixed
    {
        return match ($field['type']) {

            'text' => TextInput::make($key)
                ->label($field['label'])
                ->placeholder($field['placeholder'] ?? null)
                ->required(fn() => $this->activeTab === $tabId && ($field['required'] ?? false))
                ->maxLength($field['maxLength'] ?? 255),

            'textarea' => Textarea::make($key)
                ->label($field['label'])
                ->rows($field['rows'] ?? 3)
                ->required(fn() => $this->activeTab === $tabId && ($field['required'] ?? false)),

            'number' => TextInput::make($key)
                ->label($field['label'])
                ->numeric()
                ->minValue($field['min'] ?? null)
                ->maxValue($field['max'] ?? null),

            'select' => Select::make($key)
                ->label($field['label'])
                ->options($field['options'] ?? [])
                ->searchable(),

            'toggle' => Toggle::make($key)
                ->label($field['label'])
                ->onColor('success')
                ->offColor('gray')
                ->default($field['default'] ?? false),

            'color' => ColorPicker::make($key)
                ->label($field['label']),

            'file' => FileUpload::make($key)
                ->label($field['label'])
                ->disk('public')
                ->directory($field['directory'] ?? 'settings')
                ->image()
                ->imageEditor()
                ->maxSize(5120) // 5MB max file size
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
            // Note: optimize() method doesn't exist in Filament v4

            'tags' => TagsInput::make($key)
                ->label($field['label'])
                ->default($field['default'] ?? []),

            'doctor_select' => Select::make($key)
                ->label($field['label'])
                ->multiple()
                ->searchable()
                ->preload()
                ->options($this->todayDoctorOptions())
                ->default($field['default'] ?? [])
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->columnSpanFull(),

            default => null,
        };
    }

    protected function todayDoctorOptions(): array
    {
        $doctorIds = Appointment::query()
            ->whereDate('appointment_date', Carbon::today())
            ->whereNotNull('doctor_id')
            ->distinct()
            ->pluck('doctor_id')
            ->all();

        if (empty($doctorIds)) {
            return [];
        }

        return Doctor::query()
            ->with('user:id,name')
            ->whereIn('id', $doctorIds)
            ->active()
            ->withoutTestDoctors()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(function (Doctor $doctor): array {
                $name = trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
                $label = $name !== '' ? $name : ($doctor->user?->name ?? 'Doctor');
                $suffix = $doctor->doctor_code ? " ({$doctor->doctor_code})" : '';

                return [$doctor->id => "Dr. {$label}{$suffix}"];
            })
            ->toArray();
    }

    protected function shouldPairField(string $type): bool
    {
        return in_array($type, ['text', 'email', 'tel', 'url', 'number', 'password', 'select', 'color', 'time', 'richtext']);
    }

    /**
     * Toggle fix:
     * The Filament Toggle component in v4 expects explicit onColor/offColor settings and will not
     * attempt Alpine expressions with undefined variables like "state".
     * We do NOT use expressions in Toggle->color() that reference "state".
     * Use 'success' and 'gray' or 'danger' for clearly defined colors.
     */
    protected function createFieldComponent(string $key, array $field, string $tabId, string $groupKey): mixed
    {
        $component = match ($field['type']) {
            'text' => TextInput::make($key)
                ->label($field['label'])
                ->placeholder($field['placeholder'] ?? null)
                ->maxLength($field['maxLength'] ?? 255)
                ->helperText($field['helper'] ?? null)
                ->required(fn() => $this->activeTab === $tabId && ($field['required'] ?? false)),

            'email' => TextInput::make($key)
                ->label($field['label'])
                ->email()
                ->placeholder($field['placeholder'] ?? null)
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->required(fn() => $this->activeTab === $tabId && ($field['required'] ?? false)),

            'tel' => TextInput::make($key)
                ->label($field['label'])
                ->tel()
                ->placeholder($field['placeholder'] ?? null)
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null),

            'url' => TextInput::make($key)
                ->label($field['label'])
                ->url()
                ->placeholder($field['placeholder'] ?? null)
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null),

            'number' => TextInput::make($key)
                ->label($field['label'])
                ->numeric()
                ->placeholder($field['placeholder'] ?? null)
                ->minValue($field['min'] ?? null)
                ->maxValue($field['max'] ?? null)
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->default($field['default'] ?? null),

            'password' => TextInput::make($key)
                ->label($field['label'])
                ->password()
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->revealable(),

            'textarea' => tap(
                Textarea::make($key)
                    ->label($field['label'])
                    ->placeholder($field['placeholder'] ?? null)
                    ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                    ->rows($field['rows'] ?? 3)
                    ->default($field['default'] ?? null),
                function ($component) use ($field) {
                    if (isset($field['maxLength'])) {
                        $component->maxLength($field['maxLength']);
                    }
                }
            ),

            'select' => Select::make($key)
                ->label($field['label'])
                ->options($field['options'] ?? [])
                ->searchable(count($field['options'] ?? []) > 10)
                ->default($field['default'] ?? null)
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->live($field['live'] ?? false),

            'toggle' => Toggle::make($key)
                ->label($field['label'])
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->onColor('success')
                ->offColor('gray')
                ->default($field['default'] ?? false)
                ->live($field['live'] ?? false),

            'color' => ColorPicker::make($key)
                ->label($field['label'])
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null),

            'file' => FileUpload::make($key)
                ->label($field['label'])
                ->image()
                ->imageEditor()
                ->directory($field['directory'] ?? 'settings')
                ->disk('public')
                ->maxSize(5120) // 5MB max file size
                ->helperText($field['helper'] ?? $field['helper_text'] ?? null)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
            // Note: optimize() method doesn't exist in Filament v4

            'tags' => TagsInput::make($key)
                ->label($field['label'])
                ->placeholder($field['placeholder'] ?? 'Add item')
                ->helperText($field['helper'] ?? null)
                ->default($field['default'] ?? []),

            'repeater' => Repeater::make($key)
                ->label($field['label'])
                ->schema(
                    $this->buildSchemaFromFields($field['fields'] ?? [], $tabId)
                )
                ->reorderable()
                ->collapsible()
                ->itemLabel(
                    fn(array $state): string =>
                    $state['title'] ?? $field['item_label'] ?? 'Item'
                )
                ->default($field['default'] ?? [])
                ->columnSpanFull(),

            'richtext' => RichEditor::make($key)
                ->label($field['label'])
                ->required(fn() => $this->activeTab === $tabId && ($field['required'] ?? false))
                ->toolbarButtons(
                    $field['toolbar'] ?? [
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                        'h2',
                        'h3',
                        'undo',
                        'redo',
                    ]
                )
                ->columnSpanFull(),

            default => null,
        };

        if ($component && isset($field['depends_on'])) {
            $component->visible(function ($get) use ($field, $groupKey) {
                $dependsOn = $field['depends_on'];
                $targetValue = $field['depends_on_value'] ?? true;
                $reverse = false;

                if (str_starts_with($dependsOn, '!')) {
                    $reverse = true;
                    $dependsOn = substr($dependsOn, 1);
                }

                // If not a full key, prepend groupKey
                if (!str_contains($dependsOn, '.')) {
                    $dependsOn = "{$groupKey}.{$dependsOn}";
                }

                $value = $get($dependsOn);

                if ($reverse) {
                    return !$value;
                }

                if (isset($field['depends_on_value'])) {
                    return $value === $targetValue;
                }

                return (bool) $value;
            });
        }

        return $component;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $envUpdates = [];

        foreach ($data as $group => $groupSettings) {
            if (!is_array($groupSettings)) continue;

            foreach ($groupSettings as $settingKey => $value) {
                // Determine type
                $type = 'string';
                if (is_bool($value)) {
                    $type = 'boolean';
                    $value = $value ? '1' : '0';
                } elseif (is_int($value)) {
                    $type = 'integer';
                } elseif (is_array($value)) {
                    $type = 'json';
                    $value = json_encode($value);
                }

                // Get field config
                $fieldConfig = $this->getFieldConfig($group, $settingKey);
                $isPublic = $fieldConfig['is_public'] ?? false;

                // Save to database
                Setting::updateOrCreate(
                    ['group' => $group, 'key' => $settingKey],
                    ['value' => $value, 'type' => $type, 'is_public' => $isPublic]
                );

                // Collect .env updates
                if (isset($fieldConfig['env_key']) && ($value !== null && $value !== '')) {
                    $envValue = is_array($value) ? implode(',', $value) : $value;
                    if ($type === 'boolean') {
                        $envValue = $value === '1' ? 'true' : 'false';
                    }
                    $envUpdates[$fieldConfig['env_key']] = $envValue;
                }
            }
        }

        // Update .env file
        if (!empty($envUpdates)) {
            $this->updateEnvFile($envUpdates);
        }

        // Clear caches
        Setting::clearCache();
        Artisan::call('config:clear');

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    protected function getFieldConfig(string $group, string $key): array
    {
        $config = config('settings', []);

        // Search through all groups and sections to find the field
        foreach ($config as $groupKey => $groupConfig) {
            foreach ($groupConfig['sections'] ?? [] as $section) {
                // Check if this section uses a different db_group
                $dbGroup = $section['db_group'] ?? $groupKey;

                // If the db_group matches and the field exists, return it
                if ($dbGroup === $group && isset($section['fields'][$key])) {
                    return $section['fields'][$key];
                }
            }
        }

        return [];
    }

    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            // Escape value if it contains spaces
            if (preg_match('/\s/', (string)$value)) {
                $value = '"' . $value . '"';
            }

            // Check if key exists
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Add new key at the end
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }

    public function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Setting::clearCache();

            Notification::make()
                ->title('All caches cleared successfully')
                ->success()
                ->send();

            $this->redirect(static::getUrl(), navigate: true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error clearing cache')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearConfigCache(): void
    {
        try {
            Artisan::call('config:clear');

            Notification::make()
                ->title('Configurations refreshed')
                ->success()
                ->send();

            $this->redirect(static::getUrl(), navigate: true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error clearing config')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearViewCache(): void
    {
        Artisan::call('view:clear');

        Notification::make()
            ->title('View cache cleared')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }
}
