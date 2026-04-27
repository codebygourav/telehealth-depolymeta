<?php

namespace App\Filament\Resources\DoctorDepartments\Schemas;

use Filament\Forms\Components\{
    TextInput,
    Textarea,
    Repeater,
    Toggle,
    RichEditor,
    DatePicker,
    Select,
    FileUpload,
    Radio
};
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Storage; // Added for storage handling if needed

use App\Enums\DepartmentRole;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\Doctor;
use App\Models\Symptom;
use \Filament\Actions\Action;

class DoctorDepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status')
                ->schema([
                    Toggle::make('status')
                        ->label('Active Department')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true)
                        ->formatStateUsing(fn ($state) => $state !== 'inactive')
                        ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'inactive'),
                ])
                ->columnSpanFull(),

            Section::make('Basic Information')
                ->icon('heroicon-o-information-circle')
                ->description('Manage the primary details of the department.')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Grid::make(1)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Department Name')
                                        ->placeholder('e.g. Cardiology')
                                        ->required(),

                                    Select::make('symptom_ids')
                                        ->label('Associated Symptoms')
                                        ->placeholder('Select symptoms...')
                                        ->multiple()
                                        ->options(fn() => Symptom::all()->pluck('name', 'id')->toArray())
                                        ->searchable(),
                                    Textarea::make('description')
                                        ->label('Department Description')
                                        ->placeholder('Provide a brief overview of the department...')
                                        ->rows(3)
                                        ->required(),
                                ])
                                ->columnSpan(2),

                            Section::make('Featured Image')
                                ->icon('heroicon-o-photo')
                                ->compact()
                                ->schema([
                                    FileUpload::make('department_featured')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->disk('public')
                                        ->directory(fn($record) => 'departments/' . ($record->id ?? 'temp'))
                                        ->preserveFilenames()
                                        ->image()
                                        ->imageEditor()
                                        ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                                        ->visibility('public'),
                                ])
                                ->columnSpan(1),

                            Section::make('Department Stamp')
                                ->icon('heroicon-o-check-badge')
                                ->compact()
                                ->schema([
                                    FileUpload::make('department_stamp')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->disk('public')
                                        ->directory(fn($record) => 'departments/' . ($record->id ?? 'temp') . '/stamps')
                                        ->preserveFilenames()
                                        ->image()
                                        ->imageEditor()
                                        ->visibility('public'),
                                ])
                                ->columnSpan(1),
                        ]),



                ])
                ->columnSpanFull(),
            Section::make('Department Doctors')
                ->icon('heroicon-o-users')
                ->description('Assign doctors to this department.')
                ->collapsible()
                ->collapsed()
                ->compact()
                ->schema([
                    Repeater::make('doctors')
                        ->columns(3)
                        ->schema([
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->placeholder('Choose a doctor')
                                ->options(
                                    fn() =>
                                    Doctor::with('user')->get()
                                        ->mapWithKeys(fn($doctor) => [
                                            $doctor->id => $doctor->user?->name ?? "Doctor ({$doctor->id})"
                                        ])
                                        ->toArray()
                                )
                                ->searchable()
                                ->required(),

                            Select::make('role')
                                ->label('Role')
                                ->placeholder('e.g. Head Surgeon')
                                ->options(collect(DepartmentRole::cases())->mapWithKeys(fn($case) => [
                                    $case->value => $case->labels(),
                                ])->toArray())
                                ->enum(DepartmentRole::class)
                                ->searchable(),

                            TextInput::make('order')
                                ->label('Display Order')
                                ->numeric()
                                ->default(fn($get) => count($get('../../doctors') ?? []) + 1)
                                ->reactive(),
                        ])
                        ->itemLabel(fn(array $state): ?string => Doctor::find($state['doctor_id'] ?? null)?->user?->name ?? null)
                        ->addActionLabel('Add New Doctor')
                        ->defaultItems(0)
                        ->columnSpanFull()
                        ->reorderable()
                        ->orderable(),
                ])
                ->columnSpanFull(),

            // ===== LAYOUT TOGGLE =====
            Section::make('Design & Layout')
                ->icon('heroicon-o-paint-brush')
                ->description('Customize how the department content is displayed.')
                ->schema([
                    Toggle::make('is_tab_layout')
                        ->label('Enable Tabbed Layout')
                        ->helperText('Switch between a simple scrollable layout or a tabbed interface.')
                        ->reactive()
                        ->onColor('success')
                        ->onIcon('heroicon-o-rectangle-stack')
                        ->offIcon('heroicon-o-bars-3')
                        ->default(false)
                        ->afterStateHydrated(
                            fn($component, $state) => $component->state($component->getRecord()?->is_tab_layout ?? false)
                        ),
                ])
                ->columnSpanFull(),

            // ===== SIMPLE LAYOUT: Additional Info & Gallery =====
            Section::make('Content & Details')
                ->icon('heroicon-o-document-text')
                ->visible(fn($get) => !$get('is_tab_layout'))
                ->schema([
                    Repeater::make('additional_information')
                        ->label('Information Sections')
                        ->schema([
                            RichEditor::make('content')
                                ->label('Section Content')
                                ->required(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => strip_tags(substr($state['content'] ?? '', 0, 50)) ?: 'New Section')
                        ->reorderable()
                        ->orderable()
                        ->addActionLabel('Add New Section'),
                ])
                ->columnSpanFull(),

            // ===== SIMPLE LAYOUT: FAQs =====
            Section::make('Frequently Asked Questions')
                ->icon('heroicon-o-question-mark-circle')
                ->visible(fn($get) => !$get('is_tab_layout'))
                ->schema([
                    Repeater::make('faqs')
                        ->label('FAQs')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('question')
                                        ->placeholder('Enter the question...')
                                        ->required(),
                                    Textarea::make('answer')
                                        ->placeholder('Enter the answer...')
                                        ->rows(1)
                                        ->autosize()
                                        ->required(),
                                ]),
                        ])
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['question'] ?? null)
                        ->reorderable()
                        ->orderable()
                        ->addActionLabel('Add FAQ'),
                ])
                ->columnSpanFull(),

            // ===== SIMPLE LAYOUT: Publications =====
            Section::make('Research & Publications')
                ->icon('heroicon-o-academic-cap')
                ->visible(fn($get) => !$get('is_tab_layout'))
                ->schema([
                    Repeater::make('publications')
                        ->label('Publications')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('publication_name')
                                        ->label('Title')
                                        ->placeholder('Publication Title')
                                        ->columnSpan(2)
                                        ->required(),
                                    DatePicker::make('publication_date')
                                        ->label('Date')
                                        ->required(),
                                ]),
                            Textarea::make('publication_description')
                                ->label('Summary')
                                ->placeholder('Brief summary of the publication...')
                                ->rows(2)
                                ->required(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['publication_name'] ?? null)
                        ->reorderable()
                        ->orderable()
                        ->addActionLabel('Add Publication'),
                ])
                ->columnSpanFull(),

            // ===== TAB LAYOUT =====
            Section::make('Tabbed Content Management')
                ->icon('heroicon-o-folder')
                ->description('Group content into multiple interactive tabs.')
                ->visible(fn($get) => $get('is_tab_layout'))
                ->schema([
                    Repeater::make('tabs')
                        ->relationship()
                        ->schema([
                            TextInput::make('tab_title')
                                ->label('Tab Heading')
                                ->placeholder('e.g. Services, Procedures, Research')
                                ->required(),

                            RichEditor::make('tab_content')
                                ->label('Tab Description')
                                ->placeholder('Enter detailed content for this tab...'),

                            Section::make('Media Gallery')
                                ->icon('heroicon-o-camera')
                                ->collapsible()
                                ->compact()
                                ->schema([
                                    FileUpload::make('tab_gallery')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->disk('public')
                                        ->directory(fn($record) => 'departments/' . ($record->department_id ?? 'temp') . '/gallery')
                                        ->multiple()
                                        ->reorderable()
                                        ->panelLayout('grid')
                                        ->imagePreviewHeight('100')
                                        ->preserveFilenames()
                                        ->visibility('public')
                                        ->acceptedFileTypes(['image/*', 'video/*', 'application/pdf'])
                                        ->extraAttributes([
                                            'class' => '
                                                [&_.filepond--root]:!min-h-[100px]
                                                [&_.filepond--root]:!h-auto
                                                [&_.filepond--list]:!grid
                                                [&_.filepond--list]:!grid-cols-5
                                                [&_.filepond--list]:!gap-2
                                                [&_.filepond--item]:!w-[100px]
                                                [&_.filepond--item]:!h-[100px]
                                                [&_.filepond--item]:!min-h-[100px]
                                                [&_.filepond--item]:!max-h-[100px]
                                                [&_.filepond--item-panel]:!h-[100px]
                                                [&_.filepond--image-preview-wrapper]:!h-[100px]
                                                [&_.filepond--image-preview]:!h-[100px]
                                                [&_.filepond--file-info]:!hidden
                                                [&_.filepond--file-status]:!hidden
                                                [&_.filepond--panel-root]:!bg-transparent
                                                [&_img]:!object-cover
                                                [&_img]:!w-[100px]
                                                [&_img]:!h-[100px]
                                                [&_img]:!min-h-[100px]
                                                [&_img]:!max-h-[100px]
                                            ',
                                        ])
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['tab_title'] ?? null)
                        ->reorderable()
                        ->orderable('order')
                        ->addActionLabel('Add New Tab'),
                ])
                ->columnSpanFull(),
        ]);
    }
}
