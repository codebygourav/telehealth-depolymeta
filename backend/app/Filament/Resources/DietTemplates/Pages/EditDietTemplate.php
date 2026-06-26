<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use App\Models\PatientDietPlan;
use App\Models\PatientDietPlanDay;
use App\Models\PatientDietPlanMeal;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\DB;

class EditDietTemplate extends EditRecord
{
    protected static string $resource = DietTemplateResource::class;

    public bool $showPatientSyncSection = false;

    protected array $dietChartDays = [];
    protected array $syncPatientPlanIds = [];

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['diet_chart_payload'] = json_encode(DietTemplateResource::dietChartDataForForm($this->record));
        $data['patient_sync_mode'] = null;
        $data['sync_patient_plans'] = [];

        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        if ($this->mustChoosePatientSyncBeforeSave()) {
            $this->form->getState();

            $this->showPatientSyncSection = true;

            Notification::make()
                ->warning()
                ->title('Choose patient update option')
                ->body('This template is assigned to active patients. Choose template-only or select patients before saving.')
                ->send();

            $this->js(<<<'JS'
                setTimeout(() => {
                    document.getElementById('diet-template-patient-sync')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }, 150);
            JS);

            return;
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->dietChartDays = DietTemplateResource::decodeDietChartPayload($data['diet_chart_payload'] ?? $this->data['diet_chart_payload'] ?? null);
        $patientSyncMode = $this->data['patient_sync_mode'] ?? null;
        $this->syncPatientPlanIds = $patientSyncMode === 'selected_patients'
            ? array_values(array_filter((array) ($this->data['sync_patient_plans'] ?? [])))
            : [];

        unset($data['days'], $data['diet_chart_payload'], $data['patient_sync_mode'], $data['sync_patient_plans']);

        return $data;
    }

    protected function afterSave(): void
    {
        DietTemplateResource::syncDietChart($this->record, $this->dietChartDays);

        if (! empty($this->syncPatientPlanIds)) {
            $this->syncSelectedPatientPlans();
        }

        $this->showPatientSyncSection = false;
        $this->data['patient_sync_mode'] = null;
        $this->data['sync_patient_plans'] = [];
    }

    private function mustChoosePatientSyncBeforeSave(): bool
    {
        return ! $this->showPatientSyncSection
            && DietTemplateResource::assignedPatientPlansQuery($this->record)->exists();
    }

    private function syncSelectedPatientPlans(): void
    {
        $template = $this->record;
        $newDaysData = DietTemplateResource::normalizeDietChartData($this->dietChartDays);

        PatientDietPlan::query()
            ->where('diet_template_id', $template->id)
            ->where('status', 'active')
            ->whereIn('id', $this->syncPatientPlanIds)
            ->get()
            ->each(function (PatientDietPlan $patientDietPlan) use ($template, $newDaysData): void {
                DB::transaction(function () use ($patientDietPlan, $template, $newDaysData): void {
                    $durationDays = (int) ($template->duration_days ?? 7);
                    $startDate = Carbon::parse($patientDietPlan->start_date);
                    $endDate = $startDate->copy()->addDays(max(0, $durationDays - 1));

                    $patientDietPlan->update([
                        'template_name' => $template->name,
                        'template_description' => $template->description,
                        'diet_category' => $template->diet_category,
                        'patient_type' => $template->patient_type,
                        'daily_calories' => $template->daily_calories,
                        'protein_target' => $template->protein_target,
                        'carbs_limit' => $template->carbs_limit,
                        'salt_limit' => $template->salt_limit,
                        'doctor_remark' => $template->doctor_remark,
                        'allowed_food_notes' => $template->allowed_food_notes,
                        'hydration_advice' => $template->hydration_advice,
                        'exercise_advice' => $template->exercise_advice,
                        'features' => $template->features,
                        'duration_days' => $durationDays,
                        'end_date' => $endDate->toDateString(),
                    ]);

                    foreach ($newDaysData as $dayData) {
                        $dayNumber = (int) $dayData['day_number'];

                        $planDay = PatientDietPlanDay::updateOrCreate(
                            [
                                'patient_diet_plan_id' => $patientDietPlan->id,
                                'day_number' => $dayNumber,
                            ],
                            [
                                'week_day' => $dayData['week_day'],
                                'date' => $startDate->copy()->addDays($dayNumber - 1)->toDateString(),
                            ]
                        );

                        $existingMeals = PatientDietPlanMeal::query()
                            ->where('patient_diet_plan_day_id', $planDay->id)
                            ->orderBy('sort_order')
                            ->get()
                            ->values();

                        $newMeals = array_values($dayData['meals'] ?? []);
                        $maxCount = max($existingMeals->count(), count($newMeals));

                        for ($index = 0; $index < $maxCount; $index++) {
                            $existingMeal = $existingMeals->get($index);
                            $mealData = $newMeals[$index] ?? null;

                            if (! $mealData) {
                                $existingMeal?->delete();

                                continue;
                            }

                            $mealPayload = [
                                'meal_type' => $mealData['meal_type'],
                                'meal_name' => $mealData['meal_name'],
                                'instructions' => $mealData['instructions'] ?? null,
                                'meal_image' => $mealData['meal_image'] ?? null,
                                'helpful_links' => $mealData['helpful_links'] ?? null,
                                'calories' => $mealData['calories'] ?? null,
                                'protein_grams' => $mealData['protein_grams'] ?? null,
                                'carbs_grams' => $mealData['carbs_grams'] ?? null,
                                'fat_grams' => $mealData['fat_grams'] ?? null,
                                'meal_time' => $mealData['start_time'] ?? null,
                                'sort_order' => $mealData['sort_order'],
                            ];

                            if ($existingMeal) {
                                $existingMeal->update($mealPayload);

                                continue;
                            }

                            PatientDietPlanMeal::create([
                                ...$mealPayload,
                                'patient_diet_plan_day_id' => $planDay->id,
                                'status' => 'pending',
                            ]);
                        }
                    }

                    $newDayNumbers = collect($newDaysData)
                        ->pluck('day_number')
                        ->map(fn ($dayNumber): int => (int) $dayNumber)
                        ->all();

                    PatientDietPlanDay::query()
                        ->where('patient_diet_plan_id', $patientDietPlan->id)
                        ->whereNotIn('day_number', $newDayNumbers)
                        ->delete();
                });
            });
    }
}
